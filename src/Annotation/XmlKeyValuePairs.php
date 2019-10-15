<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Annotation\Xml\KeyValuePairs;

@\trigger_error('XmlKeyValuePairs annotation is deprecated. Please use Xml\KeyValuePairs instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
/* final */ class XmlKeyValuePairs extends KeyValuePairs
{
}
