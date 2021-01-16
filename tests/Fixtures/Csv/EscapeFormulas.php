<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Annotation\Type;

/**
 * @Csv(escapeFormulas=true)
 * @AccessType("property")
 */
#[Csv(escapeFormulas: true)]
#[AccessType(AccessType::PROPERTY)]
class EscapeFormulas
{
    /**
     * @Type("string")
     */
    private $formula;

    public function __construct(string $formula)
    {
        $this->formula = $formula;
    }
}
