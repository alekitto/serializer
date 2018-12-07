<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Adapter\Symfony;

use Kcs\Serializer\Adapter\Symfony\MessengerSerializer;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\SerializerInterface;
use Kcs\Serializer\Tests\Fixtures\Adapter\Symfony\DummyMessage;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\Configuration\ValidationConfiguration;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Stamp\ValidationStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerConfiguration;

class MessengerSerializerTest extends TestCase
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var MessengerSerializer
     */
    private $messengerSerializer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->serializer = SerializerBuilder::create()->build();
        $this->messengerSerializer = new MessengerSerializer($this->serializer);
    }

    private function wrap($message) {
        if (\function_exists(Envelope::class.'::wrap')) {
            return Envelope::wrap($message);
        }

        return new Envelope($message);
    }

    public function testEncodedIsDecodable(): void
    {
        $envelope = $this->wrap(new DummyMessage('Hello'));

        $this->assertEquals($envelope, $this->messengerSerializer->decode($this->messengerSerializer->encode($envelope)));
    }

    public function testEncodedWithConfigurationIsDecodable(): void
    {
        $envelope = $this->wrap(new DummyMessage('Hello'));

        if (\class_exists(SerializerConfiguration::class)) {
            $envelope = $envelope
                ->with(new SerializerConfiguration(['groups' => ['foo']]))
                ->with(new ValidationConfiguration(['foo', 'bar']))
            ;
        } else {
            $envelope = $envelope
                ->with(new SerializerStamp(['groups' => ['foo']]), new ValidationStamp(['foo', 'bar']))
            ;
        }

        $this->assertEquals($envelope, $this->messengerSerializer->decode($this->messengerSerializer->encode($envelope)));
    }

    public function testEncodedIsHavingTheBodyAndTypeHeader(): void
    {
        $encoded = $this->messengerSerializer->encode($this->wrap(new DummyMessage('Hello')));

        $this->assertArrayHasKey('body', $encoded);
        $this->assertArrayHasKey('headers', $encoded);
        $this->assertArrayHasKey('type', $encoded['headers']);
        $this->assertArrayNotHasKey('X-Message-Envelope-Items', $encoded['headers']);
        $this->assertEquals(DummyMessage::class, $encoded['headers']['type']);
    }

    public function testUsesTheCustomFormatAndContext(): void
    {
        $message = new DummyMessage('Foo');

        $serializer = $this->prophesize(SerializerInterface::class);
        $serializer->serialize($message, 'csv', Argument::type(SerializationContext::class))->willReturn('Yay');
        $serializer->deserialize('Yay', new Type(DummyMessage::class), 'csv', Argument::type(DeserializationContext::class))->willReturn($message);

        $encoder = new MessengerSerializer($serializer->reveal(), 'csv', ['foo' => 'bar']);

        $encoded = $encoder->encode($this->wrap($message));
        $decoded = $encoder->decode($encoded);

        $this->assertSame('Yay', $encoded['body']);
        $this->assertSame($message, $decoded->getMessage());
    }

    public function testEncodedWithSerializationConfiguration(): void
    {
        $envelope = $this->wrap(new DummyMessage('Hello'));

        if (\class_exists(SerializerConfiguration::class)) {
            $envelope = $envelope
                ->with(new SerializerConfiguration(['groups' => ['foo']]))
                ->with(new ValidationConfiguration(['foo', 'bar']))
            ;
        } else {
            $envelope = $envelope
                ->with(new SerializerStamp(['groups' => ['foo']]), new ValidationStamp(['foo', 'bar']))
            ;
        }

        $encoded = $this->messengerSerializer->encode($envelope);

        $this->assertArrayHasKey('body', $encoded);
        $this->assertArrayHasKey('headers', $encoded);
        $this->assertArrayHasKey('type', $encoded['headers']);
        $this->assertEquals(DummyMessage::class, $encoded['headers']['type']);
        $this->assertArrayHasKey('X-Message-Envelope-Items', $encoded['headers']);

        if (\class_exists(SerializerConfiguration::class)) {
            $this->assertEquals('a:2:{s:75:"Symfony\Component\Messenger\Transport\Serialization\SerializerConfiguration";C:75:"Symfony\Component\Messenger\Transport\Serialization\SerializerConfiguration":59:{a:1:{s:7:"context";a:1:{s:6:"groups";a:1:{i:0;s:3:"foo";}}}}s:76:"Symfony\Component\Messenger\Middleware\Configuration\ValidationConfiguration";C:76:"Symfony\Component\Messenger\Middleware\Configuration\ValidationConfiguration":82:{a:2:{s:6:"groups";a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}s:17:"is_group_sequence";b:0;}}}', $encoded['headers']['X-Message-Envelope-Items']);
        } else {
            $this->assertEquals(serialize([
                SerializerStamp::class => [
                    new SerializerStamp(['groups' => ['foo']]),
                ],
                ValidationStamp::class => [
                    new ValidationStamp(['foo', 'bar']),
                ],
            ]), $encoded['headers']['X-Message-Envelope-Items']);
        }
    }
}
