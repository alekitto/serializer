<?php
namespace JMS\Serializer\Metadata\Loader;

use Kcs\Metadata\Loader\FilesLoader;

class YamlsLoader extends FilesLoader
{
    protected function getLoader($path)
    {
        return new YamlLoader($path);
    }
}
