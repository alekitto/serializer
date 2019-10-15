<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Annotation\Xml\Attribute;

@\trigger_error('XmlAttribute annotation is deprecated. Please use Xml\Attribute instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class XmlAttribute extends Attribute
{
}
