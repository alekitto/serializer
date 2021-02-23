<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DataCollector;

use Kcs\Serializer\Debug\TraceableSerializer;
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
    private ?SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, Throwable $exception = null): void
    {
        if (! $this->serializer instanceof TraceableSerializer) {
            return;
        }

        $serialize = &$this->serializer->serializeOperations;
        $deserialize = &$this->serializer->deserializeOperations;

        $errorCount = count(array_filter(array_column($serialize, 'exception'))) + count(array_filter(array_column($deserialize, 'exception')));

        $this->data = [
            'count' => count($serialize) + count($deserialize),
            'error_count' => $errorCount,
            'serialize' => $serialize,
            'deserialize' => $deserialize,
        ];
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

    public function getSerializations(): array
    {
        return $this->data['serialize'];
    }

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
        if (! $this->serializer instanceof TraceableSerializer) {
            return;
        }

        $this->data = [];
        $this->serializer->reset();
    }
}
