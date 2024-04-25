<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Exclusion;

enum Behavior
{
    case Skip;
    case Null;
}
