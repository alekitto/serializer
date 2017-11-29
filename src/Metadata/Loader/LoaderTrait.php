<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Doctrine\Common\Inflector\Inflector;

trait LoaderTrait
{
    private function createAnnotationObject($name)
    {
        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.Inflector::classify($name);
        $annotation = new $annotationClass();

        return $annotation;
    }

    private function getDefaultPropertyName($annotation)
    {
        $reflectionAnnotation = new \ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        if (isset($properties[0])) {
            return $properties[0]->name;
        }
    }
}
