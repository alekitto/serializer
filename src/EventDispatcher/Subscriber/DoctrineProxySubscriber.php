<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher\Subscriber;

use Doctrine\Common\Persistence\Proxy;
use Kcs\Serializer\EventDispatcher\Events;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DoctrineProxySubscriber implements EventSubscriberInterface
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): iterable
    {
        return [
            Events::PRE_SERIALIZE => ['onPreSerialize', 20],
        ];
    }
}
