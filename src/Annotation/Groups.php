<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class Groups
{
    /**
     * @Required
     *
     * @var string[]
     */
    public $groups;
}
