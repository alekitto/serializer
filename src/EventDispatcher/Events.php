<?php declare(strict_types=1);

namespace Kcs\Serializer\EventDispatcher;

abstract class Events
{
    const PRE_SERIALIZE = 'serializer.pre_serialize';
    const POST_SERIALIZE = 'serializer.post_serialize';
    const PRE_DESERIALIZE = 'serializer.pre_deserialize';
    const POST_DESERIALIZE = 'serializer.post_deserialize';

    final private function __construct()
    {
    }
}
