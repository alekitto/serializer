<?php

declare(strict_types=1);

namespace Kcs\Serializer\Attribute;

abstract class Version
{
    public function __construct(public string $version)
    {
    }
}
