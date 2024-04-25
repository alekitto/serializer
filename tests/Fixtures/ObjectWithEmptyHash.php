<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation as Serializer;
use Kcs\Serializer\Metadata\Access;

#[Serializer\AccessType(Access\Type::Property)]
class ObjectWithEmptyHash
{
    #[Serializer\Type("array<string, string>")]
    private $hash = [];
}
