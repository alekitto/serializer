<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DataCollector;

use Kcs\Serializer\Debug\TraceableHandlerRegistry;
use Kcs\Serializer\Debug\TraceableSerializer;
use Kcs\Serializer\Handler\HandlerRegistryInterface;
use Kcs\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

use function array_column;
use function array_filter;
use function count;

class SerializerDataCollector extends DataCollector
{
    public function __construct(private SerializerInterface $serializer, private HandlerRegistryInterface $handlerRegistry)
    {
    }

    public function collect(Request $request, Response $response, Throwable|null $exception = null): void
    {
        $handlerCalls = $serialize = $deserialize = [];
        if ($this->serializer instanceof TraceableSerializer) {
            $serialize = &$this->serializer->serializeOperations;
            $deserialize = &$this->serializer->deserializeOperations;
        }

        if ($this->handlerRegistry instanceof TraceableHandlerRegistry) {
            $handlerCalls = $this->handlerRegistry->calls;
        }

        $errorCount = count(array_filter(array_column($serialize, 'exception'))) + count(array_filter(array_column($deserialize, 'exception')));
        $this->data = [
            'count' => count($serialize) + count($deserialize),
            'error_count' => $errorCount,
            'serialize' => $serialize,
            'deserialize' => $deserialize,
            'handler_calls' => $handlerCalls,
        ];
    }

    /** @return array<string, mixed> */
    public function getHandlerCalls(): array
    {
        return $this->data['handler_calls'] ?? [];
    }

    public function getCount(): int
    {
        return $this->data['count'] ?? 0;
    }

    public function getErrorCount(): int
    {
        return $this->data['error_count'] ?? 0;
    }

    public function isEmpty(): bool
    {
        return empty($this->data['serialize']) && empty($this->data['deserialize']);
    }

    /** @return array<string, mixed> */
    public function getSerializations(): array
    {
        return $this->data['serialize'];
    }

    /** @return array<string, mixed> */
    public function getDeserializations(): array
    {
        return $this->data['deserialize'];
    }

    public function getName(): string
    {
        return 'kcs_serializer';
    }

    public function reset(): void
    {
        if ($this->serializer instanceof TraceableSerializer) {
            $this->serializer->reset();
        }

        if ($this->handlerRegistry instanceof TraceableHandlerRegistry) {
            $this->handlerRegistry->reset();
        }

        $this->data = [];
    }
}
