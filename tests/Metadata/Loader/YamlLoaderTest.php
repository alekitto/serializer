<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Kcs\Metadata\Loader\FilesLoader;
use Kcs\Metadata\Loader\Locator\IteratorFileLocator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\YamlLoader;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;

class YamlLoaderTest extends BaseLoaderTest
{
    public function testAccessorOrderIsInferred()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\Person'));
        $this->getLoaderForSubDir('accessor_inferred')->loadClassMetadata($m);
        self::assertEquals(['age', 'name'], \array_keys($m->getAttributesMetadata()));
    }

    public function testShortExposeSyntax()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\Person'));
        $this->getLoaderForSubDir('short_expose')->loadClassMetadata($m);

        self::assertArrayHasKey('name', $m->getAttributesMetadata());
        self::assertArrayNotHasKey('age', $m->getAttributesMetadata());
    }

    public function testBlogPost()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('exclude_all')->loadClassMetadata($m);

        self::assertArrayHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayNotHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('exclude_none')->loadClassMetadata($m);

        self::assertArrayNotHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            self::assertArrayHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('case')->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        self::assertEquals($p, $m->getAttributeMetadata('title'));
    }

    public function testBlogPostAccessor()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('accessor')->loadClassMetadata($m);

        self::assertArrayHasKey('title', $m->getAttributesMetadata());

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->getter = 'getOtherTitle';
        $p->setter = 'setOtherTitle';
        self::assertEquals($p, $m->getAttributeMetadata('title'));
    }

    private function getLoaderForSubDir($subDir = 'base')
    {
        $locator = new IteratorFileLocator();

        return new FilesLoader($locator->locate(__DIR__.'/yml'.($subDir ? '/'.$subDir : ''), '.yml'), YamlLoader::class);
    }

    protected function getLoader()
    {
        return $this->getLoaderForSubDir();
    }
}
