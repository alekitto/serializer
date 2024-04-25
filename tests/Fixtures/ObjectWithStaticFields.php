<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\StaticField;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
#[StaticField(name: 'additional_1', value: '12', attributes: [ new Type('integer') ])]
#[StaticField(name: 'additional_2', value: 'foobar')]
class ObjectWithStaticFields
{
    #[Type('string')]
    protected $existField = 'value';
}
