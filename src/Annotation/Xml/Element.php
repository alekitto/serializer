<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
/* final */ class Element
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
