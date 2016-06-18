<?php

require_once 'bootstrap.php';

$input = new \Symfony\Component\Console\Input\ArgvInput();
$output = new \Symfony\Component\Console\Output\ConsoleOutput();

function benchmark(\Closure $f, $format)
{
    $times = 20;
    $time = microtime(true);
    for ($i = 0; $i < $times; ++$i) {
        $f($format);
    }

    return (microtime(true) - $time) / $times;
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
$collection = createCollection();
$metrics = [];
$f = function ($format) use ($serializer, $collection) {
    $serializer->serialize($collection, $format);
};

// Load all necessary classes into memory.
$f('array');

$table = new \Symfony\Component\Console\Helper\Table($output);
$table->setHeaders(['Format', 'Direction', 'Time']);

$progressBar = new \Symfony\Component\Console\Helper\ProgressBar($output, 8);
$progressBar->start();

foreach (['array', 'json', 'yml', 'xml'] as $format) {
    $table->addRow([$format, 'serialize', benchmark($f, $format)]);
    $progressBar->advance();
}

$serialized = [
    'array' => $serializer->serialize($collection, 'array'),
    'json' => $serializer->serialize($collection, 'json'),
    'yml' => $serializer->serialize($collection, 'yml'),
    'xml' => $serializer->serialize($collection, 'xml'),
];

$type = new \Kcs\Serializer\Type\Type('array', [
    \Kcs\Serializer\Type\Type::from(\Kcs\Serializer\Tests\Fixtures\BlogPost::class)
]);
$d = function ($format) use ($serializer, $serialized, $type) {
    $serializer->deserialize($serialized[$format], $type, $format);
};

foreach (['array', 'json', 'yml', 'xml'] as $format) {
    $table->addRow([$format, 'deserialize', benchmark($d, $format)]);
    $progressBar->advance();
}

$progressBar->finish();
$progressBar->clear();

$table->render();
