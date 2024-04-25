<?php

declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_METHOD)]
final class Until extends Version
{
}
