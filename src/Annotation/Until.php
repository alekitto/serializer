<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_METHOD)]
final class Until extends Version
{
}
