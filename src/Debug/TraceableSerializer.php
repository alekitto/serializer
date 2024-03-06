<?php

declare(strict_types=1);

namespace Kcs\Serializer\Debug;

use Kcs\Serializer\Context;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Throwable;

class TraceableSerializer implements SerializerInterface
{
    private VarCloner $cloner;

    /** @var array<array-key, mixed> */
    public array $serializeOperations = [];
    /** @var array<array-key, mixed> */
    public array $deserializeOperations = [];

    public function __construct(private SerializerInterface $serializer, VarCloner|null $cloner = null)
    {
        $this->cloner = $cloner ?? new VarCloner();
    }

    public function serialize(mixed $data, string $format, SerializationContext|null $context = null, Type|null $type = null): mixed
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

    public function deserialize(mixed $data, Type $type, string $format, DeserializationContext|null $context = null): mixed
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
     * {@inheritDoc}
     */
    public function normalize(mixed $data, SerializationContext|null $context = null): array
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
     * {@inheritDoc}
     */
    public function denormalize(array $data, Type $type, DeserializationContext|null $context = null): mixed
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

    /**
     * @return array<string, mixed>
     * @phpstan-return array{data: Data, format: string, type: Data, context: Data, result: Data|null, exception: Data|null}
     */
    private function prepareDebugData(mixed $data, string $format, Type|null $type, Context|null $context): array
    {
        return [
            'data' => $this->cloner->cloneVar($data),
            'format' => $format,
            'type' => $this->cloner->cloneVar($type?->jsonSerialize()),
            'context' => $this->cloner->cloneVar(
                $context !== null ? [
                    'attributes' => $context->attributes->all(),
                ] : null,
            ),
            'result' => null,
            'exception' => null,
        ];
    }
}
