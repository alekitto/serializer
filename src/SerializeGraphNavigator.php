<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\EventDispatcher\PostSerializeEvent;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exclusion\SerializationGroupProviderInterface;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

use function assert;
use function is_array;
use function is_object;
use function is_scalar;
use function is_subclass_of;
use function iterator_to_array;

class SerializeGraphNavigator extends GraphNavigator
{
    /**
     * {@inheritdoc}
     *
     * @param SerializationContext $context
     */
    public function accept($data, ?Type $type, Context $context): mixed
    {
        if ($type === null) {
            $type = $context->guessType($data);
        }

        return $this->serialize($data, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context): mixed
    {
        if ($data instanceof SerializationGroupProviderInterface) {
            assert($context instanceof SerializationContext);
            $childGroups = $data->getSerializationGroups($context);
            $context = $context->createChildContext([
                'groups' => ! is_array($childGroups) ? iterator_to_array($childGroups, false) : $childGroups,
            ]);
        }

        return $context->visitor->visitObject($metadata, $data, $type, $context);
    }

    /**
     * Calls serialization visitors.
     */
    private function serialize(mixed $data, Type $type, SerializationContext $context): mixed
    {
        if ($data === null) {
            $type = Type::null();
        }

        $inVisitingStack = is_object($data);
        if ($inVisitingStack) {
            if ($context->isVisiting($data) && ! $context->getMetadataStack()->getCurrent() instanceof AdditionalPropertyMetadata) {
                return null;
            }

            $context->startVisiting($data);
        }

        // If we're serializing a polymorphic type, then we'll be interested in the
        // metadata for the actual type of the object, not the base class.
        if (is_object($data) && is_subclass_of($data, $type->name, false)) {
            $type = new Type($data::class, $type->getParams());
        }

        if ($this->dispatcher !== null && ! is_scalar($data)) {
            $this->dispatcher->dispatch($event = new PreSerializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        $visitor = $context->visitor;
        $visitor->startVisiting($data, $type, $context);
        $this->callVisitor($data, $type, $context, $this->getMetadataForType($type));

        if ($this->dispatcher !== null && ! is_scalar($data)) {
            $this->dispatcher->dispatch(new PostSerializeEvent($context, $data, $type));
        }

        $rs = $visitor->endVisiting($data, $type, $context);
        if ($inVisitingStack) {
            $context->stopVisiting($data);
        }

        return $rs;
    }
}
