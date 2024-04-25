<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute as PhpAttribute;

#[PhpAttribute(PhpAttribute::TARGET_METHOD | PhpAttribute::TARGET_PROPERTY)]
final class Attribute
{
    public function __construct(public string|null $namespace = null)
    {
    }
}
