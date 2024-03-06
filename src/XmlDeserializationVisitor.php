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
use Safe\Exceptions\SimplexmlException;
use SimpleXMLElement;
use UnitEnum;

use function array_key_exists;
use function in_array;
use function libxml_use_internal_errors;
use function reset;
use function Safe\libxml_get_last_error;
use function Safe\preg_replace;
use function Safe\simplexml_load_string;
use function sprintf;
use function str_replace;
use function stripos;
use function substr;
use function uniqid;
use function var_export;

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
        } catch (SimplexmlException $e) {
            throw new XmlErrorException(libxml_get_last_error(), $e);
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
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = $currentMetadata !== null && $currentMetadata->xmlEntryName ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = $currentMetadata !== null && $currentMetadata->xmlEntryNamespace ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        $nodes = $namespace !== null ? $data->children($namespace)->$entryName : $data->$entryName;
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
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = $currentMetadata !== null && $currentMetadata->xmlEntryName ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = $currentMetadata !== null && $currentMetadata->xmlEntryNamespace ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        $nodes = $namespace !== null ? $data->children($namespace)->$entryName : $data->$entryName;
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
        $name = $this->namingStrategy->translateName($metadata);

        if ($metadata->type === null) {
            throw new RuntimeException(sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
        }

        if ($metadata->xmlAttribute) {
            $attributes = $data->attributes($metadata->xmlNamespace);
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
                $enclosingElem = $data->children($metadata->xmlNamespace)->$name;
            }

            return $context->accept($enclosingElem, $metadata->type);
        }

        if ($metadata->xmlNamespace) {
            $node = $data->children($metadata->xmlNamespace)->$name;
            if (! $node->count()) {
                return null;
            }
        } else {
            $namespaces = $data->getDocNamespaces();
            if (isset($namespaces[''])) {
                $prefix = uniqid('ns-');
                $data->registerXPathNamespace($prefix, $namespaces['']);

                $nodes = $data->xpath('./' . $prefix . ':' . $name);
                if (empty($nodes)) {
                    return null;
                }

                $node = reset($nodes);
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
        $reflection = new ReflectionEnum($type->metadata->getName());
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
        $internalSubset = str_replace(['[ <!', '> ]>'], ['[<!', '>]>'], $internalSubset);

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
