<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer\EventDispatcher\Subscriber;

use Kcs\Serializer\Context;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Tests\Fixtures\SimpleObjectProxy;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class DoctrineProxySubscriberTest extends TestCase
{
    /** @var Context */
    private $visitor;

    /** @var DoctrineProxySubscriber */
    private $subscriber;

    public function testRewritesProxyClassName()
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), Type::from($obj));
        $this->subscriber->onPreSerialize($event);

        $this->assertEquals(Type::from(get_parent_class($obj)), $event->getType());
        $this->assertTrue($obj->__isInitialized());
    }

    public function testDoesNotRewriteCustomType()
    {
        $event = $this->createEvent($obj = new SimpleObjectProxy('a', 'b'), Type::from('FakedName'));
        $this->subscriber->onPreSerialize($event);

        $this->assertEquals(Type::from('FakedName'), $event->getType());
        $this->assertTrue($obj->__isInitialized());
    }

    protected function setUp()
    {
        $this->subscriber = new DoctrineProxySubscriber();
        $this->visitor = $this->prophesize(Context::class);
    }

    private function createEvent($object, Type $type)
    {
        return new PreSerializeEvent($this->visitor->reveal(), $object, $type);
    }
}
