<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Attribute as Annotations;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Inflector\Inflector;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\Exclusion\Policy;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use SimpleXMLElement;

use function array_merge;
use function array_push;
use function assert;
use function explode;
use function in_array;
use function is_string;
use function libxml_get_last_error;
use function libxml_use_internal_errors;
use function reset;
use function simplexml_load_string;
use function strtolower;

class XmlLoader extends AttributesLoader
{
    use FileLoaderTrait;
    use LoaderTrait;

    private SimpleXMLElement $document;

    public function __construct(string $filePath)
    {
        parent::__construct();

        $fileContent = $this->loadFile($filePath);

        $previous = libxml_use_internal_errors(true);
        try {
            $elem = simplexml_load_string($fileContent);
            if ($elem === false) {
                throw new XmlErrorException(libxml_get_last_error()); /* @phpstan-ignore-line */
            }
        } finally {
            libxml_use_internal_errors($previous);
        }

        $this->document = $elem;
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        if (! $this->getClassElement($classMetadata->getName())) {
            return true;
        }

        return parent::loadClassMetadata($classMetadata);
    }

    protected function isExcluded(ReflectionClass $class): bool
    {
        $element = $this->getClassElement($class->name);
        if ($element === false) {
            return false;
        }

        $attributes = $element->attributes();
        if ($attributes === null) {
            return false;
        }

        $exclude = $attributes->exclude;

        return strtolower((string) $exclude) === 'true';
    }

    /**
     * {@inheritDoc}
     */
    protected function getClassAnnotations(ClassMetadata $classMetadata): array
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
            'discriminator',
            'static-field',
            'additional-field',
        ];

        $annotations = $this->loadComplex($element, ['name'], $exclude);

        foreach ($element->xpath('./discriminator') as $discriminatorElement) {
            $discriminator = new Annotations\Discriminator([]);
            foreach ($this->loadAnnotationProperties($discriminatorElement) as $attrName => $value) {
                if ($attrName === 'groups' && is_string($value)) {
                    $value = explode(',', $value);
                }

                $discriminator->{$attrName} = $value;
            }

            $map = [];
            foreach ($discriminatorElement->xpath('./map') as $item) {
                $attr = $item->attributes();
                assert($attr !== null);

                $v = (string) $attr->value;
                $map[$v] = (string) $item;
            }

            /** @phpstan-var array<string, class-string> $map */
            $discriminator->map = $map;
            $annotations[] = $discriminator;
        }

        $reflClass = new ReflectionClass(Annotations\StaticField::class);
        foreach ($element->xpath('./static-field') as $fieldElement) {
            $field = $reflClass->newInstanceWithoutConstructor();
            foreach ($this->loadAnnotationProperties($fieldElement) as $attrName => $value) {
                if (! $reflClass->hasProperty($attrName)) {
                    continue;
                }

                $field->{$attrName} = $value;
            }

            $field->attributes = $this->loadComplex($fieldElement, ['name', 'value']);
            $annotations[] = $field;
        }

        $reflClass = new ReflectionClass(Annotations\AdditionalField::class);
        foreach ($element->xpath('./additional-field') as $fieldElement) {
            $field = (new ReflectionClass(Annotations\AdditionalField::class))->newInstanceWithoutConstructor();
            foreach ($this->loadAnnotationProperties($fieldElement) as $attrName => $value) {
                if (! $reflClass->hasProperty($attrName)) {
                    continue;
                }

                $field->{$attrName} = $value;
            }

            $field->attributes = $this->loadComplex($fieldElement, ['name']);
            $annotations[] = $field;
        }

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    protected function getMethodAnnotations(ReflectionMethod $method): array
    {
        $element = $this->getClassElement($method->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $methodName = $method->name;

        $pElems = $element->xpath("./virtual-property[@method = '" . $methodName . "']");
        if (! empty($pElems)) {
            $annotations[] = new Annotations\VirtualProperty();
            $annotations = array_merge($annotations, $this->loadComplex(reset($pElems), ['method']));
        }

        array_push(
            $annotations,
            ...$this->getAnnotationFromElement($element, "pre-serialize[@method = '" . $methodName . "']"),
            ...$this->getAnnotationFromElement($element, "post-serialize[@method = '" . $methodName . "']"),
            ...$this->getAnnotationFromElement($element, "post-deserialize[@method = '" . $methodName . "']"),
        );

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPropertyAnnotations(ReflectionProperty $property): array
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $propertyName = $property->name;

        $pElems = $element->xpath("./property[@name = '" . $propertyName . "']");
        if (! empty($pElems)) {
            $annotations = $this->loadComplex(reset($pElems));
        }

        return $annotations;
    }

    protected function isPropertyExcluded(ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return false;
        }

        $pElems = $element->xpath("./property[@name = '" . $property->name . "']");
        $pElem = empty($pElems) ? null : reset($pElems);

        if ($classMetadata->exclusionPolicy === Policy::All) {
            return ! $pElem || $pElem->attributes()->expose === null;
        }

        return $pElem && $pElem->attributes()->exclude !== null;
    }

    /**
     * @param string[] $excludedAttributes
     * @param string[] $excludedChildren
     *
     * @return object[]
     */
    private function loadComplex(SimpleXMLElement $element, array $excludedAttributes = ['name'], array $excludedChildren = []): array
    {
        $annotations = $this->getAnnotationsFromAttributes($element, $excludedAttributes);
        foreach ($element->children() as $name => $child) {
            if (in_array($name, $excludedChildren, true)) {
                continue;
            }

            array_push($annotations, ...$this->getAnnotationFromElement($element, $name));
        }

        return $annotations;
    }

    /** @return object[] */
    private function getAnnotationFromElement(SimpleXMLElement $element, string $name): array
    {
        $annotations = [];

        foreach (($element->xpath('./' . $name) ?: []) as $elem) {
            $annotation = $this->createAnnotationObject($name);

            $value = (string) $elem;
            if ($value) {
                $property = $this->getDefaultPropertyName($annotation);
                $annotation->{$property} = $value;
            }

            foreach ($this->loadAnnotationProperties($elem) as $attrName => $value) {
                $annotation->{Inflector::getInstance()->camelize($attrName)} = $value;
            }

            $annotations[] = $annotation;
        }

        return $annotations;
    }

    private function getClassElement(string $class): false|SimpleXMLElement
    {
        $elems = $this->document->xpath("./class[@name = '" . $class . "']");
        if (empty($elems)) {
            return false;
        }

        return reset($elems);
    }

    /**
     * @param string[] $excludeAttributes
     *
     * @return object[]
     */
    private function getAnnotationsFromAttributes(SimpleXMLElement $element, array $excludeAttributes = []): array
    {
        $annotations = [];

        foreach ($this->loadAnnotationProperties($element) as $attrName => $value) {
            if (in_array($attrName, $excludeAttributes, true)) {
                continue;
            }

            $annotation = $this->createAnnotationObject($attrName);
            $annotations[] = $annotation;

            $property = $this->getDefaultPropertyName($annotation);
            if (empty($property)) {
                continue;
            }

            $annotation->{$property} = $this->convertValue($annotation, $property, $value);
        }

        return $annotations;
    }

    /** @return iterable<string, mixed> */
    private function loadAnnotationProperties(SimpleXMLElement $elem): iterable
    {
        foreach (($elem->attributes() ?: []) as $attrName => $value) {
            $value = (string) $value;

            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            }

            yield $attrName => $value;
        }
    }
}
