<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher\Subscriber;

use Doctrine\Common\Persistence\Proxy;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;

class DoctrineProxySubscriber
{
    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $object = $event->getData();

        if (! $object instanceof Proxy) {
            return;
        }

        $object->__load();
        $type = $event->getType();

        if ($type->is(\get_class($object))) {
            $type->name = \get_parent_class($object);
        }
    }
}
