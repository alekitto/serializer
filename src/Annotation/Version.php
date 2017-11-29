<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

abstract class Version
{
    /**
     * @Required
     *
     * @var string
     */
    public $version;
}
