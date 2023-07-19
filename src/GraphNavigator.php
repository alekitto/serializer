<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Psr\EventDispatcher\EventDispatcherInterface;

use function assert;
use function class_exists;
use function interface_exists;
use function method_exists;

/**
 * Handles traversal along the object graph.
 *
 * This class handles traversal along the graph, and calls different methods
 * on visitors, or custom handlers to process its nodes.
 */
abstract class GraphNavigator
{
    private const BUILTIN_TYPES = [
        'NULL' => true,
        'string' => true,
        'integer' => true,
        'int' => true,
        'boolean' => true,
        'bool' => true,
        'double' => true,
        'float' => true,
        'array' => true,
        'resource' => true,
    ];

    public function __construct(
        protected MetadataFactoryInterface $metadataFactory,
        private HandlerRegistryInterface $handlerRegistry,
        protected EventDispatcherInterface|null $dispatcher = null,
    ) {
    }

    /**
     * Called for each node of the graph that is being traversed.
     *
     * @param mixed                                               $data    the data depends on the direction, and type of visitor
     * @param Type|null                                           $type    array has the format ["name" => string, "params" => array]
     * @param SerializationContext|DeserializationContext|Context $context
     *
     * @return mixed the return value depends on the direction, and type of visitor
     */
    abstract public function accept(mixed $data, Type|null $type, Context $context): mixed;

    /**
     * Call serialization visitor.
     */
    protected function callVisitor(mixed $data, Type $type, Context $context, ClassMetadata|null $metadata = null): mixed
    {
        $visitor = $context->visitor;

        // First, try whether a custom handler exists for the given type
        $handler = $this->handlerRegistry->getHandler($context->direction, $type->name);
        if ($handler !== null) {
            return $visitor->visitCustom($handler, $data, $type, $context);
        }

        switch ($type->name) {
            case 'NULL':
                return $visitor->visitNull($data, $type, $context);

            case 'string':
                return $visitor->visitString($data, $type, $context);

            case 'integer':
            case 'int':
                return $visitor->visitInteger($data, $type, $context);

            case 'boolean':
            case 'bool':
                return $visitor->visitBoolean($data, $type, $context);

            case 'double':
            case 'float':
                return $visitor->visitDouble($data, $type, $context);

            case 'array':
                if (method_exists($visitor, 'visitHash')) {
                    if ($type->countParams() === 1) {
                        return $visitor->visitArray($data, $type, $context);
                    }

                    return $visitor->visitHash($data, $type, $context);
                }

                return $visitor->visitArray($data, $type, $context);

            case 'resource':
                $msg = 'Resources are not supported in serialized data.';

                throw new RuntimeException($msg);

            default:
                if ($metadata === null) {
                    // Missing handler for custom type
                    return null;
                }

                $exclusionStrategy = $context->getExclusionStrategy();
                if ($exclusionStrategy !== null && $exclusionStrategy->shouldSkipClass($metadata, $context)) {
                    return null;
                }

                return $this->visitObject($metadata, $data, $type, $context);
        }
    }

    /**
     * Get ClassMetadata instance for type. Returns null if class does not exist.
     */
    protected function getMetadataForType(Type $type): ClassMetadata|null
    {
        if (isset($type->metadata)) {
            assert($type->metadata instanceof ClassMetadata);

            return $type->metadata;
        }

        $name = $type->name;
        if (isset(self::BUILTIN_TYPES[$name]) || (! class_exists($name) && ! interface_exists($name))) {
            return null;
        }

        $metadata = $this->metadataFactory->getMetadataFor($name);
        assert($metadata instanceof ClassMetadata);

        return $type->metadata = $metadata;
    }

    abstract protected function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context): mixed;
}
