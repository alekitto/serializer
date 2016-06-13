<?php

namespace Kcs\Serializer\Exception;

class SyntaxErrorException extends \Exception
{
    public function __construct($value, $position)
    {
        parent::__construct("Syntax Error: Unexpected $value at position $position");
    }
}
