<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Exclude
{
}
