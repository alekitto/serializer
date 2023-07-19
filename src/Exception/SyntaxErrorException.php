<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use Exception;

use function sprintf;
use function strlen;

class SyntaxErrorException extends Exception
{
    public function __construct(string $original, string $value, int|null $position)
    {
        if (! $position) {
            $position = strlen($original);
        }

        parent::__construct(sprintf('Syntax Error while parsing \'%s\': Unexpected %s at position %d', $original, $value, $position));
    }
}
