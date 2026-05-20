<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\IterableSerializationVisitorInterface;

class CompiledSerializationVisitor extends GenericSerializationVisitor implements IterableSerializationVisitorInterface
{
    use CompiledSerializationVisitorTrait;
}
