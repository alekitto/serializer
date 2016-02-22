<?php

namespace JMS\Serializer\Builder;

use Doctrine\Common\Annotations\Reader;
use Kcs\Metadata\Loader\LoaderInterface;

class CallbackDriverFactory implements DriverFactoryInterface
{
    private $callback;

    /**
     * @param callable $callable
     */
    public function __construct($callable)
    {
        $this->callback = $callable;
    }

    public function createDriver(array $metadataDirs, Reader $reader)
    {
        $driver = call_user_func($this->callback, $metadataDirs, $reader);
        if ( ! $driver instanceof LoaderInterface) {
            throw new \LogicException('The callback must return an instance of LoaderInterface.');
        }

        return $driver;
    }
}
