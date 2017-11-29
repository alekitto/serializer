<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures;

use Kcs\Serializer\Annotation\AccessType;

/**
 * @AccessType("property")
 */
class ObjectWithNullProperty extends SimpleObject
{
    private $nullProperty = null;
}
