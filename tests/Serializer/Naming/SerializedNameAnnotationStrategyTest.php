<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\Naming;

use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SerializedNameAnnotationStrategyTest extends TestCase
{
    use ProphecyTrait;

    public function testTranslateNameShouldNotCallDecoratedStrategyIfSerializedNameIsSet(): void
    {
        $delegated = $this->prophesize(PropertyNamingStrategyInterface::class);
        $delegated->translateName(Argument::any())->shouldNotBeCalled();

        $strategy = new SerializedNameAnnotationStrategy($delegated->reveal());

        $mockProperty = $this->prophesize(PropertyMetadata::class);
        $mockProperty->name = 'field';
        $mockProperty->serializedName = 'fieldSerialized';

        self::assertEquals('fieldSerialized', $strategy->translateName($mockProperty->reveal()));
    }

    public function testTranslateNameShouldCallDecoratedStrategyIfSerializedNameIsNotSet(): void
    {
        $delegated = $this->prophesize(PropertyNamingStrategyInterface::class);
        $delegated->translateName(Argument::any())->shouldNotBeCalled();

        $strategy = new SerializedNameAnnotationStrategy($delegated->reveal());

        $mockProperty = $this->prophesize(PropertyMetadata::class);
        $mockProperty->name = 'field';
        $mockProperty->serializedName = 'fieldSerialized';

        self::assertEquals('fieldSerialized', $strategy->translateName($mockProperty->reveal()));
    }
}
