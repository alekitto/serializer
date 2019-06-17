Event System
============

The serializer dispatches different events during the serialization, and
deserialization process which you can use to hook in and alter the default
behavior.

Register an Event Listener, or Subscriber
-----------------------------------------
The difference between listeners, and subscribers is that listener do not know to which events they listen
while subscribers contain that information. Thus, subscribers are easier to share, and re-use. Listeners
on the other hand, can be simple callables and do not require a dedicated class.

.. code-block :: php

    use Kcs\Serializer\EventDispatcher\Events;
    use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;

    class MyEventSubscriber implements EventSubscriberInterface
    {

        /**
         * {@inheritdoc}
         */
        public static function getSubscribedEvents()
        {
            return [
                PreSerializeEvent::class => ['onPreSerialize', 20],
            ];
        }

        public function onPreSerialize(PreSerializeEvent $event)
        {
            // do something
        }
    }

    $builder
        ->configureListeners(function(Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher) {
            $dispatcher->addListener(
                Kcs\Serializer\EventDispatcher\PreSerializeEvent::class,
                function(Kcs\Serializer\EventDispatcher\PreSerializeEvent $event) {
                    // do something
                },
                $priority = 10
            );

            $dispatcher->addSubscriber(new MyEventSubscriber());
        })
    ;

Events
------

Kcs\Serializer\EventDispatcher\PreSerializeEvent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is dispatched before a type is visited. You have access to the visitor,
data, and type. Listeners may modify the type that is being used for
serialization.

**Event Object**: ``Kcs\Serializer\EventDispatcher\PreSerializeEvent``

Kcs\Serializer\EventDispatcher\PostSerializeEvent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is dispatched right before a type is left. You can for example use this
to add additional data for an object that you normally do not save inside
objects such as links.

**Event Object**: ``Kcs\Serializer\EventDispatcher\PostSerializeEvent``

Kcs\Serializer\EventDispatcher\PreDeserializeEvent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is dispatched before an object is deserialized. You can use this to
modify submitted data, or modify the type that is being used for deserialization.

**Event Object**: ``Kcs\Serializer\EventDispatcher\PreDeserializeEvent``

Kcs\Serializer\EventDispatcher\PostDeserializeEvent
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This is dispatched after a type is processed. You can use it to normalize
submitted data if you require external services for example, or also to
perform validation of the submitted data.

**Event Object**: ``Kcs\Serializer\EventDispatcher\PostDeserializeEvent``
