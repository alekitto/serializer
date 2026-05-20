<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\GenericSerializationVisitor;

class CompiledSerializationVisitor extends GenericSerializationVisitor
{
    use CompiledSerializationVisitorTrait;
}
