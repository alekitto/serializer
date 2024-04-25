<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessType(Access\Type::Property)]
#[Serializer\AdditionalField(name: 'links', attributes: [ new Serializer\SerializedName('_links'), new Serializer\Xml\KeyValuePairs, new Serializer\Xml\XmlList(inline: true) ])]
class Author
{
    #[Serializer\Type('string')]
    #[Serializer\SerializedName('full_name')]
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
