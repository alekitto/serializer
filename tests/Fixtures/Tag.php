<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Kcs;

/**
 * @Kcs\XmlRoot("tag")
 * @Kcs\XmlNamespace(uri="http://purl.org/dc/elements/1.1/", prefix="dc")
 */
class Tag
{
    /**
     * @Kcs\XmlElement(cdata=false)
     * @Kcs\Type("string")
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }
}
