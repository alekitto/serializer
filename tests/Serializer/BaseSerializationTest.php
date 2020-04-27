<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Serializer;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Version;
use Kcs\Serializer\Construction\InitializedObjectConstructor;
use Kcs\Serializer\Construction\UnserializeObjectConstructor;
use Kcs\Serializer\Context;
use Kcs\Serializer\DeserializationContext;
use Kcs\Serializer\Direction;
use Kcs\Serializer\EventDispatcher\PostDeserializeEvent;
use Kcs\Serializer\EventDispatcher\PreSerializeEvent;
use Kcs\Serializer\EventDispatcher\Subscriber\DoctrineProxySubscriber;
use Kcs\Serializer\Exception\InvalidArgumentException;
use Kcs\Serializer\Exclusion\DepthExclusionStrategy;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\Handler\ArrayCollectionHandler;
use Kcs\Serializer\Handler\ConstraintViolationHandler;
use Kcs\Serializer\Handler\DateHandler;
use Kcs\Serializer\Handler\FormErrorHandler;
use Kcs\Serializer\Handler\HandlerRegistry;
use Kcs\Serializer\Handler\PhpCollectionHandler;
use Kcs\Serializer\Handler\UuidInterfaceHandler;
use Kcs\Serializer\JsonDeserializationVisitor;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\MetadataFactory;
use Kcs\Serializer\Naming\UnderscoreNamingStrategy;
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
use Kcs\Serializer\Tests\Fixtures\Discriminator\Vehicle;
use Kcs\Serializer\Tests\Fixtures\Garage;
use Kcs\Serializer\Tests\Fixtures\GetSetObject;
use Kcs\Serializer\Tests\Fixtures\GroupsObject;
use Kcs\Serializer\Tests\Fixtures\GroupsProvider;
use Kcs\Serializer\Tests\Fixtures\GroupsUser;
use Kcs\Serializer\Tests\Fixtures\IndexedCommentsBlogPost;
use Kcs\Serializer\Tests\Fixtures\InitializedBlogPostConstructor;
use Kcs\Serializer\Tests\Fixtures\InlineChildEmpty;
use Kcs\Serializer\Tests\Fixtures\InlineParent;
use Kcs\Serializer\Tests\Fixtures\Input;
use Kcs\Serializer\Tests\Fixtures\InvalidGroupsObject;
use Kcs\Serializer\Tests\Fixtures\Log;
use Kcs\Serializer\Tests\Fixtures\NamedDateTimeArraysObject;
use Kcs\Serializer\Tests\Fixtures\NamedDateTimeInterfaceArraysObject;
use Kcs\Serializer\Tests\Fixtures\Node;
use Kcs\Serializer\Tests\Fixtures\ObjectWithEmptyHash;
use Kcs\Serializer\Tests\Fixtures\ObjectWithIntListAndIntMap;
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
use Ramsey\Uuid\Uuid;
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
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class BaseSerializationTest extends TestCase
{
    protected $factory;
    protected $dispatcher;

    /**
     * @var Serializer
     */
    protected $serializer;
    protected $handlerRegistry;
    protected $serializationVisitors;
    protected $deserializationVisitors;

    public function testSerializeNullArray(): void
    {
        $arr = ['foo' => 'bar', 'baz' => null];

        self::assertEquals(
            $this->getContent('nullable'),
            $this->serializer->serialize($arr, $this->getFormat(), SerializationContext::create()->setSerializeNull(true))
        );
    }

    public function testSerializeNullObject(): void
    {
        $obj = new ObjectWithNullProperty('foo', 'bar');

        self::assertEquals(
            $this->getContent('simple_object_nullable'),
            $this->serializer->serialize($obj, $this->getFormat(), SerializationContext::create()->setSerializeNull(true))
        );
    }

    /**
     * @dataProvider getTypes
     */
    public function testNull(string $type): void
    {
        self::assertEquals($this->getContent('null'), $this->serialize(null), $type);

        if ($this->hasDeserializer()) {
            self::assertEquals(null, $this->deserialize($this->getContent('null'), $type));
        }
    }

    public function getTypes(): iterable
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

    public function testString(): void
    {
        self::assertEquals($this->getContent('string'), $this->serialize('foo'));

        if ($this->hasDeserializer()) {
            self::assertEquals('foo', $this->deserialize($this->getContent('string'), 'string'));
        }
    }

    /**
     * @dataProvider getBooleans
     */
    public function testBooleans(string $strBoolean, bool $boolean): void
    {
        self::assertEquals($this->getContent('boolean_'.$strBoolean), $this->serialize($boolean));

        if ($this->hasDeserializer()) {
            self::assertSame($boolean, $this->deserialize($this->getContent('boolean_'.$strBoolean), 'boolean'));
        }
    }

    public function getBooleans(): iterable
    {
        return [
            ['true', true],
            ['false', false],
        ];
    }

    /**
     * @dataProvider getNumerics
     */
    public function testNumerics(string $key, $value, string $type): void
    {
        self::assertEquals($this->getContent($key), $this->serialize($value));

        if ($this->hasDeserializer()) {
            self::assertEquals($value, $this->deserialize($this->getContent($key), $type));
        }
    }

    public function getNumerics(): iterable
    {
        return [
            ['integer', 1, 'integer'],
            ['float', 4.533, 'double'],
            ['float', 4.533, 'float'],
            ['float_trailing_zero', 1.0, 'double'],
            ['float_trailing_zero', 1.0, 'float'],
        ];
    }

    public function testSimpleObject(): void
    {
        self::assertEquals($this->getContent('simple_object'), $this->serialize($obj = new SimpleObject('foo', 'bar')));

        if ($this->hasDeserializer()) {
            self::assertEquals($obj, $this->deserialize($this->getContent('simple_object'), \get_class($obj)));
        }
    }

    public function testArrayStrings(): void
    {
        $data = ['foo', 'bar'];
        self::assertEquals($this->getContent('array_strings'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            self::assertEquals($data, $this->deserialize($this->getContent('array_strings'), Type::parse('array<string>')));
        }
    }

    public function testArrayBooleans(): void
    {
        $data = [true, false];
        self::assertEquals($this->getContent('array_booleans'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            self::assertEquals($data, $this->deserialize($this->getContent('array_booleans'), Type::parse('array<boolean>')));
        }
    }

    public function testArrayIntegers(): void
    {
        $data = [1, 3, 4];
        self::assertEquals($this->getContent('array_integers'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            self::assertEquals($data, $this->deserialize($this->getContent('array_integers'), Type::parse('array<integer>')));
        }
    }

    public function testArrayFloats(): void
    {
        $data = [1.34, 3.0, 6.42];
        self::assertEquals($this->getContent('array_floats'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            self::assertEquals($data, $this->deserialize($this->getContent('array_floats'), Type::parse('array<double>')));
        }
    }

    public function testArrayObjects(): void
    {
        $data = [new SimpleObject('foo', 'bar'), new SimpleObject('baz', 'boo')];
        self::assertEquals($this->getContent('array_objects'), $this->serialize($data));

        if ($this->hasDeserializer()) {
            self::assertEquals($data, $this->deserialize($this->getContent('array_objects'), Type::parse('array<Kcs\Serializer\Tests\Fixtures\SimpleObject>')));
        }
    }

    public function testArrayListAndMapDifference(): void
    {
        $arrayData = [0 => 1, 2 => 2, 3 => 3]; // Misses key 1
        $data = new ObjectWithIntListAndIntMap($arrayData, $arrayData);

        self::assertEquals($this->getContent('array_list_and_map_difference'), $this->serialize($data));
    }

    public function testDateTimeArrays(): void
    {
        $data = [
            new \DateTime('2047-01-01 12:47:47', new \DateTimeZone('UTC')),
            new \DateTime('2013-12-05 00:00:00', new \DateTimeZone('UTC')),
        ];

        $object = new DateTimeArraysObject($data, $data);
        $serializedObject = $this->serialize($object);

        self::assertEquals($this->getContent('array_datetimes_object'), $serializedObject);

        if ($this->hasDeserializer()) {
            /** @var DateTimeArraysObject $deserializedObject */
            $deserializedObject = $this->deserialize($this->getContent('array_datetimes_object'), DateTimeArraysObject::class);

            /* deserialized object has a default timezone set depending on user's timezone settings. That's why we manually set the UTC timezone on the DateTime objects. */
            foreach ($deserializedObject->getArrayWithDefaultDateTime() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            foreach ($deserializedObject->getArrayWithFormattedDateTime() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            self::assertEquals($object, $deserializedObject);
        }
    }

    public function testNamedDateTimeArrays(): void
    {
        $data = [
            new \DateTime('2047-01-01 12:47:47', new \DateTimeZone('UTC')),
            new \DateTime('2013-12-05 00:00:00', new \DateTimeZone('UTC')),
        ];

        $object = new NamedDateTimeArraysObject(['testdate1' => $data[0], 'testdate2' => $data[1]]);
        $serializedObject = $this->serialize($object);

        self::assertEquals($this->getContent('array_named_datetimes_object'), $serializedObject);

        if ($this->hasDeserializer()) {
            // skip XML deserialization
            if ('xml' === $this->getFormat()) {
                return;
            }

            /** @var NamedDateTimeArraysObject $deserializedObject */
            $deserializedObject = $this->deserialize($this->getContent('array_named_datetimes_object'), NamedDateTimeArraysObject::class);

            /* deserialized object has a default timezone set depending on user's timezone settings. That's why we manually set the UTC timezone on the DateTime objects. */
            foreach ($deserializedObject->getNamedArrayWithFormattedDate() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            self::assertEquals($object, $deserializedObject);
        }
    }

    public function testNamedDateTimeInterfaceArrays(): void
    {
        $data = [
            new \DateTimeImmutable('2047-01-01 12:47:47', new \DateTimeZone('UTC')),
            new \DateTime('2013-12-05 00:00:00', new \DateTimeZone('UTC')),
        ];

        $object = new NamedDateTimeInterfaceArraysObject(['testdate1' => $data[0], 'testdate2' => $data[1]]);
        $serializedObject = $this->serialize($object);

        self::assertEquals($this->getContent('array_named_datetimes_object'), $serializedObject);

        if ($this->hasDeserializer()) {
            // skip XML deserialization
            if ('xml' === $this->getFormat()) {
                return;
            }

            /** @var NamedDateTimeInterfaceArraysObject $deserializedObject */
            $deserializedObject = $this->deserialize($this->getContent('array_named_datetimes_object'), NamedDateTimeInterfaceArraysObject::class);

            /* deserialized object has a default timezone set depending on user's timezone settings. That's why we manually set the UTC timezone on the DateTime objects. */
            foreach ($deserializedObject->getNamedArrayWithFormattedDate() as $dateTime) {
                $dateTime->setTimezone(new \DateTimeZone('UTC'));
            }

            self::assertEquals($object, $deserializedObject);
        }
    }

    public function testArrayMixed(): void
    {
        self::assertEquals($this->getContent('array_mixed'), $this->serialize(['foo', 1, true, new SimpleObject('foo', 'bar'), [1, 3, true]]));
    }

    /**
     * @dataProvider getDateTime
     * @group datetime
     */
    public function testDateTime(string $key, \DateTimeInterface $value, string $type): void
    {
        self::assertEquals($this->getContent($key), $this->serialize($value));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent($key), $type);

            self::assertTrue(\is_object($deserialized));
            self::assertEquals(\get_class($value), \get_class($deserialized));
            self::assertEquals($value->getTimestamp(), $deserialized->getTimestamp());
        }
    }

    public function getDateTime(): iterable
    {
        return [
            ['date_time', new \DateTime('2011-08-30 00:00', new \DateTimeZone('UTC')), 'DateTime'],
        ];
    }

    /**
     * @dataProvider getTimestamp
     * @group datetime
     */
    public function testTimestamp(string $key, Timestamp $value): void
    {
        self::assertEquals($this->getContent($key), $this->serialize($value));
    }

    public function getTimestamp(): array
    {
        return [
            ['timestamp', new Timestamp(new \DateTime('2016-02-11 00:00:00', new \DateTimeZone('UTC')))],
        ];
    }

    public function testDateInterval(): void
    {
        $duration = new \DateInterval('PT45M');

        self::assertEquals($this->getContent('date_interval'), $this->serializer->serialize($duration, $this->getFormat()));
    }

    public function testBlogPost(): void
    {
        $post = new BlogPost('This is a nice title.', $author = new Author('Foo Bar'), new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), new Publisher('Bar Foo'));
        $post->addComment($comment = new Comment($author, 'foo'));

        $post->addTag($tag1 = new Tag('tag1'));
        $post->addTag($tag2 = new Tag('tag2'));

        self::assertEquals($this->getContent('blog_post'), $this->serialize($post));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('blog_post'), \get_class($post));
            self::assertEquals('2011-07-30T00:00:00+0000', $this->getField($deserialized, 'createdAt')->format(\DateTime::ISO8601));
            self::assertEquals('This is a nice title.', $this->getField($deserialized, 'title'));
            self::assertFalse($this->getField($deserialized, 'published'));
            self::assertSame('1edf9bf60a32d89afbb85b2be849e3ceed5f5b10', $this->getField($deserialized, 'etag'));
            self::assertEquals(new ArrayCollection([$comment]), $this->getField($deserialized, 'comments'));
            self::assertEquals(new Sequence([$comment]), $this->getField($deserialized, 'comments2'));
            self::assertEquals($author, $this->getField($deserialized, 'author'));
            self::assertEquals([$tag1, $tag2], $this->getField($deserialized, 'tag'));
        }
    }

    public function testDeserializingNull(): void
    {
        $objectConstructor = new InitializedBlogPostConstructor();
        $this->serializer = new Serializer($this->factory, $this->handlerRegistry, $objectConstructor, $this->serializationVisitors, $this->deserializationVisitors, $this->dispatcher);

        $post = new BlogPost('This is a nice title.', $author = new Author('Foo Bar'), new \DateTime('2011-07-30 00:00', new \DateTimeZone('UTC')), new Publisher('Bar Foo'));

        $this->setField($post, 'author', null);
        $this->setField($post, 'publisher', null);

        self::assertEquals($this->getContent('blog_post_unauthored'), $this->serialize($post, SerializationContext::create()->setSerializeNull(true)));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('blog_post_unauthored'), \get_class($post), DeserializationContext::create()->setSerializeNull(true));

            self::assertEquals('2011-07-30T00:00:00+0000', $this->getField($deserialized, 'createdAt')->format(\DateTime::ISO8601));
            self::assertEquals('This is a nice title.', $this->getField($deserialized, 'title'));
            self::assertFalse($this->getField($deserialized, 'published'));
            self::assertEquals(new ArrayCollection(), $this->getField($deserialized, 'comments'));
            self::assertNull($this->getField($deserialized, 'author'));
        }
    }

    public function testReadOnly(): void
    {
        $author = new AuthorReadOnly(123, 'Ruud Kamphuis');
        self::assertEquals($this->getContent('readonly'), $this->serialize($author));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('readonly'), \get_class($author));
            self::assertNull($this->getField($deserialized, 'id'));
            self::assertEquals('Ruud Kamphuis', $this->getField($deserialized, 'name'));
        }
    }

    public function testReadOnlyClass(): void
    {
        $author = new AuthorReadOnlyPerClass(123, 'Ruud Kamphuis');
        self::assertEquals($this->getContent('readonly'), $this->serialize($author));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('readonly'), \get_class($author));
            self::assertNull($this->getField($deserialized, 'id'));
            self::assertEquals('Ruud Kamphuis', $this->getField($deserialized, 'name'));
        }
    }

    public function testPrice(): void
    {
        $price = new Price(3);
        self::assertEquals($this->getContent('price'), $this->serialize($price));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('price'), \get_class($price));
            self::assertEquals(3, $this->getField($deserialized, 'price'));
        }
    }

    public function testOrder(): void
    {
        $order = new Order(new Price(12.34));
        self::assertEquals($this->getContent('order'), $this->serialize($order));

        if ($this->hasDeserializer()) {
            self::assertEquals($order, $this->deserialize($this->getContent('order'), \get_class($order)));
        }
    }

    public function testCurrencyAwarePrice(): void
    {
        $price = new CurrencyAwarePrice(2.34);
        self::assertEquals($this->getContent('currency_aware_price'), $this->serialize($price));

        if ($this->hasDeserializer()) {
            self::assertEquals($price, $this->deserialize($this->getContent('currency_aware_price'), \get_class($price)));
        }
    }

    public function testOrderWithCurrencyAwarePrice(): void
    {
        $order = new CurrencyAwareOrder(new CurrencyAwarePrice(1.23));
        self::assertEquals($this->getContent('order_with_currency_aware_price'), $this->serialize($order));

        if ($this->hasDeserializer()) {
            self::assertEquals($order, $this->deserialize($this->getContent('order_with_currency_aware_price'), \get_class($order)));
        }
    }

    public function testInline(): void
    {
        $inline = new InlineParent();

        $result = $this->serialize($inline);
        self::assertEquals($this->getContent('inline'), $result);

        // no deserialization support
    }

    public function testInlineEmptyChild(): void
    {
        $inline = new InlineParent(new InlineChildEmpty());

        $result = $this->serialize($inline);
        self::assertEquals($this->getContent('inline_child_empty'), $result);

        // no deserialization support
    }

    /**
     * @group log
     */
    public function testLog(): void
    {
        self::assertEquals($this->getContent('log'), $this->serialize($log = new Log()));

        if ($this->hasDeserializer()) {
            $deserialized = $this->deserialize($this->getContent('log'), \get_class($log));
            self::assertEquals($log, $deserialized);
        }
    }

    public function testCircularReference(): void
    {
        $object = new CircularReferenceParent();
        self::assertEquals($this->getContent('circular_reference'), $this->serialize($object));

        if ($this->hasDeserializer()) {
            $this->dispatcher->addListener(PostDeserializeEvent::class, static function (PostDeserializeEvent $event): void {
                $object = $event->getData();
                if (! $object instanceof CircularReferenceParent) {
                    return;
                }

                $object->afterDeserialization();
            });

            $deserialized = $this->deserialize($this->getContent('circular_reference'), \get_class($object));

            $col = $this->getField($deserialized, 'collection');
            self::assertCount(2, $col);
            self::assertEquals('child1', $col[0]->getName());
            self::assertEquals('child2', $col[1]->getName());
            self::assertSame($deserialized, $col[0]->getParent());
            self::assertSame($deserialized, $col[1]->getParent());

            $col = $this->getField($deserialized, 'anotherCollection');
            self::assertCount(2, $col);
            self::assertEquals('child1', $col[0]->getName());
            self::assertEquals('child2', $col[1]->getName());
            self::assertSame($deserialized, $col[0]->getParent());
            self::assertSame($deserialized, $col[1]->getParent());
        }
    }

    public function testFormErrors(): void
    {
        $errors = [
            new FormError('This is the form error'),
            new FormError('Another error'),
        ];

        self::assertEquals($this->getContent('form_errors'), $this->serialize($errors));
    }

    public function testNestedFormErrors(): void
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

        self::assertEquals($this->getContent('nested_form_errors'), $this->serialize($form));
    }

    public function testFormErrorsWithNonFormComponents(): void
    {
        if (! \class_exists(SubmitType::class)) {
            self::markTestSkipped('Not using Symfony Form >= 2.3 with submit type');
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
        $form->add('save', \method_exists(AbstractType::class, 'getBlockPrefix') ? SubmitType::class : 'submit');

        $this->serialize($form);
        self::assertTrue(true);  // Exception not thrown
    }

    public function testConstraintViolation(): void
    {
        $violation = new ConstraintViolation('Message of violation', 'Message of violation', [], null, 'foo', null);

        self::assertEquals($this->getContent('constraint_violation'), $this->serialize($violation));
    }

    public function testConstraintViolationList(): void
    {
        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation('Message of violation', 'Message of violation', [], null, 'foo', null));
        $violations->add(new ConstraintViolation('Message of another violation', 'Message of another violation', [], null, 'bar', null));

        self::assertEquals($this->getContent('constraint_violation_list'), $this->serialize($violations));
    }

    public function testDoctrineProxy(): void
    {
        if (! \class_exists(Version::class)) {
            self::markTestSkipped('Doctrine is not available.');
        }

        $object = new SimpleObjectProxy('foo', 'bar');

        self::assertEquals($this->getContent('orm_proxy'), $this->serialize($object));
    }

    public function testInitializedDoctrineProxy(): void
    {
        if (! \class_exists(Version::class)) {
            self::markTestSkipped('Doctrine is not available.');
        }

        $object = new SimpleObjectProxy('foo', 'bar');
        $object->__load();

        self::assertEquals($this->getContent('orm_proxy'), $this->serialize($object));
    }

    public function testCustomAccessor(): void
    {
        $post = new IndexedCommentsBlogPost();

        self::assertEquals($this->getContent('custom_accessor'), $this->serialize($post));
    }

    public function testMixedAccessTypes(): void
    {
        $object = new GetSetObject();

        self::assertEquals($this->getContent('mixed_access_types'), $this->serialize($object));

        if ($this->hasDeserializer()) {
            $object = $this->deserialize($this->getContent('mixed_access_types'), GetSetObject::class);
            self::assertEquals(1, $this->getField($object, 'id'));
            self::assertEquals('Johannes', $this->getField($object, 'name'));
            self::assertEquals(42, $this->getField($object, 'readOnlyProperty'));
        }
    }

    public function testAccessorOrder(): void
    {
        self::assertEquals($this->getContent('accessor_order_child'), $this->serialize(new AccessorOrderChild()));
        self::assertEquals($this->getContent('accessor_order_parent'), $this->serialize(new AccessorOrderParent()));
        self::assertEquals($this->getContent('accessor_order_methods'), $this->serialize(new AccessorOrderMethod()));
    }

    public function testGroups(): void
    {
        $groupsObject = new GroupsObject();

        self::assertEquals($this->getContent('groups_all'), $this->serializer->serialize($groupsObject, $this->getFormat()));

        self::assertEquals(
            $this->getContent('groups_foo'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo']))
        );

        self::assertEquals(
            $this->getContent('groups_foo_not_baz'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo', 'baz']))
        );

        self::assertEquals(
            $this->getContent('groups_foobar'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['foo', 'bar'])->setSerializeNull(true))
        );

        self::assertEquals(
            $this->getContent('groups_all'),
            $this->serializer->serialize($groupsObject, $this->getFormat())
        );

        self::assertEquals(
            $this->getContent('groups_default'),
            $this->serializer->serialize($groupsObject, $this->getFormat(), SerializationContext::create()->setGroups(['Default']))
        );
    }

    public function testAdvancedGroups(): void
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

        self::assertEquals(
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

    public function testInvalidGroupName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid group name "foo, bar" on "Kcs\Serializer\Tests\Fixtures\InvalidGroupsObject->foo", did you mean to create multiple groups?');

        $groupsObject = new InvalidGroupsObject();

        $this->serializer->serialize($groupsObject, $this->getFormat());
    }

    public function testVirtualProperty(): void
    {
        self::assertEquals($this->getContent('virtual_properties'), $this->serialize(new ObjectWithVirtualProperties()));
    }

    public function testVirtualVersions(): void
    {
        self::assertEquals(
            $this->getContent('virtual_properties_low'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(2))
        );

        self::assertEquals(
            $this->getContent('virtual_properties_all'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(7))
        );

        self::assertEquals(
            $this->getContent('virtual_properties_high'),
            $this->serialize(new ObjectWithVersionedVirtualProperties(), SerializationContext::create()->setVersion(9))
        );
    }

    public function testCustomHandler(): void
    {
        if (! $this->hasDeserializer()) {
            return;
        }

        $handler = static function (): CustomDeserializationObject {
            return new CustomDeserializationObject('customly_unserialized_value');
        };

        $this->handlerRegistry->registerHandler(Direction::DIRECTION_DESERIALIZATION, 'CustomDeserializationObject', $handler);

        $serialized = $this->serializer->serialize(new CustomDeserializationObject('sometext'), $this->getFormat());
        $object = $this->serializer->deserialize($serialized, Type::from('CustomDeserializationObject'), $this->getFormat());
        self::assertEquals('customly_unserialized_value', $object->someProperty);
    }

    public function testInput(): void
    {
        self::assertEquals($this->getContent('input'), $this->serializer->serialize(new Input(), $this->getFormat()));
    }

    public function testObjectWithEmptyHash(): void
    {
        self::assertEquals($this->getContent('hash_empty'), $this->serializer->serialize(new ObjectWithEmptyHash(), $this->getFormat()));
    }

    /**
     * @group null
     */
    public function testSerializeObjectWhenNull(): void
    {
        self::assertEquals(
            $this->getContent('object_when_null'),
            $this->serialize(new Comment(null, 'foo'), SerializationContext::create()->setSerializeNull(false))
        );

        self::assertEquals(
            $this->getContent('object_when_null_and_serialized'),
            $this->serialize(new Comment(null, 'foo'), SerializationContext::create()->setSerializeNull(true))
        );
    }

    /**
     * @group polymorphic
     */
    public function testPolymorphicObjects(): void
    {
        self::assertEquals(
            $this->getContent('car'),
            $this->serialize(new Car(5))
        );

        if ($this->hasDeserializer()) {
            self::assertEquals(
                new Car(5),
                $this->deserialize($this->getContent('car'), Car::class),
                'Class is resolved correctly when concrete sub-class is used.'
            );

            self::assertEquals(
                new Car(5),
                $this->deserialize($this->getContent('car'),
                    Vehicle::class
                ),
                'Class is resolved correctly when least supertype is used.'
            );

            self::assertEquals(
                new Car(5),
                $this->deserialize($this->getContent('car_without_type'), Car::class),
                'Class is resolved correctly when concrete sub-class is used and no type is defined.'
            );
        }
    }

    /**
     * @group polymorphic
     */
    public function testNestedPolymorphicObjects(): void
    {
        $garage = new Garage([new Car(3), new Moped(1)]);
        self::assertEquals(
            $this->getContent('garage'),
            $this->serialize($garage)
        );

        if ($this->hasDeserializer()) {
            self::assertEquals(
                $garage,
                $this->deserialize($this->getContent('garage'), Garage::class)
            );
        }
    }

    /**
     * @group polymorphic
     */
    public function testNestedPolymorphicInterfaces(): void
    {
        $garage = new VehicleInterfaceGarage([new Car(3), new Moped(1)]);
        self::assertEquals(
            $this->getContent('garage'),
            $this->serialize($garage)
        );

        if ($this->hasDeserializer()) {
            self::assertEquals(
                $garage,
                $this->deserialize(
                    $this->getContent('garage'),
                    VehicleInterfaceGarage::class
                )
            );
        }
    }

    /**
     * @group polymorphic
     */
    public function testPolymorphicObjectsInvalidDeserialization(): void
    {
        $this->expectException(\LogicException::class);
        if (! $this->hasDeserializer()) {
            throw new \LogicException('No deserializer');
        }

        $this->deserialize($this->getContent('car_without_type'), Vehicle::class);
    }

    public function testDepthExclusionStrategy(): void
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

        self::assertEquals($this->getContent('tree'), $this->serializer->serialize($data, $this->getFormat(), $context));
    }

    public function testDeserializingIntoExistingObject(): void
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

        self::assertSame($order, $deseralizedOrder);
        self::assertEquals(new Order(new Price(12.34)), $deseralizedOrder);
        self::assertInstanceOf(Price::class, $this->getField($deseralizedOrder, 'cost'));
    }

    public function testAdditionalField(): void
    {
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'Kcs\Serializer\Tests\Fixtures\Author::links',
            static function (VisitorInterface $visitor, Author $author, Type $type, Context $context) {
                return $visitor->visitHash([
                    'details' => 'http://foo.bar/details/'.$author->getName(),
                    'comments' => 'http://foo.bar/details/'.$author->getName().'/comments',
                ], Type::parse('array<string,string>'), $context);
            }
        );

        $list = new AuthorList();
        $list->add(new Author('Foo'));
        $list->add(new Author('Bar'));

        self::assertEquals($this->getContent('object_with_additional_field'), $this->serialize($list));
    }

    public function testSetTypeInSerialization(): void
    {
        self::assertEquals($this->getContent('type_passed_to_serialize'), $this->serialize([
            new Author('Foo'),
            new Author('Bar'),
        ], null, Type::parse('array<AuthorAsType>')));
    }

    public function testAdditionalFieldInheritedBySubclasses(): void
    {
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'Kcs\Serializer\Tests\Fixtures\Author::links',
            static function (VisitorInterface $visitor, Author $author, Type $type, Context $context) {
                return $visitor->visitHash([
                    'details' => 'http://foo.bar/details/'.$author->getName(),
                    'comments' => 'http://foo.bar/details/'.$author->getName().'/comments',
                ], Type::parse('array<string,string>'), $context);
            }
        );

        $list = new AuthorList();
        $list->add(new AuthorChild('Foo'));
        $list->add(new AuthorChild('Bar'));

        self::assertEquals($this->getContent('object_subclass_with_additional_field'), $this->serialize($list));
    }

    public function testGroupsAreProvidedIfObjectImplementsSerializationGroupsProvider(): void
    {
        $obj = new GroupsProvider();

        self::assertEquals($this->getContent('groups_provider'), $this->serialize($obj));
    }

    public function testUuid(): void
    {
        $uuid = Uuid::uuid5('1551cf94-fb02-4adc-8834-c4755f36faf8', 'foobar');
        self::assertEquals($this->getContent('uuid'), $this->serialize($uuid));

        $uuid = Uuid::uuid4();
        self::assertStringContainsString($uuid->toString(), $this->serialize($uuid));
    }

    abstract protected function getContent(string $key): string;

    abstract protected function getFormat(): string;

    protected function hasDeserializer(): bool
    {
        return true;
    }

    protected function serialize($data, Context $context = null, ?Type $type = null)
    {
        return $this->serializer->serialize($data, $this->getFormat(), $context, $type);
    }

    protected function deserialize($content, $type, ?Context $context = null)
    {
        return $this->serializer->deserialize($content, Type::from($type), $this->getFormat(), $context);
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->factory = new MetadataFactory($loader);

        $this->handlerRegistry = new HandlerRegistry();
        $this->handlerRegistry->registerSubscribingHandler(new ConstraintViolationHandler());
        $this->handlerRegistry->registerSubscribingHandler(new DateHandler());

        $translator = \is_subclass_of(IdentityTranslator::class, TranslatorInterface::class, true) ?
            new IdentityTranslator() : new IdentityTranslator(new MessageSelector());
        $this->handlerRegistry->registerSubscribingHandler(new FormErrorHandler($translator));
        $this->handlerRegistry->registerSubscribingHandler(new PhpCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new ArrayCollectionHandler());
        $this->handlerRegistry->registerSubscribingHandler(new UuidInterfaceHandler());
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'AuthorList',
            static function (VisitorInterface $visitor, $object, Type $type, Context $context) {
                return $visitor->visitHash(\iterator_to_array($object), $type, $context);
            }
        );
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_SERIALIZATION, 'AuthorAsType',
            static function (VisitorInterface $visitor, Author $object, Type $type, Context $context) {
                return $visitor->visitHash(['name' => $object->getName()], $type, $context);
            }
        );
        $this->handlerRegistry->registerHandler(Direction::DIRECTION_DESERIALIZATION, 'AuthorList',
            static function (VisitorInterface $visitor, $data, $type, Context $context) {
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
        $this->dispatcher->addListener(PreSerializeEvent::class, [new DoctrineProxySubscriber(), 'onPreSerialize'], 20);

        $namingStrategy = new SerializedNameAnnotationStrategy(new UnderscoreNamingStrategy());
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

    private function setField($obj, $name, $value): void
    {
        $ref = new \ReflectionProperty($obj, $name);
        $ref->setAccessible(true);
        $ref->setValue($obj, $value);
    }
}
