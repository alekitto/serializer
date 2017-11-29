<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class XmlElement
{
    /**
     * @var bool
     */
    public $cdata = true;

    /**
     * @var string
     */
    public $namespace;
}
