<?php

declare(strict_types=1);

namespace Kcs\Serializer\Debug;

use Kcs\Serializer\Context;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Throwable;

class TraceableSerializer implements SerializerInterface
{
    private SerializerInterface $serializer;

    public array $serializeOperations = [];
    public array $deserializeOperations = [];
    private VarCloner $cloner;

    public function __construct(SerializerInterface $serializer, ?VarCloner $cloner = null)
    {
        $this->serializer = $serializer;
        $this->cloner = $cloner ?? new VarCloner();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize($data, string $format, ?SerializationContext $context = null, ?Type $type = null)
    {
        $debugData = $this->prepareDebugData($data, $format, $type, $context);
        $this->serializeOperations[] = &$debugData;

        try {
            $result = $this->serializer->serialize($data, $format, $context, $type);
            $debugData['result'] = $this->cloner->cloneVar($result);
        } catch (Throwable $e) {
            $debugData['exception'] = $this->cloner->cloneVar($e);
            throw $e;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize($data, Type $type, string $format, ?DeserializationContext $context = null)
    {
        $debugData = $this->prepareDebugData($data, $format, $type, $context);
        $this->deserializeOperations[] = &$debugData;

        try {
            $result = $this->serializer->deserialize($data, $type, $format, $context);
            $debugData['result'] = $this->cloner->cloneVar($result);
        } catch (Throwable $e) {
            $debugData['exception'] = $this->cloner->cloneVar($e);
            throw $e;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, SerializationContext $context = null): array
    {
        $debugData = $this->prepareDebugData($data, 'array', null, $context);
        $this->serializeOperations[] = &$debugData;

        try {
            $result = $this->serializer->normalize($data, $context);
            $debugData['result'] = $this->cloner->cloneVar($result);
        } catch (Throwable $e) {
            $debugData['exception'] = $this->cloner->cloneVar($e);
            throw $e;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize(array $data, Type $type, ?DeserializationContext $context = null)
    {
        $debugData = $this->prepareDebugData($data, 'array', $type, $context);
        $this->deserializeOperations[] = &$debugData;

        try {
            $result = $this->serializer->denormalize($data, $type, $context);
            $debugData['result'] = $this->cloner->cloneVar($result);
        } catch (Throwable $e) {
            $debugData['exception'] = $this->cloner->cloneVar($e);
            throw $e;
        }

        return $result;
    }

    public function reset(): void
    {
        $this->serializeOperations = [];
        $this->deserializeOperations = [];
    }

    private function prepareDebugData($data, string $format, ?Type $type, ?Context $context): array
    {
        return [
            'data' => $this->cloner->cloneVar($data),
            'format' => $format,
            'type' => $this->cloner->cloneVar($type !== null ? $type->jsonSerialize() : null),
            'context' => $this->cloner->cloneVar(null !== $context ? [
                'attributes' => $context->attributes->all(),
            ] : null),
            'result' => null,
            'exception' => null,
        ];
    }
}
