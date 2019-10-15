<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;

/**
 * @Serializer\Xml\Root("input")
 * @Serializer\AccessType("property")
 */
class Input
{
    /**
     * @Serializer\Xml\AttributeMap
     */
    private $attributes;

    public function __construct($attributes = null)
    {
        $this->attributes = $attributes ?: [
            'type' => 'text',
            'name' => 'firstname',
            'value' => 'Adrien',
        ];
    }
}
