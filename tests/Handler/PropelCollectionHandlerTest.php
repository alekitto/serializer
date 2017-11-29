<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Handler;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Handler\PropelCollectionHandler;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Type\Type;
use Prophecy\Argument;

class PropelCollectionHandlerTest extends AbstractHandlerTest
{
    public function testSerializeShouldReturnStringRepresentation()
    {
        $data = [new TestSubject('lolo'), new TestSubject('pepe')];

        $collection = new \PropelObjectCollection();
        $collection->setData($data);

        $this->visitor->visitArray($data, Argument::type(Type::class), $this->context)->shouldBeCalled();
        $this->handler->serializeCollection($this->visitor->reveal(), $collection, Type::parse(\PropelObjectCollection::class), $this->context->reveal());
    }

    protected function createHandler(): SubscribingHandlerInterface
    {
        return new PropelCollectionHandler();
    }
}

/**
 * @AccessType("property")
 */
class TestSubject
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
