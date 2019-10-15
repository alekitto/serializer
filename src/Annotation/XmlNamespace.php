<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

@\trigger_error('XmlNamespace annotation is deprecated. Please use Xml\XmlNamespace instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target("CLASS")
 */
class XmlNamespace extends Xml\XmlNamespace
{
}
