<?php declare(strict_types=1);

/*
 * Copyright 2013 Johannes M. Schmitt <schmittjoh@gmail.com>
 * Copyright 2017 Alessandro Chitolina <alekitto@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Kcs\Serializer\Tests\Serializer\Doctrine;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Kcs\Serializer\Metadata\Loader\AnnotationLoader;
use Kcs\Serializer\Metadata\Loader\DoctrineTypeLoader;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\SerializerBuilder;
use Kcs\Serializer\Tests\Fixture\Doctrine\SingleTableInheritance\Clazz;
use Kcs\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance\Student;
use Kcs\Serializer\Tests\Fixtures\Doctrine\SingleTableInheritance\Teacher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class IntegrationTest extends TestCase
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var Serializer */
    private $serializer;

    public function testDiscriminatorIsInferredFromDoctrine()
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $student1 = new Student();
        $student2 = new Student();
        $teacher = new Teacher();
        $class = new Clazz($teacher, [$student1, $student2]);

        $em->persist($student1);
        $em->persist($student2);
        $em->persist($teacher);
        $em->persist($class);
        $em->flush();
        $em->clear();

        $reloadedClass = $em->find(get_class($class), $class->getId());
        $this->assertNotSame($class, $reloadedClass);

        $json = $this->serializer->serialize($reloadedClass, 'json');
        $this->assertEquals('{"id":1,"teacher":{"id":1,"type":"teacher"},"students":[{"id":2,"type":"student"},{"id":3,"type":"student"}]}', $json);
    }

    protected function setUp()
    {
        $connection = $this->createConnection();
        $entityManager = $this->createEntityManager($connection);

        $this->registry = $registry = new SimpleManagerRegistry(
            function ($id) use ($connection, $entityManager) {
                switch ($id) {
                    case 'default_connection':
                        return $connection;

                    case 'default_manager':
                        return $entityManager;

                    default:
                        throw new \RuntimeException(sprintf('Unknown service id "%s".', $id));
                }
            }
        );

        $loader = new AnnotationLoader();
        $loader->setReader(new AnnotationReader());
        $this->serializer = SerializerBuilder::create()
            ->setMetadataLoader(new DoctrineTypeLoader($loader, $registry))
            ->setEventDispatcher(new EventDispatcher())
            ->build()
        ;

        $this->prepareDatabase();
    }

    private function prepareDatabase()
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    private function createConnection()
    {
        $con = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        return $con;
    }

    private function createEntityManager(Connection $con)
    {
        $cfg = new Configuration();
        $cfg->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [
            __DIR__.'/../../Fixtures/Doctrine/SingleTableInheritance',
        ]));
        $cfg->setAutoGenerateProxyClasses(true);
        $cfg->setProxyNamespace('Kcs\Serializer\DoctrineProxy');
        $cfg->setProxyDir(sys_get_temp_dir().'/serializer-test-proxies');

        $em = EntityManager::create($con, $cfg);

        return $em;
    }
}

class SimpleManagerRegistry extends AbstractManagerRegistry
{
    private $services = [];
    private $serviceCreator;

    public function __construct($serviceCreator, $name = 'anonymous', array $connections = ['default' => 'default_connection'], array $managers = ['default' => 'default_manager'], $defaultConnection = null, $defaultManager = null, $proxyInterface = 'Doctrine\Common\Persistence\Proxy')
    {
        if (null === $defaultConnection) {
            $defaultConnection = key($connections);
        }
        if (null === $defaultManager) {
            $defaultManager = key($managers);
        }

        parent::__construct($name, $connections, $managers, $defaultConnection, $defaultManager, $proxyInterface);

        if (! is_callable($serviceCreator)) {
            throw new \InvalidArgumentException('$serviceCreator must be a valid callable.');
        }
        $this->serviceCreator = $serviceCreator;
    }

    public function getService($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        return $this->services[$name] = call_user_func($this->serviceCreator, $name);
    }

    public function resetService($name)
    {
        unset($this->services[$name]);
    }

    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            $manager = $this->getManager($name);

            if ($manager instanceof EntityManager) {
                try {
                    return $manager->getConfiguration()->getEntityNamespace($alias);
                } catch (ORMException $ex) {
                    // Probably mapped by another entity manager, or invalid, just ignore this here.
                }
            } else {
                throw new \LogicException(sprintf('Unsupported manager type "%s".', get_class($manager)));
            }
        }

        throw new \RuntimeException(sprintf('The namespace alias "%s" is not known to any manager.', $alias));
    }
}
