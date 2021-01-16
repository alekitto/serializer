<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Kcs\Metadata\Loader\LoaderInterface;
use Kcs\Serializer\Metadata\Loader\AttributesLoader;

/**
 * @requires PHP >= 8.0
 */
class AttributesLoaderTest extends BaseLoaderTest
{
    protected function getLoader(): LoaderInterface
    {
        return new AttributesLoader();
    }
}
