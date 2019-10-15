<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Xml\KeyValuePairs;

/**
 * @AccessType("property")
 */
class ObjectWithXmlKeyValuePairs
{
    /**
     * @var array
     * @KeyValuePairs()
     */
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
