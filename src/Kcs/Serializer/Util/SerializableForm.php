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

use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\XmlAttribute;
use Kcs\Serializer\Annotation\XmlList;
use Kcs\Serializer\Annotation\XmlRoot;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * @XmlRoot(name="form")
 */
class SerializableForm
{
    /**
     * @Type("array<Symfony\Component\Form\FormError>")
     *
     * @var FormError[]
     */
    private $errors = [];

    /**
     * @Type("array<Kcs\Serializer\Util\SerializableForm>")
     * @XmlList(entry="form", inline=true)
     *
     * @var static[]
     */
    private $children = [];

    /**
     * @Type("string")
     * @XmlAttribute()
     *
     * @var string
     */
    private $name;

    public function __construct(FormInterface $form)
    {
        $this->name = $form->getName();

        foreach ($form->getErrors() as $error) {
            $this->errors[] = $error;
        }

        foreach ($form->all() as $child) {
            $this->children[] = new static($child);
        }
    }
}
