<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Exception\LogicException;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Exception\XmlErrorException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use SimpleXMLElement;

class XmlDeserializationVisitor extends GenericDeserializationVisitor
{
    private bool $disableExternalEntities = true;

    /** @var string[] */
    private array $doctypeWhitelist = [];

    /** @var string[] */
    private array $docNamespaces = [];

    public function enableExternalEntities(): void
    {
        $this->disableExternalEntities = false;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($data)
    {
        $previous = \libxml_use_internal_errors(true);
        $previousEntityLoaderState = \libxml_disable_entity_loader($this->disableExternalEntities);

        if (false !== \stripos($data, '<!doctype')) {
            $doctype = $this->getDomDocumentType($data);
            if (! \in_array($doctype, $this->doctypeWhitelist, true)) {
                throw new InvalidArgumentException(\sprintf('The document type "%s" is not allowed. If it is safe, you may add it to the whitelist configuration.', $doctype));
            }
        }

        $doc = \simplexml_load_string($data);
        \libxml_use_internal_errors($previous);
        \libxml_disable_entity_loader($previousEntityLoaderState);

        if (false === $doc) {
            throw new XmlErrorException(\libxml_get_last_error());
        }

        $this->docNamespaces = $doc->getDocNamespaces(true);

        return $doc;
    }

    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = (null !== $currentMetadata && $currentMetadata->xmlEntryName) ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = (null !== $currentMetadata && $currentMetadata->xmlEntryNamespace) ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        $nodes = null !== $namespace ? $data->children($namespace)->$entryName : $data->$entryName;
        switch ($type->countParams()) {
            case 0:
                throw new RuntimeException(\sprintf('The array type must be specified either as "array<T>", or "array<K,V>".'));
            case 1:
                foreach ($nodes as $v) {
                    if ($this->isNullNode($v)) {
                        $result[] = $this->visitNull(null, Type::null(), $context);
                    } else {
                        $result[] = $context->accept($v, $type->getParam(0));
                    }
                }

                break;

            case 2:
                if (null === $currentMetadata) {
                    throw new RuntimeException('Maps are not supported on top-level without metadata.');
                }

                $keyType = $type->getParam(0);
                $entryType = $type->getParam(1);
                foreach ($nodes as $v) {
                    $attrs = $v->attributes();
                    if (! isset($attrs[$currentMetadata->xmlKeyAttribute])) {
                        throw new RuntimeException(\sprintf('The key attribute "%s" must be set for each entry of the map.', $currentMetadata->xmlKeyAttribute));
                    }

                    $k = $context->accept($attrs[$currentMetadata->xmlKeyAttribute], $keyType);

                    if ($this->isNullNode($v)) {
                        $result[$k] = $this->visitNull(null, Type::null(), $context);
                    } else {
                        $result[$k] = $context->accept($v, $entryType);
                    }
                }

                break;

            default:
                throw new LogicException(\sprintf('The array type does not support more than 2 parameters, but got %s.', \var_export($type['params'], true)));
        }

        $this->setData($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        $currentMetadata = $context->getMetadataStack()->getCurrent();

        $entryName = (null !== $currentMetadata && $currentMetadata->xmlEntryName) ? $currentMetadata->xmlEntryName : 'entry';
        $namespace = (null !== $currentMetadata && $currentMetadata->xmlEntryNamespace) ? $currentMetadata->xmlEntryNamespace : null;
        $result = [];

        $nodes = null !== $namespace ? $data->children($namespace)->$entryName : $data->$entryName;
        foreach ($nodes as $v) {
            if ($this->isNullNode($v)) {
                $result[] = $this->visitNull(null, Type::null(), $context);
            } else {
                $result[] = $context->accept($v, $type->getParam(0));
            }
        }

        $this->setData($result);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $name = $this->namingStrategy->translateName($metadata);

        if (null === $metadata->type) {
            throw new RuntimeException(\sprintf('You must define a type for %s::$%s.', $metadata->getReflection()->class, $metadata->name));
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
                $prefix = \uniqid('ns-');
                $data->registerXPathNamespace($prefix, $namespaces['']);

                $nodes = $data->xpath('./'.$prefix.':'.$name);
                if (empty($nodes)) {
                    return null;
                }

                $node = \reset($nodes);
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

    /**
     * {@inheritdoc}
     */
    public function visitBoolean($data, Type $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        $data = (string) $data;

        if ('true' === $data || '1' === $data) {
            $data = true;
        } elseif ('false' === $data || '0' === $data) {
            $data = false;
        } else {
            throw new RuntimeException(\sprintf('Could not convert data to boolean. Expected "true", "false", "1" or "0", but got %s.', \var_export($data, true)));
        }

        return parent::visitBoolean($data, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitCustom($handler, $data, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(
        ClassMetadata $metadata,
        $data,
        Type $type,
        Context $context,
        ?ObjectConstructorInterface $objectConstructor = null
    ) {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    /**
     * {@inheritdoc}
     */
    public function visitString($data, Type $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitString($data, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger($data, Type $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitInteger($data, $type, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble($data, Type $type, Context $context)
    {
        if ($this->isNullNode($data)) {
            return $this->visitNull(null, Type::null(), $context);
        }

        return parent::visitDouble($data, $type, $context);
    }

    /**
     * @param string[] $doctypeWhitelist
     */
    public function setDoctypeWhitelist(array $doctypeWhitelist): void
    {
        $this->doctypeWhitelist = $doctypeWhitelist;
    }

    /**
     * @return string[]
     */
    public function getDoctypeWhitelist(): array
    {
        return $this->doctypeWhitelist;
    }

    /**
     * Retrieves internalSubset even in bugfixed php versions.
     */
    private function getDomDocumentType(string $data): string
    {
        $startPos = $endPos = \stripos($data, '<!doctype');
        $braces = 0;
        do {
            $char = $data[$endPos++];
            if ('<' === $char) {
                ++$braces;
            }
            if ('>' === $char) {
                --$braces;
            }
        } while ($braces > 0);

        $internalSubset = \substr($data, $startPos, $endPos - $startPos);
        $internalSubset = \str_replace(["\n", "\r"], '', $internalSubset);
        $internalSubset = \preg_replace('/\s{2,}/', ' ', $internalSubset);
        $internalSubset = \str_replace(['[ <!', '> ]>'], ['[<!', '>]>'], $internalSubset);

        return $internalSubset;
    }

    private function isNullNode(SimpleXMLElement $node): bool
    {
        if (! \array_key_exists('xsi', $this->docNamespaces)) {
            return false;
        }

        return ($nilNodes = $node->xpath('./@xsi:nil')) && 'true' === (string) \reset($nilNodes);
    }
}
