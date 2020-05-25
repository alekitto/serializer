<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Metadata\PropertyMetadata;
use Kcs\Serializer\Type\Type;
use SplStack;

class GenericSerializationVisitor extends AbstractVisitor
{
    private ?GraphNavigator $navigator = null;
    private $root;
    private SplStack $dataStack;

    /**
     * @var mixed
     */
    private $data;

    /**
     * {@inheritdoc}
     */
    public function setNavigator(?GraphNavigator $navigator = null): void
    {
        $this->navigator = $navigator;
        $this->root = null;
        $this->dataStack = new SplStack();
    }

    /**
     * {@inheritdoc}
     */
    public function visitNull($data, Type $type, Context $context)
    {
        return $this->data = null;
    }

    /**
     * {@inheritdoc}
     */
    public function visitString($data, Type $type, Context $context)
    {
        return $this->data = (string) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitBoolean($data, Type $type, Context $context)
    {
        return $this->data = (bool) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitInteger($data, Type $type, Context $context)
    {
        return $this->data = (int) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitDouble($data, Type $type, Context $context)
    {
        return $this->data = (float) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        $rs = [];
        $elementType = $this->getElementType($type);
        $onlyValues = $type->hasParam(0) && ! $type->hasParam(1);
        $isAssociative = ! $onlyValues && \array_keys($data) !== \range(0, \count($data) - 1);

        foreach ($data as $k => $v) {
            $v = $this->navigator->accept($v, $elementType, $context);

            if (null === $v && ! $isAssociative && ! $context->shouldSerializeNull()) {
                continue;
            }

            if ($onlyValues) {
                $rs[] = $v;
            } else {
                $rs[$k] = $v;
            }
        }

        return $this->data = $rs;
    }

    /**
     * {@inheritdoc}
     */
    public function visitArray($data, Type $type, Context $context)
    {
        $rs = [];
        $elementType = $this->getElementType($type);

        foreach ($data as $k => $v) {
            $v = $this->navigator->accept($v, $elementType, $context);

            if (null === $v && ! $context->shouldSerializeNull()) {
                continue;
            }

            $rs[] = $v;
        }

        return $this->data = $rs;
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $this->data = [];
        $metadataStack = $context->getMetadataStack();

        /** @var PropertyMetadata $propertyMetadata */
        foreach ($metadata->getAttributesMetadata() as $propertyMetadata) {
            $excluded = $context->isPropertyExcluded($propertyMetadata);
            if ($excluded && PropertyMetadata::ON_EXCLUDE_SKIP === $propertyMetadata->onExclude) {
                continue;
            }

            $metadataStack->push($propertyMetadata);
            $this->visitProperty($propertyMetadata, $excluded ? null : $data, $context);
            $metadataStack->pop();
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisiting(&$data, Type $type, Context $context): void
    {
        $this->dataStack->push($this->data);
        $this->data = null;
    }

    /**
     * {@inheritdoc}
     */
    public function endVisiting($data, Type $type, Context $context)
    {
        $rs = $this->data;
        $this->data = $this->dataStack->pop();

        if (null === $this->root && 0 === $this->dataStack->count()) {
            $this->root = $rs;
        }

        return $rs;
    }

    /**
     * {@inheritdoc}
     */
    public function visitCustom(callable $handler, $data, Type $type, Context $context)
    {
        return $this->data = parent::visitCustom($handler, $data, $type, $context);
    }

    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param array|\ArrayObject $data the passed data must be understood by whatever encoding function is applied later
     */
    public function setRoot($data): void
    {
        $this->root = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->getRoot();
    }

    protected function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        $v = null !== $data ? $this->navigator->accept($metadata->getValue($data), $metadata->type, $context) : null;
        if (null === $v && ! $context->shouldSerializeNull()) {
            return;
        }

        $k = $this->namingStrategy->translateName($metadata);

        if ($metadata->inline) {
            if (\is_array($v)) {
                $this->data = \array_merge($this->data, $v);
            }
        } else {
            $this->data[$k] = $v;
        }
    }

    /**
     * Allows you to add additional data to the current object/root element.
     *
     * @param string $key
     * @param mixed  $value This value must either be a regular scalar, or an array.
     *                      It must not contain any objects anymore.
     */
    protected function addData($key, $value): void
    {
        if (isset($this->data[$key])) {
            throw new InvalidArgumentException(\sprintf('There is already data for "%s".', $key));
        }

        $this->data[$key] = $value;
    }

    protected function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @internal
     */
    protected function getData()
    {
        return $this->data;
    }
}
