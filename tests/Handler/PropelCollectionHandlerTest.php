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
use Kcs\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

class PropelCollectionHandlerTest extends TestCase
{
    /** @var $serializer \Kcs\Serializer\Serializer */
    private $serializer;

    public function setUp()
    {
        $this->serializer = SerializerBuilder::create()
            ->addDefaultHandlers() //load PropelCollectionHandler
            ->build();
    }

    public function testSerializePropelObjectCollection()
    {
        $collection = new \PropelObjectCollection();
        $collection->setData([new TestSubject('lolo'), new TestSubject('pepe')]);
        $json = $this->serializer->serialize($collection, 'json');

        $data = json_decode($json, true);

        $this->assertCount(2, $data); //will fail if PropelCollectionHandler not loaded

        foreach ($data as $testSubject) {
            $this->assertArrayHasKey('name', $testSubject);
        }
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
