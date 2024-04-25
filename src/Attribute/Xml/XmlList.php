<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute\Xml;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY)]
final class XmlList extends Collection
{
}
