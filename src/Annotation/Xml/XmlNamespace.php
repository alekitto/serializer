<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

/**
 * @Annotation
 * @Target("CLASS")
 */
/* final */ class XmlNamespace
{
    /**
     * @Required
     *
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $prefix = '';
}
