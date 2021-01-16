<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Xml\Attribute;

/**
 * @AccessType("property")
 */
class ObjectWithNullXmlAttribute extends SimpleObject
{
    /**
     * @Attribute()
     */
    private $nullAttribute = null;
}
