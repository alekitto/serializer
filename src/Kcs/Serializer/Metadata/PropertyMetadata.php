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

namespace Kcs\Serializer\Metadata;

use Kcs\Serializer\TypeParser;
use Kcs\Metadata\PropertyMetadata as BasePropertyMetadata;
use Kcs\Serializer\Exception\RuntimeException;

class PropertyMetadata extends BasePropertyMetadata
{
    const ACCESS_TYPE_PROPERTY        = 'property';
    const ACCESS_TYPE_PUBLIC_METHOD   = 'public_method';

    public $sinceVersion;
    public $untilVersion;
    public $groups = array ();
    public $serializedName;
    public $type;
    public $xmlCollection = false;
    public $xmlCollectionInline = false;
    public $xmlEntryName;
    public $xmlKeyAttribute;
    public $xmlAttribute = false;
    public $xmlValue = false;
    public $xmlNamespace;
    public $xmlKeyValuePairs = false;
    public $xmlElementCData = true;
    public $getter;
    public $setter;
    public $inline = false;
    public $readOnly = false;
    public $xmlAttributeMap = false;
    public $maxDepth = null;

    private static $typeParser;

    public function __construct($class, $name)
    {
        parent::__construct($class, $name);

        $this->getReflection()->setAccessible(true);
    }

    public function __wakeup()
    {
        parent::__wakeup();

        $this->getReflection()->setAccessible(true);
    }

    public function setAccessor($type, $getter = null, $setter = null)
    {
        if (self::ACCESS_TYPE_PUBLIC_METHOD === $type) {
            $class = $this->getReflection()->getDeclaringClass();

            if (empty($getter)) {
                if ($class->hasMethod('get'.$this->name) && $class->getMethod('get'.$this->name)->isPublic()) {
                    $getter = 'get'.$this->name;
                } elseif ($class->hasMethod('is'.$this->name) && $class->getMethod('is'.$this->name)->isPublic()) {
                    $getter = 'is'.$this->name;
                } elseif ($class->hasMethod('has'.$this->name) && $class->getMethod('has'.$this->name)->isPublic()) {
                    $getter = 'has'.$this->name;
                } else {
                    throw new RuntimeException(sprintf('There is neither a public %s method, nor a public %s method, nor a public %s method in class %s. Please specify which public method should be used for retrieving the value of the property %s.', 'get'.ucfirst($this->name), 'is'.ucfirst($this->name), 'has'.ucfirst($this->name), $this->class, $this->name));
                }
            }

            if (empty($setter) && ! $this->readOnly) {
                if ($class->hasMethod('set'.$this->name) && $class->getMethod('set'.$this->name)->isPublic()) {
                    $setter = 'set'.$this->name;
                } else {
                    throw new RuntimeException(sprintf('There is no public %s method in class %s. Please specify which public method should be used for setting the value of the property %s.', 'set'.ucfirst($this->name), $this->class, $this->name));
                }
            }
        }

        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function getValue($obj)
    {
        if (null === $this->getter) {
            $reflector = $this->getReflection();
            return $reflector->getValue($obj);
        }

        return $obj->{$this->getter}();
    }

    public function setValue($obj, $value)
    {
        if (null === $this->setter) {
            $reflector = $this->getReflection();
            $reflector->setValue($obj, $value);
            return;
        }

        $obj->{$this->setter}($value);
    }

    public function setType($type)
    {
        if (null === self::$typeParser) {
            self::$typeParser = new TypeParser();
        }

        $this->type = self::$typeParser->parse($type);
    }
}
