<?php

/*
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

namespace Kcs\Serializer\Util;

use Kcs\Serializer\Annotation\Inline;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class SerializableConstraintViolationList
{
    /**
     * @Type("array<Kcs\Serializer\Util\SerializableConstraintViolation>")
     * @XmlList(entry="violation", inline=true)
     * @Inline()
     *
     * @var SerializableConstraintViolation[]
     */
    private $violations = [];

    public function __construct(ConstraintViolationListInterface $list)
    {
        foreach ($list as $violation) {
            $this->violations[] = new SerializableConstraintViolation($violation);
        }
    }
}
