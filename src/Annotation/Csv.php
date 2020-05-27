<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
/* final */ class Csv
{
    /**
     * @var string
     */
    public $delimiter;

    /**
     * @var string
     */
    public $enclosure;

    /**
     * @var string
     */
    public $escapeChar;

    /**
     * @var bool
     */
    public $escapeFormulas;

    /**
     * @var string
     */
    public $keySeparator;

    /**
     * @var bool
     */
    public $printHeaders;

    /**
     * @var bool
     */
    public $outputBom;
}
