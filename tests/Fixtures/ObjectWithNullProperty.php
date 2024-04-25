<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class ObjectWithNullProperty extends SimpleObject
{
    private $nullProperty = null;
}
