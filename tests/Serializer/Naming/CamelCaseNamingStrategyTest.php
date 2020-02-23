<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\CamelCaseNamingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @group legacy
 */
class CamelCaseNamingStrategyTest extends TestCase
{
    public function providePropertyNames(): iterable
    {
        return [
            ['.', false, 'Created.At', 'createdAt'],
            ['_', true, 'get_s_q_l', 'getSQL'],
            ['/', true, 'my_field', 'my_field'],
            ['_', false, 'Identical', 'identical'],
        ];
    }

    /**
     * @dataProvider providePropertyNames
     */
    public function testTranslateName(string $separator, bool $lowerCase, string $expected, string $propertyName): void
    {
        $mockProperty = $this->prophesize(PropertyMetadata::class);
        $mockProperty->name = $propertyName;

        $strategy = new CamelCaseNamingStrategy($separator, $lowerCase);
        self::assertEquals($expected, $strategy->translateName($mockProperty->reveal()));
    }
}
