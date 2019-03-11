<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests;

use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Naming\CamelCaseNamingStrategy;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class SerializerBuilderTest extends TestCase
{
    /** @var SerializerBuilder */
    private $builder;

    public function testBuildWithoutAnythingElse(): void
    {
        $serializer = $this->builder->build();

        self::assertEquals('"foo"', $serializer->serialize('foo', 'json'));
        self::assertEquals('<?xml version="1.0" encoding="UTF-8"?>
<result><![CDATA[foo]]></result>
', $serializer->serialize('foo', 'xml'));
        self::assertEquals('foo
', $serializer->serialize('foo', 'yml'));

        self::assertEquals('foo', $serializer->deserialize('"foo"', Type::from('string'), 'json'));
        self::assertEquals('foo', $serializer->deserialize('<?xml version="1.0" encoding="UTF-8"?><result><![CDATA[foo]]></result>', Type::from('string'), 'xml'));
    }

    public function testDoesAddDefaultHandlers(): void
    {
        $serializer = $this->builder->build();

        self::assertEquals('"2020-04-16T00:00:00+00:00"', $serializer->serialize(new \DateTime('2020-04-16', new \DateTimeZone('UTC')), 'json'));
    }

    public function testDoesNotAddDefaultHandlersWhenExplicitlyConfigured(): void
    {
        self::assertSame($this->builder, $this->builder->configureHandlers(function (HandlerRegistry $registry) {
        }));

        self::assertEquals('{}', $this->builder->build()->serialize(new \DateTime('2020-04-16'), 'json'));
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\UnsupportedFormatException
     * @expectedExceptionMessage The format "xml" is not supported for serialization
     */
    public function testDoesNotAddOtherVisitorsWhenConfiguredExplicitly(): void
    {
        self::assertSame(
            $this->builder,
            $this->builder->setSerializationVisitor('json', new JsonSerializationVisitor(new CamelCaseNamingStrategy()))
        );

        $this->builder->build()->serialize('foo', 'xml');
    }

    protected function setUp()
    {
        $this->builder = SerializerBuilder::create();
    }
}
