<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class MaxDepth
{
    /**
     * @Required
     *
     * @var int
     */
    public $depth;
}
