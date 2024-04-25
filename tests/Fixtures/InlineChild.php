<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute as Serializer;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessType(Access\Type::Property)]
class InlineChild
{
    #[Type('string')]
    private $a = 'a';

    #[Type('string')]
    private $b = 'b';
}
