<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\StaticField;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 *
 * @StaticField(name="additional_1", value="12", attributes={@Type("integer")})
 * @StaticField(name="additional_2", value="foobar")
 */
class ObjectWithStaticFields
{
    /**
     * @Type("string")
     */
    protected $existField = 'value';
}
