<?php

declare(strict_types=1);

namespace Kcs\Serializer\Bundle\DependencyInjection\CompilerPass;

use Kcs\Serializer\Debug\TraceableVisitor;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use function Safe\sprintf;

class RegisterVisitorsPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (! $container->has('kcs_serializer.serializer')) {
            return;
        }

        $definition = $container->getDefinition('kcs_serializer.serializer');
        $serializationVisitors = $definition->getArgument(3);
        $deserializationVisitors = $definition->getArgument(4);

        foreach ($this->findAndSortTaggedServices('kcs_serializer.serialization_visitor', $container) as $reference) {
            $serviceId = (string) $reference;
            $attributes = $container->getDefinition($serviceId)->getTag('kcs_serializer.serialization_visitor');

            foreach ($attributes as $attribute) {
                $format = $attribute['format'] ?? null;
                $direction = $attribute['direction'] ?? null;

                if ($format === null) {
                    throw new InvalidConfigurationException(sprintf('Invalid tag for service "%s": format must be specified', $serviceId));
                }

                if ($direction === 'serialization') {
                    $serializationVisitors[$format] = new Reference($serviceId);
                } elseif ($direction === 'deserialization') {
                    $deserializationVisitors[$format] = new Reference($serviceId);
                } else {
                    throw new InvalidConfigurationException(sprintf('Invalid tag for service "%s": direction must be "serialization" or "deserialization"', $serviceId));
                }
            }
        }

        if ($container->hasParameter('kernel.debug') && $container->getParameter('kernel.debug')) {
            $processVisitor = static function (array &$visitors) use ($container): void {
                foreach ($visitors as $key => $reference) {
                    $def = new Definition(TraceableVisitor::class, [$reference, new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE)]);
                    $def->addTag('monolog.logger', ['channel' => 'kcs_serializer']);

                    $id = '.traceable.' . $reference;
                    $container->setDefinition($id, $def);

                    $visitors[$key] = new Reference($id);
                }
            };

            $processVisitor($serializationVisitors);
            $processVisitor($deserializationVisitors);
        }

        $definition->replaceArgument(3, $serializationVisitors);
        $definition->replaceArgument(4, $deserializationVisitors);
    }
}
