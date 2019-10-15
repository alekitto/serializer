<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

abstract class Collection
{
    /**
     * @var string
     */
    public $entry = 'entry';

    /**
     * @var bool
     */
    public $inline = false;

    /**
     * @var string
     */
    public $namespace;
}
