<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\AccessType("property")
 */
class ObjectWithIntListAndIntMap
{
    /** @Serializer\Type("array<integer>") @Serializer\Xml\XmlList */
    private $list;

    /** @Serializer\Type("array<integer,integer>") @Serializer\Xml\Map */
    private $map;

    public function __construct(array $list, array $map)
    {
        $this->list = $list;
        $this->map = $map;
    }
}
