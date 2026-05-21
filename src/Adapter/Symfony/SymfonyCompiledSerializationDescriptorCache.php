<?php

declare(strict_types=1);

namespace Kcs\Serializer\Adapter\Symfony;

use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Serialization\Compiled\CompiledClassDescriptor;
use Kcs\Serializer\Serialization\Compiled\CompiledSerializationDescriptorCacheInterface;
use ReflectionClass;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\ResourceInterface;

use function class_exists;
use function file_exists;
use function is_array;
use function is_file;
use function sha1;
use function var_export;

final class SymfonyCompiledSerializationDescriptorCache implements CompiledSerializationDescriptorCacheInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly bool $debug,
    ) {
        if (! class_exists(ConfigCache::class)) {
            throw new LogicException('The Symfony Config component is required to use the Symfony compiled serialization descriptor cache.');
        }
    }

    public function get(string $key): CompiledClassDescriptor|null
    {
        $cache = $this->createCache($key);
        if (! $cache->isFresh()) {
            return null;
        }

        $file = $cache->getPath();
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
        $this->createCache($key)->write($this->dumpDescriptor($descriptor), $this->createResources($descriptor));
    }

    private function createCache(string $key): ConfigCache
    {
        return new ConfigCache($this->directory . '/' . sha1($key) . '.php', $this->debug);
    }

    private function dumpDescriptor(CompiledClassDescriptor $descriptor): string
    {
        return "<?php\n\nreturn " . var_export($descriptor->toArray(), true) . ";\n";
    }

    /** @return ResourceInterface[] */
    private function createResources(CompiledClassDescriptor $descriptor): array
    {
        $resources = [];

        foreach ([$descriptor->className, $descriptor->namingStrategy] as $className) {
            if (! class_exists($className)) {
                continue;
            }

            $file = (new ReflectionClass($className))->getFileName();
            if ($file === false || ! is_file($file)) {
                continue;
            }

            $resources[] = new FileResource($file);
        }

        return $resources;
    }
}
