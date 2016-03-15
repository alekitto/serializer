<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Serializer\EventDispatcher\Subscriber;

use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Tests\Fixtures\SimpleObjectProxy;
use Kcs\Serializer\VisitorInterface;

class DoctrineProxySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var VisitorInterface */
    private $visitor;

    /** @var DoctrineProxySubscriber */
    private $subscriber;

    public function testRewritesProxyClassName()
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), array('name' => get_class($obj), 'params' => array()));
        $this->subscriber->onPreSerialize($event);

        $this->assertEquals(array('name' => get_parent_class($obj), 'params' => array()), $event->getType());
        $this->assertTrue($obj->__isInitialized());
    }

    public function testDoesNotRewriteCustomType()
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), array('name' => 'FakedName', 'params' => array()));
        $this->subscriber->onPreSerialize($event);

        $this->assertEquals(array('name' => 'FakedName', 'params' => array()), $event->getType());
        $this->assertTrue($obj->__isInitialized());
    }

    protected function setUp()
    {
        $this->subscriber = new DoctrineProxySubscriber();
        $this->visitor = $this->getMock('Kcs\Serializer\Context');
    }

    private function createEvent($object, array $type)
    {
        return new PreSerializeEvent($this->visitor, $object, $type);
    }
}
