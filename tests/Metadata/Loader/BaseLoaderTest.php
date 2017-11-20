<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\AuthorReadOnly;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Car;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle;
use Kcs\Serializer\Tests\Fixtures\Node;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualProperties;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualPropertiesAndExcludeAll;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces;
use Kcs\Serializer\Tests\Fixtures\Person;
use Kcs\Serializer\Tests\Fixtures\SimpleClassObject;
use Kcs\Serializer\Tests\Fixtures\SimpleSubClassObject;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

abstract class BaseLoaderTest extends TestCase
{
    public function testLoadBlogPostMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass(BlogPost::class));
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
        $p->type = Type::from('string');
        $p->groups = ['comments', 'post'];
        $p->xmlElementCData = false;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('id'));

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        $p->groups = ['comments', 'post'];
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = Type::from('DateTime');
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'published');
        $p->type = Type::from('boolean');
        $p->serializedName = 'is_published';
        $p->xmlAttribute = true;
        $p->groups = ['post'];
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('published'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->groups = ['post'];
        $p->xmlNamespace = 'http://schemas.google.com/g/2005';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'comments');
        $p->type = new Type('ArrayCollection', [Type::from(Comment::class)]);
        $p->xmlCollection = true;
        $p->xmlCollectionInline = true;
        $p->xmlEntryName = 'comment';
        $p->groups = ['comments'];
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('comments'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = Type::from(Author::class);
        $p->groups = ['post'];
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('author'));

        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\Price'));
        $this->getLoader()->loadClassMetadata($m);
        $this->assertNotNull($m);

        $p = new PropertyMetadata($m->getName(), 'price');
        $p->type = Type::from('double');
        $p->xmlValue = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('price'));
    }

    public function testVirtualProperty()
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithVirtualProperties::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('existField', $m->getAttributesMetadata());
        $this->assertArrayHasKey('virtualValue', $m->getAttributesMetadata());
        $this->assertArrayHasKey('virtualSerializedValue', $m->getAttributesMetadata());
        $this->assertArrayHasKey('typedVirtualProperty', $m->getAttributesMetadata());

        $this->assertEquals($m->getAttributeMetadata('virtualSerializedValue')->serializedName, 'test', 'Serialized name is missing');

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        $this->assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testXmlKeyValuePairs()
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithXmlKeyValuePairs::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('array', $m->getAttributesMetadata());
        $this->assertTrue($m->getAttributeMetadata('array')->xmlKeyValuePairs);
    }

    public function testVirtualPropertyWithExcludeAll()
    {
        $a = new ObjectWithVirtualPropertiesAndExcludeAll();
        $m = new ClassMetadata(new \ReflectionClass($a));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertArrayHasKey('virtualValue', $m->getAttributesMetadata());

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        $this->assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testReadOnlyDefinedBeforeGetterAndSetter()
    {
        $m = new ClassMetadata(new \ReflectionClass(AuthorReadOnly::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
    }

    public function testLoadDiscriminator()
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass(Vehicle::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertEquals('type', $m->discriminatorFieldName);
        $this->assertEquals($m->getName(), $m->discriminatorBaseClass);
        $this->assertEquals(
            [
                'car' => 'Kcs\Serializer\Tests\Fixtures\Discriminator\Car',
                'moped' => 'Kcs\Serializer\Tests\Fixtures\Discriminator\Moped',
            ],
            $m->discriminatorMap
        );
        $this->assertEquals(['Default', 'discriminator_group'], $m->discriminatorGroups);
    }

    public function testLoadDiscriminatorSubClass()
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass(Car::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertNull($m->discriminatorValue);
        $this->assertNull($m->discriminatorBaseClass);
        $this->assertNull($m->discriminatorFieldName);
        $this->assertEquals([], $m->discriminatorMap);
        $this->assertEquals([], $m->discriminatorGroups);
    }

    public function testLoadXmlObjectWithNamespacesMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithXmlNamespaces::class));
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
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = Type::from('DateTime');
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->xmlNamespace = 'http://schemas.google.com/g/2005';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = Type::from('string');
        $p->xmlAttribute = false;
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('author'));

        $p = new PropertyMetadata($m->getName(), 'language');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('language'));
    }

    public function testMaxDepth()
    {
        $m = new ClassMetadata(new \ReflectionClass(Node::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertEquals(2, $m->getAttributeMetadata('children')->maxDepth);
    }

    public function testPersonCData()
    {
        $m = new ClassMetadata(new \ReflectionClass(Person::class));
        $this->getLoader()->loadClassMetadata($m);

        $this->assertNotNull($m);
        $this->assertFalse($m->getAttributeMetadata('name')->xmlElementCData);
    }

    public function testXmlNamespaceInheritanceMetadata()
    {
        $m = new ClassMetadata(new \ReflectionClass(SimpleClassObject::class));
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
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://old.foo.example.org';
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($m->getName(), 'bar');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('bar'));

        $p = new PropertyMetadata($m->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $m->getAttributeMetadata('moo'));

        $subm = new ClassMetadata(new \ReflectionClass(SimpleSubClassObject::class));
        $this->getLoader()->loadClassMetadata($subm);

        $this->assertNotNull($subm);
        $this->assertCount(2, $subm->xmlNamespaces);
        $this->assertArrayHasKey('old_foo', $subm->xmlNamespaces);
        $this->assertEquals('http://foo.example.org', $subm->xmlNamespaces['old_foo']);
        $this->assertArrayHasKey('foo', $subm->xmlNamespaces);
        $this->assertEquals('http://better.foo.example.org', $subm->xmlNamespaces['foo']);
        $this->assertCount(3, $subm->getAttributesMetadata());

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://better.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
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

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://better.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('qux'));

        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('This test executes partially in HHVM due to wrong class reported in PropertyMetadata');

            return;
        }

        $p = new PropertyMetadata($subm->getName(), 'foo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://old.foo.example.org';
        $p->xmlAttribute = true;
        $p->class = 'Kcs\Serializer\Tests\Fixtures\SimpleClassObject';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($subm->getName(), 'bar');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->class = 'Kcs\Serializer\Tests\Fixtures\SimpleClassObject';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $this->assertEquals($p, $subm->getAttributeMetadata('bar'));
    }

    /**
     * @return LoaderInterface
     */
    abstract protected function getLoader();
}
