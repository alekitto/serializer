<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation;

use Kcs\Serializer\Exception\RuntimeException;

/**
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
final class OnExclude
{
    public const NULL = 'null';
    public const SKIP = 'skip';

    /**
     * @var string
     */
    public $policy = self::NULL;

    public function __construct(?array $values = null)
    {
        if (empty($values)) {
            return;
        }

        if (! \is_string($values['value'])) {
            throw new RuntimeException('"value" must be a string.');
        }

        $this->policy = \strtolower($values['value']);

        if (self::NULL !== $this->policy && self::SKIP !== $this->policy) {
            throw new RuntimeException('OnExclude policy must either be "null", or "skip".');
        }
    }
}
