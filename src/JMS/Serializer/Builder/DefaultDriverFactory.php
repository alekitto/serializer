<?php

namespace JMS\Serializer\Builder;

use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Metadata\Loader\AnnotationLoader;
use JMS\Serializer\Metadata\Loader\XmlLoader;
use JMS\Serializer\Metadata\Driver\YamlDriver;
use Kcs\Metadata\Loader\ChainLoader;

class DefaultDriverFactory implements DriverFactoryInterface
{
    public function createDriver(array $metadataDirs, Reader $annotationReader)
    {
        if ( ! empty($metadataDirs)) {
            $fileLocator = new FileLocator($metadataDirs);

            return new ChainLoader(array(
                new YamlDriver($fileLocator),
                new XmlLoader($fileLocator),
                new AnnotationLoader($annotationReader),
            ));
        }

        return new AnnotationLoader($annotationReader);
    }
}
