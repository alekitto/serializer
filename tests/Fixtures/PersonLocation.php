<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml\Root;

/**
 * @Root("person_location")
 * @AccessType("property")
 */
class PersonLocation
{
    /**
     * @Type("Kcs\Serializer\Tests\Fixtures\Person")
     */
    public $person;

    /**
     * @Type("string")
     */
    public $location;
}
