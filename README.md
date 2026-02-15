# Firebase SDK Bundle

A Symfony Bundle for the [Firebase PHP SDK](https://github.com/beste/firebase-php).

[![Current version](https://img.shields.io/packagist/v/kreait/firebase-bundle.svg?logo=composer)](https://packagist.org/packages/kreait/firebase-bundle)
[![Monthly Downloads](https://img.shields.io/packagist/dm/kreait/firebase-bundle.svg)](https://packagist.org/packages/kreait/firebase-bundle/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/kreait/firebase-bundle.svg)](https://packagist.org/packages/kreait/firebase-bundle/stats)
[![Tests](https://github.com/beste/firebase-bundle/actions/workflows/tests.yml/badge.svg)](https://github.com/beste/firebase-bundle/actions/workflows/tests.yml)
[![Sponsor](https://img.shields.io/static/v1?logo=GitHub&label=Sponsor&message=%E2%9D%A4&color=ff69b4)](https://github.com/sponsors/jeromegamez)

> [!IMPORTANT]
> **Support the project:** This library is downloaded 1M+ times monthly and powers thousands of applications.
> If it saves you or your team time, please consider
> [sponsoring its development](https://github.com/sponsors/jeromegamez).

> [!NOTE]
> The project moved from the `kreait` to the `beste` GitHub Organization in January 2026.
> The namespace remains `Kreait\Firebase\Symfony\Bundle` and the package name remains `kreait/firebase-bundle`.
> Please update your remote URL if you have forked or cloned the repository.

- [Overview](#overview)
- [Installation](#installation)
- [Supported Versions](#supported-versions)
- [Documentation](#documentation)
- [License](#license)

## Overview

[Firebase](https://firebase.google.com/) provides the tools and infrastructure you need to develop your app, grow your user base, and earn money. The Firebase Admin PHP SDK enables access to Firebase services from privileged environments (such as servers or cloud) in PHP.

For more information, visit the [Firebase Admin PHP SDK documentation](https://firebase-php.readthedocs.io/).

## Installation

Add the bundle using [Composer](https://getcomposer.org)

```bash
composer require kreait/firebase-bundle
```

```php
// Symfony without Flex
// in %kernel.root_dir%/AppKernel.php
$bundles = array(
    // ...
    new Kreait\Firebase\Symfony\Bundle\FirebaseBundle(),
);

// Symfony with Flex
// in config/bundles.php
return [
    // ...
    Kreait\Firebase\Symfony\Bundle\FirebaseBundle::class => ['all' => true],
];
```

## Configuration

### Minimal

```yaml
# app/config/config.yml (Symfony without Flex)
# config/packages/firebase.yaml (Symfony with Flex)
kreait_firebase:
    projects:
        my_project:
            credentials: '%kernel.project_dir%/config/my_project_credentials.json'
        other_project: # optional
            credentials: '%kernel.project_dir%/config/other_project_credentials.json'
```

The following services will be available for your project:

* `kreait_firebase.my_project.app_check`
* `kreait_firebase.my_project.auth`
* `kreait_firebase.my_project.database`
* `kreait_firebase.my_project.firestore`
* `kreait_firebase.my_project.messaging`
* `kreait_firebase.my_project.remote_config`
* `kreait_firebase.my_project.storage`
* `kreait_firebase.other_project.*`

The following classes will be available for dependency injection if you have configured only one project:

* `Kreait\Firebase\Contract\AppCheck`
* `Kreait\Firebase\Contract\Auth`
* `Kreait\Firebase\Contract\Database`
* `Kreait\Firebase\Contract\Firestore`
* `Kreait\Firebase\Contract\Messaging`
* `Kreait\Firebase\Contract\RemoteConfig`
* `Kreait\Firebase\Contract\Storage`

To make it easier to use classes via dependency injection in the constructor of a class when multiple projects exist, you can do this in the constructor:

* `Kreait\Firebase\Contract\AppCheck $myProjectAppCheck`
* `Kreait\Firebase\Contract\Auth $myProjectAuth`
* `Kreait\Firebase\Contract\Database $myProjectDatabase`
* `Kreait\Firebase\Contract\Firestore $myProjectFirestore`
* `Kreait\Firebase\Contract\Messaging $myProjectMessaging`
* `Kreait\Firebase\Contract\RemoteConfig $myProjectRemoteConfig`
* `Kreait\Firebase\Contract\Storage $myProjectStorage`

### Full

```yaml
# app/config/config.yml (Symfony without Flex)
# config/packages/firebase.yaml (Symfony with Flex)
kreait_firebase:
    projects:
        my_project:
            # Optional: Path to the project's Service Account credentials file
            # If omitted, the credentials will be auto-discovered as described
            # in https://firebase-php.readthedocs.io/en/stable/setup.html
            credentials: '%kernel.project_dir%/config/my_project_credentials.json'
            # Optional: Override the project being used
            # If omitted, the project is automatically derived, e.g. from credentials
            project_id: 'project-b'
            # Optional: If set to true, this project will be used when 
            # type hinting the component classes of the Firebase SDK,
            # e.g. Kreait\Firebase\Auth, Kreait\Firebase\Database,
            # Kreait\Firebase\Messaging, etc.
            default: false 
            # Optional: If set to false, the service and its alias can only be
            # used via dependency injection, and not be retrieved from the
            # container directly.
            public: true
            # Optional: Should only be used if the URL of your Realtime
            # Database can not be generated with the project id of the 
            # given Service Account
            database_uri: 'https://my_project.firebaseio.com'
            # Optional: Make the client tenant aware
            tenant_id: 'tenant-id'
            # Optional: Used to cache Google's public keys.
            verifier_cache: null # Example: cache.app
            # Optional: Used to cache the authentication tokens for connecting to the Firebase servers.
            auth_token_cache: null # Example: cache.app
            # Optional: Service id of Kreait\Firebase\Http\HttpClientOptions
            # used to configure the SDK's HTTP client.
            http_client_options: null # Example: app.firebase.http_client_options
```

## Documentation

For documentation of the underlying SDK, visit the [Firebase Admin PHP SDK documentation](https://firebase-php.readthedocs.io/).

## License

This project is licensed under the [MIT License](LICENSE).

Your use of Firebase is governed by the [Terms of Service for Firebase Services](https://firebase.google.com/terms/).
