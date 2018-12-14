<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

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
    public function testParse(string $type, Type $expected)
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

    /**
     * @expectedException \Kcs\Serializer\Exception\SyntaxErrorException
     */
    public function testParamTypeMustEndWithBracket()
    {
        $this->parser->parse('Foo<bar');
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\SyntaxErrorException
     */
    public function testMustStartWithName()
    {
        $this->parser->parse(',');
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\SyntaxErrorException
     */
    public function testEmptyParams()
    {
        $this->parser->parse('Foo<>');
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\SyntaxErrorException
     */
    public function testNoTrailingComma()
    {
        $this->parser->parse('Foo<aa,>');
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\SyntaxErrorException
     */
    public function testLeadingBackslash()
    {
        $this->parser->parse('Foo<\Bar>');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->parser = new Parser();
    }
}
