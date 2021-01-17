YAML Reference
--------------

The following is the reference for YAML mapping.  
For additional information of attributes and elements, see [PHP attributes reference](./php_attributes.md).

> `symfony/yaml` must be installed to be able to use yaml mapping.

```yaml
# Vendor\MyBundle\Resources\config\serializer\Model.ClassName.yml
Vendor\MyBundle\Model\ClassName:
    exclusion_policy: ALL
    xml_root:
        name: foobar
        namespace: http://your.default.namespace
    exclude: true
    read_only: false
    access_type: public_method # defaults to property
    accessor_order:
        order: custom
        custom: [propertyName1, propertyName2, ..., propertyNameN]
    discriminator:
        field: type
        map:
            some-value: ClassName
    virtual_properties:
        getSomeProperty:
            serialized_name: foo
            type: integer
    xml_namespace:
        - { uri: http://your.default.namespace }
        - { prefix: atom, uri: http://www.w3.org/2005/Atom }

    additional_fields:
        links:              # Additional fields supports all the options of a normal property
            serialized_name: _links
            groups: [foo, bar]

    static_fields:
        static:              # Static fields supports all the options of a normal property
            value: '42'      # Value is required in a static field

    properties:
        some-property:
            exclude: true
            expose: true
            access_type: public_method
            accessor: # access_type must be set to public_method
                getter: getSomeOtherProperty
                setter: setSomeOtherProperty
            type: string
            serialized_name: foo
            since: 1.0
            until: 1.1
            groups: [foo, bar]
            xml_attribute:
                namespace: http://www.w3.org/2005/Atom
            xml_value: true
            inline: true
            read_only: true
            xml_key_value_pairs: true
            xml_list:
                inline: true
                entry_name: foo
                namespace: http://www.w3.org/2005/Atom
            xml_map:
                inline: true
                key_attribute_name: foo
                entry_name: bar
                namespace: http://www.w3.org/2005/Atom
            xml_attribute_map: true
            xml_element:
                cdata: false
                namespace: http://www.w3.org/2005/Atom
            max_depth: 2
```
