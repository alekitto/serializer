<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\Handler\ConstraintViolationHandler;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\FormErrorHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\PhpCollectionHandler;
use Kcs\Serializer\Handler\PropelCollectionHandler;
use Kcs\Serializer\Handler\UuidInterfaceHandler;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\AttributesLoader;
use Kcs\Serializer\Metadata\Loader\ReflectionLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
use Psr\Cache\CacheItemPoolInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcher;

use const PHP_VERSION_ID;

/**
 * Builder for serializer instances.
 *
 * This object makes serializer construction a breeze for projects that do not use
 * any special dependency injection container.
 */
class SerializerBuilder
{
    private HandlerRegistry $handlerRegistry;
    private bool $handlersConfigured = false;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private bool $listenersConfigured = false;
    private ?ObjectConstructorInterface $objectConstructor = null;

    /** @var VisitorInterface[] */
    private array $serializationVisitors;

    /** @var VisitorInterface[] */
    private array $deserializationVisitors;

    private PropertyNamingStrategyInterface $propertyNamingStrategy;
    private ?CacheItemPoolInterface $cache = null;
    private ?Reader $annotationReader = null;
    private ?LoaderInterface $metadataLoader = null;

    public static function create(): self
    {
        return new static();
    }

    final public function __construct()
    {
        $this->handlerRegistry = new HandlerRegistry();
        $this->serializationVisitors = [];
        $this->deserializationVisitors = [];
    }

    public function setAnnotationReader(Reader $reader): self
    {
        $this->annotationReader = $reader;

        return $this;
    }

    public function setCache(?CacheItemPoolInterface $cache = null): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function setMetadataLoader(LoaderInterface $metadataLoader): self
    {
        $this->metadataLoader = $metadataLoader;

        return $this;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function addDefaultHandlers(): self
    {
        $this->handlersConfigured = true;
        $this->handlerRegistry->registerSubscribingHandler(new DateHandler());
        $this->handlerRegistry->registerSubscribingHandler(new ConstraintViolationHandler());
        $this->handlerRegistry->registerSubscribingHandler(new FormErrorHandler());
        $this->handlerRegistry->registerSubscribingHandler(new PhpCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new PropelCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new UuidInterfaceHandler());

        return $this;
    }

    public function configureHandlers(Closure $closure): self
    {
        $this->handlersConfigured = true;
        $closure($this->handlerRegistry);

        return $this;
    }

    public function addDefaultListeners(): self
    {
        $this->listenersConfigured = true;

        if ($this->eventDispatcher instanceof SymfonyEventDispatcher) {
            $this->eventDispatcher->addListener(PreSerializeEvent::class, [new DoctrineProxySubscriber(), 'onPreSerialize'], 20);
        }

        return $this;
    }

    public function configureListeners(Closure $closure): self
    {
        $this->listenersConfigured = true;
        $closure($this->eventDispatcher);

        return $this;
    }

    public function setObjectConstructor(ObjectConstructorInterface $constructor): self
    {
        $this->objectConstructor = $constructor;

        return $this;
    }

    public function setPropertyNamingStrategy(PropertyNamingStrategyInterface $propertyNamingStrategy): self
    {
        $this->propertyNamingStrategy = $propertyNamingStrategy;

        return $this;
    }

    public function setSerializationVisitor(string $format, VisitorInterface $visitor): self
    {
        $this->serializationVisitors[$format] = $visitor;

        return $this;
    }

    public function setDeserializationVisitor(string $format, VisitorInterface $visitor): self
    {
        $this->deserializationVisitors[$format] = $visitor;

        return $this;
    }

    public function addDefaultSerializationVisitors(): self
    {
        $this->initializePropertyNamingStrategy();

        $this->serializationVisitors = [
            'array' => new GenericSerializationVisitor($this->propertyNamingStrategy),
            'xml' => new XmlSerializationVisitor($this->propertyNamingStrategy),
            'yml' => new YamlSerializationVisitor($this->propertyNamingStrategy),
            'json' => new JsonSerializationVisitor($this->propertyNamingStrategy),
            'csv' => new CsvSerializationVisitor($this->propertyNamingStrategy),
        ];

        return $this;
    }

    public function addDefaultDeserializationVisitors(): self
    {
        $this->initializePropertyNamingStrategy();

        $this->deserializationVisitors = [
            'array' => new GenericDeserializationVisitor($this->propertyNamingStrategy),
            'xml' => new XmlDeserializationVisitor($this->propertyNamingStrategy),
            'yml' => new YamlDeserializationVisitor($this->propertyNamingStrategy),
            'json' => new JsonDeserializationVisitor($this->propertyNamingStrategy),
        ];

        return $this;
    }

    public function build(): SerializerInterface
    {
        $metadataLoader = $this->metadataLoader;
        if ($metadataLoader === null) {
            $annotationReader = $this->annotationReader ?: new AnnotationReader();

            $metadataLoader = new AnnotationLoader();
            $metadataLoader->setReader($annotationReader);

            if (PHP_VERSION_ID >= 80000) {
                $metadataLoader = new AttributesLoader($metadataLoader);
            }
        }

        $metadataLoader = new ReflectionLoader($metadataLoader);
        $metadataFactory = new MetadataFactory($metadataLoader, $this->eventDispatcher, $this->cache);

        if (! $this->handlersConfigured) {
            $this->addDefaultHandlers();
        }

        if (! $this->listenersConfigured) {
            $this->addDefaultListeners();
        }

        if (empty($this->serializationVisitors) && empty($this->deserializationVisitors)) {
            $this->addDefaultSerializationVisitors();
            $this->addDefaultDeserializationVisitors();
        }

        return new Serializer(
            $metadataFactory,
            $this->handlerRegistry,
            $this->objectConstructor ?: new InitializedObjectConstructor(new UnserializeObjectConstructor()),
            $this->serializationVisitors,
            $this->deserializationVisitors,
            $this->eventDispatcher
        );
    }

    private function initializePropertyNamingStrategy(): void
    {
        if (isset($this->propertyNamingStrategy)) {
            return;
        }

        $this->propertyNamingStrategy = new SerializedNameAnnotationStrategy(new UnderscoreNamingStrategy());
    }
}
