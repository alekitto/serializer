Serializer
==========

Introduction
------------
This library allows you to (de-)serialize data of any complexity. Currently, it supports XML, JSON, and YAML.
It is a fork of the popular JMS serializer.

It also provides you with a rich tool-set to adapt the output to your specific needs.

Built-in features include:

- (De-)serialize data of any complexity; circular references are handled gracefully.
- Supports many built-in PHP types (such as dates)
- Integrates with Doctrine ORM, et. al.
- Supports versioning, e.g. for APIs
- Configurable via PHP, XML, YAML, or Doctrine Annotations

Installation
------------
This library can be easily installed via composer

.. code-block :: bash

    composer require kcs/serializer

or just add it to your ``composer.json`` file directly.

Usage
-----
For standalone projects usage of the provided builder is encouraged::

    $serializer = Kcs\Serializer\SerializerBuilder::create()->build();
    $jsonContent = $serializer->serialize($data, 'json');
    echo $jsonContent; // or return it in a Response

Or you can use the included symfony bundle::

    new \Kcs\Serializer\Bundle\SerializerBundle();

Documentation
-------------

.. toctree ::
    :hidden:

    configuration
    usage
    event_system
    handlers
    reference
    cookbook

- :doc:`Configuration <configuration>`
- :doc:`Usage <usage>`
- :doc:`Events <event_system>`
- :doc:`Handlers <handlers>`

- Recipes
    * :doc:`/cookbook/exclusion_strategies`

- Reference
    * :doc:`Annotations </reference/annotations>`
    * :doc:`XML Reference </reference/xml_reference>`
    * :doc:`YML Reference </reference/yml_reference>`

License
-------

The code is released under the business-friendly `MIT license`.

Documentation is subject to the `Attribution-NonCommercial-NoDerivs 3.0 Unported
license`_.

.. _Attribution-NonCommercial-NoDerivs 3.0 Unported license: http://creativecommons.org/licenses/by-nc-nd/3.0/

