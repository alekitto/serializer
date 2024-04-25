<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Xml\KeyValuePairs;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class ObjectWithXmlKeyValuePairs
{
    /**
     * @var array
     */
    #[KeyValuePairs]
    private $array = [
        'key-one' => 'foo',
        'key-two' => 1,
        'nested-array' => [
            'bar' => 'foo',
        ],
        'without-keys' => [
            1,
            'test',
        ],
        'mixed' => [
            'test',
            'foo' => 'bar',
            '1_foo' => 'bar',
        ],
        1 => 'foo',
    ];
}
