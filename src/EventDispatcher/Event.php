<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher;

use Kcs\Serializer\Context;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\EventDispatcher\Event as BaseEvent;

class Event extends BaseEvent
{
    /**
     * @var Type
     */
    protected $type;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var mixed
     */
    private $data;

    public function __construct(Context $context, $data, Type $type)
    {
        $this->context = $context;
        $this->type = $type;
        $this->data = $data;
    }

    public function getVisitor(): VisitorInterface
    {
        return $this->context->visitor;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }
}
