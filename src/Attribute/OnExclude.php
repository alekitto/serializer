<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

use Attribute;
use Kcs\Serializer\Metadata\Exclusion\Behavior;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class OnExclude
{
    public function __construct(public Behavior $policy)
    {
    }
}
