<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\CamelCaseNamingStrategy;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\AuthorList;
use Kcs\Serializer\Tests\Fixtures\Order;
use Kcs\Serializer\Tests\Fixtures\Price;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $namingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());

        $this->serializer = new Serializer(
            new MetadataFactory($loader),
            new HandlerRegistry(),
            new UnserializeObjectConstructor(),
            ['array' => new GenericSerializationVisitor($namingStrategy)],
            ['array' => new GenericDeserializationVisitor($namingStrategy)]
        );
    }

    public function testToArray()
    {
        $order = new Order(new Price(5));

        $expected = [
            'cost' => [
                'price' => 5,
            ],
        ];

        $result = $this->serializer->toArray($order);

        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider scalarValues
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     * @expectedExceptionMessageRegExp /The input data of type ".+" did not convert to an array, but got a result of type ".+"./
     */
    public function testToArrayWithScalar($input)
    {
        $result = $this->serializer->toArray($input);

        $this->assertEquals([$input], $result);
    }

    public function scalarValues(): iterable
    {
        return [
            [42],
            [3.14159],
            ['helloworld'],
            [true],
        ];
    }

    public function testFromArray()
    {
        $data = [
            'cost' => [
                'price' => 2.5,
            ],
        ];

        $expected = new Order(new Price(2.5));
        $result = $this->serializer->fromArray($data, Type::from(Order::class));

        $this->assertEquals($expected, $result);
    }

    public function testToArrayReturnsArrayObjectAsArray()
    {
        $result = $this->serializer->toArray(new Author(null));

        $this->assertSame([], $result);
    }

    public function testToArrayConversNestedArrayObjects()
    {
        $list = new AuthorList();
        $list->add(new Author(null));

        $result = $this->serializer->toArray($list);
        $this->assertSame(['authors' => [[]]], $result);
    }
}
