<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
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
    protected function setUp(): void
    {
        $namingStrategy = new SerializedNameAnnotationStrategy(new UnderscoreNamingStrategy());
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
     */
    public function testToArrayWithScalar($input): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/The input data of type ".+" did not convert to an array, but got a result of type ".+"./');
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
