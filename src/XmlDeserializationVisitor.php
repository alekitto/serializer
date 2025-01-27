<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use ReflectionEnum;
use SimpleXMLElement;
use UnitEnum;

use function array_change_key_case;
use function array_combine;
use function array_key_exists;
use function array_keys;
use function assert;
use function in_array;
use function is_string;
use function libxml_get_last_error;
use function libxml_use_internal_errors;
use function mb_convert_case;
use function preg_replace;
use function reset;
use function simplexml_load_string;
use function sprintf;
use function str_replace;
use function stripos;
use function strtolower;
use function substr;
use function var_export;

use const CASE_LOWER;
use const MB_CASE_LOWER;

class XmlDeserializationVisitor extends GenericDeserializationVisitor
{
    /** @var string[] */
    private array $doctypeWhitelist = [];

    /** @var string[] */
    private array $docNamespaces = [];

    public function prepare(mixed $data): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        if (stripos($data, '<!doctype') !== false) {
            $doctype = $this->getDomDocumentType($data);
            if (! in_array($doctype, $this->doctypeWhitelist, true)) {
                throw new InvalidArgumentException(sprintf('The document type "%s" is not allowed. If it is safe, you may add it to the whitelist configuration.', $doctype));
            }
        }

        try {
            $doc = simplexml_load_string($data);
            if ($doc === false) {
                throw new XmlErrorException(libxml_get_last_error()); /* @phpstan-ignore-line */
            }
        } finally {
            libxml_use_internal_errors($previous);
        }

        $this->docNamespaces = $doc->getDocNamespaces(true) ?: [];

        return $doc;
    }

    /**
     * {@inheritDoc}
     */
    public function visitHash(mixed $data, Type $type, Context $context): array
    {
        assert($context instanceof DeserializationContext);
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = $currentMetadata !== null && $currentMetadata->xmlEntryName ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = $currentMetadata !== null && $currentMetadata->xmlEntryNamespace ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        if ($context->ignoreCase) {
            $entryName = strtolower($entryName);
            $nodes = $namespace !== null
                ? $data->xpath('./*[namespace-uri() = "' . $namespace . '" and translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $entryName . '"]')
                : $data->xpath('./*[translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $entryName . '"]');
        } else {
            $nodes = $namespace !== null ? $data->children($namespace)->$entryName : $data->$entryName;
        }

        switch ($type->countParams()) {
            case 0:
                throw new RuntimeException(sprintf('The array type must be specified either as "array<T>", or "array<K,V>".'));

            case 1:
                foreach ($nodes as $k => $v) {
                    $context->getMetadataStack()->pushIndexPath((string) $k);
                    if ($this->isNullNode($v)) {
                        $result[] = $this->visitNull(null, Type::null(), $context);
                    } else {
                        $result[] = $context->accept($v, $type->getParam(0));
                    }

                    $context->getMetadataStack()->popIndexPath();
                }

                break;

            case 2:
                if ($currentMetadata === null) {
                    throw new RuntimeException('Maps are not supported on top-level without metadata.');
                }

                $keyType = $type->getParam(0);
                $entryType = $type->getParam(1);
                foreach ($nodes as $k => $v) {
                    $attrs = $v->attributes();
                    if (! isset($attrs[$currentMetadata->xmlKeyAttribute])) {
                        throw new RuntimeException(sprintf('The key attribute "%s" must be set for each entry of the map.', $currentMetadata->xmlKeyAttribute));
                    }

                    $context->getMetadataStack()->pushIndexPath((string) $k);
                    $k = $context->accept($attrs[$currentMetadata->xmlKeyAttribute], $keyType);

                    if ($this->isNullNode($v)) {
                        $result[$k] = $this->visitNull(null, Type::null(), $context);
                    } else {
                        $result[$k] = $context->accept($v, $entryType);
                    }

                    $context->getMetadataStack()->popIndexPath();
                }

                break;

            default:
                throw new LogicException(sprintf('The array type does not support more than 2 parameters, but got %s.', var_export($type->getParams(), true)));
        }

        $this->setData($result);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function visitArray(mixed $data, Type $type, Context $context): array
    {
        assert($context instanceof DeserializationContext);
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = $currentMetadata !== null && $currentMetadata->xmlEntryName ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = $currentMetadata !== null && $currentMetadata->xmlEntryNamespace ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        if ($context->ignoreCase) {
            $entryName = strtolower($entryName);
            $nodes = $namespace !== null
                ? $data->xpath('./*[namespace-uri() = "' . $namespace . '" and translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $entryName . '"]')
                : $data->xpath('./*[translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $entryName . '"]');
        } else {
            $nodes = $namespace !== null ? $data->children($namespace)->$entryName : $data->$entryName;
        }

        foreach ($nodes as $k => $v) {
            $context->getMetadataStack()->pushIndexPath((string) $k);
            if ($this->isNullNode($v)) {
                $result[] = $this->visitNull(null, Type::null(), $context);
            } else {
                $result[] = $context->accept($v, $type->getParam(0));
            }

            $context->getMetadataStack()->popIndexPath();
        }

        $this->setData($result);

        return $result;
    }

    protected function visitProperty(PropertyMetadata $metadata, mixed $data, Context $context): mixed
    {
        assert($context instanceof DeserializationContext);
        $name = $context->namingStrategy->translateName($metadata);

        if ($data !== null && $context->ignoreCase) {
            $name = mb_convert_case($name, MB_CASE_LOWER);
        }

        if ($metadata->type === null) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        if ($metadata->xmlAttribute) {
            $attributes = $data->attributes($metadata->xmlNamespace);
            if ($context->ignoreCase) {
                $keys = array_keys(((array) $attributes)['@attributes'] ?? []);
                $keys = array_change_key_case(array_combine($keys, $keys), CASE_LOWER);
                $name = $keys[$name] ?? $name;
            }

            if (isset($attributes[$name])) {
                return $context->accept($attributes[$name], $metadata->type);
            }

            return null;
        }

        if ($metadata->xmlValue) {
            return $context->accept($data, $metadata->type);
        }

        if ($metadata->xmlCollection) {
            $enclosingElem = $data;
            if (! $metadata->xmlCollectionInline) {
                $children = $data->children($metadata->xmlNamespace);
                if ($context->ignoreCase) {
                    $children = (object) array_change_key_case((array) $children, CASE_LOWER);
                }

                $enclosingElem = $children->$name;
            }

            return $context->accept($enclosingElem, $metadata->type);
        }

        if ($metadata->xmlNamespace) {
            if ($context->ignoreCase) {
                $children = $data->xpath('./*[namespace-uri() = "' . $metadata->xmlNamespace . '" and translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $name . '"]');
                if (empty($children)) {
                    return null;
                }

                $node = $children[0];
            } else {
                $children = $data->children($metadata->xmlNamespace);
                $node = $children->$name;

                if (! $node->count()) {
                    return null;
                }
            }
        } else {
            $namespaces = $data->getDocNamespaces();
            if (isset($namespaces[''])) {
                $localNameFn = 'local-name()';
                if ($context->ignoreCase) {
                    $localNameFn = 'translate(' . $localNameFn . ', \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\')';
                }

                $nodes = $data->xpath('./*[namespace-uri() = "' . $namespaces[''] . '" and ' . $localNameFn . ' = "' . $name . '"]');
                if (empty($nodes)) {
                    return null;
                }

                $node = reset($nodes);
            } elseif ($context->ignoreCase) {
                $children = $data->xpath('./*[translate(local-name(), \'ABCDEFGHIJKLMNOPQRSTUVWXYZ\', \'abcdefghijklmnopqrstuvwxyz\') = "' . $name . '"]');
                if (empty($children)) {
                    return null;
                }

                $node = $children[0];
            } else {
                if (! isset($data->$name)) {
                    return null;
                }

                $node = $data->$name;
            }
        }

        if ($this->isNullNode($node)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return $context->accept($node, $metadata->type);
    }

    public function visitBoolean(mixed $data, Type $type, Context $context): bool
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        $data = (string) $data;

        if ($data === 'true' || $data === '1') {
            $data = true;
        } elseif ($data === 'false' || $data === '0') {
            $data = false;
        } else {
            throw new RuntimeException(sprintf('Could not convert data to boolean. Expected "true", "false", "1" or "0", but got %s.', var_export($data, true)));
        }

        return parent::visitBoolean($data, $type, $context);
    }

    public function visitCustom(callable $handler, mixed $data, Type $type, Context $context): mixed
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitCustom($handler, $data, $type, $context);
    }

    public function visitEnum(mixed $data, Type $type, Context $context): UnitEnum|null
    {
        $reflection = new ReflectionEnum($type->metadata->getName()); // @phpstan-ignore-line
        $data = (string) $data;

        $backingType = $reflection->getBackingType();
        if ($backingType !== null && (string) $backingType === 'int') {
            $data = (int) $data;
        }

        return parent::visitEnum($data, $type, $context);
    }

    public function visitObject(
        ClassMetadata $metadata,
        mixed $data,
        Type $type,
        Context $context,
        ObjectConstructorInterface|null $objectConstructor = null,
    ): object {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    public function visitString(mixed $data, Type $type, Context $context): string|null
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitString($data, $type, $context);
    }

    public function visitInteger(mixed $data, Type $type, Context $context): int|null
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitInteger($data, $type, $context);
    }

    public function visitDouble(mixed $data, Type $type, Context $context): float|null
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitDouble($data, $type, $context);
    }

    /** @param string[] $doctypeWhitelist */
    public function setDoctypeWhitelist(array $doctypeWhitelist): void
    {
        $this->doctypeWhitelist = $doctypeWhitelist;
    }

    /** @return string[] */
    public function getDoctypeWhitelist(): array
    {
        return $this->doctypeWhitelist;
    }

    /**
     * Retrieves internalSubset even in bugfixed php versions.
     */
    private function getDomDocumentType(string $data): string
    {
        $startPos = $endPos = stripos($data, '<!doctype');
        $braces = 0;
        do {
            $char = $data[$endPos++];
            if ($char === '<') {
                ++$braces;
            }

            if ($char !== '>') {
                continue;
            }

            --$braces;
        } while ($braces > 0);

        $internalSubset = substr($data, $startPos ?: 0, $endPos - $startPos);
        $internalSubset = str_replace(["\n", "\r"], '', $internalSubset);
        $internalSubset = preg_replace('/\s{2,}/', ' ', $internalSubset);
        assert(is_string($internalSubset));
        $internalSubset = str_replace(['[ <!', '> ]>'], ['[<!', '>]>'], $internalSubset);
        assert(is_string($internalSubset));

        return $internalSubset;
    }

    private function isNullNode(SimpleXMLElement $node): bool
    {
        if (! array_key_exists('xsi', $this->docNamespaces)) {
            return false;
        }

        return ($nilNodes = $node->xpath('./@xsi:nil')) && (string) reset($nilNodes) === 'true';
    }
}
