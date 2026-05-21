<?php

declare(strict_types=1);

namespace Kcs\Serializer\Serialization\Compiled;

use Kcs\Serializer\Exception\RuntimeException;

use function file_exists;
use function file_put_contents;
use function is_array;
use function is_dir;
use function mkdir;
use function rename;
use function sha1;
use function sprintf;
use function uniqid;
use function var_export;

final class PhpFileCompiledSerializationDescriptorCache implements CompiledSerializationDescriptorCacheInterface
{
    public function __construct(private readonly string $directory)
    {
    }

    public function get(string $key): CompiledClassDescriptor|null
    {
        $file = $this->getFile($key);
        if (! file_exists($file)) {
            return null;
        }

        /** @var array{className: string, namingStrategy: string, properties: list<array{name: string, serializedName: string, nativeType: string|null, inline: bool}>} $data */
        $data = require $file;
        if (! is_array($data)) {
            return null;
        }

        return CompiledClassDescriptor::fromArray($data);
    }

    public function save(string $key, CompiledClassDescriptor $descriptor): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Cannot create compiled serialization cache directory "%s".', $this->directory));
        }

        $file = $this->getFile($key);
        $tmp = $file . '.' . uniqid('', true) . '.tmp';
        $contents = "<?php\n\nreturn " . var_export($descriptor->toArray(), true) . ";\n";

        if (file_put_contents($tmp, $contents) === false || ! rename($tmp, $file)) {
            throw new RuntimeException(sprintf('Cannot write compiled serialization cache file "%s".', $file));
        }
    }

    private function getFile(string $key): string
    {
        return $this->directory . '/' . sha1($key) . '.php';
    }
}
