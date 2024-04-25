Handlers
========

Introduction
------------
Handlers allow you to change the serialization, or deserialization process for a single type/format combination.

Simple Callables
----------------
You can register simple callables on the builder object:

Callback handlers receive four arguments: the visitor, the data, the type and the context.

```php
$builder
    ->configureHandlers(function (\Kcs\Serializer\Handler\HandlerRegistry $registry) {
        $registry->registerHandler(\Kcs\Serializer\Direction::Serialization, 'MyObject',
            function (\Kcs\Serializer\VisitorInterface $visitor, MyObject $obj, \Kcs\Serializer\Type\Type $type, \Kcs\Serializer\Context $context) {
                return $visitor->visitString($obj->getName(), $type, $context);
            }
        );
    })
;
```

Serialization/Deserialization handlers
--------------------------------------
Serialization and deserialization handlers are the easiest way to customize the serialization and deserialization process.
This is the recommended way to customize serialization of custom objects.

The type handled must be exposed via a static `getType` method which have to return the type name as string.  
The type name is usually the FQCN of the object to be serialized.

The customization should be implemented in the `serialize` (or `deserialize`).  
While serializing a normalized (array or scalar) version of the object should be returned.
In case of deserialization, the deserialized representation of the data should be returned (normally an object).

```php
use Kcs\Serializer\Handler\SerializationHandlerInterface;

class MyObjectHandler implements SerializationHandlerInterface
{
    /**
     * @inheritDoc
     */
    public static function getType(): string
    {
        return MyObject::class;
    }
    
    /**
     * @inheritDoc
     */
    public function serialize($data)
    {
        if ($data === null) {
            return null;
        }

        return $data->getName();
    }
}
```

You can register handlers via builder object:

```php
$builder
    ->configureHandlers(function (\Kcs\Serializer\Handler\HandlerRegistry $registry) {
        $registry->registerSerializationHandler(new MyObjectHandler());
    })
;
```


Subscribing Handlers
--------------------
Subscribing handlers are the most advanced way to customize the (de)serialization of objects.

A subscribing handler must expose a static `getSubscribingMethods` which yield the configuration for custom
serialization/deserialization.

Every yielded item must be an array containing three properties:

- direction: one of `Direction::DIRECTION_SERIALIZATION` or `Direction::DIRECTION_DESERIALIZATION`
- type: the type name. Could be a FQCN or a custom type string
- method: the method in the same class to call if type matches

The receiving method will receive four arguments: the visitor, the data, the type object and the context.

You should use the visitor methods (ex: visitString, visitHash, visitNull, etc) to correctly serialize 
or deserialize the data.

```php
use Kcs\Serializer\Direction;
use Kcs\Serializer\Handler\SubscribingHandlerInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class MyHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods(): iterable
    {
        yield [
            'direction' => Direction::Serialization,
            'type' => \DateTime::class,
            'method' => 'serializeDateTimeToJson',
        ];
    }

    public function serializeDateTimeToJson(VisitorInterface $visitor, \DateTime $date, Type $type, Context $context)
    {
        return $visitor->visitString($date->format($type['params'][0]), $type, $context);
    }
}
```

You can register subscribing handlers via builder object:

```php
$builder
    ->configureHandlers(function (\Kcs\Serializer\Handler\HandlerRegistry $registry) {
        $registry->registerSubscribingHandler(new MyHandler());
    })
;
```
