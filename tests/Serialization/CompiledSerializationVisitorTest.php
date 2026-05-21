<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serialization;

use DateTimeImmutable;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Context;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Direction;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\IterableSerializationVisitorInterface;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationVisitor;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Publisher;
use Kcs\Serializer\Tests\Fixtures\SimpleObject;
use Kcs\Serializer\Type\Type as SerializerType;
use PHPUnit\Framework\TestCase;

final class CompiledSerializationVisitorTest extends TestCase
{
    public function testCompiledVisitorSerializesSimpleDtoLikeObjectsLikeBaseline(): void
    {
        $data = [];
        for ($i = 0; $i < 5; ++$i) {
            $data[] = new SimpleObject('foo', 'bar');
        }

        self::assertSame($this->baseline()->serialize($data, 'array'), $this->compiled()->serialize($data, 'array'));
    }

    public function testCompiledVisitorSerializesNestedDtoLikeObjectsLikeBaseline(): void
    {
        $data = [];
        for ($i = 0; $i < 5; ++$i) {
            $data[] = new CompiledParentDto('p' . $i, new CompiledChildDto('c' . $i, $i));
        }

        self::assertSame($this->baseline()->serialize($data, 'array'), $this->compiled()->serialize($data, 'array'));
    }

    public function testCompiledVisitorSerializesArrayOfDtoLikeObjectsLikeBaseline(): void
    {
        $children = [];
        for ($i = 0; $i < 5; ++$i) {
            $children[] = new CompiledChildDto('c' . $i, $i);
        }

        $data = [new CompiledListDto('p', $children)];

        self::assertSame($this->baseline()->serialize($data, 'array'), $this->compiled()->serialize($data, 'array'));
    }

    public function testCompiledVisitorDelegatesUnsupportedPropertiesWithoutChangingOutput(): void
    {
        $data = [new CompiledUnsupportedDto('p', new DateTimeImmutable(), ['foo', 'bar'])];
        $visitor = new CompiledSerializationVisitor();
        $compiled = SerializerBuilder::create()
            ->setSerializationVisitor('array', $visitor)
            ->build();

        self::assertSame($this->baseline()->serialize($data, 'array'), $compiled->serialize($data, 'array'));
        self::assertSame(0, $visitor->getCompiledSerializationStats()->fallbackObjects);
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->delegatedProperties);
    }

    public function testCompiledVisitorSerializesBlogPostLikeBaselineWithDelegatedProperties(): void
    {
        $post = new BlogPost('Title', new Author('Author'), new DateTime(), new Publisher('Publisher'));
        $post->addComment(new Comment(new Author('Comment Author'), 'Comment'));

        $visitor = new CompiledSerializationVisitor();
        $compiled = SerializerBuilder::create()
            ->setSerializationVisitor('array', $visitor)
            ->build();

        self::assertSame($this->baseline()->serialize([$post], 'array'), $compiled->serialize([$post], 'array'));
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->compiledObjects);
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->delegatedProperties);
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->iterableFastPathProperties);
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->skippedNullProperties);
        self::assertSame(0, $visitor->getCompiledSerializationStats()->fallbackObjects);
    }

    public function testCompiledVisitorKeepsCustomHandlersForUnknownTypes(): void
    {
        $data = [new CompiledCustomHandledDto('value')];

        $compiled = SerializerBuilder::create()
            ->configureHandlers(static function ($registry): void {
                $registry->registerHandler(
                    Direction::Serialization,
                    'custom_handler_type',
                    static fn ($visitor, $data, $type, $context) => $visitor->visitString('handled:' . $data, $type, $context),
                );
            })
            ->enableCompiledSerialization()
            ->build();

        self::assertSame([['value' => 'handled:value']], $compiled->serialize($data, 'array'));
    }

    public function testArrayCollectionHandlerUsesIterableVisitorFastPath(): void
    {
        $visitor = new CompiledIterableSpyVisitor();
        $handler = new ArrayCollectionHandler();

        self::assertSame(
            'fast-path',
            $handler->serializeCollection(
                $visitor,
                new ArrayCollection([new CompiledChildDto('c', 1)]),
                new SerializerType(ArrayCollection::class, [SerializerType::from(CompiledChildDto::class)]),
                SerializationContext::create(),
            ),
        );
        self::assertTrue($visitor->visitedIterable);
    }

    public function testCompiledJsonVisitorKeepsEmptyCollectionMapsAsObjects(): void
    {
        $data = [new CompiledCollectionMapDto(new ArrayCollection())];

        self::assertSame(
            $this->baseline()->serialize($data, 'json'),
            SerializerBuilder::create()->enableCompiledSerialization()->build()->serialize($data, 'json'),
        );
    }

    public function testCompiledSerializationDescriptorsCanBeStoredAndReloadedFromPhpFiles(): void
    {
        $directory = sys_get_temp_dir() . '/serializer_compiled_descriptors_' . bin2hex(random_bytes(4));
        $data = [new CompiledParentDto('p', new CompiledChildDto('c', 1))];

        $first = SerializerBuilder::create()
            ->enableCompiledSerialization()
            ->setCompiledSerializationCacheDirectory($directory)
            ->build();
        $baseline = $first->serialize($data, 'array');

        self::assertNotFalse(glob($directory . '/*.php'));
        self::assertNotSame([], glob($directory . '/*.php'));

        $second = SerializerBuilder::create()
            ->enableCompiledSerialization()
            ->setCompiledSerializationCacheDirectory($directory)
            ->build();

        self::assertSame($baseline, $second->serialize($data, 'array'));

        foreach (glob($directory . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($directory);
    }

    public function testCompiledSerializationDescriptorCacheValidatesTranslatedNames(): void
    {
        $directory = sys_get_temp_dir() . '/serializer_compiled_descriptors_' . bin2hex(random_bytes(4));
        $data = [new CompiledChildDto('c', 1)];

        $first = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new CompiledPrefixNamingStrategy('first_'))
            ->enableCompiledSerialization()
            ->setCompiledSerializationCacheDirectory($directory)
            ->build();
        self::assertSame([['first_name' => 'c', 'first_age' => 1]], $first->serialize($data, 'array'));

        $second = SerializerBuilder::create()
            ->setPropertyNamingStrategy(new CompiledPrefixNamingStrategy('second_'))
            ->enableCompiledSerialization()
            ->setCompiledSerializationCacheDirectory($directory)
            ->build();
        self::assertSame([['second_name' => 'c', 'second_age' => 1]], $second->serialize($data, 'array'));

        foreach (glob($directory . '/*.php') ?: [] as $file) {
            unlink($file);
        }

        rmdir($directory);
    }

    public function testCompiledVisitorFallsBackWhenGroupsAreActive(): void
    {
        $data = [new SimpleObject('foo', 'bar')];
        $context = SerializationContext::create();
        $context->setGroups(['Default']);
        $visitor = new CompiledSerializationVisitor();
        $compiled = SerializerBuilder::create()
            ->setSerializationVisitor('array', $visitor)
            ->build();

        self::assertSame(
            $this->baseline()->serialize($data, 'array', clone $context),
            $compiled->serialize($data, 'array', clone $context),
        );
        self::assertGreaterThan(0, $visitor->getCompiledSerializationStats()->fallbackObjects);
    }

    public function testBuilderCanEnableCompiledSerializationForDefaultVisitors(): void
    {
        $data = [new SimpleObject('foo', 'bar')];

        self::assertSame(
            $this->baseline()->serialize($data, 'array'),
            SerializerBuilder::create()->enableCompiledSerialization()->build()->serialize($data, 'array'),
        );
        self::assertSame(
            $this->baseline()->serialize($data, 'json'),
            SerializerBuilder::create()->enableCompiledSerialization()->build()->serialize($data, 'json'),
        );
        self::assertSame(
            $this->baseline()->serialize($data, 'yml'),
            SerializerBuilder::create()->enableCompiledSerialization()->build()->serialize($data, 'yml'),
        );
    }

    private function baseline(): \Kcs\Serializer\SerializerInterface
    {
        return SerializerBuilder::create()->build();
    }

    private function compiled(): \Kcs\Serializer\SerializerInterface
    {
        return SerializerBuilder::create()
            ->setSerializationVisitor('array', new CompiledSerializationVisitor())
            ->build();
    }
}

final class CompiledChildDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}

final class CompiledParentDto
{
    public function __construct(
        public string $id,
        public CompiledChildDto $child,
    ) {
    }
}

final class CompiledListDto
{
    /** @param CompiledChildDto[] $children */
    public function __construct(
        public string $id,
        #[Type('array<' . CompiledChildDto::class . '>')]
        public array $children,
    ) {
    }
}

final class CompiledUnsupportedDto
{
    /** @param list<string> $tags */
    public function __construct(
        public string $id,
        public DateTimeImmutable $createdAt,
        public array $tags,
    ) {
    }
}

final class CompiledCollectionMapDto
{
    public function __construct(
        #[Type('ArrayCollection<string, string>')]
        public ArrayCollection $items,
    ) {
    }
}

final class CompiledCustomHandledDto
{
    public function __construct(
        #[Type('custom_handler_type')]
        public string $value,
    ) {
    }
}

final class CompiledIterableSpyVisitor extends GenericSerializationVisitor implements IterableSerializationVisitorInterface
{
    public bool $visitedIterable = false;

    /** @inheritDoc */
    public function visitIterable(iterable $data, SerializerType $type, Context $context): mixed
    {
        $this->visitedIterable = true;

        return 'fast-path';
    }
}

final class CompiledPrefixNamingStrategy implements PropertyNamingStrategyInterface
{
    public function __construct(private readonly string $prefix)
    {
    }

    public function translateName(PropertyMetadata $property): string
    {
        return $this->prefix . $property->name;
    }
}
