<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Metadata\ClassMetadataInterface;
use Kcs\Metadata\Loader\FileLoaderTrait;
use Kcs\Serializer\Annotation as Annotations;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Inflector\Inflector;
use Kcs\Serializer\Metadata\ClassMetadata;
use SimpleXMLElement;

use function explode;

class XmlLoader extends AnnotationLoader
{
    use FileLoaderTrait;
    use LoaderTrait;

    private SimpleXMLElement $document;

    public function __construct($filePath)
    {
        parent::__construct();
        $file_content = $this->loadFile($filePath);

        $previous = \libxml_use_internal_errors(true);
        $elem = \simplexml_load_string($file_content);
        \libxml_use_internal_errors($previous);

        if (false === $elem) {
            throw new XmlErrorException(\libxml_get_last_error());
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

    /**
     * {@inheritdoc}
     */
    protected function isExcluded(\ReflectionClass $class): bool
    {
        $element = $this->getClassElement($class->name);

        return $element && ($exclude = $element->attributes()->exclude) && 'true' === \strtolower($exclude);
    }

    /**
     * {@inheritdoc}
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
                $v = (string) $item->attributes()->value;
                $map[$v] = (string) $item;
            }

            $discriminator->map = $map;
            $annotations[] = $discriminator;
        }

        foreach ($element->xpath('./static-field') as $fieldElement) {
            $field = (new \ReflectionClass(Annotations\StaticField::class))->newInstanceWithoutConstructor();
            foreach ($this->loadAnnotationProperties($fieldElement) as $attrName => $value) {
                $field->{$attrName} = $value;
            }

            $field->attributes = $this->loadComplex($fieldElement, ['name', 'value']);
            $annotations[] = $field;
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMethodAnnotations(\ReflectionMethod $method): array
    {
        $element = $this->getClassElement($method->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $methodName = $method->name;

        if ($pElems = $element->xpath("./virtual-property[@method = '".$methodName."']")) {
            $annotations[] = new Annotations\VirtualProperty();
            $annotations = \array_merge($annotations, $this->loadComplex(\reset($pElems), ['method']));
        }

        $annotations = \array_merge($annotations, $this->getAnnotationFromElement($element, "pre-serialize[@method = '".$methodName."']"));
        $annotations = \array_merge($annotations, $this->getAnnotationFromElement($element, "post-serialize[@method = '".$methodName."']"));
        $annotations = \array_merge($annotations, $this->getAnnotationFromElement($element, "post-deserialize[@method = '".$methodName."']"));

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return [];
        }

        $annotations = [];
        $propertyName = $property->name;

        if ($pElems = $element->xpath("./property[@name = '".$propertyName."']")) {
            $annotations = $this->loadComplex(\reset($pElems));
        }

        return $annotations;
    }

    /**
     * {@inheritdoc}
     */
    protected function isPropertyExcluded(\ReflectionProperty $property, ClassMetadata $classMetadata): bool
    {
        $element = $this->getClassElement($property->getDeclaringClass()->getName());
        if (! $element) {
            return false;
        }

        $pElems = $element->xpath("./property[@name = '".$property->name."']");
        $pElem = \reset($pElems);

        if (Annotations\ExclusionPolicy::ALL === $classMetadata->exclusionPolicy) {
            return ! $pElem || null === $pElem->attributes()->expose;
        }

        return $pElem && null !== $pElem->attributes()->exclude;
    }

    private function loadComplex(SimpleXMLElement $element, array $excludedAttributes = ['name'], array $excludedChildren = []): array
    {
        $annotations = $this->getAnnotationsFromAttributes($element, $excludedAttributes);

        foreach ($element->children() as $name => $child) {
            if (\in_array($name, $excludedChildren, true)) {
                continue;
            }

            $annotations = \array_merge($annotations, $this->getAnnotationFromElement($element, $name));
        }

        return $annotations;
    }

    private function getAnnotationFromElement(SimpleXMLElement $element, string $name): array
    {
        $annotations = [];

        foreach ($element->xpath('./'.$name) as $elem) {
            $annotation = $this->createAnnotationObject($name);

            if ($value = (string) $elem) {
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

    private function getClassElement(string $class)
    {
        if (! $elems = $this->document->xpath("./class[@name = '".$class."']")) {
            return false;
        }

        return \reset($elems);
    }

    private function getAnnotationsFromAttributes(SimpleXMLElement $element, array $excludeAttributes = []): array
    {
        $annotations = [];

        foreach ($this->loadAnnotationProperties($element) as $attrName => $value) {
            if (\in_array($attrName, $excludeAttributes, true)) {
                continue;
            }

            $annotation = $this->createAnnotationObject($attrName);
            $annotations[] = $annotation;

            if ($property = $this->getDefaultPropertyName($annotation)) {
                $annotation->{$property} = $this->convertValue($annotation, $property, $value);
            }
        }

        return $annotations;
    }

    /**
     * @param SimpleXMLElement $elem
     *
     * @return iterable
     */
    private function loadAnnotationProperties(SimpleXMLElement $elem): iterable
    {
        foreach ($elem->attributes() as $attrName => $value) {
            $value = (string) $value;

            if ('true' === $value) {
                $value = true;
            } elseif ('false' === $value) {
                $value = false;
            }

            yield $attrName => $value;
        }
    }
}
