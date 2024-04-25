<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;
use Kcs\Serializer\Metadata\Access\Type as MetadataAccessType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class AccessType
{
    public function __construct(public MetadataAccessType $type)
    {
    }
}
