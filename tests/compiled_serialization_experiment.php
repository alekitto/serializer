<?php declare(strict_types=1);

use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationVisitor;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\Publisher;
use Kcs\Serializer\Tests\Fixtures\SimpleObject;

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

function experimentReport(string $name, Closure $baseline, Closure $compiled, CompiledSerializationVisitor $compiledVisitor, int $times = 1000): void
{
    $baselineOutput = $baseline();
    $compiledOutput = $compiled();
    experimentAssertSameOutput($name, $baselineOutput, $compiledOutput);

    $compiledVisitor->resetCompiledSerializationStats();
    $baselineTime = experimentBenchmark($baseline, $times);
    $compiledTime = experimentBenchmark($compiled, $times);
    $stats = $compiledVisitor->getCompiledSerializationStats();

    printf(
        "%s baseline %.6f compiled %.6f ratio %.2fx\n",
        $name,
        $baselineTime,
        $compiledTime,
        $compiledTime > 0 ? $baselineTime / $compiledTime : INF,
    );
    printf(
        "  compiled_objects %d fallback_objects %d delegated_properties %d iterable_fast_path_properties %d skipped_null_properties %d\n",
        $stats->compiledObjects,
        $stats->fallbackObjects,
        $stats->delegatedProperties,
        $stats->iterableFastPathProperties,
        $stats->skippedNullProperties,
    );
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
        #[Type('array<' . ExperimentChildDto::class . '>')]
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
$compiledVisitor = new CompiledSerializationVisitor();
$compiled = SerializerBuilder::create()
    ->setSerializationVisitor('array', $compiledVisitor)
    ->build();
$groupsContext = SerializationContext::create();
$groupsContext->setGroups(['Default']);

foreach ([
    'simple_object' => [$simpleObjects, 1000, null],
    'nested_object' => [$nestedObjects, 1000, null],
    'list_object' => [$listObjects, 1000, null],
    'fallback_object' => [$fallbackObjects, 1000, null],
    'fallback_blog_post' => [$blogPosts, 200, null],
    'groups_fallback' => [$simpleObjects, 1000, $groupsContext],
] as $name => [$dataset, $times, $context]) {
    $baselineClosure = static fn () => $baseline->serialize($dataset, 'array', $context !== null ? clone $context : null);
    $compiledClosure = static fn () => $compiled->serialize($dataset, 'array', $context !== null ? clone $context : null);

    experimentReport($name, $baselineClosure, $compiledClosure, $compiledVisitor, $times);
}
