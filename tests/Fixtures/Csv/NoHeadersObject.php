<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Annotation\Type;

/**
 * @Csv(printHeaders=false)
 * @AccessType("property")
 */
class NoHeadersObject
{
    /**
     * @Type("string")
     */
    private $id = 'what_a_nice_id';

    /**
     * @Type("string")
     */
    private $title = 'This is a great title';
}
