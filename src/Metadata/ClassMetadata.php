<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\ClassMetadata as BaseClassMetadata;
use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Exception\InvalidArgumentException;
use LogicException;

use function array_flip;
use function array_keys;
use function array_merge;
use function array_search;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function ksort;
use function sprintf;
use function uksort;
use function var_export;

/**
 * Class Metadata used to customize the serialization process.
 */
class ClassMetadata extends BaseClassMetadata
{
    public const ACCESSOR_ORDER_UNDEFINED = 'undefined';
    public const ACCESSOR_ORDER_ALPHABETICAL = 'alphabetical';
    public const ACCESSOR_ORDER_CUSTOM = 'custom';

    public string $exclusionPolicy = ExclusionPolicy::NONE;
    public string $defaultAccessType = PropertyMetadata::ACCESS_TYPE_PUBLIC_METHOD;
    public bool $readOnly = false;
    public ?string $xmlRootName = null;
    public ?string $xmlRootNamespace = null;
    public ?string $xmlEncoding = null;
    public array $xmlNamespaces = [];
    public string $csvDelimiter = ',';
    public string $csvEnclosure = '"';
    public string $csvEscapeChar = '\\';
    public bool $csvEscapeFormulas = false;
    public string $csvKeySeparator = '.';
    public bool $csvNoHeaders = false;
    public bool $csvOutputBom = false;
    public ?string $accessorOrder = null;
    public ?array $customOrder = null;
    public bool $discriminatorDisabled = false;
    public ?string $discriminatorBaseClass = null;
    public ?string $discriminatorFieldName = null;
    public ?string $discriminatorValue = null;
    public array $discriminatorMap = [];
    public array $discriminatorGroups = [];

    public function setDiscriminator(string $fieldName, array $map, array $groups): void
    {
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('The $fieldName cannot be empty.');
        }

        if (empty($map)) {
            throw new \InvalidArgumentException('The discriminator map cannot be empty.');
        }

        $this->discriminatorBaseClass = $this->getName();
        $this->discriminatorFieldName = $fieldName;
        $this->discriminatorMap = $map;
        $this->discriminatorGroups = $groups;
    }

    /**
     * Sets the order of properties in the class.
     *
     * @throws InvalidArgumentException When the accessor order is not valid
     * @throws InvalidArgumentException When the custom order is not valid
     */
    public function setAccessorOrder(string $order, array $customOrder = []): void
    {
        if (! in_array($order, [self::ACCESSOR_ORDER_UNDEFINED, self::ACCESSOR_ORDER_ALPHABETICAL, self::ACCESSOR_ORDER_CUSTOM], true)) {
            throw new InvalidArgumentException(sprintf('The accessor order "%s" is invalid.', $order));
        }

        foreach ($customOrder as $name) {
            if (! is_string($name)) {
                throw new InvalidArgumentException(sprintf('$customOrder is expected to be a list of strings, but got element of value %s.', var_export($name, true)));
            }
        }

        $this->accessorOrder = $order;
        $this->customOrder = array_flip($customOrder);
        $this->sortProperties();
    }

    public function addAttributeMetadata(MetadataInterface $metadata): void
    {
        parent::addAttributeMetadata($metadata);
        $this->sortProperties();
    }

    public function merge(MetadataInterface $object): void
    {
        if (! $object instanceof self) {
            throw new InvalidArgumentException('$object must be an instance of ClassMetadata.');
        }

        parent::merge($object);

        $this->xmlRootName = $this->xmlRootName ?: $object->xmlRootName;
        $this->xmlRootNamespace = $this->xmlRootNamespace ?: $object->xmlRootNamespace;
        $this->xmlNamespaces = array_merge($object->xmlNamespaces, $this->xmlNamespaces);

        // Handler methods are not inherited

        if (! $this->accessorOrder && $object->accessorOrder) {
            $this->accessorOrder = $object->accessorOrder;
            $this->customOrder = $object->customOrder;
        }

        if (
            $this->discriminatorFieldName && $object->discriminatorFieldName &&
            $this->discriminatorFieldName !== $object->discriminatorFieldName
        ) {
            throw new LogicException(sprintf('The discriminator of class "%s" would overwrite the discriminator of the parent class "%s". Please define all possible sub-classes in the discriminator of %s.', $this->getName(), $object->discriminatorBaseClass, $object->discriminatorBaseClass));
        }

        $this->mergeDiscriminatorMap($object);
        $this->sortProperties();
    }

    public function registerNamespace(string $uri, ?string $prefix = null): void
    {
        if ($prefix === null) {
            $prefix = '';
        }

        $this->xmlNamespaces[$prefix] = $uri;
    }

    public function __wakeup(): void
    {
        $this->sortProperties();
    }

    public function getSubtype($data): string
    {
        if (is_array($data) && isset($data[$this->discriminatorFieldName])) {
            $typeValue = (string) $data[$this->discriminatorFieldName];
        } elseif (isset($data->{$this->discriminatorFieldName})) {
            $typeValue = (string) $data->{$this->discriminatorFieldName};
        } else {
            throw new LogicException("The discriminator field name '{$this->discriminatorFieldName}' for " . "base-class '{$this->getName()}' was not found in input data.");
        }

        if (! isset($this->discriminatorMap[$typeValue])) {
            throw new LogicException("The type value '$typeValue' does not exist in the discriminator map of class '{$this->getName()}'. Available types: " . implode(', ', array_keys($this->discriminatorMap)));
        }

        return $this->discriminatorMap[$typeValue];
    }

    private function sortProperties(): void
    {
        switch ($this->accessorOrder) {
            case self::ACCESSOR_ORDER_ALPHABETICAL:
                ksort($this->attributesMetadata);
                break;

            case self::ACCESSOR_ORDER_CUSTOM:
                $order = $this->customOrder;
                $sorting = array_flip(array_keys($this->attributesMetadata));
                uksort($this->attributesMetadata, static function ($a, $b) use ($order, $sorting): int {
                    $existsA = isset($order[$a]);
                    $existsB = isset($order[$b]);

                    if (! $existsA && ! $existsB) {
                        return $sorting[$a] - $sorting[$b];
                    }

                    if (! $existsA) {
                        return 1;
                    }

                    if (! $existsB) {
                        return -1;
                    }

                    return $order[$a] < $order[$b] ? -1 : 1;
                });
                break;
        }
    }

    private function mergeDiscriminatorMap(self $object): void
    {
        if (empty($object->discriminatorMap) || $this->getReflectionClass()->isAbstract()) {
            return;
        }

        if (false === $typeValue = array_search($this->getName(), $object->discriminatorMap, true)) {
            throw new LogicException('The sub-class "' . $this->getName() . '" is not listed in the discriminator of the base class "' . $this->discriminatorBaseClass);
        }

        $this->discriminatorValue = $typeValue;
        $this->discriminatorFieldName = $object->discriminatorFieldName;
        $this->discriminatorGroups = $object->discriminatorGroups;

        $discriminatorProperty = new StaticPropertyMetadata($this->getName(), $this->discriminatorFieldName, $typeValue);
        $discriminatorProperty->groups = $this->discriminatorGroups;
        $discriminatorProperty->serializedName = $this->discriminatorFieldName;

        $this->addAttributeMetadata($discriminatorProperty);
    }
}
