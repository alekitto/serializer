<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Exception\RuntimeException;

class CsvSerializationTest extends BaseSerializationTest
{
    /**
     * {@inheritdoc}
     */
    protected function getContent(string $key): string
    {
        if (! \file_exists($file = __DIR__.'/csv/'.$key.'.csv')) {
            throw new RuntimeException(\sprintf('The content with key "%s" does not exist.', $key));
        }

        return \file_get_contents($file);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormat(): string
    {
        return 'csv';
    }

    /**
     * {@inheritdoc}
     */
    protected function hasDeserializer(): bool
    {
        return false;
    }
}
