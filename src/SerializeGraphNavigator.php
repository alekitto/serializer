<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\EventDispatcher\PostSerializeEvent;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\Exclusion\SerializationGroupProviderInterface;
use Kcs\Serializer\Metadata\AdditionalPropertyMetadata;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

class SerializeGraphNavigator extends GraphNavigator
{
    /**
     * @inheritDoc
     *
     * @param SerializationContext $context
     */
    public function accept($data, ?Type $type, Context $context)
    {
        if (null === $type) {
            $type = $context->guessType($data);
        }

        return $this->serialize($data, $type, $context);
    }

    protected function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context)
    {
        if ($data instanceof SerializationGroupProviderInterface) {
            $childGroups = $data->getSerializationGroups($context);
            $context = $context->createChildContext([
                'groups' => ! \is_array($childGroups) ? \iterator_to_array($childGroups, false) : $childGroups
            ]);
        }

        return $context->visitor->visitObject($metadata, $data, $type, $context);
    }

    private function serialize($data, Type $type, SerializationContext $context)
    {
        if (null === $data) {
            $type = Type::null();
        }

        if ($inVisitingStack = \is_object($data)) {
            if ($context->isVisiting($data) && ! $context->getMetadataStack()->getCurrent() instanceof AdditionalPropertyMetadata) {
                return null;
            }

            $context->startVisiting($data);
        }

        // If we're serializing a polymorphic type, then we'll be interested in the
        // metadata for the actual type of the object, not the base class.
        if (\is_object($data) && \is_subclass_of($data, $type->name, false)) {
            $type = new Type(\get_class($data), $type->getParams());
        }

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch($event = new PreSerializeEvent($context, $data, $type));
            $data = $event->getData();
        }

        $visitor = $context->visitor;
        $visitor->startVisiting($data, $type, $context);
        $this->callVisitor($data, $type, $context, $this->getMetadataForType($type));

        if (null !== $this->dispatcher && ! \is_scalar($data)) {
            $this->dispatcher->dispatch(new PostSerializeEvent($context, $data, $type));
        }

        $rs = $visitor->endVisiting($data, $type, $context);
        if ($inVisitingStack) {
            $context->stopVisiting($data);
        }

        return $rs;
    }
}
