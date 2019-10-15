<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Legacy\ObjectWithXmlKeyValuePairs;
use Kcs\Serializer\Tests\Fixtures\Legacy\ObjectWithXmlNamespaces;
use Kcs\Serializer\Tests\Fixtures\Price;
use Kcs\Serializer\Type\Type;

class AnnotationLoaderTest extends BaseLoaderTest
{
    protected function getLoader(): LoaderInterface
    {
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());

        return $loader;
    }

    /**
     * @group legacy
     */
    public function testLegacyXmlKeyValuePairs(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(ObjectWithXmlKeyValuePairs::class));
        $this->getLoader()->loadClassMetadata($m);

        self::assertArrayHasKey('array', $m->getAttributesMetadata());
        self::assertTrue($m->getAttributeMetadata('array')->xmlKeyValuePairs);
    }

    /**
     * @group legacy
     */
    public function testLoadLegacyBlogPostMetadata(): void
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

    /**
     * @group legacy
     */
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
}
