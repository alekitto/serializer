Configuration
=============

Constructing a Serializer
-------------------------

This library provides a special builder object which makes constructing serializer instances a breeze in any PHP
project. In its shortest version, it's just a single line of code:

```php
$serializer = Kcs\Serializer\SerializerBuilder::create()->build();
```

This serializer is fully functional, but you might want to tweak it a bit for example to configure a cache directory.

Configuring a Cache Directory
-----------------------------
The serializer collects metadata about your objects from various sources such as annotations, attributes or yaml mapping files.
In order to make this process as efficient as possible, it is encouraged to let the serializer cache that information. For
that, you can configure a cache:

```php
$cache = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(directory: 'cache/dir');
$builder = new \Kcs\Serializer\SerializerBuilder();

$serializer =
    Kcs\Serializer\SerializerBuilder::create()
    ->setCache($cache)
    ->build();
```

You can use any implementation of `Psr\Cache\CacheItemPoolInterface`, but it is discouraged to set a cache in development environment,
as you may not notice changes you've done to your metadata.

Use events
----------

If you want to listen on pre/post (de)serialize events, you need to set an event dispatcher into the serializer.

```php
$eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
$builder = new \Kcs\Serializer\SerializerBuilder();

$serializer =
    Kcs\Serializer\SerializerBuilder::create()
    ->setEventDispatcher($eventDispatcher)
    ->build();
```

You can use any instance of `Psr\EventDispatcher\EventDispatcherInterface`.
See [Event system](./event_system.md) section for more information.

Adding Custom Handlers
----------------------

If you have created custom handlers, you can add them to the serializer easily:

```php
$serializer = Kcs\Serializer\SerializerBuilder::create()
    ->addDefaultHandlers()
    ->configureHandlers(function (\Kcs\Serializer\Handler\HandlerRegistry $registry) {
        $registry->registerHandler(\Kcs\Serializer\Direction::DIRECTION_SERIALIZATION, MyObject::class,
            fn ($visitor, MyObject $obj, array $type) => $visitor->visitString($obj->getName())
        );
    })
    ->build();
```

For more complex handlers, it is advisable to extract them to dedicated classes,
see [handlers documentation](./handlers.md).

Other options
-------------

There are many other options on serializer builder.
In the vast majority of the cases you just want the builder to configure the serializer with default values.  
The other options available are listed here for reference.

```php
$builder = new \Kcs\Serializer\SerializerBuilder();

$serializer = Kcs\Serializer\SerializerBuilder::create()
    ->setAnnotationReader($reader)          // Override default annotation reader
    ->setMetadataLoader($metadataLoader)    // An implementation of \Kcs\Metadata\Loader\LoaderInterface that loads class metadata
    ->setObjectConstructor($constructor)    // To customize object construction process on deserialization
    ->setPropertyNamingStrategy($namingStrategy)    // By default "underscore" naming strategy is set. You can use one of builtin naming strategy or implement your own
    ->addDefaultSerializationVisitors()     // Add defaults serialization visitors (array, xml, yml, json and csv)
    ->addDefaultDeserializationVisitors()
    ->setSerializationVisitor('format', $serializationVisitor)  // Add or override serialization visitor for the given format. Can be used to override builtin visitors or add a custom format to the serializer
    ->setDeserializationVisitor('format', $deserializationVisitor)  // Same as above
    ->build();
```
