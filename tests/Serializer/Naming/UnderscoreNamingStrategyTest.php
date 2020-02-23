<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
use PHPUnit\Framework\TestCase;

class UnderscoreNamingStrategyTest extends TestCase
{
    public function providePropertyNames(): iterable
    {
        return [
            ['created_at', 'createdAt'],
            ['field123', 'field123'],
            ['get_sql', 'getSQL'],
            ['my_field', 'my_field'],
            ['identical', 'identical'],
        ];
    }

    /**
     * @dataProvider providePropertyNames
     */
    public function testTranslateName(string $expected, string $propertyName): void
    {
        $mockProperty = $this->prophesize(PropertyMetadata::class);
        $mockProperty->name = $propertyName;

        $strategy = new UnderscoreNamingStrategy();
        self::assertEquals($expected, $strategy->translateName($mockProperty->reveal()));
    }
}
