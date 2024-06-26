<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\ClassMetadata as BaseClassMetadata;
use Kcs\Metadata\MetadataInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use LogicException;

use function array_flip;
use function array_keys;
use function array_merge;
use function array_search;
use function assert;
use function implode;
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
    public Exclusion\Policy $exclusionPolicy = Exclusion\Policy::None;
    public Access\Type $defaultAccessType = Access\Type::PublicMethod;
    public bool $immutable = false;
    public string|null $xmlRootName = null;
    public string|null $xmlRootNamespace = null;
    public string|null $xmlEncoding = null;
    public string $csvDelimiter = ',';
    public string $csvEnclosure = '"';
    public string $csvEscapeChar = '\\';
    public bool $csvEscapeFormulas = false;
    public string $csvKeySeparator = '.';
    public bool $csvNoHeaders = false;
    public bool $csvOutputBom = false;
    public Access\Order $accessorOrder = Access\Order::Undefined;
    public bool $discriminatorDisabled = false;
    public string|null $discriminatorBaseClass = null;
    public string|null $discriminatorFieldName = null;
    public string|null $discriminatorValue = null;

    /** @var string[] */
    public array $xmlNamespaces = [];

    /** @var string[]|null */
    public array|null $customOrder = null;

    /** @var array<string, class-string> */
    public array $discriminatorMap = [];

    /** @var string[] */
    public array $discriminatorGroups = [];

    /**
     * @param array<string, class-string> $map
     * @param string[] $groups
     */
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
     * @param string[] $customOrder
     *
     * @throws InvalidArgumentException When the accessor order is not valid or when the custom order is not valid.
     */
    public function setAccessorOrder(Access\Order $order, array $customOrder = []): void
    {
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

    public function merge(MetadataInterface $metadata): void
    {
        if (! $metadata instanceof self) {
            throw new InvalidArgumentException('$object must be an instance of ClassMetadata.');
        }

        parent::merge($metadata);

        $this->xmlRootName = $this->xmlRootName ?: $metadata->xmlRootName;
        $this->xmlRootNamespace = $this->xmlRootNamespace ?: $metadata->xmlRootNamespace;
        $this->xmlNamespaces = array_merge($metadata->xmlNamespaces, $this->xmlNamespaces);

        // Handler methods are not inherited
        if ($this->accessorOrder === Access\Order::Undefined && $metadata->accessorOrder !== Access\Order::Undefined) {
            $this->accessorOrder = $metadata->accessorOrder;
            $this->customOrder = $metadata->customOrder;
        }

        if (
            $this->discriminatorFieldName && $metadata->discriminatorFieldName &&
            $this->discriminatorFieldName !== $metadata->discriminatorFieldName
        ) {
            throw new LogicException(sprintf('The discriminator of class "%s" would overwrite the discriminator of the parent class "%s". Please define all possible sub-classes in the discriminator of %s.', $this->getName(), $metadata->discriminatorBaseClass, $metadata->discriminatorBaseClass));
        }

        $this->mergeDiscriminatorMap($metadata);
        $this->sortProperties();
    }

    public function registerNamespace(string $uri, string|null $prefix = null): void
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

    /**
     * @param array<string, string>|object $data
     *
     * @phpstan-return class-string
     */
    public function getSubtype(array|object $data): string
    {
        if (is_array($data) && isset($data[$this->discriminatorFieldName])) {
            $typeValue = (string) $data[$this->discriminatorFieldName];
        } elseif (isset($data->{$this->discriminatorFieldName})) {
            $typeValue = (string) $data->{$this->discriminatorFieldName};
        } else {
            throw new LogicException(sprintf("The discriminator field name '%s' for base-class '%s' was not found in input data.", $this->discriminatorFieldName, $this->getName()));
        }

        if (! isset($this->discriminatorMap[$typeValue])) {
            throw new LogicException(sprintf("The type value '%s' does not exist in the discriminator map of class '%s'. Available types: %s", $typeValue, $this->getName(), implode(', ', array_keys($this->discriminatorMap))));
        }

        return $this->discriminatorMap[$typeValue];
    }

    private function sortProperties(): void
    {
        switch ($this->accessorOrder) {
            case Access\Order::Undefined:
                // no-op
                break;

            case Access\Order::Alphabetical:
                ksort($this->attributesMetadata);
                break;

            case Access\Order::Custom:
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

        $typeValue = array_search($this->getName(), $object->discriminatorMap, true);
        if ($typeValue === false) {
            throw new LogicException('The sub-class "' . $this->getName() . '" is not listed in the discriminator of the base class "' . $this->discriminatorBaseClass);
        }

        $this->discriminatorValue = $typeValue;
        $this->discriminatorFieldName = $object->discriminatorFieldName;
        $this->discriminatorGroups = $object->discriminatorGroups;

        assert($this->discriminatorFieldName !== null);

        $discriminatorProperty = new StaticPropertyMetadata($this->getName(), $this->discriminatorFieldName, $typeValue);
        $discriminatorProperty->groups = $this->discriminatorGroups;
        $discriminatorProperty->serializedName = $this->discriminatorFieldName;

        $this->addAttributeMetadata($discriminatorProperty);
    }
}
