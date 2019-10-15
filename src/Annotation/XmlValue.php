<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Annotation\Xml\Value;

@\trigger_error('XmlValue annotation is deprecated. Please use Xml\Value instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class XmlValue extends Value
{
}
