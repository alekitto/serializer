<?php declare(strict_types=1);

namespace Kcs\Serializer;

use Kcs\Serializer\Exception\RuntimeException;

class JsonDeserializationVisitor extends GenericDeserializationVisitor
{
    public function prepare($str)
    {
        $decoded = json_decode($str, true);

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return $decoded;

            case JSON_ERROR_DEPTH:
                throw new RuntimeException('Could not decode JSON, maximum stack depth exceeded.');
            case JSON_ERROR_STATE_MISMATCH:
                throw new RuntimeException('Could not decode JSON, underflow or the nodes mismatch.');
            case JSON_ERROR_CTRL_CHAR:
                throw new RuntimeException('Could not decode JSON, unexpected control character found.');
            case JSON_ERROR_SYNTAX:
                throw new RuntimeException('Could not decode JSON, syntax error - malformed JSON.');
            case JSON_ERROR_UTF8:
                throw new RuntimeException('Could not decode JSON, malformed UTF-8 characters (incorrectly encoded?)');
            default:
                throw new RuntimeException('Could not decode JSON.');
        }
    }

    public function getResult()
    {
        return $this->getRoot();
    }
}
