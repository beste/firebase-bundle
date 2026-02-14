<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\Tests\DependencyInjection\Factory;

use Kreait\Firebase\Factory as FirebaseFactory;
use Kreait\Firebase\Http\HttpClientOptions;
use Kreait\Firebase\Symfony\Bundle\DependencyInjection\Factory\ProjectFactory;
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

    public function test_it_can_handle_a_custom_database_uri(): void
    {
        $this->factory->createDatabase($this->defaultConfig + ['database_uri' => 'https://domain.tld']);
        $this->addToAssertionCount(1);
    }

    public function test_it_can_handle_a_credentials_path(): void
    {
        $this->factory->createAuth(['credentials' => __DIR__.'/../../_fixtures/valid_credentials.json']);
        $this->addToAssertionCount(1);
    }

    public function test_it_can_handle_a_credentials_string(): void
    {
        $credentials = \file_get_contents(__DIR__.'/../../_fixtures/valid_credentials.json');

        $this->factory->createAuth(['credentials' => $credentials]);
        $this->addToAssertionCount(1);
    }

    public function test_it_can_handle_a_credentials_array(): void
    {
        $credentials = \json_decode(\file_get_contents(__DIR__.'/../../_fixtures/valid_credentials.json'), true);

        $this->factory->createAuth(['credentials' => $credentials]);
        $this->addToAssertionCount(1);
    }

    public function test_it_can_handle_a_tenant_id(): void
    {
        $this->factory->createAuth($this->defaultConfig + ['tenant_id' => 'tenant-id']);
        $this->addToAssertionCount(1);
    }

    public function test_it_can_handle_a_project_id(): void
    {
        $instance = $this->factory->createAuth($this->defaultConfig + ['project_id' => 'project-b']);
        $this->addToAssertionCount(1);
    }

    public function test_it_accepts_a_PSR16_verifier_cache(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $this->factory->setVerifierCache($cache);
        $this->factory->createAuth($this->defaultConfig);
        $this->addToAssertionCount(1);
    }

    public function test_it_accepts_a_PSR6_verifier_cache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $this->factory->setVerifierCache($cache);
        $this->factory->createAuth($this->defaultConfig);
        $this->addToAssertionCount(1);
    }

    public function test_it_accepts_a_PSR16_auth_token_cache(): void
    {
        $cache = $this->createMock(CacheInterface::class);

        $this->factory->setAuthTokenCache($cache);
        $this->factory->createAuth($this->defaultConfig);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_accepts_a_PSR6_auth_token_cache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);

        $this->factory->setAuthTokenCache($cache);
        $this->factory->createAuth($this->defaultConfig);

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_accepts_http_client_options(): void
    {
        $httpClientOptions = HttpClientOptions::default()->withTimeout(10.0);

        $this->factory->setHttpClientOptions($httpClientOptions);
        $this->factory->createAuth($this->defaultConfig);

        $this->addToAssertionCount(1);
    }
}
