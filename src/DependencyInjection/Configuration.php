<?php

declare(strict_types=1);

namespace Kreait\Firebase\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder($this->name);

        $builder->getRootNode()
            ->fixXmlConfig('project')
            ->children()
                ->arrayNode('projects')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->variableNode('credentials')
                                ->info('Path to the project\'s Service Account credentials file or the json/array credentials parameters. If omitted, the credentials will be auto-dicovered as described in https://firebase-php.readthedocs.io/en/stable/setup.html')
                                ->example('%kernel.project_dir%/config/my_project_credentials.json or credentials: type ..')
                                ->validate()
                                    ->ifTrue(static function ($v) {return !\is_string($v) && !\is_array($v); })
                                    ->thenInvalid('Service Account credentials must be provided as a path to the project\'s credentials file, as a JSON encoded string or as an array')
                                ->end()
                            ->end()
                            ->scalarNode('project_id')
                                ->defaultNull()
                                ->info('Override the project id. Useful when credentials and service are from different projects')
                            ->end()
                            ->scalarNode('public')
                                ->defaultTrue()
                                ->info('If set to false, the service and its alias can only be used via dependency injection, and not be retrieved from the container directly.')
                            ->end()
                            ->scalarNode('default')
                                ->defaultNull()
                                ->info('If set to true, this project will be used when type hinting the component classes of the Firebase SDK, e.g. Kreait\\Firebase\\Auth, Kreait\\Firebase\\Database, Kreait\\Firebase\\Messaging, etc.')
                            ->end()
                            ->scalarNode('database_uri')
                                ->example('https://my-project.firebaseio.com')
                                ->info('Should only be used if the URL of your Realtime Database can not be generated with the project id of the given Service Account')
                            ->end()
                            ->scalarNode('tenant_id')
                                ->defaultNull()
                                ->info('Make the client tenant aware')
                            ->end()
                            ->scalarNode('verifier_cache')
                                ->defaultNull()
                                ->example('cache.app')
                                ->info('Used to cache Google\'s public keys.')
                            ->end()
                            ->scalarNode('auth_token_cache')
                                ->defaultNull()
                                ->example('cache.app')
                                ->info('Used to cache the authentication tokens for connecting to the Firebase servers.')
                            ->end()
                            ->scalarNode('http_client_options')
                                ->defaultNull()
                                ->example('app.firebase.http_client_options')
                                ->info('Service id of a Kreait\\Firebase\\Http\\HttpClientOptions instance to configure the SDK HTTP client.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
