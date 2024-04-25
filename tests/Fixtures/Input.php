<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Metadata\Access\Type;

#[Serializer\Xml\Root("input")]
#[Serializer\AccessType(Type::Property)]
class Input
{
    #[Serializer\Xml\AttributeMap]
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
