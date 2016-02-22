<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace JMS\Serializer\Tests\Metadata\Loader;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\VirtualPropertyMetadata;
use Kcs\Metadata\Loader\LoaderInterface;

abstract class BaseLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadBlogPostMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertEquals('blog-post', $m->xmlRootName);
        $this->assertCount(4, $m->xmlNamespaces);
        $this->assertArrayHasKey('', $m->xmlNamespaces);
        $this->assertEquals('http://example.com/namespace', $m->xmlNamespaces['']);
        $this->assertArrayHasKey('gd', $m->xmlNamespaces);
        $this->assertEquals('http://schemas.google.com/g/2005', $m->xmlNamespaces['gd']);
        $this->assertArrayHasKey('atom', $m->xmlNamespaces);
        $this->assertEquals('http://www.w3.org/2005/Atom', $m->xmlNamespaces['atom']);
        $this->assertArrayHasKey('dc', $m->xmlNamespaces);
        $this->assertEquals('http://purl.org/dc/elements/1.1/', $m->xmlNamespaces['dc']);

        $p = new PropertyMetadata($m->getName(), 'id');
        $p->type = array('name' => 'string', 'params' => array());
        $p->groups = array("comments","post");
        $p->xmlElementCData = false;
        $this->assertEquals($p, $m->getAttributeMetadata('id'));

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = array('name' => 'string', 'params' => array());
        $p->groups = array("comments","post");
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        $this->assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = array('name' => 'DateTime', 'params' => array());
        $p->xmlAttribute = true;
        $this->assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'published');
        $p->type = array('name' => 'boolean', 'params' => array());
        $p->serializedName = 'is_published';
        $p->xmlAttribute = true;
        $p->groups = array("post");
        $this->assertEquals($p, $m->getAttributeMetadata('published'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlAttribute = true;
        $p->groups = array("post");
        $p->xmlNamespace = "http://schemas.google.com/g/2005";
        $this->assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'comments');
        $p->type = array('name' => 'ArrayCollection', 'params' => array(array('name' => 'JMS\Serializer\Tests\Fixtures\Comment', 'params' => array())));
        $p->xmlCollection = true;
        $p->xmlCollectionInline = true;
        $p->xmlEntryName = 'comment';
        $p->groups = array("comments");
        $this->assertEquals($p, $m->getAttributeMetadata('comments'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = array('name' => 'JMS\Serializer\Tests\Fixtures\Author', 'params' => array());
        $p->groups = array("post");
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        $this->assertEquals($p, $m->getAttributeMetadata('author'));

        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Price'));
        $this->getLoader()->loadClassMetadata($m);
        $this->assertNotNull($m);

        $p = new PropertyMetadata($m->getName(), 'price');
        $p->type = array('name' => 'double', 'params' => array());
        $p->xmlValue = true;
        $this->assertEquals($p, $m->getAttributeMetadata('price'));
    }

    public function testVirtualProperty()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithVirtualProperties'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('existField', $m->getAttributesMetadata());
        $this->assertArrayHasKey('virtualValue', $m->getAttributesMetadata());
        $this->assertArrayHasKey('virtualSerializedValue', $m->getAttributesMetadata());
        $this->assertArrayHasKey('typedVirtualProperty', $m->getAttributesMetadata());

        $this->assertEquals($m->getAttributeMetadata('virtualSerializedValue')->serializedName, 'test', 'Serialized name is missing' );

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        $this->assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testXmlKeyValuePairs()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('array', $m->getAttributesMetadata());
        $this->assertTrue($m->getAttributeMetadata('array')->xmlKeyValuePairs);
    }

    public function testVirtualPropertyWithExcludeAll()
    {
        $a = new \JMS\Serializer\Tests\Fixtures\ObjectWithVirtualPropertiesAndExcludeAll();
        $m = new ClassMetadata(new \ReflectionClass($a));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('virtualValue', $m->getAttributesMetadata());

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        $this->assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testReadOnlyDefinedBeforeGetterAndSetter()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\AuthorReadOnly'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
    }

    public function testLoadDiscriminator()
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Discriminator\Vehicle'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertEquals('type', $m->discriminatorFieldName);
        $this->assertEquals($m->getName(), $m->discriminatorBaseClass);
        $this->assertEquals(
            array(
                'car' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Car',
                'moped' => 'JMS\Serializer\Tests\Fixtures\Discriminator\Moped',
            ),
            $m->discriminatorMap
        );
    }

    public function testLoadDiscriminatorSubClass()
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Discriminator\Car'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertNull($m->discriminatorValue);
        $this->assertNull($m->discriminatorBaseClass);
        $this->assertNull($m->discriminatorFieldName);
        $this->assertEquals(array(), $m->discriminatorMap);
    }

    public function testLoadXmlObjectWithNamespacesMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertEquals('test-object', $m->xmlRootName);
        $this->assertEquals('http://example.com/namespace', $m->xmlRootNamespace);
        $this->assertCount(3, $m->xmlNamespaces);
        $this->assertArrayHasKey('', $m->xmlNamespaces);
        $this->assertEquals('http://example.com/namespace', $m->xmlNamespaces['']);
        $this->assertArrayHasKey('gd', $m->xmlNamespaces);
        $this->assertEquals('http://schemas.google.com/g/2005', $m->xmlNamespaces['gd']);
        $this->assertArrayHasKey('atom', $m->xmlNamespaces);
        $this->assertEquals('http://www.w3.org/2005/Atom', $m->xmlNamespaces['atom']);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        $this->assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = array('name' => 'DateTime', 'params' => array());
        $p->xmlAttribute = true;
        $this->assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlAttribute = true;
        $p->xmlNamespace = "http://schemas.google.com/g/2005";
        $this->assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlAttribute = false;
        $p->xmlNamespace = "http://www.w3.org/2005/Atom";
        $this->assertEquals($p, $m->getAttributeMetadata('author'));

        $p = new PropertyMetadata($m->getName(), 'language');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlAttribute = true;
        $p->xmlNamespace = "http://purl.org/dc/elements/1.1/";
        $this->assertEquals($p, $m->getAttributeMetadata('language'));
    }

    public function testMaxDepth()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Node'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertEquals(2, $m->getAttributeMetadata('children')->maxDepth);
    }

    public function testPersonCData()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertFalse($m->getAttributeMetadata('name')->xmlElementCData);
    }

    public function testXmlNamespaceInheritanceMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\SimpleClassObject'));
        $this->getLoader()->loadClassMetadata($m);
        
        $this->assertNotNull($m);
        $this->assertCount(3, $m->xmlNamespaces);
        $this->assertArrayHasKey('old_foo', $m->xmlNamespaces);
        $this->assertEquals('http://old.foo.example.org', $m->xmlNamespaces['old_foo']);
        $this->assertArrayHasKey('foo', $m->xmlNamespaces);
        $this->assertEquals('http://foo.example.org', $m->xmlNamespaces['foo']);
        $this->assertArrayHasKey('new_foo', $m->xmlNamespaces);
        $this->assertEquals('http://new.foo.example.org', $m->xmlNamespaces['new_foo']);
        $this->assertCount(3, $m->getAttributesMetadata());

        $p = new PropertyMetadata($m->getName(), 'foo');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://old.foo.example.org";
        $p->xmlAttribute = true;
        $this->assertEquals($p, $m->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($m->getName(), 'bar');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://foo.example.org";
        $this->assertEquals($p, $m->getAttributeMetadata('bar'));

        $p = new PropertyMetadata($m->getName(), 'moo');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://new.foo.example.org";
        $this->assertEquals($p, $m->getAttributeMetadata('moo'));


        $subm = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\SimpleSubClassObject'));
        $this->getLoader()->loadClassMetadata($subm);

        $this->assertNotNull($subm);
        $this->assertCount(2, $subm->xmlNamespaces);
        $this->assertArrayHasKey('old_foo', $subm->xmlNamespaces);
        $this->assertEquals('http://foo.example.org', $subm->xmlNamespaces['old_foo']);
        $this->assertArrayHasKey('foo', $subm->xmlNamespaces);
        $this->assertEquals('http://better.foo.example.org', $subm->xmlNamespaces['foo']);
        $this->assertCount(3, $subm->getAttributesMetadata());

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://better.foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://new.foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('qux'));

        $subm->merge($m);
        $this->assertNotNull($subm);
        $this->assertCount(3, $subm->xmlNamespaces);
        $this->assertArrayHasKey('old_foo', $subm->xmlNamespaces);
        $this->assertEquals('http://foo.example.org', $subm->xmlNamespaces['old_foo']);
        $this->assertArrayHasKey('foo', $subm->xmlNamespaces);
        $this->assertEquals('http://better.foo.example.org', $subm->xmlNamespaces['foo']);
        $this->assertArrayHasKey('new_foo', $subm->xmlNamespaces);
        $this->assertEquals('http://new.foo.example.org', $subm->xmlNamespaces['new_foo']);
        $this->assertCount(5, $subm->getAttributesMetadata());

        $p = new PropertyMetadata($subm->getName(), 'foo');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://old.foo.example.org";
        $p->xmlAttribute = true;
        $p->class = 'JMS\Serializer\Tests\Fixtures\SimpleClassObject';
        $this->assertEquals($p, $subm->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($subm->getName(), 'bar');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://foo.example.org";
        $p->class = 'JMS\Serializer\Tests\Fixtures\SimpleClassObject';
        $this->assertEquals($p, $subm->getAttributeMetadata('bar'));

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://better.foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = array('name' => 'string', 'params' => array());
        $p->xmlNamespace = "http://new.foo.example.org";
        $this->assertEquals($p, $subm->getAttributeMetadata('qux'));
    }


    /**
     * @return LoaderInterface
     */
    abstract protected function getLoader();
}
