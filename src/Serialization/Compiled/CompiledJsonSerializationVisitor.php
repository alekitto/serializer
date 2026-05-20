<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use ArrayObject;
use Kcs\Serializer\Context;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

use function count;

class CompiledJsonSerializationVisitor extends JsonSerializationVisitor
{
    use CompiledSerializationVisitorTrait {
        visitObject as private visitCompiledObject;
    }

    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, \Kcs\Serializer\Construction\ObjectConstructorInterface|null $objectConstructor = null): array|object
    {
        $rs = $this->visitCompiledObject($metadata, $data, $type, $context, $objectConstructor);

        if (count($rs) === 0) {
            $this->setData($rs = new ArrayObject());
        }

        return $rs;
    }
}
