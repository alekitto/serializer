<?php declare(strict_types=1);
/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
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

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Type\Parser\Parser;
use Kcs\Serializer\Type\Type;
use PHPUnit\Framework\TestCase;

class TypeParserTest extends TestCase
{
    private $parser;

    /**
     * @dataProvider getTypes
     */
    public function testParse($type, Type $expected)
    {
        $this->assertEquals($expected, $this->parser->parse($type));
    }

    public function getTypes()
    {
        $types = [
            ['string', Type::from('string')],
            ['array<Foo>', new Type('array', [Type::from('Foo')])],
            ['array<Foo,Bar>', new Type('array', [Type::from('Foo'), Type::from('Bar')])],
            ['array<Foo\Bar, Baz\Boo>', new Type('array', [Type::from('Foo\Bar'), Type::from('Baz\Boo')])],
            ['a<b<c,d>,e>', new Type('a', [new Type('b', [Type::from('c'), Type::from('d')]), Type::from('e')])],
            ['Foo', Type::from('Foo')],
            ['Foo\Bar', Type::from('Foo\Bar')],
            ['Foo<"asdf asdf">', new Type('Foo', ['asdf asdf'])],
        ];

        return $types;
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

    protected function setUp()
    {
        $this->parser = new Parser();
    }
}
