<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;

/**
 * Interface for visitors.
 *
 * This contains the minimal set of values that must be supported for any
 * output format.
 */
interface VisitorInterface
{
    /**
     * Allows visitors to convert the input data to a different representation
     * before the actual serialization/deserialization process starts.
     */
    public function prepare(mixed $data): mixed;

    public function visitNull(mixed $data, Type $type, Context $context): mixed;

    public function visitString(mixed $data, Type $type, Context $context): mixed;

    public function visitBoolean(mixed $data, Type $type, Context $context): mixed;

    public function visitDouble(mixed $data, Type $type, Context $context): mixed;

    public function visitInteger(mixed $data, Type $type, Context $context): mixed;

    public function visitArray(mixed $data, Type $type, Context $context): mixed;

    // public function visitEnum(ClassMetadata $metadata, mixed $data, Type $type, Context $context): mixed;

    public function visitHash(mixed $data, Type $type, Context $context): mixed;

    public function visitObject(
        ClassMetadata $metadata,
        mixed $data,
        Type $type,
        Context $context,
        ?ObjectConstructorInterface $objectConstructor = null
    ): mixed;

    public function visitCustom(callable $handler, mixed $data, Type $type, Context $context): mixed;

    /**
     * Called before the properties of the object are being visited.
     */
    public function startVisiting(mixed &$data, Type $type, Context $context): void;

    /**
     * Called after all properties of the object have been visited.
     */
    public function endVisiting(mixed $data, Type $type, Context $context): mixed;

    /**
     * Called before serialization/deserialization starts.
     */
    public function setNavigator(?GraphNavigator $navigator = null): void;

    public function getResult(): mixed;
}
