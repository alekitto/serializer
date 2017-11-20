<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class GroupsObject
{
    /**
     * @Groups({"foo"})
     * @Type("string")
     */
    private $foo;

    /**
     * @Groups({"foo","bar"})
     * @Type("string")
     */
    private $foobar;

    /**
     * @Groups({"bar", "Default"})
     * @Type("string")
     */
    private $bar;

    /**
     * @Groups({"foo", "!baz"})
     * @Type("string")
     *
     * @var string
     */
    private $baz;

    /**
     * @Type("string")
     */
    private $none;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
        $this->foobar = 'foobar';
        $this->none = 'none';
        $this->baz = 'baz';
    }
}
