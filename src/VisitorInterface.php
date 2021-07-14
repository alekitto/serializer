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
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function prepare($data);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitNull($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitString($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitBoolean($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitDouble($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitInteger($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitArray($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitHash($data, Type $type, Context $context);

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitObject(
        ClassMetadata $metadata,
        $data,
        Type $type,
        Context $context,
        ?ObjectConstructorInterface $objectConstructor = null
    );

    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function visitCustom(callable $handler, $data, Type $type, Context $context);

    /**
     * Called before the properties of the object are being visited.
     *
     * @param mixed $data
     */
    public function startVisiting(&$data, Type $type, Context $context): void;

    /**
     * Called after all properties of the object have been visited.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function endVisiting($data, Type $type, Context $context);

    /**
     * Called before serialization/deserialization starts.
     */
    public function setNavigator(?GraphNavigator $navigator = null): void;

    /**
     * @return mixed
     */
    public function getResult();
}
