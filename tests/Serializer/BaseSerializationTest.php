<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Context;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Direction;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Exclusion\DepthExclusionStrategy;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\Handler\ConstraintViolationHandler;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\FormErrorHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\PhpCollectionHandler;
use Kcs\Serializer\JsonDeserializationVisitor;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\CamelCaseNamingStrategy;
use Kcs\Serializer\Naming\SerializedNameAnnotationStrategy;
use Kcs\Serializer\SerializationContext;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\Tests\Fixtures\AccessorOrderChild;
use Kcs\Serializer\Tests\Fixtures\AccessorOrderMethod;
use Kcs\Serializer\Tests\Fixtures\AccessorOrderParent;
use Kcs\Serializer\Tests\Fixtures\Author;
use Kcs\Serializer\Tests\Fixtures\AuthorChild;
use Kcs\Serializer\Tests\Fixtures\AuthorList;
use Kcs\Serializer\Tests\Fixtures\AuthorReadOnly;
use Kcs\Serializer\Tests\Fixtures\AuthorReadOnlyPerClass;
use Kcs\Serializer\Tests\Fixtures\BlogPost;
use Kcs\Serializer\Tests\Fixtures\CircularReferenceParent;
use Kcs\Serializer\Tests\Fixtures\Comment;
use Kcs\Serializer\Tests\Fixtures\CurrencyAwareOrder;
use Kcs\Serializer\Tests\Fixtures\CurrencyAwarePrice;
use Kcs\Serializer\Tests\Fixtures\CustomDeserializationObject;
use Kcs\Serializer\Tests\Fixtures\DateTimeArraysObject;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Car;
use Kcs\Serializer\Tests\Fixtures\Discriminator\Moped;
use Kcs\Serializer\Tests\Fixtures\Garage;
use Kcs\Serializer\Tests\Fixtures\GetSetObject;
use Kcs\Serializer\Tests\Fixtures\GroupsObject;
use Kcs\Serializer\Tests\Fixtures\GroupsUser;
use Kcs\Serializer\Tests\Fixtures\IndexedCommentsBlogPost;
use Kcs\Serializer\Tests\Fixtures\InitializedBlogPostConstructor;
use Kcs\Serializer\Tests\Fixtures\InlineChildEmpty;
use Kcs\Serializer\Tests\Fixtures\InlineParent;
use Kcs\Serializer\Tests\Fixtures\Input;
use Kcs\Serializer\Tests\Fixtures\InvalidGroupsObject;
use Kcs\Serializer\Tests\Fixtures\Log;
use Kcs\Serializer\Tests\Fixtures\NamedDateTimeArraysObject;
use Kcs\Serializer\Tests\Fixtures\Node;
use Kcs\Serializer\Tests\Fixtures\ObjectWithEmptyHash;
use Kcs\Serializer\Tests\Fixtures\ObjectWithIntListAndIntMap;
use Kcs\Serializer\Tests\Fixtures\ObjectWithLifecycleCallbacks;
use Kcs\Serializer\Tests\Fixtures\ObjectWithNullProperty;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVersionedVirtualProperties;
use Kcs\Serializer\Tests\Fixtures\ObjectWithVirtualProperties;
use Kcs\Serializer\Tests\Fixtures\Order;
use Kcs\Serializer\Tests\Fixtures\Price;
use Kcs\Serializer\Tests\Fixtures\Publisher;
use Kcs\Serializer\Tests\Fixtures\SimpleObject;
use Kcs\Serializer\Tests\Fixtures\SimpleObjectProxy;
use Kcs\Serializer\Tests\Fixtures\Tag;
use Kcs\Serializer\Tests\Fixtures\Timestamp;
use Kcs\Serializer\Tests\Fixtures\Tree;
use Kcs\Serializer\Tests\Fixtures\VehicleInterfaceGarage;
use Kcs\Serializer\Type\Type;
use Kcs\Serializer\VisitorInterface;
use Kcs\Serializer\XmlDeserializationVisitor;
use Kcs\Serializer\XmlSerializationVisitor;
use Kcs\Serializer\YamlDeserializationVisitor;
use Kcs\Serializer\YamlSerializationVisitor;
use PhpCollection\Sequence;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryBuilder;
use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

abstract class BaseSerializationTest extends TestCase
{
    protected $factory;
    protected $dispatcher;

    /** @var Serializer */
    protected $serializer;
    protected $handlerRegistry;
    protected $serializationVisitors;
    protected $deserializationVisitors;

    public function testSerializeNullArray()
    {
        $arr = ['foo' => 'bar', 'baz' => null, null];

        $this->assertEquals(
            $this->getContent('nullable'),
            $this->serializer->serialize($arr, $this->getFormat(), SerializationContext::create()->setSerializeNull(true))
        );
    }

    public function testSerializeNullObject()
    {
        $obj = new ObjectWithNullProperty('foo', 'bar');

        $this->assertEquals(
            $this->getContent('simple_object_nullable'),
            $this->serializer->serialize($obj, $this->getFormat(), SerializationContext::create()->setSerializeNull(true))
        );
    }

    /**
     * @dataProvider getTypes
     */
    public function testNull($type)
    {
        $this->assertEquals($this->getContent('null'), $this->serialize(null), $type);

        if ($this->hasDeserializer()) {
            $this->assertEquals(null, $this->deserialize($this->getContent('null'), $type));
        }
    }

    public function getTypes()
    {
        return [
            ['NULL'],
            ['integer'],
            ['double'],
            ['float'],
            ['string'],
            ['DateTime'],
        ];
    }

    public function testString()
    {
        $this->assertEquals($this->getContent('string'), $this->serialize('foo'));

        if ($this->hasDeserializer()) {
            $this->assertEquals('foo', $this->deserialize($this->getContent('string'), 'string'));
        }
    }

    /**
     * @dataProvider getBooleans
     */
    public function testBooleans($strBoolean, $boolean)
    {
        $this->assertEquals($this->getContent('boolean_'.$strBoolean), $this->serialize($boolean));

        if ($this->hasDeserializer()) {
            $this->assertSame($boolean, $this->deserialize($this->getContent('boolean_'.$strBoolean), 'boolean'));
        }
    }

    public function getBooleans()
    {
        return [['true', true], ['false', false]];
    }

    /**
     * @dataProvider getNumerics
     */
    public function testNumerics($key, $value, $type)
    {
        $this->assertEquals($this->getContent($key), $this->serialize($value));

        if ($this->hasDeserializer()) {
            $this->assertEquals($value, $this->deserialize($this->getContent($key), $type));
        }
    }

    public function getNumerics()
    {
        return [
            ['integer', 1, 'integer'],
            ['float', 4.533, 'double'],
            ['float', 4.533, 'float'],
            ['float_trailing_zero', 1.0, 'double'],
            ['float_trailing_zero', 1.0, 'float'],
        ];
    }

    public function testSimpleObject()
    {
        $this->assertEquals($this->getContent('simple_object'), $this->serialize($obj = new SimpleObject('foo', 'bar')));

        if ($this->hasDeserializer()) {
            $this->assertEquals($obj, $this->deserialize($this->getContent('simple_object'), get_class($obj)));
        }
    }

    public function testArrayStrings()
    {
        $data = ['foo', 'bar'];
        $this->assertEquals($this->getContent('array_strings'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            $this->assertEquals($data, $this->deserialize($this->getContent('array_strings'), Type::parse('array<string>')));
        }
    }

    public function testArrayBooleans()
    {
        $data = [true, false];
        $this->assertEquals($this->getContent('array_booleans'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            $this->assertEquals($data, $this->deserialize($this->getContent('array_booleans'), Type::parse('array<boolean>')));
        }
    }

    public function testArrayIntegers()
    {
        $data = [1, 3, 4];
        $this->assertEquals($this->getContent('array_integers'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            $this->assertEquals($data, $this->deserialize($this->getContent('array_integers'), Type::parse('array<integer>')));
        }
    }

    public function testArrayFloats()
    {
        $data = [1.34, 3.0, 6.42];
        $this->assertEquals($this->getContent('array_floats'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            $this->assertEquals($data, $this->deserialize($this->getContent('array_floats'), Type::parse('array<double>')));
        }
    }

    public function testArrayObjects()
    {
        $data = [new SimpleObject('foo', 'bar'), new SimpleObject('baz', 'boo')];
        $this->assertEquals($this->getContent('array_objects'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            $this->assertEquals($data, $this->deserialize($this->getContent('array_objects'), Type::parse('array<Kcs\Serializer\Tests\Fixtures\SimpleObject>')));
        }
    }

    public function testArrayListAndMapDifference()
    {
        $arrayData = [0 => 1, 2 => 2, 3 => 3]; // Misses key 1
        $data = new ObjectWithIntListAndIntMap($arrayData, $arrayData);

        $this->assertEquals($this->getContent('array_list_and_map_difference'), $this->serialize($data));
    }

    public function testDateTimeArrays()
    {
        $data = [
            new \DateTime('2047-01-01 12:47:47', new \DateTimeZone('UTC')),
            new \DateTime('2013-12-05 00:00:00', new \DateTimeZone('UTC')),
        ];

        $object = new DateTimeArraysObject($data, $data);
        $serializedObject = $this->serialize($object);

        $this->assertEquals($this->getContent('array_datetimes_object'), $serializedObject);

        if ($this->hasDeserializer()) {
            /** @var DateTimeArraysObject $deserializedObject */
            $deserializedObject = $this->deserialize($this->getContent('array_datetimes_object'), 'Kcs\Serializer\Tests\Fixtures\DateTimeArraysObject');

            /* deserialized object has a default timezone set depending on user's timezone settings. That's why we manually set the UTC timezone on the DateTime objects. */
            foreach ($deserializedObject->getArrayWithDefaultDateTime() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            foreach ($deserializedObject->getArrayWithFormattedDateTime() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            $this->assertEquals($object, $deserializedObject);
        }
    }

    public function testNamedDateTimeArrays()
    {
        $data = [
            new \DateTime('2047-01-01 12:47:47', new \DateTimeZone('UTC')),
            new \DateTime('2013-12-05 00:00:00', new \DateTimeZone('UTC')),
        ];

        $object = new NamedDateTimeArraysObject(['testdate1' => $data[0], 'testdate2' => $data[1]]);
        $serializedObject = $this->serialize($object);

        $this->assertEquals($this->getContent('array_named_datetimes_object'), $serializedObject);

        if ($this->hasDeserializer()) {
            // skip XML deserialization
            if ('xml' === $this->getFormat()) {
                return;
            }

            /** @var NamedDateTimeArraysObject $deserializedObject */
            $deserializedObject = $this->deserialize($this->getContent('array_named_datetimes_object'), 'Kcs\Serializer\Tests\Fixtures\NamedDateTimeArraysObject');

            /* deserialized object has a default timezone set depending on user's timezone settings. That's why we manually set the UTC timezone on the DateTime objects. */
            foreach ($deserializedObject->getNamedArrayWithFormattedDate() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            $this->assertEquals($object, $deserializedObject);
        }
    }

    public function testArrayMixed()
    {
        $this->assertEquals($this->getContent('array_mixed'), $this->serialize(['foo', 1, true, new SimpleObject('foo', 'bar'), [1, 3, true]]));
    }

    /**
     * @dataProvider getDateTime
     * @group datetime
     */
    public function testDateTime($key, $value, $type)
    {
        $this->assertEquals($this->getContent($key), $this->serialize($value));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent($key), $type);

            $this->assertTrue(is_object($deserialized));
            $this->assertEquals(get_class($value), get_class($deserialized));
            $this->assertEquals($value->getTimestamp(), $deserialized->getTimestamp());
        }
    }

    public function getDateTime()
    {
        return [
            ['date_time', new \DateTime('2011-08-30 00:00', new \DateTimeZone('UTC')), 'DateTime'],
        ];
    }

    /**
     * @dataProvider getTimestamp
     * @group datetime
     */
    public function testTimestamp($key, $value)
    {
        $this->assertEquals($this->getContent($key), $this->serialize($value));
    }

    public function getTimestamp()
    {
        return [
            ['timestamp', new Timestamp(new \DateTime('2016-02-11 00:00:00', new \DateTimeZone('UTC')))],
        ];
    }

    public function testDateInterval()
    {
        $duration = new \DateInterval('PT45M');

        $this->assertEquals($this->getContent('date_interval'), $this->serializer->serialize($duration, $this->getFormat()));
    }

    public function testBlogPost()
    {
        $post = new BlogPost('This is a nice title.', $author = new Author('Foo Bar'), new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), new Publisher('Bar Foo'));
        $post->addComment($comment = new Comment($author, 'foo'));

        $post->addTag($tag1 = new Tag('tag1'));
        $post->addTag($tag2 = new Tag('tag2'));

        $this->assertEquals($this->getContent('blog_post'), $this->serialize($post));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('blog_post'), get_class($post));
            $this->assertEquals('2011-07-30T00:00:00+0000', $this->getField($deserialized, 'createdAt')->format(\DateTime::ISO8601));
            $this->assertAttributeEquals('This is a nice title.', 'title', $deserialized);
            $this->assertAttributeSame(false, 'published', $deserialized);
            $this->assertAttributeSame('1edf9bf60a32d89afbb85b2be849e3ceed5f5b10', 'etag', $deserialized);
            $this->assertAttributeEquals(new ArrayCollection([$comment]), 'comments', $deserialized);
            $this->assertAttributeEquals(new Sequence([$comment]), 'comments2', $deserialized);
            $this->assertAttributeEquals($author, 'author', $deserialized);
            $this->assertAttributeEquals([$tag1, $tag2], 'tag', $deserialized);
        }
    }

    public function testDeserializingNull()
    {
        $objectConstructor = new InitializedBlogPostConstructor();
        $this->serializer = new Serializer($this->factory, $this->handlerRegistry, $objectConstructor, $this->serializationVisitors, $this->deserializationVisitors, $this->dispatcher);

        $post = new BlogPost('This is a nice title.', $author = new Author('Foo Bar'), new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), new Publisher('Bar Foo'));

        $this->setField($post, 'author', null);
        $this->setField($post, 'publisher', null);

        $this->assertEquals($this->getContent('blog_post_unauthored'), $this->serialize($post, SerializationContext::create()->setSerializeNull(true)));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('blog_post_unauthored'), get_class($post), DeserializationContext::create()->setSerializeNull(true));

            $this->assertEquals('2011-07-30T00:00:00+0000', $this->getField($deserialized, 'createdAt')->format(\DateTime::ISO8601));
            $this->assertAttributeEquals('This is a nice title.', 'title', $deserialized);
            $this->assertAttributeSame(false, 'published', $deserialized);
            $this->assertAttributeEquals(new ArrayCollection(), 'comments', $deserialized);
            $this->assertEquals(null, $this->getField($deserialized, 'author'));
        }
    }

    public function testReadOnly()
    {
        $author = new AuthorReadOnly(123, 'Ruud Kamphuis');
        $this->assertEquals($this->getContent('readonly'), $this->serialize($author));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('readonly'), get_class($author));
            $this->assertNull($this->getField($deserialized, 'id'));
            $this->assertEquals('Ruud Kamphuis', $this->getField($deserialized, 'name'));
        }
    }

    public function testReadOnlyClass()
    {
        $author = new AuthorReadOnlyPerClass(123, 'Ruud Kamphuis');
        $this->assertEquals($this->getContent('readonly'), $this->serialize($author));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('readonly'), get_class($author));
            $this->assertNull($this->getField($deserialized, 'id'));
            $this->assertEquals('Ruud Kamphuis', $this->getField($deserialized, 'name'));
        }
    }

    public function testPrice()
    {
        $price = new Price(3);
        $this->assertEquals($this->getContent('price'), $this->serialize($price));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('price'), get_class($price));
            $this->assertEquals(3, $this->getField($deserialized, 'price'));
        }
    }

    public function testOrder()
    {
        $order = new Order(new Price(12.34));
        $this->assertEquals($this->getContent('order'), $this->serialize($order));

        if ($this->hasDeserializer()) {
            $this->assertEquals($order, $this->deserialize($this->getContent('order'), get_class($order)));
        }
    }

    public function testCurrencyAwarePrice()
    {
        $price = new CurrencyAwarePrice(2.34);
        $this->assertEquals($this->getContent('currency_aware_price'), $this->serialize($price));

        if ($this->hasDeserializer()) {
            $this->assertEquals($price, $this->deserialize($this->getContent('currency_aware_price'), get_class($price)));
        }
    }

    public function testOrderWithCurrencyAwarePrice()
    {
        $order = new CurrencyAwareOrder(new CurrencyAwarePrice(1.23));
        $this->assertEquals($this->getContent('order_with_currency_aware_price'), $this->serialize($order));

        if ($this->hasDeserializer()) {
            $this->assertEquals($order, $this->deserialize($this->getContent('order_with_currency_aware_price'), get_class($order)));
        }
    }

    public function testInline()
    {
        $inline = new InlineParent();

        $result = $this->serialize($inline);
        $this->assertEquals($this->getContent('inline'), $result);

        // no deserialization support
    }

    public function testInlineEmptyChild()
    {
        $inline = new InlineParent(new InlineChildEmpty());

        $result = $this->serialize($inline);
        $this->assertEquals($this->getContent('inline_child_empty'), $result);

        // no deserialization support
    }

    /**
     * @group log
     */
    public function testLog()
    {
        $this->assertEquals($this->getContent('log'), $this->serialize($log = new Log()));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('log'), get_class($log));
            $this->assertEquals($log, $deserialized);
        }
    }

    public function testCircularReference()
    {
        $object = new CircularReferenceParent();
        $this->assertEquals($this->getContent('circular_reference'), $this->serialize($object));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('circular_reference'), get_class($object));

            $col = $this->getField($deserialized, 'collection');
            $this->assertEquals(2, count($col));
            $this->assertEquals('child1', $col[0]->getName());
            $this->assertEquals('child2', $col[1]->getName());
            $this->assertSame($deserialized, $col[0]->getParent());
            $this->assertSame($deserialized, $col[1]->getParent());

            $col = $this->getField($deserialized, 'anotherCollection');
            $this->assertEquals(2, count($col));
            $this->assertEquals('child1', $col[0]->getName());
            $this->assertEquals('child2', $col[1]->getName());
            $this->assertSame($deserialized, $col[0]->getParent());
            $this->assertSame($deserialized, $col[1]->getParent());
        }
    }

    public function testLifecycleCallbacks()
    {
        $object = new ObjectWithLifecycleCallbacks();
        $this->assertEquals($this->getContent('lifecycle_callbacks'), $this->serialize($object));
        $this->assertAttributeSame(null, 'name', $object);

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('lifecycle_callbacks'), get_class($object));
            $this->assertEquals($object, $deserialized);
        }
    }

    public function testFormErrors()
    {
        $errors = [
            new FormError('This is the form error'),
            new FormError('Another error'),
        ];

        $this->assertEquals($this->getContent('form_errors'), $this->serialize($errors));
    }

    public function testNestedFormErrors()
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $formConfigBuilder = new FormConfigBuilder('foo', null, $dispatcher->reveal());
        $formConfigBuilder->setCompound(true);
        $formConfigBuilder->setDataMapper($this->prophesize(DataMapperInterface::class)->reveal());
        $fooConfig = $formConfigBuilder->getFormConfig();

        $form = new Form($fooConfig);
        $form->addError(new FormError('This is the form error'));

        $formConfigBuilder = new FormConfigBuilder('bar', null, $dispatcher->reveal());
        $barConfig = $formConfigBuilder->getFormConfig();
        $child = new Form($barConfig);
        $child->addError(new FormError('Error of the child form'));
        $form->add($child);

        $this->assertEquals($this->getContent('nested_form_errors'), $this->serialize($form));
    }

    public function testFormErrorsWithNonFormComponents()
    {
        if (! class_exists(SubmitType::class)) {
            $this->markTestSkipped('Not using Symfony Form >= 2.3 with submit type');
        }

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $factoryBuilder = new FormFactoryBuilder();
        $factoryBuilder->addType(new SubmitType());
        $factoryBuilder->addType(new ButtonType());
        $factory = $factoryBuilder->getFormFactory();

        $formConfigBuilder = new FormConfigBuilder('foo', null, $dispatcher->reveal());
        $formConfigBuilder->setFormFactory($factory);
        $formConfigBuilder->setCompound(true);
        $formConfigBuilder->setDataMapper($this->prophesize(DataMapperInterface::class)->reveal());
        $fooConfig = $formConfigBuilder->getFormConfig();

        $form = new Form($fooConfig);
        $form->add('save', method_exists(AbstractType::class, 'getBlockPrefix') ? SubmitType::class : 'submit');

        $this->serialize($form);
        $this->assertTrue(true);  // Exception not thrown
    }

    public function testConstraintViolation()
    {
        $violation = new ConstraintViolation('Message of violation', 'Message of violation', [], null, 'foo', null);

        $this->assertEquals($this->getContent('constraint_violation'), $this->serialize($violation));
    }

    public function testConstraintViolationList()
    {
        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation('Message of violation', 'Message of violation', [], null, 'foo', null));
        $violations->add(new ConstraintViolation('Message of another violation', 'Message of another violation', [], null, 'bar', null));

        $this->assertEquals($this->getContent('constraint_violation_list'), $this->serialize($violations));
    }

    public function testDoctrineProxy()
    {
        if (! class_exists('Doctrine\ORM\Version')) {
            $this->markTestSkipped('Doctrine is not available.');
        }

        $object = new SimpleObjectProxy('foo', 'bar');

        $this->assertEquals($this->getContent('orm_proxy'), $this->serialize($object));
    }

    public function testInitializedDoctrineProxy()
    {
        if (! class_exists('Doctrine\ORM\Version')) {
            $this->markTestSkipped('Doctrine is not available.');
        }

        $object = new SimpleObjectProxy('foo', 'bar');
        $object->__load();

        $this->assertEquals($this->getContent('orm_proxy'), $this->serialize($object));
    }

    public function testCustomAccessor()
    {
        $post = new IndexedCommentsBlogPost();

        $this->assertEquals($this->getContent('custom_accessor'), $this->serialize($post));
    }

    public function testMixedAccessTypes()
    {
        $object = new GetSetObject();

        $this->assertEquals($this->getContent('mixed_access_types'), $this->serialize($object));

        if ($this->hasDeserializer()) {
            $object = $this->deserialize($this->getContent('mixed_access_types'), 'Kcs\Serializer\Tests\Fixtures\GetSetObject');
            $this->assertAttributeEquals(1, 'id', $object);
            $this->assertAttributeEquals('Johannes', 'name', $object);
            $this->assertAttributeEquals(42, 'readOnlyProperty', $object);
        }
    }

    public function testAccessorOrder()
    {
        $this->assertEquals($this->getContent('accessor_order_child'), $this->serialize(new AccessorOrderChild()));
        $this->assertEquals($this->getContent('accessor_order_parent'), $this->serialize(new AccessorOrderParent()));
        $this->assertEquals($this->getContent('accessor_order_methods'), $this->serialize(new AccessorOrderMethod()));
    }

    public function testGroups()
    {
        $groupsObject = new GroupsObject();

        $this->assertEquals($this->getContent('groups_all'), $this->serializer->serialize($groupsObject, $this->getFormat()));

        $this->assertEquals(
            $this->getContent('groups_foo'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo']))
        );

        $this->assertEquals(
            $this->getContent('groups_foo_not_baz'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo', 'baz']))
        );

        $this->assertEquals(
            $this->getContent('groups_foobar'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo', 'bar'])->setSerializeNull(true))
        );

        $this->assertEquals(
            $this->getContent('groups_all'),
            $this->serializer->serialize($groupsObject, $this->getFormat())
        );

        $this->assertEquals(
            $this->getContent('groups_default'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['Default']))
        );
    }

    public function testAdvancedGroups()
    {
        $adrien = new GroupsUser(
            'John',
            new GroupsUser(
                'John Manager',
                null,
                [
                    new GroupsUser(
                        'John Manager friend 1',
                        new GroupsUser('John Manager friend 1 manager')
                    ),
                    new GroupsUser('John Manager friend 2'),
                ]
            ),
            [
                new GroupsUser(
                    'John friend 1',
                    new GroupsUser('John friend 1 manager')
                ),
                new GroupsUser(
                    'John friend 2',
                    new GroupsUser('John friend 2 manager')
                ),
            ]
        );

        $this->assertEquals(
            $this->getContent('groups_advanced'),
            $this->serializer->serialize(
                $adrien,
                $this->getFormat(),
                SerializationContext::create()->setGroups([
                    'Default',
                    'manager_group',
                    'friends_group',
                    'manager' => [
                        'Default',
                        'friends_group',
                        'friends' => ['nickname_group'],
                    ],
                    'friends' => [
                        'manager_group',
                    ],
                ])
            )
        );
    }

    /**
     * @expectedException \Kcs\Serializer\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid group name "foo, bar" on "Kcs\Serializer\Tests\Fixtures\InvalidGroupsObject->foo", did you mean to create multiple groups?
     */
    public function testInvalidGroupName()
    {
        $groupsObject = new InvalidGroupsObject();

        $this->serializer->serialize($groupsObject, $this->getFormat());
    }

    public function testVirtualProperty()
    {
        $this->assertEquals($this->getContent('virtual_properties'), $this->serialize(new ObjectWithVirtualProperties()));
    }

    public function testVirtualVersions()
    {
        $this->assertEquals(
            $this->getContent('virtual_properties_low'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(2))
        );

        $this->assertEquals(
            $this->getContent('virtual_properties_all'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(7))
        );

        $this->assertEquals(
            $this->getContent('virtual_properties_high'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(9))
        );
    }

    public function testCustomHandler()
    {
        if (! $this->hasDeserializer()) {
            return;
        }

        $handler = function () {
            return new CustomDeserializationObject('customly_unserialized_value');
        };

        $this->handlerRegistry->registerHandler(Direction::DIRECTION_DESERIALIZATION, 'CustomDeserializationObject', $handler);

        $serialized = $this->serializer->serialize(new CustomDeserializationObject('sometext'), $this->getFormat());
        $object = $this->serializer->deserialize($serialized, Type::from('CustomDeserializationObject'), $this->getFormat());
        $this->assertEquals('customly_unserialized_value', $object->someProperty);
    }

    public function testInput()
    {
        $this->assertEquals($this->getContent('input'), $this->serializer->serialize(new Input(), $this->getFormat()));
    }

    public function testObjectWithEmptyHash()
    {
        $this->assertEquals($this->getContent('hash_empty'), $this->serializer->serialize(new ObjectWithEmptyHash(), $this->getFormat()));
    }

    /**
     * @group null
     */
    public function testSerializeObjectWhenNull()
    {
        $this->assertEquals(
            $this->getContent('object_when_null'),
            $this->serialize(new Comment(null, 'foo'), SerializationContext::create()->setSerializeNull(false))
        );

        $this->assertEquals(
            $this->getContent('object_when_null_and_serialized'),
            $this->serialize(new Comment(null, 'foo'), SerializationContext::create()->setSerializeNull(true))
        );
    }

    /**
     * @group polymorphic
     */
    public function testPolymorphicObjects()
    {
        $this->assertEquals(
            $this->getContent('car'),
            $this->serialize(new Car(5))
        );

        if ($this->hasDeserializer()) {
            $this->assertEquals(
                new Car(5),
                $this->deserialize(
                    $this->getContent('car'),
                    'Kcs\Serializer\Tests\Fixtures\Discriminator\Car'
                ),
                'Class is resolved correctly when concrete sub-class is used.'
            );

            $this->assertEquals(
                new Car(5),
                $this->deserialize(
                    $this->getContent('car'),
                    'Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle'
                ),
                'Class is resolved correctly when least supertype is used.'
            );

            $this->assertEquals(
                new Car(5),
                $this->deserialize(
                    $this->getContent('car_without_type'),
                    'Kcs\Serializer\Tests\Fixtures\Discriminator\Car'
                ),
                'Class is resolved correctly when concrete sub-class is used and no type is defined.'
            );
        }
    }

    /**
     * @group polymorphic
     */
    public function testNestedPolymorphicObjects()
    {
        $garage = new Garage([new Car(3), new Moped(1)]);
        $this->assertEquals(
            $this->getContent('garage'),
            $this->serialize($garage)
        );

        if ($this->hasDeserializer()) {
            $this->assertEquals(
                $garage,
                $this->deserialize(
                    $this->getContent('garage'),
                    'Kcs\Serializer\Tests\Fixtures\Garage'
                )
            );
        }
    }

    /**
     * @group polymorphic
     */
    public function testNestedPolymorphicInterfaces()
    {
        $garage = new VehicleInterfaceGarage([new Car(3), new Moped(1)]);
        $this->assertEquals(
            $this->getContent('garage'),
            $this->serialize($garage)
        );

        if ($this->hasDeserializer()) {
            $this->assertEquals(
                $garage,
                $this->deserialize(
                    $this->getContent('garage'),
                    'Kcs\Serializer\Tests\Fixtures\VehicleInterfaceGarage'
                )
            );
        }
    }

    /**
     * @group polymorphic
     * @expectedException \LogicException
     */
    public function testPolymorphicObjectsInvalidDeserialization()
    {
        if (! $this->hasDeserializer()) {
            throw new \LogicException('No deserializer');
        }

        $this->deserialize(
            $this->getContent('car_without_type'),
            'Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle'
        );
    }

    public function testDepthExclusionStrategy()
    {
        $context = SerializationContext::create()
            ->addExclusionStrategy(new DepthExclusionStrategy())
        ;

        $data = new Tree(
            new Node([
                new Node([
                    new Node([
                        new Node([
                            new Node(),
                        ]),
                    ]),
                ]),
            ])
        );

        $this->assertEquals($this->getContent('tree'), $this->serializer->serialize($data, $this->getFormat(), $context));
    }

    public function testDeserializingIntoExistingObject()
    {
        if (! $this->hasDeserializer()) {
            return;
        }

        $objectConstructor = new InitializedObjectConstructor(new UnserializeObjectConstructor());
        $serializer = new Serializer(
            $this->factory, $this->handlerRegistry, $objectConstructor,
            $this->serializationVisitors, $this->deserializationVisitors, $this->dispatcher
        );

        $order = new Order(new Price(12));

        $context = new DeserializationContext();
        $context->attributes->set('target', $order);

        $deseralizedOrder = $serializer->deserialize(
            $this->getContent('order'),
            Type::from($order),
            $this->getFormat(),
            $context
        );

        $this->assertSame($order, $deseralizedOrder);
        $this->assertEquals(new Order(new Price(12.34)), $deseralizedOrder);
        $this->assertAttributeInstanceOf('Kcs\Serializer\Tests\Fixtures\Price', 'cost', $deseralizedOrder);
    }

    public function testAdditionalField()
    {
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'Kcs\Serializer\Tests\Fixtures\Author::links',
            function (VisitorInterface $visitor, Author $author, Type $type, Context $context) {
                return $visitor->visitArray([
                    'details' => 'http://foo.bar/details/'.$author->getName(),
                    'comments' => 'http://foo.bar/details/'.$author->getName().'/comments',
                ], Type::parse('array<string,string>'), $context);
            }
        );

        $list = new AuthorList();
        $list->add(new Author('Foo'));
        $list->add(new Author('Bar'));

        $this->assertEquals($this->getContent('object_with_additional_field'), $this->serialize($list));
    }

    public function testAdditionalFieldInheritedBySubclasses()
    {
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'Kcs\Serializer\Tests\Fixtures\Author::links',
            function (VisitorInterface $visitor, Author $author, Type $type, Context $context) {
                return $visitor->visitArray([
                    'details' => 'http://foo.bar/details/'.$author->getName(),
                    'comments' => 'http://foo.bar/details/'.$author->getName().'/comments',
                ], Type::parse('array<string,string>'), $context);
            }
        );

        $list = new AuthorList();
        $list->add(new AuthorChild('Foo'));
        $list->add(new AuthorChild('Bar'));

        $this->assertEquals($this->getContent('object_subclass_with_additional_field'), $this->serialize($list));
    }

    abstract protected function getContent($key);

    abstract protected function getFormat();

    protected function hasDeserializer()
    {
        return true;
    }

    protected function serialize($data, Context $context = null)
    {
        return $this->serializer->serialize($data, $this->getFormat(), $context);
    }

    protected function deserialize($content, $type, Context $context = null)
    {
        return $this->serializer->deserialize($content, Type::from($type), $this->getFormat(), $context);
    }

    protected function setUp()
    {
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->factory = new MetadataFactory($loader);

        $this->handlerRegistry = new HandlerRegistry();
        $this->handlerRegistry->registerSubscribingHandler(new ConstraintViolationHandler());
        $this->handlerRegistry->registerSubscribingHandler(new DateHandler());
        $this->handlerRegistry->registerSubscribingHandler(new FormErrorHandler(new IdentityTranslator(new MessageSelector())));
        $this->handlerRegistry->registerSubscribingHandler(new PhpCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'AuthorList',
            function (VisitorInterface $visitor, $object, Type $type, Context $context) {
                return $visitor->visitArray(iterator_to_array($object), $type, $context);
            }
        );
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_DESERIALIZATION, 'AuthorList',
            function (VisitorInterface $visitor, $data, $type, Context $context) {
                $type = new Type(
                    'array',
                    [
                        Type::from('integer'),
                        Type::from(Author::class),
                    ]
                );

                $elements = $context->accept($data, $type);
                $list = new AuthorList();
                foreach ($elements as $author) {
                    $list->add($author);
                }

                return $list;
            }
        );

        $this->dispatcher = new EventDispatcher();
        $this->dispatcher->addSubscriber(new DoctrineProxySubscriber());

        $namingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());
        $objectConstructor = new UnserializeObjectConstructor();
        $this->serializationVisitors = [
            'array' => new GenericSerializationVisitor($namingStrategy),
            'json' => new JsonSerializationVisitor($namingStrategy),
            'xml' => new XmlSerializationVisitor($namingStrategy),
            'yml' => new YamlSerializationVisitor($namingStrategy),
        ];
        $this->deserializationVisitors = [
            'array' => new GenericDeserializationVisitor($namingStrategy),
            'xml' => new XmlDeserializationVisitor($namingStrategy),
            'yml' => new YamlDeserializationVisitor($namingStrategy),
            'json' => new JsonDeserializationVisitor($namingStrategy),
        ];

        $this->serializer = new Serializer($this->factory, $this->handlerRegistry, $objectConstructor, $this->serializationVisitors, $this->deserializationVisitors, $this->dispatcher);
    }

    protected function getField($obj, $name)
    {
        $ref = new \ReflectionProperty($obj, $name);
        $ref->setAccessible(true);

        return $ref->getValue($obj);
    }

    private function setField($obj, $name, $value)
    {
        $ref = new \ReflectionProperty($obj, $name);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }
}
