<?php declare(strict_types=1);
/*
 * Copyright 2016 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
    protected function getCatchablePatterns()
    {
        return [
            '[a-zA-Z_\x7f-\xff\\\][a-z0-9A-Z_\x7f-\xff\:\\\]*[a-zA-Z_\x7f-\xff][a-z0-9A-Z_\x7f-\xff]*', // PHP Class Name: http://php.net/manual/en/language.oop5.basic.php
            '"(?:[^"]|"")*"|\'(?:[^\']|\'\')*\'',          // Strings
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getNonCatchablePatterns()
    {
        return [
            '\s+',
            '(.)',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getType(&$value)
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
