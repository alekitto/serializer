<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Type;

/**
 * @AccessType("property")
 */
class Timestamp
{
    /**
     * @Type("DateTime<'U'>")
     */
    private $timestamp;

    public function __construct($timestamp)
    {
        $this->timestamp = $timestamp;
    }
}
