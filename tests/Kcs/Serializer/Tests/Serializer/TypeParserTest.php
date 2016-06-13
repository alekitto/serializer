<?php

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Modifications copyright (c) 2016 Alessandro Chitolina <alekitto@gmail.com>
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

class TypeParserTest extends \PHPUnit_Framework_TestCase
{
    private $parser;

    /**
     * @dataProvider getTypes
     */
    public function testParse($type, $name, array $params = array())
    {
        $this->assertEquals(array('name' => $name, 'params' => $params), $this->parser->parse($type));
    }

    public function getTypes()
    {
        $types = array();
        $types[] = array('string', 'string');
        $types[] = array('array<Foo>', 'array', array(array('name' => 'Foo', 'params' => array())));
        $types[] = array('array<Foo,Bar>', 'array', array(array('name' => 'Foo', 'params' => array()), array('name' => 'Bar', 'params' => array())));
        $types[] = array('array<Foo\Bar, Baz\Boo>', 'array', array(array('name' => 'Foo\Bar', 'params' => array()), array('name' => 'Baz\Boo', 'params' => array())));
        $types[] = array('a<b<c,d>,e>', 'a', array(array('name' => 'b', 'params' => array(array('name' => 'c', 'params' => array()), array('name' => 'd', 'params' => array()))), array('name' => 'e', 'params' => array())));
        $types[] = array('Foo', 'Foo');
        $types[] = array('Foo\Bar', 'Foo\Bar');
        $types[] = array('Foo<"asdf asdf">', 'Foo', array('asdf asdf'));

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
