<?php declare(strict_types=1);

namespace Doctrine\Persistence {
    use Composer\InstalledVersions;

    if (version_compare(InstalledVersions::getVersion('doctrine/common'), '3', '<')) {
        class ManagerRegistry extends \Doctrine\Common\Persistence\ManagerRegistry { }
        class ObjectManager extends \Doctrine\Common\Persistence\ObjectManager { }
        interface Proxy extends \Doctrine\Common\Persistence\Proxy { }
    }
}

namespace Doctrine\Persistence\Mapping {
    use Composer\InstalledVersions;

    if (version_compare(InstalledVersions::getVersion('doctrine/common'), '3', '<')) {
        class ClassMetadata extends \Doctrine\Common\Persistence\Mapping\ClassMetadata { }
    }
}
