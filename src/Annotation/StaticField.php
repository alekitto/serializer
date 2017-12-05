<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
final class StaticField
{
    /**
     * @Required
     *
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @Required
     *
     * @var mixed
     */
    public $value;
}
