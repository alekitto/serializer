<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class Type
{
    /**
     * @Required
     *
     * @var string
     */
    public $name;
}
