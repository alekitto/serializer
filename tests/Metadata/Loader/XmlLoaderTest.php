<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Driver;

use Kcs\Metadata\Loader\FilesLoader;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Metadata\Loader\Locator\IteratorFileLocator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\XmlLoader;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\GetSetObject;
use Kcs\Serializer\Tests\Metadata\Loader\BaseLoaderTest;
use Kcs\Serializer\Type\Type;

class XmlLoaderTest extends BaseLoaderTest
{
    public function testBlogPostExcludeAllStrategy(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(BlogPost::class));
        $this->getLoader('exclude_all')->loadClassMetadata($m);

        self::assertArrayHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayNotHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostExcludeNoneStrategy(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(BlogPost::class));
        $this->getLoader('exclude_none')->loadClassMetadata($m);

        self::assertArrayNotHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostCaseInsensitive(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(BlogPost::class));
        $this->getLoader('case')->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        self::assertEquals($p, $m->getAttributeMetadata('title'));
    }

    public function testAccessorAttributes(): void
    {
        $m = new ClassMetadata(new \ReflectionClass(GetSetObject::class));
        $this->getLoader()->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'name');
        $p->type = Type::from('string');
        $p->getter = 'getTrimmedName';
        $p->setter = 'setCapitalizedName';

        self::assertEquals($p, $m->getAttributeMetadata('name'));
    }

    protected function getLoader(): LoaderInterface
    {
        $append = '/base';
        if (1 === \func_num_args()) {
            $append = '/'.\func_get_arg(0);
        }

        $locator = new IteratorFileLocator();

        return new FilesLoader($locator->locate(__DIR__.'/xml'.$append, '.xml'), XmlLoader::class);
    }
}
