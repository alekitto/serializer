<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata;

use Kcs\Metadata\PropertyMetadata as BasePropertyMetadata;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Type\Type;

class PropertyMetadata extends BasePropertyMetadata
{
    public const ACCESS_TYPE_PROPERTY = 'property';
    public const ACCESS_TYPE_PUBLIC_METHOD = 'public_method';

    public const ON_EXCLUDE_NULL = 'null';
    public const ON_EXCLUDE_SKIP = 'skip';

    /**
     * @var string
     */
    public $sinceVersion;

    /**
     * @var string
     */
    public $untilVersion;

    /**
     * @var string[]
     */
    public $groups = [];

    /**
     * @var string[]
     */
    public $exclusionGroups = [];

    /**
     * @var string
     */
    public $onExclude = self::ON_EXCLUDE_NULL;

    /**
     * @var string
     */
    public $serializedName;

    /**
     * @var Type
     */
    public $type;

    /**
     * @var bool
     */
    public $xmlCollection = false;

    /**
     * @var bool
     */
    public $xmlCollectionInline = false;

    /**
     * @var string
     */
    public $xmlEntryName;

    /**
     * @var string
     */
    public $xmlEntryNamespace;

    /**
     * @var string
     */
    public $xmlKeyAttribute;

    /**
     * @var bool
     */
    public $xmlAttribute = false;

    /**
     * @var bool
     */
    public $xmlValue = false;

    /**
     * @var string
     */
    public $xmlNamespace;

    /**
     * @var bool
     */
    public $xmlKeyValuePairs = false;

    /**
     * @var bool
     */
    public $xmlElementCData = true;

    /**
     * @var string
     */
    public $getter;

    /**
     * @var string
     */
    public $setter;

    /**
     * @var bool
     */
    public $inline = false;

    /**
     * @var bool
     */
    public $readOnly = false;

    /**
     * @var bool
     */
    public $xmlAttributeMap = false;

    /**
     * @var int|null
     */
    public $maxDepth;

    /**
     * @var string
     */
    public $accessorType = self::ACCESS_TYPE_PUBLIC_METHOD;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $class, string $name)
    {
        parent::__construct($class, $name);

        $this->getReflection()->setAccessible(true);
    }

    /**
     * {@inheritdoc}
     */
    public function __wakeup()
    {
        parent::__wakeup();

        $this->getReflection()->setAccessible(true);
    }

    public function setAccessor(string $type, ?string $getter = null, ?string $setter = null): void
    {
        $this->accessorType = $type;
        $this->getter = $getter;
        $this->setter = $setter;
    }

    public function getValue($obj)
    {
        if (self::ACCESS_TYPE_PROPERTY === $this->accessorType) {
            $reflector = $this->getReflection();

            return $reflector->getValue($obj);
        }

        if (null === $this->getter) {
            $this->initializeGetterAccessor();
        }

        if ($this->getter instanceof \Closure) {
            return \call_user_func($this->getter->bindTo($obj));
        }

        return $obj->{$this->getter}();
    }

    public function setValue($obj, $value): void
    {
        if ($this->readOnly) {
            return;
        }

        if (self::ACCESS_TYPE_PROPERTY === $this->accessorType) {
            $reflector = $this->getReflection();
            $reflector->setValue($obj, $value);

            return;
        }

        if (null === $this->setter) {
            $this->initializeSetterAccessor();
        }

        if ($this->setter instanceof \Closure) {
            \call_user_func($this->setter->bindTo($obj), $value);

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
            'get'.\ucfirst($this->name),
            'is'.\ucfirst($this->name),
            'has'.\ucfirst($this->name),
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
        } catch (\ReflectionException $e) {
            // Property does not exist.
        }

        throw new RuntimeException(\sprintf('There is no public method named "%s" in class %s. Please specify which public method should be used for retrieving the value of the property %s.', \implode('" or "', $methods), $this->class, $this->name));
    }

    protected function initializeSetterAccessor(): void
    {
        if ($this->checkMethod($setter = 'set'.\ucfirst($this->name))) {
            $this->setter = $setter;

            return;
        }

        try {
            $reflector = $this->getReflection();
            if ($reflector->isPublic()) {
                $name = $this->name;
                $this->setter = function ($value) use ($name) {
                    $this->$name = $value;
                };

                return;
            }
        } catch (\ReflectionException $e) {
            // Property does not exist.
        }

        throw new RuntimeException(\sprintf('There is no public %s method in class %s. Please specify which public method should be used for setting the value of the property %s.', 'set'.\ucfirst($this->name), $this->class, $this->name));
    }

    private function checkMethod(string $name): bool
    {
        $class = $this->getReflection()->getDeclaringClass();

        return $class->hasMethod($name) && $class->getMethod($name)->isPublic();
    }
}
