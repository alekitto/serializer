<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\ExclusionPolicy;
use Kcs\Serializer\Attribute\Expose;
use Kcs\Serializer\Metadata\Access;
use Kcs\Serializer\Metadata\Exclusion;

#[ExclusionPolicy(Exclusion\Policy::All)]
#[AccessType(Access\Type::Property)]
class AllExcludedObject
{
    private $foo = 'foo';

    #[Expose]
    private $bar = 'bar';
}
