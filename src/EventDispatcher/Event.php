<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher;

use Kcs\Serializer\Context;
use Kcs\Serializer\Type\Type;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    protected $type;
    private $context;
    private $data;

    public function __construct(Context $context, $data, Type $type)
    {
        $this->context = $context;
        $this->type = $type;
        $this->data = $data;
    }

    public function getVisitor()
    {
        return $this->context->getVisitor();
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(Type $type)
    {
        $this->type = $type;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
}
