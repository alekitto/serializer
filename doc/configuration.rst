Configuration
=============

If using this as standalone library you should initialize doctrine annotations library::

    Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');

Constructing a Serializer
-------------------------

This library provides a special builder object which makes constructing serializer instances a breeze in any PHP
project. In its shortest version, it's just a single line of code::

    $serializer = Kcs\Serializer\SerializerBuilder::create()->build();

This serializer is fully functional, but you might want to tweak it a bit for example to configure a cache directory.

Configuring a Cache Directory
-----------------------------
The serializer collects several metadata about your objects from various sources such as YML, XML, or annotations. In
order to make this process as efficient as possible, it is encourage to let the serializer cache that information. For
that, you can configure a cache::

    $cache = new Doctrine\Common\Cache\FilesystemCache('cache/dir');
    $builder = new Kcs\Serializer\SerializerBuilder();

    $serializer =
        Kcs\Serializer\SerializerBuilder::create()
        ->setCache($cache)
        ->build();

You can use any implementation of the doctrine cache, but it is discouraged to set a cache in development environment,
as you may not notice changes you've done to your metadata.

Adding Custom Handlers
----------------------
If you have created custom handlers, you can add them to the serializer easily::

    $serializer =
        Kcs\Serializer\SerializerBuilder::create()
            ->addDefaultHandlers()
            ->configureHandlers(function(Kcs\Serializer\Handler\HandlerRegistry $registry) {
                $registry->registerHandler('serialization', 'MyObject', 'json',
                    function($visitor, MyObject $obj, array $type) {
                        return $obj->getName();
                    }
                );
            })
            ->build();

For more complex handlers, it is advisable to extract them to dedicated classes,
see :doc:`handlers documentation <handlers>`.
