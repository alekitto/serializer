<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer;

use Doctrine\Common\Cache\Cache;
use JMS\Serializer\Handler\PhpCollectionHandler;
use JMS\Serializer\Handler\PropelCollectionHandler;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Construction\UnserializeObjectConstructor;
use JMS\Serializer\Metadata\Loader\AnnotationLoader;
use JMS\Serializer\Metadata\MetadataFactory;
use Kcs\Metadata\Loader\LoaderInterface;
use PhpCollection\Map;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\ArrayCollectionHandler;
use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Exception\InvalidArgumentException;

/**
 * Builder for serializer instances.
 *
 * This object makes serializer construction a breeze for projects that do not use
 * any special dependency injection container.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SerializerBuilder
{
    private $handlerRegistry;
    private $handlersConfigured = false;
    private $eventDispatcher;
    private $listenersConfigured = false;
    private $objectConstructor;
    private $serializationVisitors;
    private $deserializationVisitors;
    private $visitorsAdded = false;
    private $propertyNamingStrategy;
    private $debug = false;
    private $cache = null;
    private $annotationReader;
    private $metadataLoader = null;

    public static function create()
    {
        return new static();
    }

    public function __construct()
    {
        $this->handlerRegistry = new HandlerRegistry();
        $this->eventDispatcher = new EventDispatcher();
        $this->serializationVisitors = new Map();
        $this->deserializationVisitors = new Map();
    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->annotationReader = $reader;

        return $this;
    }

    public function setDebug($bool)
    {
        $this->debug = (boolean) $bool;

        return $this;
    }

    public function setCache(Cache $cache = null)
    {
        $this->cache = $cache;
        return $this;
    }

    public function setMetadataLoader(LoaderInterface $metadataLoader)
    {
        $this->metadataLoader = $metadataLoader;
        return $this;
    }

    public function addDefaultHandlers()
    {
        $this->handlersConfigured = true;
        $this->handlerRegistry->registerSubscribingHandler(new DateHandler());
        $this->handlerRegistry->registerSubscribingHandler(new PhpCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new PropelCollectionHandler());

        return $this;
    }

    public function configureHandlers(\Closure $closure)
    {
        $this->handlersConfigured = true;
        $closure($this->handlerRegistry);

        return $this;
    }

    public function addDefaultListeners()
    {
        $this->listenersConfigured = true;
        $this->eventDispatcher->addSubscriber(new DoctrineProxySubscriber());

        return $this;
    }

    public function configureListeners(\Closure $closure)
    {
        $this->listenersConfigured = true;
        $closure($this->eventDispatcher);

        return $this;
    }

    public function setObjectConstructor(ObjectConstructorInterface $constructor)
    {
        $this->objectConstructor = $constructor;

        return $this;
    }

    public function setPropertyNamingStrategy(PropertyNamingStrategyInterface $propertyNamingStrategy)
    {
        $this->propertyNamingStrategy = $propertyNamingStrategy;

        return $this;
    }

    public function setSerializationVisitor($format, VisitorInterface $visitor)
    {
        $this->visitorsAdded = true;
        $this->serializationVisitors->set($format, $visitor);

        return $this;
    }

    public function setDeserializationVisitor($format, VisitorInterface $visitor)
    {
        $this->visitorsAdded = true;
        $this->deserializationVisitors->set($format, $visitor);

        return $this;
    }

    public function addDefaultSerializationVisitors()
    {
        $this->initializePropertyNamingStrategy();

        $this->visitorsAdded = true;
        $this->serializationVisitors->setAll(array(
            'xml' => new XmlSerializationVisitor($this->propertyNamingStrategy),
            'yml' => new YamlSerializationVisitor($this->propertyNamingStrategy),
            'json' => new JsonSerializationVisitor($this->propertyNamingStrategy),
        ));

        return $this;
    }

    public function addDefaultDeserializationVisitors()
    {
        $this->initializePropertyNamingStrategy();

        $this->visitorsAdded = true;
        $this->deserializationVisitors->setAll(array(
            'xml' => new XmlDeserializationVisitor($this->propertyNamingStrategy),
            'json' => new JsonDeserializationVisitor($this->propertyNamingStrategy),
        ));

        return $this;
    }

    public function build()
    {
        $annotationReader = $this->annotationReader;
        if (null === $annotationReader) {
            $annotationReader = new AnnotationReader();
        }

        $metadataLoader = $this->metadataLoader;
        if (null === $metadataLoader) {
            $metadataLoader = new AnnotationLoader($annotationReader);
        }

        $metadataFactory = new MetadataFactory($metadataLoader, null, $this->cache);

        if ( ! $this->handlersConfigured) {
            $this->addDefaultHandlers();
        }

        if ( ! $this->listenersConfigured) {
            $this->addDefaultListeners();
        }

        if ( ! $this->visitorsAdded) {
            $this->addDefaultSerializationVisitors();
            $this->addDefaultDeserializationVisitors();
        }

        return new Serializer(
            $metadataFactory,
            $this->handlerRegistry,
            $this->objectConstructor ?: new UnserializeObjectConstructor(),
            $this->serializationVisitors,
            $this->deserializationVisitors,
            $this->eventDispatcher
        );
    }

    private function initializePropertyNamingStrategy()
    {
        if (null !== $this->propertyNamingStrategy) {
            return;
        }

        $this->propertyNamingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
    }
}
