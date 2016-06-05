<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer;

use Doctrine\Common\Cache\Cache;
use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Handler\PhpCollectionHandler;
use Kcs\Serializer\Handler\PropelCollectionHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\EventDispatcher\EventDispatcher;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Naming\CamelCaseNamingStrategy;
use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;

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
    private $propertyNamingStrategy;
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
        $this->serializationVisitors = [];
        $this->deserializationVisitors = [];
    }

    public function setAnnotationReader(Reader $reader)
    {
        $this->annotationReader = $reader;

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
        $this->serializationVisitors[$format] = $visitor;

        return $this;
    }

    public function setDeserializationVisitor($format, VisitorInterface $visitor)
    {
        $this->deserializationVisitors[$format] = $visitor;

        return $this;
    }

    public function addDefaultSerializationVisitors()
    {
        $this->initializePropertyNamingStrategy();

        $this->serializationVisitors = [
            'array' => new GenericSerializationVisitor($this->propertyNamingStrategy),
            'xml' => new XmlSerializationVisitor($this->propertyNamingStrategy),
            'yml' => new YamlSerializationVisitor($this->propertyNamingStrategy),
            'json' => new JsonSerializationVisitor($this->propertyNamingStrategy),
        ];

        return $this;
    }

    public function addDefaultDeserializationVisitors()
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

    private function initializePropertyNamingStrategy()
    {
        if (null !== $this->propertyNamingStrategy) {
            return;
        }

        $this->propertyNamingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
    }
}
