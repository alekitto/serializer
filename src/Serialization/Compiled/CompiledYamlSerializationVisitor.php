<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\IterableSerializationVisitorInterface;
use Kcs\Serializer\YamlSerializationVisitor;

class CompiledYamlSerializationVisitor extends YamlSerializationVisitor implements IterableSerializationVisitorInterface
{
    use CompiledSerializationVisitorTrait;
}
