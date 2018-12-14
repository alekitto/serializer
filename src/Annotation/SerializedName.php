<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Exception\RuntimeException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
final class SerializedName
{
    /**
     * @var string
     */
    public $name;

    public function __construct(?array $values = null)
    {
        if (empty($values)) {
            return;
        }

        if (! \is_string($values['value'])) {
            throw new RuntimeException(\sprintf('"value" must be a string.'));
        }

        $this->name = $values['value'];
    }
}
