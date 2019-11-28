<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\XmlAttribute;

/**
 * @AccessType("property")
 */
class ObjectWithNullXmlAttribute extends SimpleObject
{
    /**
     * @XmlAttribute()
     */
    private $nullAttribute = null;
}
