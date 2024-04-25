<?php

declare(strict_types=1);

namespace Kcs\Serializer\Type\Parser;

use Doctrine\Common\Lexer\AbstractLexer;

use function ctype_alpha;
use function str_replace;
use function substr;

final class Lexer extends AbstractLexer
{
    public const T_NONE = 1;
    public const T_STRING = 2;
    public const T_COMMA = 3;
    public const T_CLOSED_BRACKET = 4;
    public const T_OPEN_BRACKET = 5;

    public const T_IDENTIFIER = 100;

    /**
     * {@inheritDoc}
     */
    protected function getCatchablePatterns(): array
    {
        return [
            '[a-zA-Z_\x7f-\xff\\\][a-z0-9A-Z_\x7f-\xff\:\\\]*[a-zA-Z_\x7f-\xff][a-z0-9A-Z_\x7f-\xff]*', // PHP Class Name: http://php.net/manual/en/language.oop5.basic.php
            '"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'',          // Strings
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getNonCatchablePatterns(): array
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    protected function getType(string &$value): int
    {
        $type = self::T_NONE;

        // Differentiate between quoted names, identifiers, input parameters and symbols
        if ($value[0] === "'") {
            $value = str_replace("''", "'", substr($value, 1, -1));

            return self::T_STRING;
        }

        if ($value[0] === '"') {
            $value = substr($value, 1, -1);

            return self::T_STRING;
        }

        if (ctype_alpha($value[0])) {
            return self::T_IDENTIFIER;
        }

        switch ($value) {
            case ',':
                return self::T_COMMA;

            case '>':
                return self::T_CLOSED_BRACKET;

            case '<':
                return self::T_OPEN_BRACKET;

            default:
                // Do nothing
                break;
        }

        return $type;
    }
}
