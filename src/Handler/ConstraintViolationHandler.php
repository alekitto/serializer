<?php

declare(strict_types=1);

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
    /**
     * {@inheritdoc}
     */
    public static function getSubscribingMethods(): iterable
    {
        return [
            [
                'direction' => Direction::DIRECTION_SERIALIZATION,
                'type' => ConstraintViolationList::class,
                'method' => 'serializeList',
            ],
            [
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
