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

    public function testToArray(): void
    {
        $order = new Order(new Price(5));

        $expected = [
            'cost' => [
                'price' => 5,
            ],
        ];

        $result = $this->serializer->normalize($order);

        self::assertEquals($expected, $result);
    }

    /**
     * @dataProvider scalarValues
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     * @expectedExceptionMessageRegExp /The input data of type ".+" did not convert to an array, but got a result of type ".+"./
     */
    public function testToArrayWithScalar($input): void
    {
        $result = $this->serializer->normalize($input);

        self::assertEquals([$input], $result);
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

    public function testFromArray(): void
    {
        $data = [
            'cost' => [
                'price' => 2.5,
            ],
        ];

        $expected = new Order(new Price(2.5));
        $result = $this->serializer->denormalize($data, Type::from(Order::class));

        self::assertEquals($expected, $result);
    }

    public function testToArrayReturnsArrayObjectAsArray(): void
    {
        $result = $this->serializer->normalize(new Author(null));

        self::assertSame([], $result);
    }

    public function testToArrayConversNestedArrayObjects(): void
    {
        $list = new AuthorList();
        $list->add(new Author(null));

        $result = $this->serializer->normalize($list);
        self::assertSame(['authors' => [[]]], $result);
    }
}
