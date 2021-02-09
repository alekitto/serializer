<?php declare(strict_types=1);

namespace Kcs\Serializer\Tests\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Bundle\DependencyInjection\CompilerPass\RegisterVisitorsPass;
use Kcs\Serializer\Debug\TraceableVisitor;
use Kcs\Serializer\GenericDeserializationVisitor;
use Kcs\Serializer\GenericSerializationVisitor;
use Kcs\Serializer\JsonSerializationVisitor;
use Kcs\Serializer\Serializer;
use Kcs\Serializer\XmlSerializationVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class RegisterVisitorsPassTest extends TestCase
{
    private const SERVICE_ID = 'kcs_serializer.serializer';

    private RegisterVisitorsPass $pass;

    protected function setUp(): void
    {
        $this->pass = new RegisterVisitorsPass();
    }

    public function testShouldWorkWithEmptyContainer(): void
    {
        $container = new ContainerBuilder();
        $this->pass->process($container);

        $this->addToAssertionCount(1);
    }

    public function testShouldThrowIfFormatIsNotDefined(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::SERVICE_ID, Serializer::class)
            ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'direction' => 'serialization'
            ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid tag for service "'.GenericSerializationVisitor::class.'": format must be specified');
        $this->pass->process($container);
    }

    public function testShouldThrowIfDirectionIsInvalid(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::SERVICE_ID, Serializer::class)
            ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'array',
                'direction' => 'test',
            ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid tag for service "'.GenericSerializationVisitor::class.'": direction must be "serialization" or "deserialization"');

        $this->pass->process($container);
    }

    public function testShouldThrowIfDirectionIsMissing(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::SERVICE_ID, Serializer::class)
            ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'array',
            ]);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid tag for service "'.GenericSerializationVisitor::class.'": direction must be "serialization" or "deserialization"');

        $this->pass->process($container);
    }

    public function testShouldSearchForVisitorsTagged(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::SERVICE_ID, Serializer::class)
            ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'generic',
                'direction' => 'serialization'
            ]);

        $container->register(GenericDeserializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'generic',
                'direction' => 'deserialization'
            ]);

        $this->pass->process($container);

        $definition = $container->getDefinition(self::SERVICE_ID);

        self::assertEquals([
            'generic' => new Reference(GenericSerializationVisitor::class)
        ], $definition->getArgument(3));
        self::assertEquals([
            'generic' => new Reference(GenericDeserializationVisitor::class)
        ], $definition->getArgument(4));
    }

    public function testShouldSortVisitors(): void
    {
        $container = new ContainerBuilder();
        $container->register(self::SERVICE_ID, Serializer::class)
            ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'generic',
                'direction' => 'serialization',
                'priority' => -5,
            ]);
        $container->register(JsonSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'json',
                'direction' => 'serialization',
                'priority' => 20,
            ]);
        $container->register(XmlSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'xml',
                'direction' => 'serialization',
                'priority' => 10,
            ]);

        $this->pass->process($container);

        $definition = $container->getDefinition(self::SERVICE_ID);

        self::assertEquals([
            'json' => new Reference(JsonSerializationVisitor::class),
            'xml' => new Reference(XmlSerializationVisitor::class),
            'generic' => new Reference(GenericSerializationVisitor::class)
        ], $definition->getArgument(3));
    }

    public function testShouldDecorateVisitorsIfDebugIsEnabled(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => true]));
        $container->register(self::SERVICE_ID, Serializer::class)
                  ->setArguments([null, null, null, [], [], null]);

        $container->register(GenericSerializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'generic',
                'direction' => 'serialization'
            ]);

        $container->register(GenericDeserializationVisitor::class)
            ->addTag('kcs_serializer.serialization_visitor', [
                'format' => 'generic',
                'direction' => 'deserialization'
            ]);

        $this->pass->process($container);

        $definition = $container->getDefinition(self::SERVICE_ID);

        self::assertEquals([
            'generic' => new Reference('.traceable.' . GenericSerializationVisitor::class)
        ], $definition->getArgument(3));

        self::assertEquals([
            'generic' => new Reference('.traceable.' . GenericDeserializationVisitor::class)
        ], $definition->getArgument(4));

        $definition = new Definition(TraceableVisitor::class, [
            new Reference(GenericSerializationVisitor::class),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);
        $definition->addTag('monolog.logger', ['channel' => 'kcs_serializer']);

        self::assertEquals($definition, $container->getDefinition('.traceable.' . GenericSerializationVisitor::class));
    }
}
