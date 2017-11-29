<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Driver;

use Kcs\Metadata\Loader\FilesLoader;
use Kcs\Metadata\Loader\Locator\IteratorFileLocator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Loader\XmlLoader;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Tests\Metadata\Loader\BaseLoaderTest;
use Kcs\Serializer\Type\Type;

class XmlLoaderTest extends BaseLoaderTest
{
    public function testBlogPostExcludeAllStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('exclude_all')->loadClassMetadata($m);

        $this->assertArrayHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            $this->assertArrayNotHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('exclude_none')->loadClassMetadata($m);

        $this->assertArrayNotHasKey('title', $m->getAttributesMetadata());

        $excluded = ['createdAt', 'published', 'comments', 'author'];
        foreach ($excluded as $key) {
            $this->assertArrayHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('case')->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = Type::from('string');
        $this->assertEquals($p, $m->getAttributeMetadata('title'));
    }

    public function testAccessorAttributes()
    {
        $m = new ClassMetadata(new \ReflectionClass('Kcs\Serializer\Tests\Fixtures\GetSetObject'));
        $this->getLoader()->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'name');
        $p->type = Type::from('string');
        $p->getter = 'getTrimmedName';
        $p->setter = 'setCapitalizedName';

        $this->assertEquals($p, $m->getAttributeMetadata('name'));
    }

    protected function getLoader()
    {
        $append = '/base';
        if (1 == func_num_args()) {
            $append = '/'.func_get_arg(0);
        }

        $locator = new IteratorFileLocator();

        return new FilesLoader($locator->locate(__DIR__.'/xml'.$append, '.xml'), XmlLoader::class);
    }
}
