<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Annotation\Xml\Root;

@\trigger_error('XmlRoot annotation is deprecated. Please use Xml\Root instead.', E_USER_DEPRECATED);

/**
 * @Annotation
 * @Target("CLASS")
 */
class XmlRoot extends Root
{
}
