<?php declare(strict_types=1);

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
     * Parse a type string.
     *
     * @param string $input
     *
     * @return Type
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
     * @param int $type
     *
     * @return mixed
     *
     * @throws SyntaxErrorException
     */
    private function match(int $type)
    {
        if (! $this->lexer->isNextToken($type)) {
            $this->syntaxError();
        }

        $value = $this->lexer->lookahead['value'];
        $this->lexer->moveNext();

        return $value;
    }

    /**
     * Parse internal type string.
     *
     * @return Type
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
        } while (
            (null !== $this->lexer->lookahead && Lexer::T_COMMA === $this->lexer->lookahead['type']) &&
            $this->lexer->moveNext()
        );

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
        $value = null !== $this->lexer->lookahead ? $this->lexer->lookahead['value'] : null;
        $position = null !== $this->lexer->lookahead ?
            (int) $this->lexer->lookahead['position'] :
            (int) $this->lexer->token['position'] + \strlen($this->lexer->token['value'])
        ;

        throw new SyntaxErrorException(
            $this->lexer->getInputUntilPosition(PHP_INT_MAX),
            $value ?: 'end of string',
            $position
        );
    }
}
