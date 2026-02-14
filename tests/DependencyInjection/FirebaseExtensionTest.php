<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\Tests\DependencyInjection;

use Kreait\Firebase;
use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Symfony\Bundle\DependencyInjection\Factory\ProjectFactory;
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
use Symfony\Component\DependencyInjection\Reference;
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

    public function testAProjectIsCreatedWithAServiceForEachFeature(): void
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

    public function testAVerifierCacheCanBeUsed(): void
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

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $container->set($cacheServiceId, $cache);

        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get(Firebase\Contract\Auth::class));
    }

    public function testAnAuthTokenCacheCanBeUsed(): void
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

        $cache = $this->createStub(CacheItemPoolInterface::class);
        $container->set($cacheServiceId, $cache);

        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get(Firebase\Contract\Auth::class));
    }

    public function testHttpClientOptionsCanBeUsed(): void
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

        $this->assertInstanceOf(Firebase\Contract\Auth::class, $container->get(Firebase\Contract\Auth::class));
    }

    public function testAProjectCanBePrivate(): void
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

    public function testItCanProvideMultipleProjects(): void
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

    public function testProjectFactoryOptionsDoNotLeakBetweenProjects(): void
    {
        $fooCacheServiceId = 'cache.foo';
        $barCacheServiceId = 'cache.bar';

        $container = $this->createContainer([
            'projects' => [
                'foo' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'verifier_cache' => $fooCacheServiceId,
                ],
                'bar' => [
                    'credentials' => __DIR__.'/../_fixtures/valid_credentials.json',
                    'auth_token_cache' => $barCacheServiceId,
                ],
            ],
        ]);

        $fooCalls = $container->getDefinition($this->projectFactoryServiceId('foo'))->getMethodCalls();
        $barCalls = $container->getDefinition($this->projectFactoryServiceId('bar'))->getMethodCalls();

        $this->assertContainsEquals(['setVerifierCache', [new Reference($fooCacheServiceId)]], $fooCalls);
        $this->assertNotContainsEquals(['setAuthTokenCache', [new Reference($barCacheServiceId)]], $fooCalls);

        $this->assertContainsEquals(['setAuthTokenCache', [new Reference($barCacheServiceId)]], $barCalls);
        $this->assertNotContainsEquals(['setVerifierCache', [new Reference($fooCacheServiceId)]], $barCalls);
    }

    public function testItSupportsSpecifyingCredentials(): void
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

    public function testItAcceptsOnlyOneDefaultProject(): void
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

    public function testItHasNoDefaultProjectIfNoneCouldBeDetermined(): void
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
