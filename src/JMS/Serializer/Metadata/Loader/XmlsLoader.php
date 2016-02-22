<?php
namespace JMS\Serializer\Metadata\Loader;

use Kcs\Metadata\Loader\FilesLoader;

class XmlsLoader extends FilesLoader
{
    protected function getLoader($path)
    {
        return new XmlLoader($path);
    }
}
