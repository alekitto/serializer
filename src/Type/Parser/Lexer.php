<?php declare(strict_types=1);

namespace Kcs\Serializer\Type\Parser;

use Doctrine\Common\Lexer\AbstractLexer;

class Lexer extends AbstractLexer
{
    const T_NONE = 1;
    const T_STRING = 2;
    const T_COMMA = 3;
    const T_CLOSED_BRACKET = 4;
    const T_OPEN_BRACKET = 5;

    const T_IDENTIFIER = 100;

    /**
     * {@inheritdoc}
     */
    protected function getCatchablePatterns(): array
    {
        return [
            '[a-zA-Z_\x7f-\xff\\\][a-z0-9A-Z_\x7f-\xff\:\\\]*[a-zA-Z_\x7f-\xff][a-z0-9A-Z_\x7f-\xff]*', // PHP Class Name: http://php.net/manual/en/language.oop5.basic.php
            '"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'',          // Strings
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getNonCatchablePatterns(): array
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(&$value): int
    {
        $type = self::T_NONE;

        // Differentiate between quoted names, identifiers, input parameters and symbols
        if ("'" === $value[0]) {
            $value = str_replace("''", "'", substr($value, 1, strlen($value) - 2));

            return self::T_STRING;
        } elseif ('"' === $value[0]) {
            $value = substr($value, 1, strlen($value) - 2);

            return self::T_STRING;
        } elseif (ctype_alpha($value[0])) {
            return self::T_IDENTIFIER;
        } else {
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
        }

        return $type;
    }
}
