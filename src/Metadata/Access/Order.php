<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Access;

enum Order
{
    case Undefined;
    case Alphabetical;
    case Custom;
}
