<?php declare(strict_types=1);

namespace Kcs\Serializer;

use ArrayObject;
use JsonException;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

class JsonSerializationVisitor extends GenericSerializationVisitor
{
    private int $options = 0;

    /**
     * {@inheritdoc}
     */
    public static function getFormat(): string
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(): string
    {
        try {
            return \json_encode($this->getRoot(), $this->options | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('An error occurred while encoding your data: '.$exception->getMessage(), 0, $exception);
        }
    }

    public function getOptions(): int
    {
        return $this->options;
    }

    public function setOptions(int $options): void
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function visitHash($data, Type $type, Context $context)
    {
        $result = parent::visitHash($data, $type, $context);

        if ($type->hasParam(1) && 0 === \count($result)) {
            // ArrayObject is specially treated by the json_encode function and
            // serialized to { } while a mere array would be serialized to [].
            $this->setData($result = new ArrayObject());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function visitObject(ClassMetadata $metadata, $data, Type $type, Context $context, ObjectConstructorInterface $objectConstructor = null)
    {
        $rs = parent::visitObject($metadata, $data, $type, $context, $objectConstructor);

        // Force JSON output to "{}" instead of "[]" if it contains either no properties or all properties are null.
        if (0 === \count($rs)) {
            $this->setData($rs = new ArrayObject());
        }

        return $rs;
    }
}
