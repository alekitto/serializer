<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Csv;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[Csv(enclosure: "'")]
#[AccessType(Access\Type::Property)]
class EnclosureObject
{
    #[Type('string')]
    private $id = 'what_a_nice_id';

    #[Type('string')]
    private $title = 'This is a great title';
}
