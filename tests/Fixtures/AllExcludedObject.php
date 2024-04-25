<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\ExclusionPolicy;
use Kcs\Serializer\Annotation\Expose;
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
