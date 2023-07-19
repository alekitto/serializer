<?php

declare(strict_types=1);

namespace Kcs\Serializer\Type\Parser;

use Kcs\Serializer\Exception\SyntaxErrorException;
use Kcs\Serializer\Type\Type;

use function assert;

use const PHP_INT_MAX;

/**
 * Parses a serializer type.
 */
final class Parser
{
    private Lexer $lexer;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    /**
     * Parse a type string.
     *
     * @throws SyntaxErrorException
     */
    public function parse(string $input): Type
    {
        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        return $this->parseInternal();
    }

    /**
     * Match a token in the lexer.
     *
     * @throws SyntaxErrorException
     */
    private function match(int $type): mixed
    {
        if (! $this->lexer->isNextToken($type)) {
            $this->syntaxError();
        }

        assert($this->lexer->lookahead?->value !== null);
        $value = $this->lexer->lookahead->value;
        $this->lexer->moveNext();

        return $value;
    }

    /**
     * Parse internal type string.
     *
     * @throws SyntaxErrorException
     */
    private function parseInternal(): Type
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
        } while ($this->lexer->lookahead?->type === Lexer::T_COMMA && $this->lexer->moveNext());

        $this->match(Lexer::T_CLOSED_BRACKET);

        return new Type($typeName, $params);
    }

    /**
     * Throw a syntax error exception.
     *
     * @throws SyntaxErrorException
     */
    private function syntaxError(): void
    {
        $value = $this->lexer->lookahead?->value;
        $position = $this->lexer->lookahead?->position;

        throw new SyntaxErrorException($this->lexer->getInputUntilPosition(PHP_INT_MAX), (string) ($value ?? 'end of string'), $position);
    }
}
