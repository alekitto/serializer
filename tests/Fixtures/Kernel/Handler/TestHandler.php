<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Kernel\Handler;

use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;

class TestHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        yield [
            'direction' => Direction::DIRECTION_SERIALIZATION,
            'type' => 'TestObject',
            'method' => 'serialize',
        ];
    }

    public function serialize()
    {
    }
}
