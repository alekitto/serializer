XML Reference
-------------
The following is the reference for XML mapping.

For additional information of attributes and elements, see [PHP attributes reference](./php_attributes.md).

```xml
<!-- MyBundle\Resources\config\serializer\Fully.Qualified.ClassName.xml -->
<?xml version="1.0" encoding="UTF-8" ?>
<serializer>
    <class name="Fully\Qualified\ClassName" exclusion-policy="ALL" exclude="true"
        access-type="public_method" immutable="false">
        <accessor-order order="custom" custom="propertyName1,propertyName2,...,propertyNameN"/>
        <xml-root name="foo-bar" namespace="http://foo.bar/2016/ns" />
        <xml-namespace prefix="atom" uri="http://www.w3.org/2005/Atom" />
        <discriminator field="type">
            <map value="some-value">ClassName</map>
        </discriminator>
        <csv delimiter=";" enclosure="'" escape-char="\" escape-formulas="true" key-separator="_" print-headers="true" output-bom="true" />
        <property name="some-property"
                  exclude="true"
                  expose="true"
                  exclusion-policy="null"
                  type="string"
                  serialized-name="foo"
                  since="1.0"
                  until="1.1"
                  access-type="public_method"
                  accessor-getter="getSomeProperty"
                  accessor-setter="setSomeProperty"
                  inline="true"
                  immutable="true"
                  groups="foo,bar"
                  xml-key-value-pairs="true"
                  xml-attribute-map="true"
                  max-depth="2"
        >
            <!-- You can also specify the type as element which is necessary if
                 your type contains "<" or ">" characters. -->
            <type><![CDATA[]]></type>

            <accessor getter="getSomeProperty" setter="setSomeProperty" />
            <xml-attribute namespace="http://www.w3.org/2005/Atom" />
            <xml-list inline="true" entry-name="foobar" />
            <xml-map inline="true" key-attribute-name="foo" entry-name="bar" namespace="http://www.w3.org/2005/Atom" />
            <xml-element cdata="false" namespace="http://www.w3.org/2005/Atom" />
        </property>
        <additional-field name="additional" serialized-name="foobar" /> <!-- additonal-field supports all the attributes and elements of property -->
        <static-field name="static" value="42" /> <!-- static-field supports all the attributes and elements of property -->
        
        <pre-serialize method="foo" />
        <post-serialize method="bar" />
        <post-deserialize method="baz" />

        <!-- Virtual property has the same attributes and elements of the
             property element, except for the exclude attribute -->
        <virtual-property method="public_method" />
    </class>
</serializer>
```
