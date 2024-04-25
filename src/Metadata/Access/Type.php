<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Access;

enum Type
{
    case PublicMethod;
    case Property;
}
