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

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

/**
 * Serializer Interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface SerializerInterface
{
    /**
     * Serializes the given data to the specified output format.
     *
     * @param mixed                $data
     * @param string               $format
     * @param SerializationContext $context
     *
     * @return string
     */
    public function serialize($data, $format, SerializationContext $context = null);

    /**
     * Deserializes the given data to the specified type.
     *
     * @param string|mixed           $data
     * @param Type                   $type
     * @param string                 $format
     * @param DeserializationContext $context
     *
     * @return mixed
     */
    public function deserialize($data, Type $type, $format, DeserializationContext $context = null);
}
