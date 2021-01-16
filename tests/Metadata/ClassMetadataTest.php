<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata;

use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Tests\Fixtures\Entity_74;
use Kcs\Serializer\Tests\Fixtures\Entity_74_Proxy;
use PHPUnit\Framework\TestCase;

class ClassMetadataTest extends TestCase
{
    public function getAccessOrderCases(): array
    {
        return [
            [['b', 'a'], ['b', 'a']],
            [['a', 'b'], ['a', 'b']],
            [['b'], ['b', 'a']],
            [['a'], ['a', 'b']],
            [['foo', 'bar'], ['b', 'a']],
        ];
    }

    /**
     * @dataProvider getAccessOrderCases
     */
    public function testSetAccessorOrderCustom(array $order, array $expected): void
    {
        $metadata = new ClassMetadata(new \ReflectionClass(PropertyMetadataOrder::class));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'b'));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'a'));
        self::assertEquals(['b', 'a'], \array_keys($metadata->getAttributesMetadata()));

        $metadata->setAccessorOrder(ClassMetadata::ACCESSOR_ORDER_CUSTOM, $order);
        self::assertEquals($expected, \array_keys($metadata->getAttributesMetadata()));
    }

    public function testSetAccessorOrderAlphabetical(): void
    {
        $metadata = new ClassMetadata(new \ReflectionClass(PropertyMetadataOrder::class));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'b'));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'a'));

        self::assertEquals(['b', 'a'], \array_keys($metadata->getAttributesMetadata()));

        $metadata->setAccessorOrder(ClassMetadata::ACCESSOR_ORDER_ALPHABETICAL);
        self::assertEquals(['a', 'b'], \array_keys($metadata->getAttributesMetadata()));
    }

    /**
     * @dataProvider providerPublicMethodData
     */
    public function testAccessorTypePublicMethod($property, $getterInit, $setterInit, $getterName, $setterName): void
    {
        $object = new PropertyMetadataPublicMethod();

        $metadata = new PropertyMetadata(\get_class($object), $property);
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD, $getterInit, $setterInit);

        $metadata->setValue($object, 'x');
        self::assertEquals(\sprintf('%1$s:%1$s:x', \strtoupper($property)), $metadata->getValue($object));

        self::assertEquals($getterName, $metadata->getter);
        self::assertEquals($setterName, $metadata->setter);
    }

    /**
     * @dataProvider providerPublicMethodException
     */
    public function testAccessorTypePublicMethodException($getter, $setter): void
    {
        $this->expectException(RuntimeException::class);
        $object = new PropertyMetadataPublicMethod();

        $metadata = new PropertyMetadata(\get_class($object), 'e');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD, $getter, $setter);

        if (null === $getter) {
            $metadata->getValue($object);
        }

        if (null === $setter) {
            $metadata->setValue($object, null);
        }
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testShouldNotThrowAccessingUninitializedProperty(): void
    {
        $object = new Entity_74();

        $metadata = new PropertyMetadata(\get_class($object), 'uninitialized');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PROPERTY);

        self::assertNull($metadata->getValue($object));
    }

    /**
     * @requires PHP >= 7.4
     */
    public function testShouldCallMagicGetOnUnsetProperties(): void
    {
        $object = new Entity_74_Proxy();

        $metadata = new PropertyMetadata(\get_class($object), 'uninitialized');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PROPERTY);

        self::assertEquals(42, $metadata->getValue($object));

        $metadata = new PropertyMetadata(\get_class($object), 'notUnset');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PROPERTY);

        self::assertNull($metadata->getValue($object));
    }

    public function testAccessorTypePublicMethodWithPublicPropertyException(): void
    {
        $object = new PropertyMetadataPublicMethod();
        $object->f = 'FOOBAR';

        $metadata = new PropertyMetadata(\get_class($object), 'f');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD);

        self::assertEquals('FOOBAR', $metadata->getValue($object));

        $metadata->setValue($object, 'BARBAR');
        self::assertEquals('BARBAR', $object->f);
    }

    public function providerPublicMethodData(): array
    {
        return [
            ['a', null, null, 'getA', 'setA'],
            ['b', null, null, 'isB', 'setB'],
            ['c', null, null, 'hasC', 'setC'],
            ['d', 'fetchd', 'saved', 'fetchd', 'saved'],
        ];
    }

    public function providerPublicMethodException(): array
    {
        return [
            [null, null],
            [null, 'setx'],
            ['getx', null],
        ];
    }
}

class PropertyMetadataOrder
{
    private $b;
    private $a;
}

class PropertyMetadataPublicMethod
{
    private $a;
    private $b;
    private $c;
    private $d;
    private $e;
    public $f;

    public function getA(): string
    {
        return 'A:'.$this->a;
    }

    public function setA($a): void
    {
        $this->a = 'A:'.$a;
    }

    public function isB(): string
    {
        return 'B:'.$this->b;
    }

    public function setB($b): void
    {
        $this->b = 'B:'.$b;
    }

    public function hasC(): string
    {
        return 'C:'.$this->c;
    }

    public function setC($c): void
    {
        $this->c = 'C:'.$c;
    }

    public function fetchD(): string
    {
        return 'D:'.$this->d;
    }

    public function saveD($d): void
    {
        $this->d = 'D:'.$d;
    }
}
