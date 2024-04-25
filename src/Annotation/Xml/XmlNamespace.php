<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class XmlNamespace
{
    public function __construct(public string $uri, public string $prefix = '')
    {
    }
}
