<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serialization;

use DateTimeImmutable;
use DateTime;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationVisitor;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Publisher;
use Kcs\Serializer\Tests\Fixtures\SimpleObject;
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
        self::assertSame(0, $visitor->getCompiledSerializationStats()->fallbackObjects);
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
