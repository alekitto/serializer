Usage
=====

Serializing Objects
-------------------
Most common usage is probably to serialize objects. This can be achieved
very easily:

```php
$serializer = \Kcs\Serializer\SerializerBuilder::create()->build();
$serializer->serialize($object, 'json');
$serializer->serialize($object, 'csv');
$serializer->serialize($object, 'xml');
$serializer->serialize($object, 'yml');
$serializer->serialize($object, 'array'); // or $serializer->normalize($object);
```

```twig
{{ object | serialize }} {# uses JSON #}
{{ object | serialize('json') }}
{{ object | serialize('xml') }}
{{ object | serialize('yml') }}
```

Deserializing Objects
---------------------
You can also deserialize objects from their XML, or JSON representation. For
example, when accepting data via an API.

```php
$serializer = Kcs\Serializer\SerializerBuilder::create()->build();
$object = $serializer->deserialize($jsonData, \Kcs\Serializer\Type\Type::from('MyNamespace\MyObject'), 'json');
```
