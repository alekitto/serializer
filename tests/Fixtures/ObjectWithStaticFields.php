<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\StaticField;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
#[StaticField(name: 'additional_1', value: '12', attributes: [ new Type('integer') ])]
#[StaticField(name: 'additional_2', value: 'foobar')]
class ObjectWithStaticFields
{
    #[Type('string')]
    protected $existField = 'value';
}
