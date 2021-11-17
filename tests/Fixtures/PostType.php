<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

enum PostType: string
{
    case POST_TEXT = 'text';
    case POST_IMAGE = 'image';
}
