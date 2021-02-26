<?php

declare(strict_types=1);

namespace Kcs\Serializer\Tests\Bundle;

use DateTime;
use Kcs\Serializer\Debug\TraceableHandlerRegistry;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Tests\Fixtures\Kernel\AppKernel;
use PhpCollection\Sequence;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Validator\ConstraintViolation;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class SerializerBundleTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new AppKernel('test', true);
    }

    public function testHandlerShouldBeRegistered(): void
    {
        $client = self::createClient();
        $registry = $client->getContainer()->get('handler_registry');
        if ($registry instanceof TraceableHandlerRegistry) {
            $registry = (fn () => $this->decorated)->bindTo($registry, TraceableHandlerRegistry::class)();
        }

        $handler = $registry->getHandler(Direction::DIRECTION_SERIALIZATION, 'TestObject');
        self::assertEquals([$client->getContainer()->get('test_handler'), 'serialize'], $handler);
    }

    public function testDefaultHandlersAreRegistered(): void
    {
        $client = self::createClient();
        $registry = $client->getContainer()->get('handler_registry');

        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, 'ArrayCollection'));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, ConstraintViolation::class));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, DateTime::class));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, Form::class));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, Sequence::class));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, 'PropelCollection'));
        self::assertNotNull($registry->getHandler(Direction::DIRECTION_SERIALIZATION, UuidInterface::class));
    }

    public function testFunctional(): void
    {
        $client = self::createClient();

        $client->request('GET', '/json');
        $response = $client->getResponse();
        self::assertJsonStringEqualsJsonString(json_encode([
            'comments' => [
                'Foo' => [
                    'comments' => [
                        ['author' => ['full_name' => 'Foo'], 'text' => 'foo'],
                        ['author' => ['full_name' => 'Foo'], 'text' => 'bar'],
                    ],
                    'count' => 2,
                ],
            ],
        ], JSON_THROW_ON_ERROR), $response->getContent());

        $client->request('GET', '/xml');
        $response = $client->getResponse();
        self::assertXmlStringEqualsXmlString(<<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<post>
  <comments count="2" author-name="Foo">
    <comment>
      <author>
        <full_name><![CDATA[Foo]]></full_name>
      </author>
      <text><![CDATA[foo]]></text>
    </comment>
    <comment>
      <author>
        <full_name><![CDATA[Foo]]></full_name>
      </author>
      <text><![CDATA[bar]]></text>
    </comment>
  </comments>
</post>

EOF
        , $response->getContent());

        $client->request('GET', '/yaml');
        $response = $client->getResponse();
        self::assertEquals(<<<EOF
comments:
    Foo:
        comments:
            -
                author:
                    full_name: Foo
                text: foo
            -
                author:
                    full_name: Foo
                text: bar
        count: 2

EOF
        , $response->getContent());
    }
}
