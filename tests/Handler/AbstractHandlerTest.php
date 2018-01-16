<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

abstract class AbstractHandlerTest extends TestCase
{
    /**
     * @var SubscribingHandlerInterface
     */
    protected $handler;

    /**
     * @var VisitorInterface|ObjectProphecy
     */
    protected $visitor;

    /**
     * @var Context|ObjectProphecy
     */
    protected $context;

    public function setUp()
    {
        $this->visitor = $this->prophesize(VisitorInterface::class);
        $this->context = $this->prophesize(Context::class);

        $this->handler = $this->createHandler();
    }

    abstract protected function createHandler(): SubscribingHandlerInterface;
}
