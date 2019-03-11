<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Inflector\Inflector;

trait LoaderTrait
{
    private function createAnnotationObject(string $name)
    {
        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.Inflector::classify($name);

        return new $annotationClass();
    }

    private function getDefaultPropertyName($annotation): ?string
    {
        $reflectionAnnotation = new \ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        return isset($properties[0]) ? $properties[0]->name : null;
    }
}
