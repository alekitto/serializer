<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Tests\Fixtures\GroupsObject;

/**
 * @Csv(outputBom=true)
 * @AccessType("property")
 */
#[Csv(outputBom: true)]
#[AccessType(AccessType::PROPERTY)]
class BomObject extends GroupsObject
{
}
