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

namespace Kcs\Serializer\Twig;

use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;

/**
 * Serializer helper twig extension
 *
 * Basically provides access to KcsSerializer from Twig
 */
class SerializerExtension extends \Twig_Extension
{
    protected $serializer;

    public function getName()
    {
        return 'kcs_serializer';
    }

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('serialize', [$this, 'serialize']),
        ];
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('serialization_context', '\Kcs\Serializer\SerializationContext::create'),
        ];
    }

    /**
     * Serialize $object
     *
     * @param object $object
     * @param string $type
     * @param SerializationContext $context
     *
     * @return string
     */
    public function serialize($object, $type = 'json', SerializationContext $context = null)
    {
        return $this->serializer->serialize($object, $type, $context);
    }
}
