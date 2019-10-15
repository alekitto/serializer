<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Annotation\Xml\AttributeMap;

@\trigger_error('XmlAttributeMap annotation is deprecated. Please use Xml\AttributeMap instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class XmlAttributeMap extends AttributeMap
{
}
