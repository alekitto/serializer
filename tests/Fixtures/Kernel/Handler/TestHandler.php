<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Kernel\Handler;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;

class TestHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): iterable
    {
        yield [
            'direction' => Direction::Serialization,
            'type' => 'TestObject',
            'method' => 'serialize',
        ];
    }

    public function serialize()
    {
    }
}
