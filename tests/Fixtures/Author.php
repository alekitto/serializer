<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 * @Serializer\AdditionalField(name="links", attributes={
 *     @Serializer\SerializedName("_links"),
 *     @Serializer\Xml\KeyValuePairs(),
 *     @Serializer\Xml\XmlList(inline=true)
 * })
 */
#[Serializer\AccessType(Serializer\AccessType::PROPERTY)]
#[Serializer\AdditionalField(name: 'links', attributes: [
    [Serializer\SerializedName::class, ['_links']],
    [Serializer\Xml\KeyValuePairs::class],
    [Serializer\Xml\XmlList::class, ['inline' => true]],
])]
class Author
{
    /**
     * @Serializer\Type("string")
     * @Serializer\SerializedName("full_name")
     */
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
