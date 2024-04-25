<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Fixtures\Csv;

use Kcs\Serializer\Annotation\AccessType;
use Kcs\Serializer\Annotation\Csv;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Metadata\Access;

#[Csv(escapeFormulas: true)]
#[AccessType(Access\Type::Property)]
class EscapeFormulas
{
    public function __construct(
        #[Type('string')]
        private string $formula,
    ) {
    }
}
