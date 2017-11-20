<?php

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Alessandro Chitolina <alekitto@gmail.com>
 */
final class AdditionalField
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
    public $attributes;
}
