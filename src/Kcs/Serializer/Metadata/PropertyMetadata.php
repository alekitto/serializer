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
    public $accessorType = self::ACCESS_TYPE_PUBLIC_METHOD;

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
        $this->accessorType = $type;
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function getValue($obj)
    {
        if (PropertyMetadata::ACCESS_TYPE_PROPERTY === $this->accessorType) {
            $reflector = $this->getReflection();
            return $reflector->getValue($obj);
        }

        if (null === $this->getter) {
            $this->initializeGetterAccessor();
        }

        return $obj->{$this->getter}();
    }

    public function setValue($obj, $value)
    {
        if ($this->readOnly) {
            return;
        }

        if (PropertyMetadata::ACCESS_TYPE_PROPERTY === $this->accessorType) {
            $reflector = $this->getReflection();
            $reflector->setValue($obj, $value);
            return;
        }

        if (null === $this->setter) {
            $this->initializeSetterAccessor();
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

    protected function initializeGetterAccessor()
    {
        $methods = [
            'get'.ucfirst($this->name),
            'is'.ucfirst($this->name),
            'has'.ucfirst($this->name),
            $this->name
        ];

        foreach ($methods as $method) {
            if ($this->checkMethod($method)) {
                $this->getter = $method;
                return;
            }
        }

        throw new RuntimeException(sprintf('There is no public method named "%s" in class %s. Please specify which public method should be used for retrieving the value of the property %s.', implode('" or "', $methods), $this->class, $this->name));
    }

    protected function initializeSetterAccessor()
    {
        if ($this->checkMethod($setter = 'set'.ucfirst($this->name))) {
            $this->setter = $setter;
            return;
        }

        throw new RuntimeException(sprintf('There is no public %s method in class %s. Please specify which public method should be used for setting the value of the property %s.', 'set'.ucfirst($this->name), $this->class, $this->name));
    }

    private function checkMethod($name)
    {
        $class = $this->getReflection()->getDeclaringClass();

        return $class->hasMethod($name) && $class->getMethod($name)->isPublic();
    }
}
