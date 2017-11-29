<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Exception\RuntimeException;

class YamlSerializationTest extends BaseSerializationTest
{
    protected function getContent($key)
    {
        if (! file_exists($file = __DIR__.'/yml/'.$key.'.yml')) {
            throw new RuntimeException(sprintf('The content with key "%s" does not exist.', $key));
        }

        return file_get_contents($file);
    }

    protected function getFormat()
    {
        return 'yml';
    }

    protected function hasDeserializer()
    {
        return true;
    }
}
