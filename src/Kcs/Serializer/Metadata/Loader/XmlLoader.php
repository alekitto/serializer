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

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Util\Inflector;
use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Metadata\ClassMetadata;

class XmlLoader extends AnnotationLoader
{
    use FileLoaderTrait;

    /**
     * @var \SimpleXMLElement
     */
    private $document;

    public function __construct($filePath)
    {
        $file_content = $this->loadFile($filePath);

        $previous = libxml_use_internal_errors(true);
        $elem = simplexml_load_string($file_content);
        libxml_use_internal_errors($previous);

        if (false === $elem) {
            throw new XmlErrorException(libxml_get_last_error());
        }

        $this->document = $elem;
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata)
    {
        if (! $this->getClassElement($classMetadata->getName())) {
            return true;
        }

        return parent::loadClassMetadata($classMetadata);
    }

    protected function isExcluded(\ReflectionClass $class)
    {
        $element = $this->getClassElement($class->name);

        return $element && ($exclude = $element->attributes()->exclude) && 'true' === strtolower($exclude);
    }

    protected function getClassAnnotations(ClassMetadata $classMetadata)
    {
        $element = $this->getClassElement($classMetadata->getName());
        if (! $element) {
            return [];
        }

        $exclude = [
            'property',
            'virtual-property',
            'pre-serialize',
            'post-serialize',
            'post-deserialize',
            'discriminator'
        ];

        $annotations = $this->loadComplex($element, ['name'], $exclude);

        foreach ($element->xpath("./discriminator") as $discriminatorElement) {
            $discriminator = new Annotations\Discriminator();
            foreach ($this->loadAnnotationProperties($discriminatorElement) as $attrName => $value) {
                $discriminator->{$attrName} = $value;
            }

            $map = [];
            foreach ($discriminatorElement->xpath("./map") as $item) {
                $v = (string)$item->attributes()->value;
                $map[ $v ] = (string)$item;
            }

            $discriminator->map = $map;
            $annotations[] = $discriminator;
        }

        return $annotations;
    }

    protected function getMethodAnnotations(\ReflectionMethod $method)
    {
        $element = $this->getClassElement($method->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $methodName = $method->name;

        if ($pElems = $element->xpath("./virtual-property[@method = '".$methodName."']")) {
            $annotations[] = new Annotations\VirtualProperty();
            $annotations = array_merge($annotations, $this->loadComplex(reset($pElems), ['method']));
        }

        $annotations = array_merge($annotations, $this->getAnnotationFromElement($element, "pre-serialize[@method = '".$methodName."']"));
        $annotations = array_merge($annotations, $this->getAnnotationFromElement($element, "post-serialize[@method = '".$methodName."']"));
        $annotations = array_merge($annotations, $this->getAnnotationFromElement($element, "post-deserialize[@method = '".$methodName."']"));

        return $annotations;
    }

    protected function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $propertyName = $property->name;

        if ($pElems = $element->xpath("./property[@name = '".$propertyName."']")) {
            $annotations = $this->loadComplex(reset($pElems));
        }

        return $annotations;
    }

    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata)
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return false;
        }

        $pElems = $element->xpath("./property[@name = '".$property->name."']");
        $pElem = reset($pElems);

        if ($classMetadata->exclusionPolicy === Annotations\ExclusionPolicy::ALL) {
            return ! $pElem || null === $pElem->attributes()->expose;
        }

        return $pElem && null !== $pElem->attributes()->exclude;
    }

    private function loadComplex(\SimpleXMLElement $element, array $excludedAttributes = ['name'], array $excludedChildren = [])
    {
        $annotations = $this->getAnnotationsFromAttributes($element, $excludedAttributes);

        foreach($element->children() as $name => $child) {
            if (in_array($name, $excludedChildren)) {
                continue;
            }

            $annotations = array_merge($annotations, $this->getAnnotationFromElement($element, $name));
        }

        return $annotations;
    }

    private function getAnnotationFromElement(\SimpleXMLElement $element, $name)
    {
        $annotations = [];

        foreach ($element->xpath("./".$name) as $elem) {
            $annotation = $this->createAnnotationObject($name);

            if ($value = (string)$elem) {
                $annotationReflection = new \ReflectionClass($annotation);
                $property = $annotationReflection->getProperties()[0]->name;

                $annotation->{$property} = $value;
            }

            foreach ($this->loadAnnotationProperties($elem) as $attrName => $value) {
                $annotation->{Inflector::camelize($attrName)} = $value;
            }

            $annotations[] = $annotation;
        }

        return $annotations;
    }

    private function getClassElement($class)
    {
        if (! $elems = $this->document->xpath("./class[@name = '".$class."']")) {
            return false;
        }

        return reset($elems);
    }

    private function createAnnotationObject($name)
    {
        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.Inflector::classify($name);
        $annotation = new $annotationClass();

        return $annotation;
    }

    private function getAnnotationsFromAttributes(\SimpleXMLElement $element, array $excludeAttributes = [])
    {
        $annotations = [];

        foreach ($this->loadAnnotationProperties($element) as $attrName => $value) {
            if (in_array($attrName, $excludeAttributes)) {
                continue;
            }

            $annotation = $this->createAnnotationObject($attrName);
            $annotationReflection = new \ReflectionClass($annotation);

            $properties = $annotationReflection->getProperties();
            if ($property = reset($properties)) {
                $property = $property->name;

                $annotation->{$property} = $value;
                $annotations[] = $annotation;
            }
        }

        return $annotations;
    }

    /**
     * @param \SimpleXMLElement $elem
     * @return \Generator
     */
    private function loadAnnotationProperties(\SimpleXMLElement $elem)
    {
        foreach ($elem->attributes() as $attrName => $value) {
            $value = (string)$value;

            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }

            yield $attrName => $value;
        }
    }
}
