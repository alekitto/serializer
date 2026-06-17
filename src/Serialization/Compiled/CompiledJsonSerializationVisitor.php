<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use ArrayObject;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\IterableSerializationVisitorInterface;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

use function count;

class CompiledJsonSerializationVisitor extends JsonSerializationVisitor implements IterableSerializationVisitorInterface
{
    use CompiledSerializationVisitorTrait {
        visitObject as private visitCompiledObject;
        visitIterable as private visitCompiledIterable;
    }

    /**
     * @param iterable<array-key, mixed> $data
     *
     * @return array<array-key, mixed>|object
     */
    public function visitIterable(iterable $data, Type $type, Context $context): array|object
    {
        $rs = $this->visitCompiledIterable($data, $type, $context);

        if ($type->hasParam(1) && count($rs) === 0) {
            $this->setData($rs = new ArrayObject());
        }

        return $rs;
    }

    /** @return array<array-key, mixed>|object */
    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, ObjectConstructorInterface|null $objectConstructor = null): array|object
    {
        $rs = $this->visitCompiledObject($metadata, $data, $type, $context, $objectConstructor);

        if (count($rs) === 0) {
            $this->setData($rs = new ArrayObject());
        }

        return $rs;
    }
}
