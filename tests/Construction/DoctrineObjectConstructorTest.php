<?php declare(strict_types=1);
/*
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Construction;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Common\Persistence\ObjectManager;
use Kcs\Serializer\Construction\DoctrineObjectConstructor;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;

class DoctrineObjectConstructorTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorUseFallbackIfNoManagerMatch()
    {
        $fallbackConstructor = $this->createMock(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor);

        $registry1 = $this->createMock(ManagerRegistry::class);
        $registry1->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $registry2 = $this->createMock(ManagerRegistry::class);
        $registry2->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $objectConstructor
            ->addManagerRegistry($registry1)
            ->addManagerRegistry($registry2)
        ;

        $fallbackConstructor->expects($this->once())
            ->method('construct');

        $visitor = $this->createMock(VisitorInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $context = $this->createMock(DeserializationContext::class);
        $objectConstructor->construct($visitor, $metadata, [], new Type('EntityObject'), $context);
    }

    public function testConstructorUseFallbackIfObjectIsTransient()
    {
        $fallbackConstructor = $this->createMock(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor);

        $registry1 = $this->createMock(ManagerRegistry::class);
        $registry1->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with($this->equalTo('EntityObject'))
            ->willReturn(true);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $registry2 = $this->createMock(ManagerRegistry::class);
        $registry2->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $objectConstructor
            ->addManagerRegistry($registry1)
            ->addManagerRegistry($registry2)
        ;

        $fallbackConstructor->expects($this->once())
            ->method('construct');

        $visitor = $this->createMock(VisitorInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->method('getName')
            ->willReturn('EntityObject');

        $context = $this->createMock(DeserializationContext::class);
        $objectConstructor->construct($visitor, $metadata, [], new Type('EntityObject'), $context);
    }

    public function testConstructorUseFallbackIfFindReturnsNull()
    {
        $fallbackConstructor = $this->createMock(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor);

        $registry1 = $this->createMock(ManagerRegistry::class);
        $registry1->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with($this->equalTo('EntityObject'))
            ->willReturn(false);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $objectManager->expects($this->once())
            ->method('find')
            ->with($this->equalTo('EntityObject'), $this->equalTo(4))
            ->willReturn(null);

        $registry2 = $this->createMock(ManagerRegistry::class);
        $registry2->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $objectConstructor
            ->addManagerRegistry($registry1)
            ->addManagerRegistry($registry2)
        ;

        $fallbackConstructor->expects($this->once())
            ->method('construct');

        $visitor = $this->createMock(VisitorInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->method('getName')
            ->willReturn('EntityObject');

        $context = $this->createMock(DeserializationContext::class);
        $objectConstructor->construct($visitor, $metadata, 4, new Type('EntityObject'), $context);
    }

    public function testConstructorUseFallbackIfDataDoesNotContainsIdentifier()
    {
        $fallbackConstructor = $this->createMock(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor);

        $registry1 = $this->createMock(ManagerRegistry::class);
        $registry1->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn(null);

        $classMetadata = $this->createMock(\Doctrine\Common\Persistence\Mapping\ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('isTransient')
            ->with($this->equalTo('EntityObject'))
            ->willReturn(false);

        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $objectManager->expects($this->never())
            ->method('find');
        $objectManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($this->equalTo('EntityObject'))
            ->willReturn($classMetadata);

        $registry2 = $this->createMock(ManagerRegistry::class);
        $registry2->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($objectManager);

        $objectConstructor
            ->addManagerRegistry($registry1)
            ->addManagerRegistry($registry2)
        ;

        $fallbackConstructor->expects($this->once())
            ->method('construct');

        $visitor = $this->createMock(VisitorInterface::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->method('getName')
            ->willReturn('EntityObject');

        $context = $this->createMock(DeserializationContext::class);
        $objectConstructor->construct($visitor, $metadata, ['field' => 'text'], new Type('EntityObject'), $context);
    }
}
