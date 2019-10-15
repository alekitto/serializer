<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

@\trigger_error('XmlList annotation is deprecated. Please use Xml\XmlList instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class XmlList extends Xml\XmlList
{
}
