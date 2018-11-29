<?php

declare(strict_types=1);

/*
 * This file is part of the monorepo package.
 *
 *     (c) Anthonius Munthi <https://itstoni.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monorepo\DI\Compiler;

use Monorepo\Command\CommandInterface;
use Monorepo\Console\Application;
use Monorepo\Event\EventDispatcher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     *
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container)
    {
        $ids         = $container->getServiceIds();
        $commands    = [];
        $subscribers = [];

        foreach ($ids as $id) {
            $definition = $container->findDefinition($id);
            $class      = $definition->getClass();

            $definition
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->setAutowired(true)
            ;

            if (!class_exists($class)) {
                continue;
            }
            if (!$container->hasDefinition($class) && class_exists($class)) {
                if ($class !== $id) {
                    $container
                        ->setAlias($class, $id)
                        ->setPublic(true)
                    ;
                }
            }

            $r = new \ReflectionClass($class);
            if (
                $r->implementsInterface(CommandInterface::class)
                && !\in_array($class, $commands)
            ) {
                $commands[] = $class;
            }

            if (
                $r->implementsInterface(EventSubscriberInterface::class)
                && !\in_array($class, $subscribers)
            ) {
                $subscribers[] = $class;
            }
        }

        $app        = $container->findDefinition(Application::class);
        $dispatcher = $container->findDefinition(EventDispatcher::class);

        foreach ($commands as $class) {
            $app->addMethodCall('add', [new Reference($class)]);
        }

        foreach ($subscribers as $class) {
            $dispatcher->addMethodCall('addSubscriber', [new Reference($class)]);
        }
    }
}
