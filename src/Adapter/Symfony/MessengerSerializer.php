<?php declare(strict_types=1);

namespace Kcs\Serializer\Adapter\Symfony;

use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Type\Type;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\DecoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\EncoderInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerConfiguration;

class MessengerSerializer implements DecoderInterface, EncoderInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $format;

    /**
     * @var array
     */
    private $context;

    public function __construct(SerializerInterface $serializer, string $format = 'json', array $context = [])
    {
        $this->serializer = $serializer;
        $this->format = $format;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        if (empty($encodedEnvelope['body']) || empty($encodedEnvelope['headers'])) {
            throw new \InvalidArgumentException('Encoded envelope should have at least a `body` and some `headers`.');
        }

        if (empty($encodedEnvelope['headers']['type'])) {
            throw new \InvalidArgumentException('Encoded envelope does not have a `type` header.');
        }

        $envelopeItems = isset($encodedEnvelope['headers']['X-Message-Envelope-Items']) ? unserialize($encodedEnvelope['headers']['X-Message-Envelope-Items']) : [];

        $context = $this->context;
        /** @var SerializerConfiguration|null $serializerConfig */
        if ($serializerConfig = $envelopeItems[SerializerConfiguration::class] ?? null) {
            $context = $serializerConfig->getContext() + $context;
        }

        $message = $this->serializer->deserialize(
            $encodedEnvelope['body'],
            Type::parse($encodedEnvelope['headers']['type']),
            $this->format,
            ContextConverter::toDeserializationContext($context)
        );

        return new Envelope($message, $envelopeItems);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Envelope $envelope): array
    {
        $context = $this->context;

        /** @var SerializerConfiguration|null $serializerConfig */
        if ($serializerConfig = $envelope->get(SerializerConfiguration::class)) {
            $context = $serializerConfig->getContext() + $context;
        }

        $headers = ['type' => \get_class($envelope->getMessage())];
        if ($configurations = $envelope->all()) {
            $headers['X-Message-Envelope-Items'] = serialize($configurations);
        }

        return [
            'body' => $this->serializer->serialize($envelope->getMessage(), $this->format, ContextConverter::toSerializationContext($context)),
            'headers' => $headers,
        ];
    }
}
