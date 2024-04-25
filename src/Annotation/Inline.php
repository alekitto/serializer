<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
final class Inline
{
}
