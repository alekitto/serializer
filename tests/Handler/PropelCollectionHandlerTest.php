<?php declare(strict_types=1);

/*
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

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Handler\PropelCollectionHandler;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;
use Prophecy\Argument;

class PropelCollectionHandlerTest extends AbstractHandlerTest
{
    public function testSerializeShouldReturnStringRepresentation()
    {
        $data = [new TestSubject('lolo'), new TestSubject('pepe')];

        $collection = new \PropelObjectCollection();
        $collection->setData($data);

        $this->visitor->visitArray($data, Argument::type(Type::class), $this->context)->shouldBeCalled();
        $this->handler->serializeCollection($this->visitor->reveal(), $collection, Type::parse(\PropelObjectCollection::class), $this->context->reveal());
    }

    protected function createHandler(): SubscribingHandlerInterface
    {
        return new PropelCollectionHandler();
    }
}

/**
 * @AccessType("property")
 */
class TestSubject
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
