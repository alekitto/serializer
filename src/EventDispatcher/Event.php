<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher;

use Kcs\Serializer\Context;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class Event implements StoppableEventInterface
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

    /**
     * @var bool
     */
    private $propagationStopped = false;

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

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
