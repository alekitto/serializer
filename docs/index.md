Serializer
==========

Introduction
------------
This library allows you to (de-)serialize data of any complexity. Currently, it supports XML, JSON, YAML and CSV (serialization only).
It is a fork of the popular JMS serializer.

It also provides you with a rich tool-set to adapt the output to your specific needs.

Built-in features include:

- (De-)serialize data of any complexity; circular references are handled gracefully.
- Supports many built-in PHP types (such as dates)
- Integrates with Doctrine ORM, et al.
- Supports versioning, e.g. for APIs
- Configurable via PHP, XML, YAML, annotations or PHP attributes

Installation
------------
This library can be easily installed via composer

```bash
$ composer require kcs/serializer
```

or just add it to your `composer.json` file directly.

Usage
-----
For standalone projects usage of the provided builder is encouraged

```php
$serializer = Kcs\Serializer\SerializerBuilder::create()->build();
$jsonContent = $serializer->serialize($data, 'json');
echo $jsonContent; // or return it in a Response
```

Or you can use the included symfony bundle

```php
new \Kcs\Serializer\Bundle\SerializerBundle();
```

Documentation
-------------

- [Configuration](./configuration.md)
- [Usage](./usage.md)
- [Events](./event_system.md)
- [Handlers](./handlers.md)

- Recipes
    * [Exclusion strategy](./cookbook/exclusion_strategies.md)

- Reference
    * [PHP Attributes](./reference/php_attributes.md)
    * [XML Reference](./reference/xml_reference.md)
    * [YAML Reference](./reference/yml_reference.md)

License
-------

The code is released under the business-friendly `MIT license`.
