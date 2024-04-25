<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Xml\Value;
use Kcs\Serializer\Metadata\Access\Type;

#[AccessType(Type::Property)]
class InvalidUsageOfXmlValue
{
    #[Value]
    private $value = 'bar';

    private $element = 'foo';
}
