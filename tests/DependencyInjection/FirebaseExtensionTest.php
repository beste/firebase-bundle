<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\Tests\DependencyInjection;

use Kreait\Firebase;
use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Symfony\Bundle\DependencyInjection\FirebaseExtension;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionException;
use stdClass;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use TypeError;

/**
 * @internal
 */
final class FirebaseExtensionTest extends TestCase
{
    private FirebaseExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new FirebaseExtension();
    }

    public function test_a_project_is_created_with_a_service_for_each_feature(): void
    {
        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
            ],
        ]);

        $this->assertInstanceOf(Firebase\Contract\Database::class, $container->get($this->extension->getAlias().'.foo.database'));
        $this->assertInstanceOf(Firebase\Contract\Database::class, $container->get(Firebase\Contract\Database::class));
        $this->assertInstanceOf(Firebase\Contract\Database::class, $container->get(Firebase\Contract\Database::class.' $fooDatabase'));

        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get($this->extension->getAlias().'.foo.auth'));
        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get(Firebase\Contract\Auth::class));
        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get(Firebase\Contract\Auth::class.' $fooAuth'));

        $this->assertInstanceOf(Firebase\Contract\Storage::class, $container->get($this->extension->getAlias().'.foo.storage'));
        $this->assertInstanceOf(Firebase\Contract\Storage::class, $container->get(Firebase\Contract\Storage::class));
        $this->assertInstanceOf(Firebase\Contract\Storage::class, $container->get(Firebase\Contract\Storage::class.' $fooStorage'));

        $this->assertInstanceOf(Firebase\Contract\RemoteConfig::class, $container->get($this->extension->getAlias().'.foo.remote_config'));
        $this->assertInstanceOf(Firebase\Contract\RemoteConfig::class, $container->get(Firebase\Contract\RemoteConfig::class));
        $this->assertInstanceOf(Firebase\Contract\RemoteConfig::class, $container->get(Firebase\Contract\RemoteConfig::class.' $fooRemoteConfig'));

        $this->assertInstanceOf(Firebase\Contract\Messaging::class, $container->get($this->extension->getAlias().'.foo.messaging'));
        $this->assertInstanceOf(Firebase\Contract\Messaging::class, $container->get(Firebase\Contract\Messaging::class));
        $this->assertInstanceOf(Firebase\Contract\Messaging::class, $container->get(Firebase\Contract\Messaging::class.' $fooMessaging'));

        $this->assertInstanceOf(Firebase\Contract\AppCheck::class, $container->get($this->extension->getAlias().'.foo.app_check'));
        $this->assertInstanceOf(Firebase\Contract\AppCheck::class, $container->get(Firebase\Contract\AppCheck::class));
        $this->assertInstanceOf(Firebase\Contract\AppCheck::class, $container->get(Firebase\Contract\AppCheck::class.' $fooAppCheck'));
    }

    public function test_a_verifier_cache_can_be_used(): void
    {
        $cacheServiceId = 'cache.app.simple.mock';

        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'verifier_cache' => $cacheServiceId,
                ],
            ],
        ]);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $container->set($cacheServiceId, $cache);

        $container->get(Firebase\Contract\Auth::class);
        $this->addToAssertionCount(1);
    }

    public function test_an_auth_token_cache_can_be_used(): void
    {
        $cacheServiceId = 'cache.app.simple.mock';

        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'auth_token_cache' => $cacheServiceId,
                ],
            ],
        ]);
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $container->set($cacheServiceId, $cache);

        $container->get(Firebase\Contract\Auth::class);
        $this->addToAssertionCount(1);
    }

    public function test_http_client_options_can_be_used(): void
    {
        $httpClientOptionsServiceId = 'firebase.http_client_options';

        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'http_client_options' => $httpClientOptionsServiceId,
                ],
            ],
        ]);
        $container->set($httpClientOptionsServiceId, HttpClientOptions::default()->withTimeout(10.0));

        $container->get(Firebase\Contract\Auth::class);
        $this->addToAssertionCount(1);
    }

    public function test_a_project_can_be_private(): void
    {
        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'public' => false,
                ],
            ],
        ]);
        $container->compile();

        $this->assertFalse($container->has($this->extension->getAlias().'.foo'));
    }

    public function test_it_can_provide_multiple_projects(): void
    {
        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
                'bar' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
            ],
        ]);

        $this->assertTrue($container->hasDefinition($this->extension->getAlias().'.foo.auth'));
        $this->assertTrue($container->hasDefinition($this->extension->getAlias().'.bar.auth'));
    }

    public function test_it_supports_specifying_credentials(): void
    {
        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
            ],
        ]);

        $this->assertTrue($container->hasDefinition($this->extension->getAlias().'.foo.auth'));
    }

    public function test_it_accepts_only_one_default_project(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->createContainer([
            'projects' => [
                'foo' => [
                    'default' => true,
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
                'bar' => [
                    'default' => true,
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
            ],
        ]);
    }

    public function test_it_has_no_default_project_if_none_could_be_determined(): void
    {
        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
                'bar' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                ],
            ],
        ], $makeServicesPublic = true);

        $this->assertFalse($container->hasAlias(Firebase\Contract\Auth::class));
    }

    private function createContainer(array $config = [], $makeServicesPublic = false): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // Make all services public just for testing
        if ($makeServicesPublic) {
            $container->addCompilerPass(new class() implements CompilerPassInterface {
                public function process(ContainerBuilder $container): void
                {
                    \array_map(static function (Definition $definition): void {
                        $definition->setPublic(true);
                    }, $container->getDefinitions());

                    \array_map(static function (Alias $alias): void {
                        $alias->setPublic(true);
                    }, $container->getAliases());
                }
            });
        }

        $this->extension->load([$this->extension->getAlias() => $config], $container);

        return $container;
    }
}
