<?php declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Debug\TraceableVisitor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
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
            foreach ($attributes as $attribute) {
                $format = $attribute['format'] ?? null;
                $direction = $attribute['direction'] ?? null;

                if ($format === null) {
                    throw new InvalidConfigurationException('Invalid tag for service "%s": format must be specified');
                }

                if ($direction === 'serialization') {
                    $serializationVisitors[$format] = new Reference($serviceId);
                } elseif ($direction === 'deserialization') {
                    $deserializationVisitors[$format] = new Reference($serviceId);
                } else {
                    throw new InvalidConfigurationException('Invalid tag for service "%s": direction must be "serialization" or "deserialization"');
                }
            }
        }

        if ($container->getParameter('kernel.debug')) {
            foreach ($serializationVisitors as $key => $reference) {
                $def = new Definition(TraceableVisitor::class, [$reference, new Reference('logger')]);
                $def->addTag('monolog.logger', [
                    'channel' => 'kcs_serializer',
                ]);

                $id = '.traceable.'.$reference;
                $container->setDefinition($id, $def);

                $serializationVisitors[$key] = new Reference($id);
            }

            foreach ($deserializationVisitors as $key => $reference) {
                $def = new Definition(TraceableVisitor::class, [$reference, new Reference('logger')]);
                $def->addTag('monolog.logger', [
                    'channel' => 'kcs_serializer',
                ]);

                $id = '.traceable.'.$reference;
                $container->setDefinition($id, $def);

                $deserializationVisitors[$key] = new Reference($id);
            }
        }

        $definition->replaceArgument(3, $serializationVisitors);
        $definition->replaceArgument(4, $deserializationVisitors);
    }
}
