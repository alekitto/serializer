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

namespace JMS\Serializer\Tests\Metadata\Driver;

use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\Loader\XmlsLoader;
use JMS\Serializer\Tests\Metadata\Loader\BaseLoaderTest;
use JMS\Serializer\Metadata\PropertyMetadata;
use Kcs\Metadata\Loader\Locator\IteratorFileLocator;

class XmlLoaderTest extends BaseLoaderTest
{
    public function testBlogPostExcludeAllStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('exclude_all')->loadClassMetadata($m);

        $this->assertArrayHasKey('title', $m->getAttributesMetadata());

        $excluded = array('createdAt', 'published', 'comments', 'author');
        foreach ($excluded as $key) {
            $this->assertArrayNotHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostExcludeNoneStrategy()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('exclude_none')->loadClassMetadata($m);

        $this->assertArrayNotHasKey('title', $m->getAttributesMetadata());

        $excluded = array('createdAt', 'published', 'comments', 'author');
        foreach ($excluded as $key) {
            $this->assertArrayHasKey($key, $m->getAttributesMetadata());
        }
    }

    public function testBlogPostCaseInsensitive()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\BlogPost'));
        $this->getLoader('case')->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'title');
        $p->type = array('name' => 'string', 'params' => array());
        $this->assertEquals($p, $m->getAttributeMetadata('title'));
    }

    public function testAccessorAttributes()
    {
        $m = new ClassMetadata(new \ReflectionClass('JMS\Serializer\Tests\Fixtures\GetSetObject'));
        $this->getLoader()->loadClassMetadata($m);

        $p = new PropertyMetadata($m->getName(), 'name');
        $p->type = array('name' => 'string', 'params' => array());
        $p->getter = 'getTrimmedName';
        $p->setter = 'setCapitalizedName';

        $this->assertEquals($p, $m->getAttributeMetadata('name'));
    }

    protected function getLoader()
    {
        $append = '/base';
        if (func_num_args() == 1) {
            $append = '/'.func_get_arg(0);
        }

        $locator = new IteratorFileLocator();
        return new XmlsLoader($locator->locate(__DIR__.'/xml'.$append, '.xml'));
    }
}