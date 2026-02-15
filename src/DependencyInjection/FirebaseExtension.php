<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\DependencyInjection;

use Kreait\Firebase;
use Kreait\Firebase\Symfony\Bundle\DependencyInjection\Factory\ProjectFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class FirebaseExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->register(ProjectFactory::class, ProjectFactory::class)->setPublic(false);

        $projectConfigurations = $config['projects'] ?? [];
        $projectConfigurationsCount = \count($projectConfigurations);

        $this->assertThatOnlyOneDefaultProjectExists($projectConfigurations);

        foreach ($projectConfigurations as $projectName => $projectConfiguration) {
            if (1 === $projectConfigurationsCount) {
                $projectConfiguration['default'] = $projectConfiguration['default'] ?? true;
            }

            $this->processProjectConfiguration($projectName, $projectConfiguration, $container);
        }
    }

    private function processProjectConfiguration(string $name, array $config, ContainerBuilder $container): void
    {
        $projectFactory = $this->registerProjectFactory($name, $config, $container);

        $this->registerService($name, 'database', $config, Firebase\Contract\Database::class, $projectFactory, $container, 'createDatabase');
        $this->registerService($name, 'auth', $config, Firebase\Contract\Auth::class, $projectFactory, $container, 'createAuth');
        $this->registerService($name, 'storage', $config, Firebase\Contract\Storage::class, $projectFactory, $container, 'createStorage');
        $this->registerService($name, 'remote_config', $config, Firebase\Contract\RemoteConfig::class, $projectFactory, $container, 'createRemoteConfig');
        $this->registerService($name, 'messaging', $config, Firebase\Contract\Messaging::class, $projectFactory, $container, 'createMessaging');
        $this->registerService($name, 'firestore', $config, Firebase\Contract\Firestore::class, $projectFactory, $container, 'createFirestore');
        $this->registerService($name, 'app_check', $config, Firebase\Contract\AppCheck::class, $projectFactory, $container, 'createAppCheck');
    }

    public function getAlias(): string
    {
        return 'kreait_firebase';
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias());
    }

    private function registerProjectFactory(string $name, array $config, ContainerBuilder $container): Reference
    {
        $projectFactoryServiceId = \sprintf('%s.%s.project_factory', $this->getAlias(), $name);
        $projectFactory = clone $container->getDefinition(ProjectFactory::class);

        if ($config['verifier_cache'] ?? null) {
            $projectFactory->addMethodCall('setVerifierCache', [new Reference($config['verifier_cache'])]);
        }

        if ($config['auth_token_cache'] ?? null) {
            $projectFactory->addMethodCall('setAuthTokenCache', [new Reference($config['auth_token_cache'])]);
        }

        if ($config['http_client_options'] ?? null) {
            $projectFactory->addMethodCall('setHttpClientOptions', [new Reference($config['http_client_options'])]);
        }

        $container->setDefinition($projectFactoryServiceId, $projectFactory);

        return new Reference($projectFactoryServiceId);
    }

    private function registerService(string $name, string $postfix, array $config, string $contract, Reference $projectFactory, ContainerBuilder $container, string $method = 'create'): void
    {
        $projectServiceId = \sprintf('%s.%s.%s', $this->getAlias(), $name, $postfix);
        $isPublic = $config['public'];

        $container->register($projectServiceId, $contract)
            ->setFactory([$projectFactory, $method])
            ->setLazy(true)
            ->addArgument($config)
            ->setPublic($isPublic);

        if ($config['default'] ?? false) {
            $container->setAlias($contract, $projectServiceId)->setPublic($isPublic);
        }

        $container->registerAliasForArgument($projectServiceId, $contract, $name.ucfirst($postfix));
    }

    private function assertThatOnlyOneDefaultProjectExists(array $projectConfigurations): void
    {
        $count = 0;

        foreach ($projectConfigurations as $projectConfiguration) {
            if ($projectConfiguration['default'] ?? false) {
                ++$count;
            }

            if ($count > 1) {
                throw new InvalidConfigurationException('Only one project can be set as default.');
            }
        }
    }
}
