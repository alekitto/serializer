<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Tests\Metadata;

use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\SerializationContext;

class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function getAccessOrderCases()
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
    public function testSetAccessorOrderCustom(array $order, array $expected)
    {
        $metadata = new ClassMetadata(new \ReflectionClass(PropertyMetadataOrder::class));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'b'));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'a'));
        $this->assertEquals(['b', 'a'], array_keys($metadata->getAttributesMetadata()));

        $metadata->setAccessorOrder(ClassMetadata::ACCESSOR_ORDER_CUSTOM, $order);
        $this->assertEquals($expected, array_keys($metadata->getAttributesMetadata()));
    }

    public function testSetAccessorOrderAlphabetical()
    {
        $metadata = new ClassMetadata(new \ReflectionClass(PropertyMetadataOrder::class));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'b'));
        $metadata->addAttributeMetadata(new PropertyMetadata(PropertyMetadataOrder::class, 'a'));

        $this->assertEquals(['b', 'a'], array_keys($metadata->getAttributesMetadata()));

        $metadata->setAccessorOrder(ClassMetadata::ACCESSOR_ORDER_ALPHABETICAL);
        $this->assertEquals(['a', 'b'], array_keys($metadata->getAttributesMetadata()));
    }

    /**
     * @dataProvider providerPublicMethodData
     */
    public function testAccessorTypePublicMethod($property, $getterInit, $setterInit, $getterName, $setterName)
    {
        $object = new PropertyMetadataPublicMethod();

        $metadata = new PropertyMetadata(get_class($object), $property);
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD, $getterInit, $setterInit);

        $metadata->setValue($object, 'x');
        $this->assertEquals(sprintf('%1$s:%1$s:x', strtoupper($property)), $metadata->getValue($object, new SerializationContext()));

        $this->assertEquals($getterName, $metadata->getter);
        $this->assertEquals($setterName, $metadata->setter);
    }

    /**
     * @dataProvider providerPublicMethodException
     * @expectedException \Kcs\Serializer\Exception\RuntimeException
     */
    public function testAccessorTypePublicMethodException($getter, $setter)
    {
        $object = new PropertyMetadataPublicMethod();

        $metadata = new PropertyMetadata(get_class($object), 'e');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD, $getter, $setter);

        if (null === $getter) {
            $metadata->getValue($object);
        }

        if (null === $setter) {
            $metadata->setValue($object, null);
        }
    }

    public function testAccessorTypePublicMethodWithPublicPropertyException()
    {
        $object = new PropertyMetadataPublicMethod();
        $object->f = 'FOOBAR';

        $metadata = new PropertyMetadata(get_class($object), 'f');
        $metadata->setAccessor(PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD);

        $this->assertEquals('FOOBAR', $metadata->getValue($object));

        $metadata->setValue($object, 'BARBAR');
        $this->assertEquals('BARBAR', $object->f);
    }

    public function providerPublicMethodData()
    {
        return [
            ['a', null, null, 'getA', 'setA'],
            ['b', null, null, 'isB', 'setB'],
            ['c', null, null, 'hasC', 'setC'],
            ['d', 'fetchd', 'saved', 'fetchd', 'saved'],
        ];
    }

    public function providerPublicMethodException()
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
    private $b, $a;
}

class PropertyMetadataPublicMethod
{
    private $a, $b, $c, $d, $e;
    public $f;

    public function getA()
    {
        return 'A:'.$this->a;
    }

    public function setA($a)
    {
        $this->a = 'A:'.$a;
    }

    public function isB()
    {
        return 'B:'.$this->b;
    }

    public function setB($b)
    {
        $this->b = 'B:'.$b;
    }

    public function hasC()
    {
        return 'C:'.$this->c;
    }

    public function setC($c)
    {
        $this->c = 'C:'.$c;
    }

    public function fetchD()
    {
        return 'D:'.$this->d;
    }

    public function saveD($d)
    {
        $this->d = 'D:'.$d;
    }
}
