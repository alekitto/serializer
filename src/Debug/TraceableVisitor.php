<?php

declare(strict_types=1);

namespace Kcs\Serializer\Debug;

use Closure;
use Kcs\Serializer\Construction\ObjectConstructorInterface;
use Kcs\Serializer\Context;
use Kcs\Serializer\GraphNavigator;
use Kcs\Serializer\Metadata\ClassMetadata;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Psr\Log\LoggerInterface;
use ReflectionFunction;

use function array_unshift;
use function get_debug_type;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

class TraceableVisitor implements VisitorInterface
{
    public function __construct(private VisitorInterface $visitor, private LoggerInterface $logger)
    {
    }

    public function prepare(mixed $data): mixed
    {
        $this->logger->debug(
            'Preparing data...',
            ['data' => $data],
        );

        return $this->visitor->prepare($data);
    }

    public function visitNull(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting null at path {path}',
            [
                'path' => $this->getPath($context),
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitNull($data, $type, $context);
    }

    public function visitString(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting string at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitString($data, $type, $context);
    }

    public function visitBoolean(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting boolean at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitBoolean($data, $type, $context);
    }

    public function visitDouble(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting float/double at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitDouble($data, $type, $context);
    }

    public function visitInteger(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting integer at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitInteger($data, $type, $context);
    }

    public function visitArray(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting array at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitArray($data, $type, $context);
    }

    public function visitHash(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Visiting hashmap at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitHash($data, $type, $context);
    }

    public function visitEnum(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'Start visiting enum at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitEnum($data, $type, $context);
    }

    public function visitObject(ClassMetadata $metadata, mixed $data, Type $type, Context $context, ObjectConstructorInterface|null $objectConstructor = null): mixed
    {
        $this->logger->debug(
            'Start visiting object at path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitObject($metadata, $data, $type, $context, $objectConstructor);
    }

    public function visitCustom(callable $handler, mixed $data, Type $type, Context $context): mixed
    {
        if (is_array($handler)) {
            $handlerRepresentation = (is_string($handler[0]) ? $handler[0] : get_debug_type($handler[0])) . '::' . $handler[1];
        } elseif (is_string($handler)) {
            $handlerRepresentation = $handler;
        } elseif ($handler instanceof Closure) {
            $reflection = new ReflectionFunction($handler);
            $handlerRepresentation = sprintf('Closure (file: %s, line: %d)', $reflection->getFileName(), $reflection->getStartLine());
        } else {
            $handlerRepresentation = get_debug_type($handler);
        }

        $this->logger->debug(
            'Calling custom handler "{handler}" at path {path}',
            [
                'handler' => $handlerRepresentation,
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->visitCustom($handler, $data, $type, $context);
    }

    public function startVisiting(mixed &$data, Type $type, Context $context): void
    {
        $this->visitor->startVisiting($data, $type, $context);
    }

    public function endVisiting(mixed $data, Type $type, Context $context): mixed
    {
        $this->logger->debug(
            'End visiting path {path}',
            [
                'path' => $this->getPath($context),
                'data' => $data,
                'type' => $type->jsonSerialize(),
            ],
        );

        return $this->visitor->endVisiting($data, $type, $context);
    }

    public function setNavigator(GraphNavigator|null $navigator = null): void
    {
        $this->visitor->setNavigator($navigator);
    }

    public function getResult(): mixed
    {
        return $this->visitor->getResult();
    }

    private function getPath(Context $context): string
    {
        $path = $context->getMetadataStack()->getPath();
        array_unshift($path, '<root>');

        return implode(' -> ', $path);
    }
}
