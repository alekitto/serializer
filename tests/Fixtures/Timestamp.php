<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Attribute\AccessType;
use Kcs\Serializer\Attribute\Type;
use Kcs\Serializer\Metadata\Access;

#[AccessType(Access\Type::Property)]
class Timestamp
{
    #[Type("DateTime<'U'>")]
    private $timestamp;

    public function __construct($timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
