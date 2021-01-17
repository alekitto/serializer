PHP Attributes
==============

Accessor
--------
This attribute can be defined on a property to specify which public method should
be called to retrieve, or set the value of the given property.

Arguments:

| name   | type   | required | description                                    |
| ------ | ------ | -------- | ---------------------------------------------- |
| getter | string | no       | The getter method to use to read the property  |  
| setter | string | no       | The setter method to use to write the property |  

```php
use Kcs\Serializer\Annotation\Accessor;

class User
{
    private $id;

    #[Accessor(getter: 'getTrimmedName', setter: 'setName')]
    private $name;

    // ...
    public function getTrimmedName(): string
    {
        return trim($this->name);
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}
```

AccessorOrder
-------------
This attribute can be defined on a class to control the order of properties. By
default, the order is undefined, but you may change it to either "alphabetical", or "custom".

Arguments:

| name            | type     | required               | description                                      |
| --------------- | -------- | ---------------------- | ------------------------------------------------ |
| order (default) | string   | yes                    | Could be "undefined", "alphabetical" or "custom" |  
| custom          | string[] | yes if order is custom | Property names of custom order                   |  

```php
use Kcs\Serializer\Annotation\AccessorOrder;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\VirtualProperty;

/**
 * Resulting Property Order: id, name
 */
#[AccessorOrder(AccessorOrder::ALPHABETICAL)]
class User
{
    private $id;
    private $name;
}

/**
 * Resulting Property Order: name, id
 */
#[AccessorOrder(AccessorOrder::CUSTOM, custom: ['name', 'id'])]
class User
{
    private $id;
    private $name;
}

/**
 * Resulting Property Order: name, mood, id
 */
#[AccessorOrder(AccessorOrder::CUSTOM, custom: ['name', 'SomeMethod', 'id'])]
class User
{
    private $id;
    private $name;

    #[VirtualProperty()]
    #[SerializedName('mood')]
    public function getSomeMethod(): string
    {
        return 'happy';
    }
}
```

AccessType
----------
This attribute can be defined on a property, or a class to specify in which way
the properties should be accessed. By default, the serializer will retrieve or
set the value via public methods, but you may change this to use a reflection instead

Arguments:

| name           | type   | required               | description                             |
| -------------- | ------ | ---------------------- | --------------------------------------- |
| type (default) | string | yes                    | Could be "property", or "public_method" |

```php
use Kcs\Serializer\Annotation\AccessType;

#[AccessType(AccessType::PROPERTY)]
class User
{
    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = trim($name);
    }
}
```

AdditionalField
---------------

This attribute can be used to add a virtual property based on the object instance
without writing a virtual property method.

The default type of the AdditionalField is `ClassName::property` and should be
handled by a specific serializer handler

Arguments:

| name           | type                      | required | description                                      |
| -------------- | ------------------------- | -------- | ------------------------------------------------ |
| name (default) | string                    | yes      | The name of the additional field                 |
| attributes     | [class-string, mixed[]][] | no       | Attributes to be applied to the additional field |

?> `attributes` array elements are 2-elements array in which the first is the attribute class name
and the second is the array of arguments.  
This is needed as PHP attributes only accepts constant expressions, so "new" is not valid in that context.

```php
use Kcs\Serializer\Annotation\AdditionalField;
use Kcs\Serializer\Annotation\SerializedName;

// ! A custom handler for User::user_links must be registered on handler registry

#[AdditionalField('user_links', attributes: [ [SerializedName::class, ['@links']] ])]
class User
{
    private $name;

    public function getName()
    {
        return $this->name;
    }
}
```

Csv
---

This attribute allows customization of csv output.
It is only applied if the object is the root object of serialization.

Arguments:

| name                | type    | required | description                                  |
| ------------------- | ------- | -------- | -------------------------------------------- |
| delimiter (default) | string  | no       | The csv delimiter (one character)            |
| enclosure           | string  | no       | The field enclosure (one character)          |
| escapeChar          | string  | no       | The escape character (at most one character) |
| escapeFormulas      | bool    | no       | Whether to escape formulas or not            |
| keySeparator        | string  | no       | The key separator                            |
| printHeaders        | bool    | no       | Whether to print headers in the first row    |
| outputBom           | bool    | no       | Output UTF BOM                               |

```php
use Kcs\Serializer\Annotation\Csv;

#[Csv(delimiter: ';')]
class User
{
    private $name;

    public function getName()
    {
        return $this->name;
    }
}
```

Discriminator
-------------

This attribute allows deserialization of relations which are polymorphic, but
where a common base class exists. The `Discriminator` attribute has to be applied
to the least super type:

Arguments:

| name          | type                  | required | description                                                                             |
| ------------- | --------------------- | -------- | --------------------------------------------------------------------------------------- |
| map (default) | array<string, string> | yes      | The discriminator map                                                                   |
| field         | string                | no       | The field name                                                                          |
| disabled      | bool                  | no       | Whether to disable discriminator (useful in combination with doctrine metadata loaders) |
| groups        | string[]              | no       | The key separator                                                                       |

```php
use Kcs\Serializer\Annotation\Discriminator;

    #[Discriminator(field: 'type', map: ['car' => 'Car', 'moped' => 'Moped'])]
    abstract class Vehicle { }
    
    class Car extends Vehicle { }
    class Moped extends Vehicle { }
```

Exclude
-------
This attribute can be defined on a property to indicate that the property should
not be serialized/unserialized. Works only in combination with ExclusionPolicy('none').

No arguments.

ExclusionPolicy
---------------
This attribute can be defined on a class to indicate the exclusion strategy
that should be used for the class.

| Policy   | Description                                                                                                             |
| -------- | ----------------------------------------------------------------------------------------------------------------------- |
| all      | all properties are excluded by default; only properties marked with Expose will be serialized/unserialized              |
| none     | no properties are excluded by default; all properties except those marked with @Exclude will be serialized/unserialized |

Arguments:

| name             | type   | required | description                   |
| ---------------- | ------ | -------- | ----------------------------- |
| policy (default) | string | yes      | The exclusion policy to apply |

Expose
------
This attribute can be defined on a property to indicate that the property should
be serialized/unserialized. Works only in combination with all ExclusionPolicy.

No arguments.

Groups
------
This attribute can be defined on a property to specify if the property
should be serialized or excluded when only serializing specific groups (see
[Exclusion strategy](../cookbook/exclusion_strategies.md)).
To exclude a property the group name must be prefixed with "!"

Arguments:

| name             | type     | required | description              |
| ---------------- | -------- | -------- | ------------------------ |
| groups (default) | string[] | yes      | The serialization groups |

Inline
------
This attribute can be defined on a property to indicate that the data of the property
should be inlined.

No arguments.

**Note**: This only works for serialization, the serializer will not be able to deserialize
objects with this attribute. Also, AccessorOrder will be using the name of the property
to determine the order.

MaxDepth
--------
This attribute can be defined on a property to limit the depth to which the
content will be serialized. It is very useful when a property will contain a
large object graph.

Arguments:

| name            | type | required | description             |
| --------------- | ---- | -------- | ----------------------- |
| depth (default) | int  | yes      | The maximum graph depth |

OnExclude
---------
Change the behavior of the property exclusion. The default behavior is to skip
the property, but can be set to serialize as null with OnExclude("null").

See [Exclusion strategy](../cookbook/exclusion_strategies.md) for more information.

Arguments:

| name             | type   | required | description                        |
| ---------------- | ------ | -------- | ---------------------------------- |
| policy (default) | string | yes      | The behavior to apply on exclusion |

ReadOnly
--------
This attribute can be defined on a property or a class to indicate that the data of the property
is read only and cannot be set during deserialization.

A property can be marked as non read only with `ReadOnly(false)` attribute (useful when a class is marked as read only).

No arguments.

SerializedName
--------------
This attribute can be defined on a property to define the serialized name for a
property. If this is not defined, the property will be translated via the
configured naming strategy.

Arguments:

| name           | type   | required | description         |
| -------------- | ------ | -------- | ------------------- |
| name (default) | string | yes      | The serialized name |

Since
-----
This attribute can be defined on a property to specify starting from which
version this property is available. If an earlier version is serialized, then
this property is excluded automatically. The version must be in a format that is
understood by PHP's `version_compare` function.

Arguments:

| name              | type   | required | description                            |
| ----------------- | ------ | -------- | -------------------------------------- |
| version (default) | string | yes      | A normalized version string to compare |

StaticField
-----------
Can be used to add a static property to an object.

Arguments:

| name           | type                      | required | description                                      |
| -------------- | ------------------------- | -------- | ------------------------------------------------ |
| name (default) | string                    | yes      | The name of the additional field                 |
| value          | mixed                     | yes      | The value of the static field                    |
| attributes     | [class-string, mixed[]][] | no       | Attributes to be applied to the additional field |

For example, you can use:

```php
use Kcs\Serializer\Annotation\StaticField;
use Kcs\Serializer\Annotation\Type;

#[StaticField('additional', value: '12', attributes: [ [Type::class, ['integer']] ])]
class User
{
    private $name;

    public function getName()
    {
        return $this->name;
    }
}
```

To add a property named "additional" which has a value of 12.
Attributes such as Type, Groups, OnExclude, etc can be added to the "attributes"
property of this attribute.

Type
----
This attribute can be defined on a property to specify the type of that property.
For deserialization, this attribute must be defined. For serialization, you may
define it in order to enhance the produced output; for example, you may want to
force a certain format to be used for DateTime types.

Arguments:

| name           | type   | required | description                                     |
| -------------- | -------| -------- | ----------------------------------------------- |
| name (default) | string | yes      | The type name. See table below for valid values |

Available Types:

| Type                       | Description                                                                                                                |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| boolean                    | Primitive boolean                                                                                                          |
| integer                    | Primitive integer                                                                                                          |
| double                     | Primitive double                                                                                                           |
| string                     | Primitive string                                                                                                           |
| array                      | An array with arbitrary keys, and values.                                                                                  |
| array<T>                   | A list of type T (T can be any available type).<br>Examples:<br>array<string>, array<MyNamespace\MyObject>, etc.           |
| array<K, V>                | A map of keys of type K to values of type V.<br>Examples: array<string, string>, array<string, MyNamespace\MyObject>, etc. |
| DateTime                   | PHP's DateTime object (default format/timezone)                                                                            |
| DateTime<'format'>         | PHP's DateTime object (custom format/default timezone)                                                                     |
| DateTime<'format', 'zone'> | PHP's DateTime object (custom format/timezone)                                                                             |
| T                          | Where T is a fully qualified class name or a custom name (a specific handler is needed).                                   |
| ArrayCollection<T>         | Similar to array<T>, but will be deserialized into Doctrine's ArrayCollection class.                                       |
| ArrayCollection<K, V>      | Similar to array<K, V>, but will be deserialized into Doctrine's ArrayCollection class.                                    |

Examples:

```php
namespace MyNamespace;

use Kcs\Serializer\Annotation\Type;

class BlogPost
{
    #[Type("ArrayCollection<MyNamespace\Comment>")]
    private $comments;

    #[Type("string")]
    private $title;

    #[Type(MyNamespace\Author::class)]
    private $author;

    #[Type(\DateTime::class)]
    private $createdAt;

    #[Type("DateTime<'Y-m-d'>")]
    private $updatedAt;

    #[Type("boolean")]
    private $published;

    #[Type("array<string, string>")]
    private $keyValueStore;
}
```

Until
-----
This attribute can be defined on a property to specify until which version this
property was available. If a later version is serialized, then this property is
excluded automatically. The version must be in a format that is understood by
PHP's `version_compare` function.

Arguments:

| name              | type   | required | description                            |
| ----------------- | ------ | -------- | -------------------------------------- |
| version (default) | string | yes      | A normalized version string to compare |

VirtualProperty
---------------
This attribute can be defined on a method to indicate that the data returned by
the method should appear like a property of the object.

No arguments.

**Note**: This only works for serialization and is completely ignored during
deserialization.

Xml\Attribute
-------------
This allows you to mark properties which should be set as attributes,
and not as child elements.

Arguments:

| name                | type   | required | description                                    |
| ------------------- | ------ | -------- | ---------------------------------------------- |
| namespace (default) | string | no       | The (optional) namespace URI for the attribute |

```php
use Kcs\Serializer\Annotation\Xml;

class User
{
    #[Xml\Attribute()]
    private $id = 1;
    private $name = 'Johannes';
}
```

Resulting XML:

```xml
<result id="1">
    <name><![CDATA[Johannes]]></name>
</result>
```

Xml\AttributeMap
----------------

This is similar to the Xml\KeyValuePairs, but instead of creating child elements, it creates attributes.

No arguments.

```php
use Kcs\Serializer\Annotation\Xml;

class Input
{
    #[Xml\AttributeMap()]
    private $id = array(
        'name' => 'firstname',
        'value' => 'Adrien',
    );
}
```

Resulting XML:

```xml
<result name="firstname" value="Adrien"/>
```

Xml\Element
-----------
This attribute can be defined on a property to add additional xml serialization/deserialization properties.

Arguments:

| name            | type   | required | description                                    |
| --------------- | ------ | -------- | ---------------------------------------------- |
| cdata (default) | bool   | no       | If the element should be wrapped in CDATA tag  |
| namespace       | string | no       | The (optional) namespace URI for the attribute |

```php
use Kcs\Serializer\Annotation\Xml;

#[Xml\XmlNamespace(uri: 'http://www.w3.org/2005/Atom', prefix: 'atom')]
class User
{
    #[Xml\Element(cdata: false, namespace: 'http://www.w3.org/2005/Atom')]
    private $id = 'my_id';
}
```

Resulting XML:

```xml
<atom:id>my_id</atom:id>
```

Xml\KeyValuePairs
-----------------
This allows you to use the keys of an array as xml tags.

No arguments.

!> When a key is an invalid xml tag name (e.g. 1_foo) the tag name *entry* will be used instead of the key.

Xml\Root
--------
This allows you to specify the name of the top-level element.

Arguments:

| name           | type   | required | description                                    |
| -------------- | ------ | -------- | ---------------------------------------------- |
| name (default) | string | no       | The name of the root element                   |
| namespace      | string | no       | The (optional) namespace URI for the attribute |
| encoding       | string | no       | The XML document encoding                      |

```php
use Kcs\Serializer\Annotation\Xml;

#[Xml\Root("user")]
class User
{
    private $name = 'Johannes';
}
```

Resulting XML:

```xml
<user>
    <name><![CDATA[Johannes]]></name>
</user>
```

?> Xml\Root only applies to the root element, but is for example not taken into 
account for collections. You can define the entry name for collections using 
Xml\XmlList, or Xml\Map.

Xml\Value
---------
This allows you to mark properties which should be set as the value of the
current element. Note that this has the limitation that any additional
properties of that object must have the Xml\Attribute attribute.
Xml\Value also has property cdata. Which has the same meaning as the one in Xml\Element.

Arguments:

| name            | type   | required | description                                    |
| --------------- | ------ | -------- | ---------------------------------------------- |
| cdata (default) | bool   | no       | If the element should be wrapped in CDATA tag  |

```php
use Kcs\Serializer\Annotation\Xml;

#[Xml\Root('price')]
class Price
{
    #[Xml\Attribute()]
    private $currency = 'EUR';

    #[Xml\Value]
    private $amount = 1.23;
}
```

Resulting XML:

```xml
<price currency="EUR">1.23</price>
```

Xml\XmlList
-----------
This allows you to define several properties of how arrays should be
serialized. This is very similar to Xml\Map, and should be used if the
keys of the array are not important.

Arguments:

| name            | type   | required | description                                   |
| --------------- | ------ | -------- | --------------------------------------------- |
| entry (default) | string | no       | The name of each element                      |
| inline          | bool   | no       | Whether the list elements should be inlined   |
| namespace       | string | no       | The (optional) namespace URI for the elements |

```php
use Kcs\Serializer\Annotation\Xml;

#[Xml\Root('post')]
class Post
{
    #[Xml\XmlList(inline: true, entry: 'comment')]
    private $comments;
    
    public function __construct()
    {
        $this->comments = [
            new Comment('Foo'),
            new Comment('Bar'),
        ];
    }
}

class Comment
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
```

Resulting XML:

```xml
<post>
    <comment>
        <text><![CDATA[Foo]]></text>
    </comment>
    <comment>
        <text><![CDATA[Bar]]></text>
    </comment>
</post>
```

You can also specify the entry tag namespace using the `namespace` attribute (`Xml\XmlList(inline: true, entry: "comment", namespace: "http://www.example.com/ns")`).

Xml\Map
-------
Similar to Xml\XmlList, but the keys of the array are meaningful.

Arguments:

| name            | type   | required | description                                   |
| --------------- | ------ | -------- | --------------------------------------------- |
| entry (default) | string | no       | The name of each element                      |
| inline          | bool   | no       | Whether the list elements should be inlined   |
| namespace       | string | no       | The (optional) namespace URI for the elements |

Xml\XmlNamespace
----------------
This attribute allows you to specify Xml namespace/s and prefix used.

Arguments:

| name          | type   | required | description                                                             |
| ------------- | ------ | -------- | ----------------------------------------------------------------------- |
| uri (default) | string | yes      | The URI of the namespace                                                |
| prefix        | string | no       | The prefix to be applied to elements/attributes with the same namespace |

```php
use Kcs\Serializer\Annotation\Groups;
use Kcs\Serializer\Annotation\SerializedName;
use Kcs\Serializer\Annotation\Type;
use Kcs\Serializer\Annotation\Xml;

#[Xml\XmlNamespace(uri: 'http://example.com/namespace')]
#[Xml\XmlNamespace(uri: 'http://www.w3.org/2005/Atom', prefix: 'atom')]
class BlogPost
{
    #[Type('Kcs\Serializer\Tests\Fixtures\Author')]
    #[Groups(['post'])]
    #[Xml\Element(namespace: 'http://www.w3.org/2005/Atom')]
    private $author;
}

class Author
{
    #[Type('string')]
    #[SerializedName('full_name')] 
    private $name;
}
```

Resulting XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<blog-post xmlns="http://example.com/namespace" xmlns:atom="http://www.w3.org/2005/Atom">
    <atom:author>
        <full_name><![CDATA[Foo Bar]]></full_name>
    </atom:author>
</blog>
```
