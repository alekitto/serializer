<?php declare(strict_types=1);

use Kcs\Serializer\Context;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Metadata\Access\Type as AccessType;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\Publisher;
use Kcs\Serializer\Tests\Fixtures\SimpleObject;
use Kcs\Serializer\Type\Type;

require_once __DIR__ . '/bootstrap.php';

function experimentBenchmark(Closure $f, int $times = 1000): float
{
    $time = microtime(true);
    for ($i = 0; $i < $times; ++$i) {
        $f();
    }

    return (microtime(true) - $time) / $times;
}

function experimentAssertSameOutput(string $name, mixed $baseline, mixed $compiled): void
{
    if ($baseline === $compiled) {
        return;
    }

    throw new RuntimeException(sprintf(
        "Compiled output differs from baseline for \"%s\".\nBaseline sample: %s\nCompiled sample: %s",
        $name,
        var_export(is_array($baseline) ? array_slice($baseline, 0, 3) : $baseline, true),
        var_export(is_array($compiled) ? array_slice($compiled, 0, 3) : $compiled, true),
    ));
}

function experimentReport(string $name, Closure $baseline, Closure $compiled, int $times = 1000): void
{
    $baselineOutput = $baseline();
    $compiledOutput = $compiled();
    experimentAssertSameOutput($name, $baselineOutput, $compiledOutput);

    $baselineTime = experimentBenchmark($baseline, $times);
    $compiledTime = experimentBenchmark($compiled, $times);

    printf(
        "%s baseline %.6f compiled %.6f ratio %.2fx\n",
        $name,
        $baselineTime,
        $compiledTime,
        $compiledTime > 0 ? $baselineTime / $compiledTime : INF,
    );
}

final class ExperimentPropertyPlan
{
    public function __construct(
        public readonly PropertyMetadata $metadata,
        public readonly string $serializedName,
        public readonly string|null $nativeType,
        private readonly Closure|null $reader,
    ) {
    }

    public function read(object $object): mixed
    {
        if ($this->reader !== null) {
            return ($this->reader)($object);
        }

        return $this->metadata->getValue($object);
    }
}

final class ExperimentClassPlan
{
    /** @param ExperimentPropertyPlan[] $properties */
    public function __construct(
        public readonly array $properties,
        public readonly bool $nativeOnly,
    ) {
    }
}

final class ExperimentPlanFactory
{
    /** @var array<int, array<int, ExperimentClassPlan>> */
    private array $plans = [];

    public function getPlan(ClassMetadata $metadata, Context $context): ExperimentClassPlan
    {
        $metadataId = spl_object_id($metadata);
        $namingId = spl_object_id($context->namingStrategy);
        if (isset($this->plans[$metadataId][$namingId])) {
            return $this->plans[$metadataId][$namingId];
        }

        $properties = [];
        $nativeOnly = true;
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);

            $nativeType = $this->getNativeType($propertyMetadata);
            if ($nativeType === null || $propertyMetadata->inline) {
                $nativeOnly = false;
            }

            $properties[] = new ExperimentPropertyPlan(
                $propertyMetadata,
                $context->namingStrategy->translateName($propertyMetadata),
                $nativeType,
                $this->createReader($propertyMetadata),
            );
        }

        return $this->plans[$metadataId][$namingId] = new ExperimentClassPlan($properties, $nativeOnly && $properties !== []);
    }

    private function createReader(PropertyMetadata $metadata): Closure|null
    {
        if ($metadata->accessorType !== AccessType::Property) {
            return null;
        }

        try {
            $reflection = $metadata->getReflection();
        } catch (ReflectionException) {
            return null;
        }

        if ($reflection->hasType()) {
            return null;
        }

        $property = $metadata->name;
        $reader = Closure::bind(static fn (object $object): mixed => $object->{$property}, null, $reflection->getDeclaringClass()->name);
        assert($reader !== null);

        return $reader;
    }

    private function getNativeType(PropertyMetadata $metadata): string|null
    {
        $type = $metadata->type;
        if ($type === null || $type->countParams() !== 0) {
            return null;
        }

        return match ($type->name) {
            'string' => 'string',
            'integer', 'int' => 'int',
            'boolean', 'bool' => 'bool',
            'double', 'float' => 'float',
            default => null,
        };
    }
}

final class ExperimentCompiledVisitor extends GenericSerializationVisitor
{
    private GraphNavigator $experimentNavigator;
    private ExperimentPlanFactory $planFactory;
    private int $compiledObjects = 0;
    private int $fallbackObjects = 0;

    /** @var array<int, true> */
    private array $unsupportedPlans = [];

    public function setNavigator(GraphNavigator|null $navigator = null): void
    {
        parent::setNavigator($navigator);

        if ($navigator !== null) {
            $this->experimentNavigator = $navigator;
        }

        $this->planFactory ??= new ExperimentPlanFactory();
    }

    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, \Kcs\Serializer\Construction\ObjectConstructorInterface|null $objectConstructor = null): mixed
    {
        if ($context->getExclusionStrategy() !== null) {
            ++$this->fallbackObjects;

            return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
        }

        $plan = $this->planFactory->getPlan($metadata, $context);
        if (isset($this->unsupportedPlans[spl_object_id($plan)])) {
            ++$this->fallbackObjects;

            return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
        }

        $result = $this->serializeObjectFromPlan($plan, $data, $context);

        if ($result !== null) {
            ++$this->compiledObjects;
            $this->setData($result);

            return $result;
        }

        $this->unsupportedPlans[spl_object_id($plan)] = true;
        ++$this->fallbackObjects;

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    public function resetStats(): void
    {
        $this->compiledObjects = 0;
        $this->fallbackObjects = 0;
    }

    /** @return array{compiled: int, fallback: int} */
    public function getStats(): array
    {
        return [
            'compiled' => $this->compiledObjects,
            'fallback' => $this->fallbackObjects,
        ];
    }

    /** @return array<string, mixed>|null */
    private function serializeObjectFromPlan(ExperimentClassPlan $plan, object $data, Context $context): array|null
    {
        $result = [];
        foreach ($plan->properties as $property) {
            $supported = false;
            $serialized = $this->serializeProperty($property, $data, $context, $supported);
            if (! $supported) {
                return null;
            }

            if ($serialized === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            $result[$property->serializedName] = $serialized;
        }

        return $result;
    }

    private function serializeProperty(ExperimentPropertyPlan $property, object $data, Context $context, bool &$supported): mixed
    {
        $supported = true;
        $value = $property->read($data);
        if ($value === null) {
            return null;
        }

        if ($property->nativeType !== null) {
            return match ($property->nativeType) {
                'string' => (string) $value,
                'int' => (int) $value,
                'bool' => (bool) $value,
                'float' => (float) $value,
                default => $value,
            };
        }

        $type = $property->metadata->type;
        if ($type === null) {
            $supported = false;

            return null;
        }

        if (is_object($value) && $type->countParams() === 0 && class_exists($type->name)) {
            return $this->serializeTypedObject($type, $value, $context, $supported);
        }

        if (is_array($value) && $type->name === 'array' && $type->countParams() > 0 && $type->countParams() <= 2) {
            return $this->serializeTypedArray($type, $value, $context, $supported);
        }

        $supported = false;

        return null;
    }

    /** @return array<string, mixed>|null */
    private function serializeTypedObject(Type $type, object $value, Context $context, bool &$supported): array|null
    {
        $supported = true;
        $metadata = $context->getMetadataFactory()->getMetadataFor($type->name);
        assert($metadata instanceof ClassMetadata);

        $result = $this->serializeObjectFromPlan($this->planFactory->getPlan($metadata, $context), $value, $context);
        $supported = $result !== null;

        return $result;
    }

    /** @param mixed[] $value */
    private function serializeTypedArray(Type $type, array $value, Context $context, bool &$supported): array|null
    {
        $supported = true;
        $elementType = $this->getElementType($type);
        if ($elementType === null) {
            $supported = false;

            return null;
        }

        $onlyValues = $type->hasParam(0) && ! $type->hasParam(1);
        $result = [];
        foreach ($value as $key => $item) {
            $itemSupported = false;
            $serialized = $this->serializeArrayItem($elementType, $item, $context, $itemSupported);
            if (! $itemSupported) {
                $supported = false;

                return null;
            }

            if ($serialized === null && ! $context->shouldSerializeNull()) {
                continue;
            }

            if ($onlyValues) {
                $result[] = $serialized;
            } else {
                $result[$key] = $serialized;
            }
        }

        return $result;
    }

    private function serializeArrayItem(Type $type, mixed $item, Context $context, bool &$supported): mixed
    {
        $supported = true;
        if ($item === null) {
            return null;
        }

        if ($type->countParams() === 0) {
            $nativeType = match ($type->name) {
                'string' => 'string',
                'integer', 'int' => 'int',
                'boolean', 'bool' => 'bool',
                'double', 'float' => 'float',
                default => null,
            };
            if ($nativeType !== null) {
                return match ($nativeType) {
                    'string' => (string) $item,
                    'int' => (int) $item,
                    'bool' => (bool) $item,
                    'float' => (float) $item,
                    default => $item,
                };
            }

            if (is_object($item) && class_exists($type->name)) {
                return $this->serializeTypedObject($type, $item, $context, $supported);
            }
        }

        $supported = false;

        return null;
    }
}

final class ExperimentChildDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}

final class ExperimentParentDto
{
    public function __construct(
        public string $id,
        public ExperimentChildDto $child,
    ) {
    }
}

final class ExperimentListDto
{
    /** @param ExperimentChildDto[] $children */
    public function __construct(
        public string $id,
        public array $children,
    ) {
    }
}

final class ExperimentUnsupportedDto
{
    /** @param list<string> $tags */
    public function __construct(
        public string $id,
        public DateTimeImmutable $createdAt,
        public array $tags,
    ) {
    }
}

$simpleObjects = [];
for ($i = 0; $i < 500; ++$i) {
    $simpleObjects[] = new SimpleObject('foo', 'bar');
}

$nestedObjects = [];
for ($i = 0; $i < 500; ++$i) {
    $nestedObjects[] = new ExperimentParentDto('p' . $i, new ExperimentChildDto('c' . $i, $i));
}

$listObjects = [];
for ($i = 0; $i < 200; ++$i) {
    $children = [];
    for ($j = 0; $j < 5; ++$j) {
        $children[] = new ExperimentChildDto('c' . $j, $j);
    }

    $listObjects[] = new ExperimentListDto('p' . $i, $children);
}

$fallbackObjects = [];
for ($i = 0; $i < 200; ++$i) {
    $fallbackObjects[] = new ExperimentUnsupportedDto('p' . $i, new DateTimeImmutable(), ['foo', 'bar']);
}

$blogPosts = [];
for ($i = 0; $i < 50; ++$i) {
    $post = new BlogPost('Title ' . $i, new Author('Author ' . $i), new DateTime(), new Publisher('Publisher ' . $i));
    for ($j = 0; $j < 5; ++$j) {
        $post->addComment(new Comment(new Author('Comment Author ' . $j), 'Comment ' . $j));
    }

    $blogPosts[] = $post;
}

$baseline = SerializerBuilder::create()->build();
$compiledVisitor = new ExperimentCompiledVisitor();
$compiled = SerializerBuilder::create()
    ->setSerializationVisitor('array', $compiledVisitor)
    ->build();

foreach ([
    'simple_object' => [$simpleObjects, 1000, null],
    'nested_object' => [$nestedObjects, 1000, null],
    'list_object' => [$listObjects, 1000, null],
    'fallback_object' => [$fallbackObjects, 1000, null],
    'fallback_blog_post' => [$blogPosts, 200, null],
    'groups_fallback' => [$simpleObjects, 1000, SerializationContext::create()->setGroups(['Default'])],
] as $name => [$dataset, $times, $context]) {
    $baselineClosure = static fn () => $baseline->serialize($dataset, 'array', $context !== null ? clone $context : null);
    $compiledClosure = static fn () => $compiled->serialize($dataset, 'array', $context !== null ? clone $context : null);

    $compiledVisitor->resetStats();
    experimentReport($name, $baselineClosure, $compiledClosure, $times);
    $stats = $compiledVisitor->getStats();
    printf("  compiled_objects %d fallback_objects %d\n", $stats['compiled'], $stats['fallback']);
}
