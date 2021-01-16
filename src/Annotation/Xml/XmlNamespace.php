<?php declare(strict_types=1);

namespace Kcs\Serializer\Annotation\Xml;

use Attribute;
use TypeError;

use function Safe\sprintf;

/**
 * @Annotation
 * @Target("CLASS")
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class XmlNamespace
{
    /**
     * @Required
     */
    public string $uri;
    public string $prefix = '';

    public function __construct($uri, ?string $prefix = null)
    {
        if (is_string($uri)) {
            $data = ['uri' => $uri];
        } elseif (is_array($uri)) {
            $data = $uri;
        } else {
            throw new TypeError(sprintf('Argument #1 passed to %s must be a string. %s passed', __METHOD__, get_debug_type($uri)));
        }

        $this->uri = $data['uri'] ?? $data['value'];
        $this->prefix = $prefix ?? $data['prefix'] ?? '';
    }
}
