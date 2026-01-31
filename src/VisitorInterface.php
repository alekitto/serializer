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

    /**
     * Visits NULL value.
     */
    public function visitNull(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits string value.
     * Data will be converted to string. This means that a Stringable object will be
     * converted to its string representation by this method.
     */
    public function visitString(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits boolean value.
     * Data will be converted to boolean. All falsy values will be converted to false.
     */
    public function visitBoolean(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits a floating point value.
     * Integers will be converted to float.
     */
    public function visitDouble(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits a floating point value.
     * Floats will be converted (truncated) to integer.
     */
    public function visitInteger(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits an array.
     * The array will be treated as list (without keys).
     */
    public function visitArray(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits an array.
     * The array will be treated as map (with keys).
     */
    public function visitHash(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits an enum (PHP 8.1).
     */
    public function visitEnum(mixed $data, Type $type, Context $context): mixed;

    /**
     * Visits an object.
     * This will recursively visit all its properties (if not excluded).
     */
    public function visitObject(
        ClassMetadata $metadata,
        mixed $data,
        Type $type,
        Context $context,
        ObjectConstructorInterface|null $objectConstructor = null,
    ): mixed;

    /**
     * Calls a custom handler.
     */
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
    public function setNavigator(GraphNavigator|null $navigator = null): void;

    /**
     * Gets the result of the (de)serializer.
     */
    public function getResult(): mixed;
}
