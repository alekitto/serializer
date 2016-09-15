<?php

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

use Kcs\Serializer\Exception\SyntaxErrorException;
use Kcs\Serializer\Type\Type;

/**
 * Parses a serializer type.
 */
final class Parser
{
    /**
     * @var Lexer
     */
    private $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    /**
     * Parse a type string
     *
     * @param string $input
     * @return Type
     */
    public function parse($input)
    {
        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        return $this->parseInternal();
    }

    /**
     * Match a token in the lexer
     *
     * @param int $type
     * @return mixed
     *
     * @throws SyntaxErrorException
     */
    protected function match($type)
    {
        if (! $this->lexer->isNextToken($type)) {
            $this->syntaxError();
        }

        $value = $this->lexer->lookahead['value'];
        $this->lexer->moveNext();

        return $value;
    }

    /**
     * Parse internal type string
     *
     * @return Type
     *
     * @throws SyntaxErrorException
     */
    private function parseInternal()
    {
        $typeName = $this->match(Lexer::T_IDENTIFIER);
        if (! $this->lexer->isNextToken(Lexer::T_OPEN_BRACKET)) {
            return new Type($typeName);
        }

        $this->match(Lexer::T_OPEN_BRACKET);

        $params = [];
        do {
            if ($this->lexer->isNextToken(Lexer::T_IDENTIFIER)) {
                $params[] = $this->parseInternal();
            } elseif ($this->lexer->isNextToken(Lexer::T_STRING)) {
                $params[] = $this->match(Lexer::T_STRING);
            } else {
                $this->syntaxError();
            }
        } while ($this->lexer->lookahead['type'] === Lexer::T_COMMA && $this->lexer->moveNext());

        $this->match(Lexer::T_CLOSED_BRACKET);

        return new Type($typeName, $params);
    }

    /**
     * Throw a syntax error exception
     *
     * @throws SyntaxErrorException
     */
    private function syntaxError()
    {
        throw new SyntaxErrorException(
            $this->lexer->getInputUntilPosition(PHP_INT_MAX),
            $this->lexer->lookahead['value'] ?: 'end of string',
            $this->lexer->lookahead['position']
        );
    }
}
