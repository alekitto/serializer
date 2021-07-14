<?php

declare(strict_types=1);

namespace Kcs\Serializer\Exception;

use Exception;

use function strlen;

class SyntaxErrorException extends Exception
{
    public function __construct(string $original, string $value, ?int $position)
    {
        if (! $position) {
            $position = strlen($original);
        }

        parent::__construct("Syntax Error while parsing '$original': Unexpected $value at position $position");
    }
}
