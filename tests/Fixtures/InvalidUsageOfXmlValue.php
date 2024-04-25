<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Xml\Value;
use Kcs\Serializer\Metadata\Access\Type;

#[AccessType(Type::Property)]
class InvalidUsageOfXmlValue
{
    #[Value]
    private $value = 'bar';

    private $element = 'foo';
}
