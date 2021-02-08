<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Debug\TraceableVisitor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterVisitorsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('kcs_serializer.serializer');
        $serializationVisitors = $definition->getArgument(3);
        $deserializationVisitors = $definition->getArgument(4);

        foreach ($container->findTaggedServiceIds('kcs_serializer.serialization_visitor') as $serviceId => $attributes) {
            $def = $container->getDefinition($serviceId);
            $className = $def->getClass();

            foreach ($attributes as $attribute) {
                $direction = $attribute['direction'] ?? null;
                if ($direction === 'serialization') {
                    $serializationVisitors[$className::getFormat()] = new Reference($serviceId);
                } elseif ($direction === 'deserialization') {
                    $deserializationVisitors[$className::getFormat()] = new Reference($serviceId);
                }
            }
        }

        if ($container->getParameter('kernel.debug')) {
            foreach ($serializationVisitors as $key => $reference) {
                $def = new Definition(TraceableVisitor::class, [$reference, new Reference('logger')]);
                $def->addTag('monolog.logger', [
                    'name' => 'monolog.logger',
                    'channel' => 'kcs_serializer',
                ]);

                $serializationVisitors[$key] = $def;
            }

            foreach ($deserializationVisitors as $key => $reference) {
                $def = new Definition(TraceableVisitor::class, [$reference, new Reference('logger')]);
                $def->addTag('monolog.logger', [
                    'name' => 'monolog.logger',
                    'channel' => 'kcs_serializer',
                ]);

                $deserializationVisitors[$key] = $def;
            }
        }

        $definition->replaceArgument(3, $serializationVisitors);
        $definition->replaceArgument(4, $deserializationVisitors);
    }
}
