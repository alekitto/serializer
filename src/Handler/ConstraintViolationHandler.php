<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
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

namespace Kcs\Serializer\Handler;

use Kcs\Serializer\Context;
use Kcs\Serializer\Direction;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\Util\SerializableConstraintViolation;
use Kcs\Serializer\Util\SerializableConstraintViolationList;
use Kcs\Serializer\VisitorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class ConstraintViolationHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return [
            [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => ConstraintViolationList::class,
                'method' => 'serializeList',
            ], [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => ConstraintViolation::class,
                'method' => 'serializeViolation',
            ],
        ];
    }

    public function serializeList(VisitorInterface $visitor, ConstraintViolationList $list, Type $type, Context $context)
    {
        $serializableList = new SerializableConstraintViolationList($list);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableList);

        return $visitor->visitObject($metadata, $serializableList, $type, $context);
    }

    public function serializeViolation(VisitorInterface $visitor, ConstraintViolation $violation, Type $type, Context $context)
    {
        $serializableViolation = new SerializableConstraintViolation($violation);
        $metadata = $context->getMetadataFactory()->getMetadataFor($serializableViolation);

        return $visitor->visitObject($metadata, $serializableViolation, $type, $context);
    }
}
