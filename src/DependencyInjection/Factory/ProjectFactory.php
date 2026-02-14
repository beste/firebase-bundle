<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\DependencyInjection\Factory;

use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Http\HttpClientOptions;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class ProjectFactory
{
    private ?CacheItemPoolInterface $verifierCache = null;
    private ?CacheItemPoolInterface $authTokenCache = null;
    private ?HttpClientOptions $httpClientOptions = null;

    /**
     * @param CacheInterface|CacheItemPoolInterface $verifierCache
     */
    public function setVerifierCache($verifierCache = null): void
    {
        if ($verifierCache instanceof CacheInterface) {
            $verifierCache = new Psr16Adapter($verifierCache);
        }

        $this->verifierCache = $verifierCache;
    }

    /**
     * @param CacheInterface|CacheItemPoolInterface|null $authTokenCache
     */
    public function setAuthTokenCache($authTokenCache = null): void
    {
        if ($authTokenCache instanceof CacheInterface) {
            $authTokenCache = new Psr16Adapter($authTokenCache);
        }

        $this->authTokenCache = $authTokenCache;
    }

    public function setHttpClientOptions(?HttpClientOptions $httpClientOptions = null): void
    {
        $this->httpClientOptions = $httpClientOptions;
    }

    public function createAuth(array $config = []): Firebase\Contract\Auth
    {
        return $this->createFactory($config)->createAuth();
    }

    public function createFactory(array $config = []): Factory
    {
        $factory = new Factory();

        if ($config['credentials'] ?? null) {
            $factory = $factory->withServiceAccount($config['credentials']);
        }

        if ($config['database_uri'] ?? null) {
            $factory = $factory->withDatabaseUri($config['database_uri']);
        }

        if ($config['tenant_id'] ?? null) {
            $factory = $factory->withTenantId($config['tenant_id']);
        }

        if ($config['project_id'] ?? null) {
            $factory = $factory->withProjectId($config['project_id']);
        }

        if ($this->verifierCache) {
            $factory = $factory->withVerifierCache($this->verifierCache);
        }

        if ($this->authTokenCache) {
            $factory = $factory->withAuthTokenCache($this->authTokenCache);
        }

        if ($this->httpClientOptions) {
            $factory = $factory->withHttpClientOptions($this->httpClientOptions);
        }

        return $factory;
    }

    public function createDatabase(array $config = []): Firebase\Contract\Database
    {
        return $this->createFactory($config)->createDatabase();
    }

    public function createFirestore(array $config = []): Firebase\Contract\Firestore
    {
        return $this->createFactory($config)->createFirestore();
    }

    public function createMessaging(array $config = []): Firebase\Contract\Messaging
    {
        return $this->createFactory($config)->createMessaging();
    }

    public function createRemoteConfig(array $config = []): Firebase\Contract\RemoteConfig
    {
        return $this->createFactory($config)->createRemoteConfig();
    }

    public function createStorage(array $config = []): Firebase\Contract\Storage
    {
        return $this->createFactory($config)->createStorage();
    }

    public function createAppCheck(array $config = []): Firebase\Contract\AppCheck
    {
        return $this->createFactory($config)->createAppCheck();
    }
}
