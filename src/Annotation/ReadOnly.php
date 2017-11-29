<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"CLASS","PROPERTY"})
 */
final class ReadOnly
{
    /**
     * @var bool
     */
    public $readOnly = true;
}
