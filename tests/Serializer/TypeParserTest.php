<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Exception\SyntaxErrorException;
use Kcs\Serializer\Type\Parser\Parser;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class TypeParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @dataProvider getTypes
     */
    public function testParse(string $type, Type $expected): void
    {
        self::assertEquals($expected, $this->parser->parse($type));
    }

    public function getTypes(): iterable
    {
        return [
            ['string', Type::from('string')],
            ['array<Foo>', new Type('array', [Type::from('Foo')])],
            ['array<Foo,Bar>', new Type('array', [Type::from('Foo'), Type::from('Bar')])],
            ['array<Foo\Bar, Baz\Boo>', new Type('array', [Type::from('Foo\Bar'), Type::from('Baz\Boo')])],
            ['a<b<c,d>,e>', new Type('a', [new Type('b', [Type::from('c'), Type::from('d')]), Type::from('e')])],
            ['Foo', Type::from('Foo')],
            ['Foo\Bar', Type::from('Foo\Bar')],
            ['Foo<"asdf asdf">', new Type('Foo', ['asdf asdf'])],
        ];
    }

    public function testParamTypeMustEndWithBracket(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->parser->parse('Foo<bar');
    }

    public function testMustStartWithName(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->parser->parse(',');
    }

    public function testEmptyParams(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->parser->parse('Foo<>');
    }

    public function testNoTrailingComma(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->parser->parse('Foo<aa,>');
    }

    public function testLeadingBackslash(): void
    {
        $this->expectException(SyntaxErrorException::class);
        $this->parser->parse('Foo<\Bar>');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->parser = new Parser();
    }
}
