<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class XmlRoot
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
