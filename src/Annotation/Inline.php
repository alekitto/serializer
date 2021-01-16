<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[Attribute(Attribute::TARGET_PROPERTY, Attribute::TARGET_METHOD)]
final class Inline
{
}
