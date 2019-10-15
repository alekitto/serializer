<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

/**
 * @Annotation
 * @Target("CLASS")
 */
/* final */ class Root
{
    /**
     * @Required
     *
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $namespace;
}
