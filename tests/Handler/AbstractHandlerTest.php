<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\VisitorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

abstract class AbstractHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var VisitorInterface|ObjectProphecy
     */
    protected ObjectProphecy $visitor;

    /**
     * @var Context|ObjectProphecy
     */
    protected ObjectProphecy $context;
    protected SubscribingHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->visitor = $this->prophesize(VisitorInterface::class);
        $this->context = $this->prophesize(Context::class);

        $this->handler = $this->createHandler();
    }

    abstract protected function createHandler(): SubscribingHandlerInterface;
}
