<?php

declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Type\Type;

interface IterableSerializationVisitorInterface
{
    /** @param iterable<mixed, mixed> $data */
    public function visitIterable(iterable $data, Type $type, Context $context): mixed;
}
