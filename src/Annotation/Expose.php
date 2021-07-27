<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Expose
{
}
