<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Discriminator
{
    /**
     * @var string[]
     */
    public $map;

    /**
     * @var string
     */
    public $field = 'type';

    /**
     * @var bool
     */
    public $disabled = false;

    /**
     * @var string[]
     */
    public $groups;
}
