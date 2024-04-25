<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;
use Kcs\Serializer\Metadata\Exclusion;

#[Attribute(Attribute::TARGET_CLASS)]
final class ExclusionPolicy
{
    public function __construct(public Exclusion\Policy $policy)
    {
    }
}
