<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Naming\PropertyNamingStrategyInterface;
use Kcs\Serializer\Type\Type;

abstract class AbstractVisitor implements VisitorInterface
{
    public function __construct(protected PropertyNamingStrategyInterface $namingStrategy)
    {
        $this->setNavigator(null);
    }

    public function getNamingStrategy(): PropertyNamingStrategyInterface
    {
        return $this->namingStrategy;
    }

    public function prepare(mixed $data): mixed
    {
        return $data;
    }

    public function visitCustom(callable $handler, mixed $data, Type $type, Context $context): mixed
    {
        return $handler($this, $data, $type, $context);
    }

    protected function getElementType(Type $type): Type|null
    {
        if ($type->countParams() === 0) {
            return null;
        }

        $params = $type->getParams();
        if (isset($params[1]) && $params[1] instanceof Type) {
            return $params[1];
        }

        return $params[0];
    }
}
