<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessorOrder;
use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\VirtualProperty;

/**
 * @AccessorOrder("custom", custom = {"prop_name", "existField", "foo" })
 * @AccessType("property")
 */
class ObjectWithVirtualProperties
{
    /**
     * @Type("string")
     */
    protected $existField = 'value';

    /**
     * @VirtualProperty
     */
    public function getVirtualValue()
    {
        return 'value';
    }

    /**
     * @VirtualProperty
     * @SerializedName("test")
     */
    public function getVirtualSerializedValue()
    {
        return 'other-name';
    }

    /**
     * @VirtualProperty
     * @Type("int")
     */
    public function getTypedVirtualProperty()
    {
        return '1';
    }
}
