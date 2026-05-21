<?php declare(strict_types=1);
require_once 'bootstrap.php';

$input = new \Symfony\Component\Console\Input\ArgvInput();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

function benchmark(\Closure $f, $format)
{
    $times = 20;
    $time = \microtime(true);
    for ($i = 0; $i < $times; ++$i) {
        $f($format);
    }

    return (\microtime(true) - $time) / $times;
}

function createCollection()
{
    $collection = [];
    for ($i = 0; $i < 50; ++$i) {
        $collection[] = createObject();
    }

    return $collection;
}

function createObject()
{
    $post = new \Kcs\Serializer\Tests\Fixtures\BlogPost(
        'FooooooooooooooooooooooBAR',
        new \Kcs\Serializer\Tests\Fixtures\Author('Foo'),
        new \DateTime(),
        new \Kcs\Serializer\Tests\Fixtures\Publisher('Bar')
    );
    for ($i = 0; $i < 10; ++$i) {
        $post->addComment(new \Kcs\Serializer\Tests\Fixtures\Comment(new \Kcs\Serializer\Tests\Fixtures\Author('foo'), 'foobar'));
    }

    return $post;
}

$serializer = \Kcs\Serializer\SerializerBuilder::create()
    ->setEventDispatcher(new \Symfony\Component\EventDispatcher\EventDispatcher())
    ->build();
$compiledSerializer = \Kcs\Serializer\SerializerBuilder::create()
    ->setEventDispatcher(new \Symfony\Component\EventDispatcher\EventDispatcher())
    ->enableCompiledSerialization()
    ->build();
$collection = createCollection();
$metrics = [];
$f = static function ($format) use ($serializer, $collection) {
    $serializer->serialize($collection, $format);
};
$cf = static function ($format) use ($compiledSerializer, $collection) {
    $compiledSerializer->serialize($collection, $format);
};

// Load all necessary classes into memory.
$f('array');
$cf('array');

$table = new \Symfony\Component\Console\Helper\Table($output);
$table->setHeaders(['Format', 'Direction', 'Variant', 'Time']);

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, 13);
$progressBar->start();

foreach (['array', 'json', 'yml', 'xml', 'csv'] as $format) {
    $table->addRow([$format, 'serialize', 'baseline', benchmark($f, $format)]);
    $progressBar->advance();
}

foreach (['array', 'json', 'yml', 'xml'] as $format) {
    $table->addRow([$format, 'serialize', 'compiled', benchmark($cf, $format)]);
    $progressBar->advance();
}

$serialized = [
    'array' => $serializer->serialize($collection, 'array'),
    'json' => $serializer->serialize($collection, 'json'),
    'yml' => $serializer->serialize($collection, 'yml'),
    'xml' => $serializer->serialize($collection, 'xml'),
    'csv' => $serializer->serialize($collection, 'csv'),
];

$type = new \Kcs\Serializer\Type\Type('array', [
    \Kcs\Serializer\Type\Type::from(\Kcs\Serializer\Tests\Fixtures\BlogPost::class),
]);
$d = static function ($format) use ($serializer, $serialized, $type) {
    $serializer->deserialize($serialized[$format], $type, $format);
};

foreach (['array', 'json', 'yml', 'xml'] as $format) {
    $table->addRow([$format, 'deserialize', 'baseline', benchmark($d, $format)]);
    $progressBar->advance();
}

$progressBar->finish();
$progressBar->clear();

$table->render();
