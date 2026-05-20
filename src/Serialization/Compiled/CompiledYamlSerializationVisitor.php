<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\YamlSerializationVisitor;

class CompiledYamlSerializationVisitor extends YamlSerializationVisitor
{
    use CompiledSerializationVisitorTrait;
}
