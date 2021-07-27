<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class VirtualProperty
{
}
