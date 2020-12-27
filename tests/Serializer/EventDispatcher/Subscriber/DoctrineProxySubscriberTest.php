<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\EventDispatcher\Subscriber;

use Kcs\Serializer\Context;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Tests\Fixtures\SimpleObjectProxy;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DoctrineProxySubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var Context|ObjectProphecy
     */
    private ObjectProphecy $visitor;
    private DoctrineProxySubscriber $subscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->subscriber = new DoctrineProxySubscriber();
        $this->visitor = $this->prophesize(Context::class);
    }

    public function testRewritesProxyClassName(): void
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), Type::from($obj));
        $this->subscriber->onPreSerialize($event);

        self::assertEquals(Type::from(\get_parent_class($obj)), $event->getType());
        self::assertTrue($obj->__isInitialized());
    }

    public function testDoesNotRewriteCustomType(): void
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), Type::from('FakedName'));
        $this->subscriber->onPreSerialize($event);

        self::assertEquals(Type::from('FakedName'), $event->getType());
        self::assertTrue($obj->__isInitialized());
    }

    private function createEvent($object, Type $type): PreSerializeEvent
    {
        return new PreSerializeEvent($this->visitor->reveal(), $object, $type);
    }
}
