<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Attribute\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root('test-object', encoding: 'iso-8859-1')]
#[AccessType(Access\Type::Property)]
class ObjectWithXmlRootEncoding
{
    #[Type("string")]
    private $title;

    public function __construct($title)
    {
        $this->title = $title;
    }
}
