<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Metadata\StaticPropertyMetadata;
use Kcs\Serializer\Metadata\VirtualPropertyMetadata;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\AuthorReadOnly;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Car;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Moped;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle;
use Kcs\Serializer\Tests\Fixtures\Node;
use Kcs\Serializer\Tests\Fixtures\ObjectWithStaticFields;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualProperties;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualPropertiesAndExcludeAll;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlKeyValuePairs;
use Kcs\Serializer\Tests\Fixtures\ObjectWithXmlNamespaces;
use Kcs\Serializer\Tests\Fixtures\Person;
use Kcs\Serializer\Tests\Fixtures\Price;
use Kcs\Serializer\Tests\Fixtures\SimpleClassObject;
use Kcs\Serializer\Tests\Fixtures\SimpleSubClassObject;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

abstract class BaseLoaderTest extends TestCase
{
    public function testLoadBlogPostMetadata(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(BlogPost::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertEquals('blog-post', $m->xmlRootName);
        self::assertCount(4, $m->xmlNamespaces);
        self::assertArrayHasKey('', $m->xmlNamespaces);
        self::assertEquals('http://example.com/namespace', $m->xmlNamespaces['']);
        self::assertArrayHasKey('gd', $m->xmlNamespaces);
        self::assertEquals('http://schemas.google.com/g/2005', $m->xmlNamespaces['gd']);
        self::assertArrayHasKey('atom', $m->xmlNamespaces);
        self::assertEquals('http://www.w3.org/2005/Atom', $m->xmlNamespaces['atom']);
        self::assertArrayHasKey('dc', $m->xmlNamespaces);
        self::assertEquals('http://purl.org/dc/elements/1.1/', $m->xmlNamespaces['dc']);

        $p = new PropertyMetadata($m->getName(), 'id');
        $p->type = Type::from('string');
        $p->groups = ['comments', 'post'];
        $p->xmlElementCData = false;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('id'));

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        $p->groups = ['comments', 'post'];
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $p->onExclude = PropertyMetadata::ON_EXCLUDE_SKIP;
        self::assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = Type::from('DateTime');
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'published');
        $p->type = Type::from('boolean');
        $p->serializedName = 'is_published';
        $p->xmlAttribute = true;
        $p->groups = ['post'];
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('published'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->groups = ['post'];
        $p->xmlNamespace = 'http://schemas.google.com/g/2005';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'comments');
        $p->type = new Type('ArrayCollection', [Type::from(Comment::class)]);
        $p->xmlCollection = true;
        $p->xmlCollectionInline = true;
        $p->xmlEntryName = 'comment';
        $p->groups = ['comments'];
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('comments'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = Type::from(Author::class);
        $p->groups = ['post'];
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('author'));

        $m = new ClassMetadata(new \ReflectionClass(Price::class));
        $this->getLoader()->loadClassMetadata($m);
        self::assertNotNull($m);

        $p = new PropertyMetadata($m->getName(), 'price');
        $p->type = Type::from('double');
        $p->xmlValue = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('price'));
    }

    public function testStaticFields(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithStaticFields::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertArrayHasKey('existField', $m->getAttributesMetadata());
        self::assertArrayHasKey('additional_1', $m->getAttributesMetadata());
        self::assertArrayHasKey('additional_2', $m->getAttributesMetadata());

        $p = new StaticPropertyMetadata($m->getName(), 'additional_1', '12');
        $p->type = Type::parse('integer');
        self::assertEquals($p, $m->getAttributeMetadata('additional_1'));

        $p = new StaticPropertyMetadata($m->getName(), 'additional_2', 'foobar');
        self::assertEquals($p, $m->getAttributeMetadata('additional_2'));
    }

    public function testVirtualProperty(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithVirtualProperties::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertArrayHasKey('existField', $m->getAttributesMetadata());
        self::assertArrayHasKey('virtualValue', $m->getAttributesMetadata());
        self::assertArrayHasKey('virtualSerializedValue', $m->getAttributesMetadata());
        self::assertArrayHasKey('typedVirtualProperty', $m->getAttributesMetadata());

        self::assertEquals($m->getAttributeMetadata('virtualSerializedValue')->serializedName, 'test', 'Serialized name is missing');

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        self::assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testXmlKeyValuePairs(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithXmlKeyValuePairs::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertArrayHasKey('array', $m->getAttributesMetadata());
        self::assertTrue($m->getAttributeMetadata('array')->xmlKeyValuePairs);
    }

    public function testVirtualPropertyWithExcludeAll(): void
    {
        $a = new ObjectWithVirtualPropertiesAndExcludeAll();
        $m = new ClassMetadata(new \ReflectionClass($a));
        $this->getLoader()->loadClassMetadata($m);

        self::assertArrayHasKey('virtualValue', $m->getAttributesMetadata());

        $p = new VirtualPropertyMetadata($m->getName(), 'virtualValue');
        $p->getter = 'getVirtualValue';

        self::assertEquals($p, $m->getAttributeMetadata('virtualValue'));
    }

    public function testReadOnlyDefinedBeforeGetterAndSetter(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(AuthorReadOnly::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
    }

    public function testLoadDiscriminator(): void
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass(Vehicle::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertEquals('type', $m->discriminatorFieldName);
        self::assertEquals($m->getName(), $m->discriminatorBaseClass);
        self::assertEquals(
            [
                'car' => Car::class,
                'moped' => Moped::class,
            ],
            $m->discriminatorMap
        );
        self::assertEquals(['Default', 'discriminator_group'], $m->discriminatorGroups);
    }

    public function testLoadDiscriminatorSubClass(): void
    {
        /** @var $m ClassMetadata */
        $m = new ClassMetadata(new \ReflectionClass(Car::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertNull($m->discriminatorValue);
        self::assertNull($m->discriminatorBaseClass);
        self::assertNull($m->discriminatorFieldName);
        self::assertEquals([], $m->discriminatorMap);
        self::assertEquals([], $m->discriminatorGroups);
    }

    public function testLoadXmlObjectWithNamespacesMetadata(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithXmlNamespaces::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertEquals('test-object', $m->xmlRootName);
        self::assertEquals('http://example.com/namespace', $m->xmlRootNamespace);
        self::assertCount(3, $m->xmlNamespaces);
        self::assertArrayHasKey('', $m->xmlNamespaces);
        self::assertEquals('http://example.com/namespace', $m->xmlNamespaces['']);
        self::assertArrayHasKey('gd', $m->xmlNamespaces);
        self::assertEquals('http://schemas.google.com/g/2005', $m->xmlNamespaces['gd']);
        self::assertArrayHasKey('atom', $m->xmlNamespaces);
        self::assertEquals('http://www.w3.org/2005/Atom', $m->xmlNamespaces['atom']);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('title'));

        $p = new PropertyMetadata($m->getName(), 'createdAt');
        $p->type = Type::from('DateTime');
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('createdAt'));

        $p = new PropertyMetadata($m->getName(), 'etag');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->xmlNamespace = 'http://schemas.google.com/g/2005';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('etag'));

        $p = new PropertyMetadata($m->getName(), 'author');
        $p->type = Type::from('string');
        $p->xmlAttribute = false;
        $p->xmlNamespace = 'http://www.w3.org/2005/Atom';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('author'));

        $p = new PropertyMetadata($m->getName(), 'language');
        $p->type = Type::from('string');
        $p->xmlAttribute = true;
        $p->xmlNamespace = 'http://purl.org/dc/elements/1.1/';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('language'));
    }

    public function testMaxDepth(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(Node::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertEquals(2, $m->getAttributeMetadata('children')->maxDepth);
    }

    public function testPersonCData(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(Person::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertFalse($m->getAttributeMetadata('name')->xmlElementCData);
    }

    public function testXmlNamespaceInheritanceMetadata(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(SimpleClassObject::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertNotNull($m);
        self::assertCount(3, $m->xmlNamespaces);
        self::assertArrayHasKey('old_foo', $m->xmlNamespaces);
        self::assertEquals('http://old.foo.example.org', $m->xmlNamespaces['old_foo']);
        self::assertArrayHasKey('foo', $m->xmlNamespaces);
        self::assertEquals('http://foo.example.org', $m->xmlNamespaces['foo']);
        self::assertArrayHasKey('new_foo', $m->xmlNamespaces);
        self::assertEquals('http://new.foo.example.org', $m->xmlNamespaces['new_foo']);
        self::assertCount(3, $m->getAttributesMetadata());

        $p = new PropertyMetadata($m->getName(), 'foo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://old.foo.example.org';
        $p->xmlAttribute = true;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($m->getName(), 'bar');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('bar'));

        $p = new PropertyMetadata($m->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $m->getAttributeMetadata('moo'));

        $subm = new ClassMetadata(new \ReflectionClass(SimpleSubClassObject::class));
        $this->getLoader()->loadClassMetadata($subm);

        self::assertNotNull($subm);
        self::assertCount(2, $subm->xmlNamespaces);
        self::assertArrayHasKey('old_foo', $subm->xmlNamespaces);
        self::assertEquals('http://foo.example.org', $subm->xmlNamespaces['old_foo']);
        self::assertArrayHasKey('foo', $subm->xmlNamespaces);
        self::assertEquals('http://better.foo.example.org', $subm->xmlNamespaces['foo']);
        self::assertCount(3, $subm->getAttributesMetadata());

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://better.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('qux'));

        $subm->merge($m);
        self::assertNotNull($subm);
        self::assertCount(3, $subm->xmlNamespaces);
        self::assertArrayHasKey('old_foo', $subm->xmlNamespaces);
        self::assertEquals('http://foo.example.org', $subm->xmlNamespaces['old_foo']);
        self::assertArrayHasKey('foo', $subm->xmlNamespaces);
        self::assertEquals('http://better.foo.example.org', $subm->xmlNamespaces['foo']);
        self::assertArrayHasKey('new_foo', $subm->xmlNamespaces);
        self::assertEquals('http://new.foo.example.org', $subm->xmlNamespaces['new_foo']);
        self::assertCount(5, $subm->getAttributesMetadata());

        $p = new PropertyMetadata($subm->getName(), 'moo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://better.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('moo'));

        $p = new PropertyMetadata($subm->getName(), 'baz');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('baz'));

        $p = new PropertyMetadata($subm->getName(), 'qux');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://new.foo.example.org';
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('qux'));

        if (\defined('HHVM_VERSION')) {
            self::markTestSkipped('This test executes partially in HHVM due to wrong class reported in PropertyMetadata');

            return;
        }

        $p = new PropertyMetadata($subm->getName(), 'foo');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://old.foo.example.org';
        $p->xmlAttribute = true;
        $p->class = SimpleClassObject::class;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('foo'));

        $p = new PropertyMetadata($subm->getName(), 'bar');
        $p->type = Type::from('string');
        $p->xmlNamespace = 'http://foo.example.org';
        $p->class = SimpleClassObject::class;
        $p->accessorType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        self::assertEquals($p, $subm->getAttributeMetadata('bar'));
    }

    /**
     * @return LoaderInterface
     */
    abstract protected function getLoader(): LoaderInterface;
}
