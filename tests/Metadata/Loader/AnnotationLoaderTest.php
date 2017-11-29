<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Metadata\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;

class AnnotationLoaderTest extends BaseLoaderTest
{
    protected function getLoader()
    {
        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());

        return $loader;
    }
}
