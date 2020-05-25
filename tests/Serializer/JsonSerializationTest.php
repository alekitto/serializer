<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Kcs\Serializer\Context;
use Kcs\Serializer\Exception\RuntimeException;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Type\Type;

class JsonSerializationTest extends BaseSerializationTest
{
    /**
     * {@inheritdoc}
     */
    protected function getContent(string $key): string
    {
        static $outputs = [];

        if (! $outputs) {
            $outputs['readonly'] = '{"id":123,"full_name":"Ruud Kamphuis"}';
            $outputs['string'] = '"foo"';
            $outputs['boolean_true'] = 'true';
            $outputs['boolean_false'] = 'false';
            $outputs['integer'] = '1';
            $outputs['float'] = '4.533';
            $outputs['float_trailing_zero'] = '1';
            $outputs['simple_object'] = '{"foo":"foo","moo":"bar","camel_case":"boo"}';
            $outputs['circular_reference'] = '{"collection":[{"name":"child1"},{"name":"child2"}],"another_collection":[{"name":"child1"},{"name":"child2"}]}';
            $outputs['array_strings'] = '["foo","bar"]';
            $outputs['array_booleans'] = '[true,false]';
            $outputs['array_integers'] = '[1,3,4]';
            $outputs['array_floats'] = '[1.34,3,6.42]';
            $outputs['array_objects'] = '[{"foo":"foo","moo":"bar","camel_case":"boo"},{"foo":"baz","moo":"boo","camel_case":"boo"}]';
            $outputs['array_list_and_map_difference'] = '{"list":[1,2,3],"map":{"0":1,"2":2,"3":3}}';
            $outputs['array_mixed'] = '["foo",1,true,{"foo":"foo","moo":"bar","camel_case":"boo"},[1,3,true]]';
            $outputs['array_datetimes_object'] = '{"array_with_default_date_time":["2047-01-01T12:47:47+00:00","2013-12-05T00:00:00+00:00"],"array_with_formatted_date_time":["01.01.2047 12:47:47","05.12.2013 00:00:00"]}';
            $outputs['array_named_datetimes_object'] = '{"named_array_with_formatted_date":{"testdate1":"01.01.2047 12:47:47","testdate2":"05.12.2013 00:00:00"}}';
            $outputs['blog_post'] = '{"id":"what_a_nice_id","title":"This is a nice title.","created_at":"2011-07-30T00:00:00+00:00","is_published":false,"etag":"1edf9bf60a32d89afbb85b2be849e3ceed5f5b10","comments":[{"author":{"full_name":"Foo Bar"},"text":"foo"}],"comments2":[{"author":{"full_name":"Foo Bar"},"text":"foo"}],"metadata":{"foo":"bar"},"author":{"full_name":"Foo Bar"},"publisher":{"pub_name":"Bar Foo"},"tag":[{"name":"tag1"},{"name":"tag2"}]}';
            $outputs['blog_post_unauthored'] = '{"id":"what_a_nice_id","title":"This is a nice title.","created_at":"2011-07-30T00:00:00+00:00","is_published":false,"etag":"1edf9bf60a32d89afbb85b2be849e3ceed5f5b10","comments":[],"comments2":[],"metadata":{"foo":"bar"},"author":null,"publisher":null,"tag":null}';
            $outputs['price'] = '{"price":3}';
            $outputs['currency_aware_price'] = '{"currency":"EUR","amount":2.34}';
            $outputs['order'] = '{"cost":{"price":12.34}}';
            $outputs['order_with_currency_aware_price'] = '{"cost":{"currency":"EUR","amount":1.23}}';
            $outputs['log'] = '{"author_list":[{"full_name":"Johannes Schmitt"},{"full_name":"John Doe"}],"comments":[{"author":{"full_name":"Foo Bar"},"text":"foo"},{"author":{"full_name":"Foo Bar"},"text":"bar"},{"author":{"full_name":"Foo Bar"},"text":"baz"}]}';
            $outputs['lifecycle_callbacks'] = '{"name":"Foo Bar"}';
            $outputs['form_errors'] = '["This is the form error","Another error"]';
            $outputs['nested_form_errors'] = '{"errors":["This is the form error"],"children":[{"errors":["Error of the child form"],"children":[],"name":"bar"}],"name":"foo"}';
            $outputs['constraint_violation'] = '{"property_path":"foo","message":"Message of violation"}';
            $outputs['constraint_violation_list'] = '[{"property_path":"foo","message":"Message of violation"},{"property_path":"bar","message":"Message of another violation"}]';
            $outputs['article'] = '{"custom":"serialized"}';
            $outputs['orm_proxy'] = '{"foo":"foo","moo":"bar","camel_case":"proxy-boo"}';
            $outputs['custom_accessor'] = '{"comments":{"Foo":{"comments":[{"author":{"full_name":"Foo"},"text":"foo"},{"author":{"full_name":"Foo"},"text":"bar"}],"count":2}}}';
            $outputs['mixed_access_types'] = '{"id":1,"name":"Johannes","read_only_property":42}';
            $outputs['accessor_order_child'] = '{"c":"c","d":"d","a":"a","b":"b"}';
            $outputs['accessor_order_parent'] = '{"a":"a","b":"b"}';
            $outputs['accessor_order_methods'] = '{"foo":"c","b":"b","a":"a"}';
            $outputs['inline'] = '{"c":"c","a":"a","b":"b","d":"d"}';
            $outputs['inline_child_empty'] = '{"c":"c","d":"d"}';
            $outputs['groups_all'] = '{"virt":"virt_2","foo":"foo","foobar":"foobar","bar":"bar","baz":"baz","none":"none"}';
            $outputs['groups_foo'] = '{"virt":"virt_2","foo":"foo","foobar":"foobar","baz":"baz"}';
            $outputs['groups_foobar'] = '{"virt":"virt_2","foo":"foo","foobar":"foobar","bar":"bar","baz":"baz","none":null}';
            $outputs['groups_foo_not_baz'] = '{"virt":"virt_1","foo":"foo","foobar":"foobar"}';
            $outputs['groups_default'] = '{"virt":"virt_2","bar":"bar","none":"none"}';
            $outputs['groups_advanced'] = '{"name":"John","manager":{"name":"John Manager","friends":[{"nickname":"nickname"},{"nickname":"nickname"}]},"friends":[{"manager":{"name":"John friend 1 manager"}},{"manager":{"name":"John friend 2 manager"}}]}';
            $outputs['virtual_properties'] = '{"exist_field":"value","virtual_value":"value","test":"other-name","typed_virtual_property":1}';
            $outputs['virtual_properties_low'] = '{"low":1}';
            $outputs['virtual_properties_high'] = '{"high":8}';
            $outputs['virtual_properties_all'] = '{"low":1,"high":8}';
            $outputs['nullable'] = '{"foo":"bar","baz":null}';
            $outputs['null'] = 'null';
            $outputs['simple_object_nullable'] = '{"null_property":null,"foo":"foo","moo":"bar","camel_case":"boo"}';
            $outputs['input'] = '{"attributes":{"type":"text","name":"firstname","value":"Adrien"}}';
            $outputs['hash_empty'] = '{"hash":{}}';
            $outputs['object_when_null'] = '{"text":"foo"}';
            $outputs['object_when_null_and_serialized'] = '{"author":null,"text":"foo"}';
            $outputs['date_time'] = '"2011-08-30T00:00:00+00:00"';
            $outputs['timestamp'] = '{"timestamp":1455148800}';
            $outputs['date_interval'] = '"PT45M"';
            $outputs['car'] = '{"km":5,"type":"car"}';
            $outputs['car_without_type'] = '{"km":5}';
            $outputs['garage'] = '{"vehicles":[{"km":3,"type":"car"},{"km":1,"type":"moped"}]}';
            $outputs['tree'] = '{"tree":{"children":[{"children":[{"children":[],"foo":"bar"}],"foo":"bar"}],"foo":"bar"}}';
            $outputs['object_with_additional_field'] = '{"authors":[{"_links":{"details":"http:\/\/foo.bar\/details\/Foo","comments":"http:\/\/foo.bar\/details\/Foo\/comments"},"full_name":"Foo"},{"_links":{"details":"http:\/\/foo.bar\/details\/Bar","comments":"http:\/\/foo.bar\/details\/Bar\/comments"},"full_name":"Bar"}]}';
            $outputs['type_passed_to_serialize'] = '[{"name":"Foo"},{"name":"Bar"}]';
            $outputs['object_subclass_with_additional_field'] = '{"authors":[{"is_child":true,"_links":{"details":"http:\/\/foo.bar\/details\/Foo","comments":"http:\/\/foo.bar\/details\/Foo\/comments"},"full_name":"Foo"},{"is_child":true,"_links":{"details":"http:\/\/foo.bar\/details\/Bar","comments":"http:\/\/foo.bar\/details\/Bar\/comments"},"full_name":"Bar"}]}';
            $outputs['groups_provider'] = '{"foo":"foo","obj":{"virt":"virt_2","foo":"foo","foobar":"foobar","baz":"baz"}}';
            $outputs['uuid'] = '"a3b5af04-42c7-5838-acd8-a963607eaafb"';
            $outputs['custom_serialization_handler'] = '{"some_property":"sometext"}';
        }

        if (PHP_VERSION_ID >= 70000) {
            $outputs['virtual_properties'] = '{"exist_field":"value","virtual_value":"value","test":"other-name","typed_virtual_property":1}';
        }

        if (! isset($outputs[$key])) {
            throw new RuntimeException(\sprintf('The key "%s" is not supported.', $key));
        }

        return $outputs[$key];
    }

    public function getPrimitiveTypes(): iterable
    {
        return [
            [
                'type' => 'boolean',
                'data' => true,
            ],
            [
                'type' => 'boolean',
                'data' => 1,
            ],
            [
                'type' => 'integer',
                'data' => 123,
            ],
            [
                'type' => 'integer',
                'data' => '123',
            ],
            [
                'type' => 'string',
                'data' => 'hello',
            ],
            [
                'type' => 'string',
                'data' => 123,
            ],
            [
                'type' => 'double',
                'data' => 0.1234,
            ],
            [
                'type' => 'double',
                'data' => '0.1234',
            ],
        ];
    }

    /**
     * @dataProvider getPrimitiveTypes
     */
    public function testPrimitiveTypes(string $primitiveType, $data): void
    {
        $visitor = $this->serializationVisitors['json'];
        $functionToCall = 'visit'.\ucfirst($primitiveType);
        $result = $visitor->$functionToCall($data, Type::null(), $this->prophesize(Context::class)->reveal());

        switch ($primitiveType) {
            case 'boolean':
                $primitiveType = 'Bool';
                break;

            case 'double':
                $primitiveType = 'Float';
                break;

            case 'integer':
                $primitiveType = 'Int';
                break;

            default:
                $primitiveType = \ucfirst($primitiveType);
                break;
        }

        self::{'assertIs'.$primitiveType}($result);
    }

    /**
     * @group empty-object
     */
    public function testSerializeEmptyObject(): void
    {
        self::assertEquals('{}', $this->serialize(new Author(null)));
    }

    /**
     * @group encoding
     */
    public function testSerializeWithNonUtf8EncodingWhenDisplayErrorsOff(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        \ini_set('display_errors', '1');
        $this->serialize(['foo' => 'bar', 'bar' => \pack('H*', 'c32e')]);
    }

    /**
     * @group encoding
     */
    public function testSerializeWithNonUtf8EncodingWhenDisplayErrorsOn(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        \ini_set('display_errors', '0');
        $this->serialize(['foo' => 'bar', 'bar' => \pack('H*', 'c32e')]);
    }

    public function testSerializeArrayWithEmptyObject(): void
    {
        self::assertEquals('[{}]', $this->serialize([new \stdClass()]));
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormat(): string
    {
        return 'json';
    }
}
