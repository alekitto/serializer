<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\ReadOnly;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlElement;
use Kcs\Serializer\Annotation\XmlNamespace;
use Kcs\Serializer\Annotation\XmlRoot;

/**
 * @XmlRoot("publisher")
 * @XmlNamespace(uri="http://example.com/namespace2", prefix="ns2")
 * @ReadOnly()
 */
class Publisher
{
    /**
     * @Type("string")
     * @XmlElement(namespace="http://example.com/namespace2")
     * @SerializedName("pub_name")
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
