<?php declare(strict_types=1);

/*
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class DoctrineObjectConstructorTest extends TestCase
{
    public function testConstructorUseFallbackIfNoManagerMatch()
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();
        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn();

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), [], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, [], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfObjectIsTransient()
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(true);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), [], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, [], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfFindReturnsNull()
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->find('EntityObject', 4)->willReturn();
        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(false);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), 4, new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, 4, Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }

    public function testConstructorUseFallbackIfDataDoesNotContainsIdentifier()
    {
        $fallbackConstructor = $this->prophesize(ObjectConstructorInterface::class);
        $objectConstructor = new DoctrineObjectConstructor($fallbackConstructor->reveal());

        $registry1 = $this->prophesize(ManagerRegistry::class);
        $registry1->getManagerForClass(Argument::any())->willReturn();

        $registry2 = $this->prophesize(ManagerRegistry::class);
        $registry2->getManagerForClass(Argument::any())->willReturn($objectManager = $this->prophesize(ObjectManager::class));

        $objectManager->find(Argument::cetera())->shouldNotBeCalled();
        $objectManager->getMetadataFactory()->willReturn($metadataFactory = $this->prophesize(ClassMetadataFactory::class));
        $metadataFactory->isTransient('EntityObject')->willReturn(false);

        $objectManager->getClassMetadata('EntityObject')
            ->willReturn($classMetadata = $this->prophesize(\Doctrine\Common\Persistence\Mapping\ClassMetadata::class));
        $classMetadata->getIdentifierFieldNames()->willReturn(['id']);

        $objectConstructor
            ->addManagerRegistry($registry1->reveal())
            ->addManagerRegistry($registry2->reveal())
        ;

        $visitor = $this->prophesize(VisitorInterface::class);

        $metadata = $this->prophesize(ClassMetadata::class);
        $metadata->getName()->willReturn('EntityObject');

        $context = $this->prophesize(DeserializationContext::class);
        $objectConstructor->construct($visitor->reveal(), $metadata->reveal(), ['field' => 'text'], new Type('EntityObject'), $context->reveal());

        $fallbackConstructor->construct($visitor, $metadata, ['field' => 'text'], Argument::type(Type::class), $context)
            ->shouldHaveBeenCalled();
    }
}
