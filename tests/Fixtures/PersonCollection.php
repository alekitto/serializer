<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;
use Kcs\Serializer\Metadata\Access;

#[Xml\Root('person_collection')]
#[AccessType(Access\Type::Property)]
class PersonCollection
{
    #[Type("ArrayCollection<".Person::class.">")]
    #[Xml\XmlList(entry: "person", inline: true)]
    public $persons;

    #[Type("string")]
    public $location;

    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }
}
