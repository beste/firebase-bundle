<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\Tests\DependencyInjection\Factory;

use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Symfony\Bundle\DependencyInjection\Factory\ProjectFactory;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * I'm confident the bundle works fine, but if you're reading this, you're obviously not, AND RIGHTFULLY SO!
 *
 * The tests only check that the code runs, not that the ProjectFactory actually passes the given values to the
 * underlying Factory of the SDK.
 *
 * Here's the thing: the Firebase Factory is final and immutable. It being final prevents it from being defined as
 * a lazy service (I think), so the credentials need to be present when Firebase services are instantiated (I think),
 * but they aren't always (I think).
 *
 * I know, I should learn this, but honestly, I'm not getting paid for this, and so far nobody has complained. If you'd
 * like the tests to be better, feel free to submit a PR (I would much appreciate it!) or become a Sponsor to buy me
 * the time to learn this properly (no guarantees, though!).
 *
 * @internal
 */
final class ProjectFactoryTest extends TestCase
{
    private ProjectFactory $factory;

    private array $defaultConfig;

    protected function setUp(): void
    {
        $this->factory = new ProjectFactory();
        $this->defaultConfig = [
            'credentials' => __DIR__ . '/../../_fixtures/valid_credentials.json'
        ];
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleACustomDatabaseUri(): void
    {
        $this->factory->createDatabase($this->defaultConfig + ['database_uri' => 'https://domain.tld']);
    }

    #[DoesNotPerformAssertions]
    public function testItCanCreateMessaging(): void
    {
        $this->factory->createMessaging($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItCanCreateRemoteConfig(): void
    {
        $this->factory->createRemoteConfig($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItCanCreateStorage(): void
    {
        $this->factory->createStorage($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItCanCreateAppCheck(): void
    {
        $this->factory->createAppCheck($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleACredentialsPath(): void
    {
        $this->factory->createAuth(['credentials' => __DIR__.'/../../_fixtures/valid_credentials.json']);
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleACredentialsString(): void
    {
        $credentials = \file_get_contents(__DIR__.'/../../_fixtures/valid_credentials.json');

        $this->factory->createAuth(['credentials' => $credentials]);
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleACredentialsArray(): void
    {
        $credentials = \json_decode(\file_get_contents(__DIR__.'/../../_fixtures/valid_credentials.json'), true);

        $this->factory->createAuth(['credentials' => $credentials]);
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleATenantId(): void
    {
        $this->factory->createAuth($this->defaultConfig + ['tenant_id' => 'tenant-id']);
    }

    #[DoesNotPerformAssertions]
    public function testItCanHandleAProjectId(): void
    {
        $this->factory->createAuth($this->defaultConfig + ['project_id' => 'project-b']);
    }

    #[DoesNotPerformAssertions]
    public function testItAcceptsAPSR16VerifierCache(): void
    {
        $cache = $this->createStub(CacheInterface::class);

        $this->factory->setVerifierCache($cache);
        $this->factory->createAuth($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItAcceptsAPSR6VerifierCache(): void
    {
        $cache = $this->createStub(CacheItemPoolInterface::class);

        $this->factory->setVerifierCache($cache);
        $this->factory->createAuth($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItAcceptsAPSR16AuthTokenCache(): void
    {
        $cache = $this->createStub(CacheInterface::class);

        $this->factory->setAuthTokenCache($cache);
        $this->factory->createAuth($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItAcceptsAPSR6AuthTokenCache(): void
    {
        $cache = $this->createStub(CacheItemPoolInterface::class);

        $this->factory->setAuthTokenCache($cache);
        $this->factory->createAuth($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItAcceptsHttpClientOptions(): void
    {
        $httpClientOptions = HttpClientOptions::default()->withTimeout(10.0);

        $this->factory->setHttpClientOptions($httpClientOptions);
        $this->factory->createAuth($this->defaultConfig);
    }

    #[DoesNotPerformAssertions]
    public function testItCanResetHttpClientOptionsToNull(): void
    {
        $httpClientOptions = HttpClientOptions::default()->withTimeout(10.0);

        $this->factory->setHttpClientOptions($httpClientOptions);
        $this->factory->setHttpClientOptions(null);
        $this->factory->createAuth($this->defaultConfig);
    }
}
