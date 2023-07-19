<?php

declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Closure;
use Error;
use Kcs\Metadata\PropertyMetadata as BasePropertyMetadata;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Type\Type;
use ReflectionException;

use function assert;
use function implode;
use function method_exists;
use function sprintf;
use function ucfirst;

class PropertyMetadata extends BasePropertyMetadata
{
    public const ACCESS_TYPE_PROPERTY = 'property';
    public const ACCESS_TYPE_PUBLIC_METHOD = 'public_method';

    public const ON_EXCLUDE_NULL = 'null';
    public const ON_EXCLUDE_SKIP = 'skip';

    public string|null $sinceVersion = null;
    public string|null $untilVersion = null;

    /** @var string[] */
    public array $groups = [];

    /** @var string[] */
    public array $exclusionGroups = [];

    public string $onExclude = self::ON_EXCLUDE_NULL;
    public string|null $serializedName = null;
    public Type|null $type = null;
    public bool $xmlCollection = false;
    public bool $xmlCollectionInline = false;
    public string|null $xmlEntryName = null;
    public string|null $xmlEntryNamespace = null;
    public string|null $xmlKeyAttribute = null;
    public bool $xmlAttribute = false;
    public bool $xmlValue = false;
    public string|null $xmlNamespace = null;
    public bool $xmlKeyValuePairs = false;
    public bool $xmlElementCData = true;

    /** @var string|callable|null */
    public $getter;

    /** @var string|callable|null */
    public $setter;

    public bool $inline = false;
    public bool $immutable = false;
    public bool $xmlAttributeMap = false;
    public int|null $maxDepth = null;
    public string $accessorType = self::ACCESS_TYPE_PUBLIC_METHOD;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $class, string $name)
    {
        parent::__construct($class, $name);

        $this->getReflection()->setAccessible(true);
    }

    public function __wakeup(): void
    {
        parent::__wakeup();

        $this->getReflection()->setAccessible(true);
    }

    public function setAccessor(string $type, string|null $getter = null, string|null $setter = null): void
    {
        $this->accessorType = $type;
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function getValue(object $obj): mixed
    {
        if ($this->accessorType === self::ACCESS_TYPE_PROPERTY) {
            $reflector = $this->getReflection();
            if ($reflector->hasType() && ! $reflector->isInitialized($obj)) {
                // There is no way to check if a property has been unset or if it is uninitialized.
                // When trying to access an uninitialized property, __get method is triggered.

                // If __get method is not present, no fallback is possible
                // Otherwise we need to catch an Error in case we are trying to access an uninitialized but set property.
                if (! method_exists($obj, '__get')) {
                    return null;
                }

                try {
                    return $reflector->getValue($obj);
                } catch (Error) {
                    return null;
                }
            }

            return $reflector->getValue($obj);
        }

        if ($this->getter === null) {
            $this->initializeGetterAccessor();
        }

        if ($this->getter instanceof Closure) {
            $bound = $this->getter->bindTo($obj);
            assert($bound !== null);

            return $bound();
        }

        return $obj->{$this->getter}();
    }

    public function setValue(object $obj, mixed $value): void
    {
        if ($this->immutable) {
            return;
        }

        if ($this->accessorType === self::ACCESS_TYPE_PROPERTY) {
            $reflector = $this->getReflection();
            $reflector->setValue($obj, $value);

            return;
        }

        if ($this->setter === null) {
            $this->initializeSetterAccessor();
        }

        if ($this->setter instanceof Closure) {
            $bound = $this->setter->bindTo($obj);
            assert($bound !== null);
            $bound($value);

            return;
        }

        $obj->{$this->setter}($value);
    }

    public function setType(string $type): void
    {
        $this->type = Type::parse($type);
    }

    protected function initializeGetterAccessor(): void
    {
        $methods = [
            'get' . ucfirst($this->name),
            'is' . ucfirst($this->name),
            'has' . ucfirst($this->name),
            $this->name,
        ];

        foreach ($methods as $method) {
            if ($this->checkMethod($method)) {
                $this->getter = $method;

                return;
            }
        }

        try {
            $reflector = $this->getReflection();
            if ($reflector->isPublic()) {
                $name = $this->name;
                $this->getter = function () use ($name) {
                    return $this->$name;
                };

                return;
            }
        } catch (ReflectionException) {
            // Property does not exist.
            // @ignoreException
        }

        throw new RuntimeException(sprintf('There is no public method named "%s" in class %s. Please specify which public method should be used for retrieving the value of the property %s.', implode('" or "', $methods), $this->class, $this->name));
    }

    protected function initializeSetterAccessor(): void
    {
        $setter = 'set' . ucfirst($this->name);
        if ($this->checkMethod($setter)) {
            $this->setter = $setter;

            return;
        }

        try {
            $reflector = $this->getReflection();
            if ($reflector->isPublic()) {
                $name = $this->name;
                $this->setter = function ($value) use ($name): void {
                    $this->$name = $value;
                };

                return;
            }
        } catch (ReflectionException) {
            // Property does not exist.
            // @ignoreException
        }

        throw new RuntimeException(sprintf('There is no public %s method in class %s. Please specify which public method should be used for setting the value of the property %s.', 'set' . ucfirst($this->name), $this->class, $this->name));
    }

    private function checkMethod(string $name): bool
    {
        $class = $this->getReflection()->getDeclaringClass();

        return $class->hasMethod($name) && $class->getMethod($name)->isPublic();
    }
}
