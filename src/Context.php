<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Metadata\Factory\MetadataFactoryInterface;
use Kcs\Serializer\Exclusion\DepthExclusionStrategy;
use Kcs\Serializer\Exclusion\DisjunctExclusionStrategy;
use Kcs\Serializer\Exclusion\ExclusionStrategyInterface;
use Kcs\Serializer\Exclusion\GroupsExclusionStrategy;
use Kcs\Serializer\Exclusion\VersionExclusionStrategy;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\MetadataStack;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Naming\SerializedNameAttributeStrategy;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
use Kcs\Serializer\Type\Type;
use LogicException;
use Stringable;

use function array_filter;
use function is_array;

/** @property int $direction */
abstract class Context
{
    public AttributesMap $attributes;
    public PropertyNamingStrategyInterface $namingStrategy;
    private string $format;
    public VisitorInterface $visitor;
    private GraphNavigator $navigator;
    private MetadataFactoryInterface $metadataFactory;
    private ExclusionStrategyInterface|null $exclusionStrategy = null;
    private bool $serializeNull = false;
    private bool $initialized = false;
    private MetadataStack $metadataStack;

    final public function __construct()
    {
        $this->attributes = new AttributesMap();
        $this->namingStrategy = new SerializedNameAttributeStrategy(new UnderscoreNamingStrategy());
    }

    public function __clone()
    {
        $this->attributes = clone $this->attributes;
    }

    /**
     * Creates a new Context
     *
     * @return static
     */
    public static function create(): self
    {
        return new static();
    }

    /** @param array<string, mixed> $attributes */
    public function createChildContext(array $attributes = []): self
    {
        if (! $this->initialized) {
            throw new LogicException('Cannot create a child context of an uninitialized context.');
        }

        $obj = static::create();

        $obj->attributes = clone $this->attributes;
        foreach ($attributes as $key => $value) {
            $obj->attributes->set($key, $value);
        }

        $obj->format = $this->format;
        $obj->visitor = $this->visitor;
        $obj->navigator = $this->navigator;
        $obj->metadataFactory = $this->metadataFactory;
        $obj->metadataStack = $this->metadataStack;

        $obj->addVersionExclusionStrategy();
        $obj->addGroupsExclusionStrategy();

        $obj->initialized = true;

        return $obj;
    }

    public function initialize(
        string $format,
        VisitorInterface $visitor,
        GraphNavigator $navigator,
        MetadataFactoryInterface $factory,
    ): void {
        if ($this->initialized) {
            throw new LogicException('This context was already initialized, and cannot be re-used.');
        }

        $this->initialized = true;
        $this->format = $format;
        $this->visitor = $visitor;
        $this->navigator = $navigator;
        $this->metadataFactory = $factory;
        $this->metadataStack = new MetadataStack();

        $this->addVersionExclusionStrategy();
        $this->addGroupsExclusionStrategy();
    }

    public function accept(mixed $data, Type|null $type = null): mixed
    {
        return $this->navigator->accept($data, $type, $this);
    }

    public function getMetadataFactory(): MetadataFactoryInterface
    {
        return $this->metadataFactory;
    }

    public function getNavigator(): GraphNavigator
    {
        return $this->navigator;
    }

    public function getExclusionStrategy(): ExclusionStrategyInterface|null
    {
        return $this->exclusionStrategy;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->assertMutable();
        $this->attributes->set($key, $value);

        return $this;
    }

    public function addExclusionStrategy(ExclusionStrategyInterface $strategy): self
    {
        $this->assertMutable();
        $this->_addExclusionStrategy($strategy);

        return $this;
    }

    public function setVersion(string|Stringable $version): self
    {
        $this->setAttribute('version', $version);

        return $this;
    }

    public function setGroups(mixed $groups): self
    {
        if (empty($groups)) {
            $groups = null;
        } elseif (! is_array($groups)) {
            $groups = (array) $groups;
        }

        $this->setAttribute('groups', $groups);

        return $this;
    }

    public function enableMaxDepthChecks(): self
    {
        $this->addExclusionStrategy(new DepthExclusionStrategy());

        return $this;
    }

    public function setSerializeNull(bool $bool): self
    {
        $this->serializeNull = $bool;

        return $this;
    }

    public function shouldSerializeNull(): bool
    {
        return $this->serializeNull;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getMetadataStack(): MetadataStack
    {
        return $this->metadataStack;
    }

    public function isPropertyExcluded(PropertyMetadata $metadata): bool
    {
        if ($this->exclusionStrategy === null) {
            return false;
        }

        return $this->exclusionStrategy->shouldSkipProperty($metadata, $this);
    }

    /**
     * Get the array of properties that should be serialized in an object.
     *
     * @return PropertyMetadata[]
     */
    public function getNonSkippedProperties(ClassMetadata $metadata): array
    {
        $this->assertInitialized();

        /** @var PropertyMetadata[] $properties */
        $properties = $metadata->getAttributesMetadata();

        return array_filter($properties, [$this, 'filterPropertyMetadata']);
    }

    abstract public function getDepth(): int;

    protected function filterPropertyMetadata(PropertyMetadata $propertyMetadata): bool
    {
        return ! $this->isPropertyExcluded($propertyMetadata);
    }

    private function assertMutable(): void
    {
        if (! $this->initialized) {
            return;
        }

        throw new LogicException('This context was already initialized and is immutable; you cannot modify it anymore.');
    }

    private function assertInitialized(): void
    {
        if ($this->initialized) {
            return;
        }

        throw new LogicException('This context is not initialized.');
    }

    /**
     * Set or add exclusion strategy.
     */
    private function _addExclusionStrategy(ExclusionStrategyInterface $strategy): void // phpcs:ignore
    {
        if ($this->exclusionStrategy === null) {
            $this->exclusionStrategy = $strategy;

            return;
        }

        if ($this->exclusionStrategy instanceof DisjunctExclusionStrategy) {
            $this->exclusionStrategy->addStrategy($strategy);

            return;
        }

        $this->exclusionStrategy = new DisjunctExclusionStrategy([
            $this->exclusionStrategy,
            $strategy,
        ]);
    }

    private function addVersionExclusionStrategy(): void
    {
        $version = $this->attributes->get('version');
        if ($version === null) {
            return;
        }

        $this->_addExclusionStrategy(new VersionExclusionStrategy($version));
    }

    private function addGroupsExclusionStrategy(): void
    {
        $groups = $this->attributes->get('groups');
        if ($groups === null) {
            return;
        }

        $this->_addExclusionStrategy(new GroupsExclusionStrategy($groups));
    }
}
