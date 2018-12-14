<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\IdenticalPropertyNamingStrategy;
use PHPUnit\Framework\TestCase;

class IdenticalPropertyNamingStrategyTest extends TestCase
{
    public function providePropertyNames(): iterable
    {
        return [
            ['createdAt'],
            ['my_field'],
            ['identical'],
        ];
    }

    /**
     * @dataProvider providePropertyNames
     */
    public function testTranslateName(string $propertyName)
    {
        $mockProperty = $this->prophesize(PropertyMetadata::class);
        $mockProperty->name = $propertyName;

        $strategy = new IdenticalPropertyNamingStrategy();
        self::assertEquals($propertyName, $strategy->translateName($mockProperty->reveal()));
    }
}
