<?php declare(strict_types=1);

namespace Kcs\Serializer\Metadata\Loader;

use Kcs\Serializer\Inflector\Inflector;
use ReflectionClass;

trait LoaderTrait
{
    private function createAnnotationObject(string $name)
    {
        switch ($className = Inflector::getInstance()->classify($name)) {
            case 'XmlList':
            case 'XmlNamespace':
                $className = 'Xml\\'.$className;
                break;

            default:
                if (0 === \strpos($className, 'Xml')) {
                    $className = 'Xml\\'.\substr($className, 3);
                }
                break;
        }

        $annotationClass = 'Kcs\\Serializer\\Annotation\\'.$className;

        return new $annotationClass();
    }

    private function getDefaultPropertyName($annotation): ?string
    {
        $reflectionAnnotation = new ReflectionClass($annotation);
        $properties = $reflectionAnnotation->getProperties();

        return isset($properties[0]) ? $properties[0]->name : null;
    }
}
