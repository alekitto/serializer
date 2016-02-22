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
use JMS\Serializer\Metadata\Loader\YamlsLoader;
use JMS\Serializer\Metadata\PropertyMetadata;
use Kcs\Metadata\Loader\Locator\IteratorFileLocator;

class YamlLoaderTest extends BaseLoaderTest
{
    public function testAccessorOrderIsInferred()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));
        $this->getLoaderForSubDir('accessor_inferred')->loadClassMetadata($m);
        $this->assertEquals(array('age', 'name'), array_keys($m->getAttributesMetadata()));
    }

    public function testShortExposeSyntax()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\Person'));
        $this->getLoaderForSubDir('short_expose')->loadClassMetadata($m);

        $this->assertArrayHasKey('name', $m->getAttributesMetadata());
        $this->assertArrayNotHasKey('age', $m->getAttributesMetadata());
    }

    public function testBlogPost()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('exclude_all')->loadClassMetadata($m);

        $this->assertArrayHasKey('title', $m->getAttributesMetadata());

        $excluded = array('createdAt', 'published', 'comments', 'author');
        foreach ($excluded as $key) {
            $this->assertArrayNotHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('exclude_none')->loadClassMetadata($m);

        $this->assertArrayNotHasKey('title', $m->getAttributesMetadata());

        $excluded = array('createdAt', 'published', 'comments', 'author');
        foreach ($excluded as $key) {
            $this->assertArrayHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('case')->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = array('name' => 'string', 'params' => array());
        $this->assertEquals($p, $m->getAttributeMetadata('title'));
    }

    public function testBlogPostAccessor()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoaderForSubDir('accessor')->loadClassMetadata($m);

        $this->assertArrayHasKey('title', $m->getAttributesMetadata());

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->getter = 'getOtherTitle';
        $p->setter = 'setOtherTitle';
        $this->assertEquals($p, $m->getAttributeMetadata('title'));
    }

    private function getLoaderForSubDir($subDir = 'base')
    {
        $locator = new IteratorFileLocator();
        return new YamlsLoader($locator->locate(__DIR__.'/yml' . ($subDir ? '/'.$subDir : ''), '.yml'));
    }

    protected function getLoader()
    {
        return $this->getLoaderForSubDir();
    }
}
