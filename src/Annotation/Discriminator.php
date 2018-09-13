<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Discriminator
{
    /**
     * @var array<string>
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
     * @var array<string>
     */
    public $groups;
}
