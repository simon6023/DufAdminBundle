# Installation

Add `simonduflos/dufadminbundle` to your `composer.json`

```json
"require": {
	"simonduflos/dufadminbundle":"dev-master"
}
```

Run `composer.phar update` to install the bundle and its dependencies.

Then add `DufAdminBundle` to your `AppKernel.php` :

```php
// app/AppKernel.php

public function registerBundles()
{
	$bundles = [
		// your other bundles
	];

	\Duf\AdminBundle\DufAdminBundle::registerInto($bundles, 'prod');

    if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
        \Duf\AdminBundle\DufAdminBundle::registerInto($bundles, 'dev');
    }
}
```

In order to use `BraincraftedBootstrapBundle`, you have to install a LESS compiler, using this command. If you encounter problems during this step, [please refer to the bundle's documentation](http://bootstrap.braincrafted.com/getting-started.html).

```cli
	npm install -g less
```

Then, add following configuration to your project

```yml
# app/config/config.yml

imports:
    - { resource: "@DufAdminBundle/Resources/config/config.yml" }

```

```yml
# app/config/routing.yml

duf_admin:
    resource: "@DufAdminBundle/Resources/config/routing.yml"
    prefix:   /site-admin

```

```yml
# app/config/security.yml
security:
    encoders:
        Symfony\Component\Security\Core\User\User:
            algorithm: plaintext
        Duf\AdminBundle\Entity\User:
            id: duf_admin.dufadminencoder
    providers:
        duf_admin_provider:
            entity: { class: Duf\AdminBundle\Entity\User }
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false
        duf_admin:
            provider: duf_admin_provider
            anonymous: ~
            form_login:
                login_path: duf_admin_login
                check_path: duf_admin_login_check
            logout:
                path: duf_admin_logout
                target: duf_admin_homepage
        duf_oauth:
            provider: duf_admin_provider
            anonymous: ~
            oauth:
                resource_owners:
                    facebook:           facebook_login
                login_path:        /oauth/login
                use_forward:       false
                failure_path:      /oauth/login
                oauth_user_provider:
                    oauth: hwi_oauth.user.provider.entity
        main:
            anonymous: ~
    access_control:
        - { path: ^/site-admin/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/site-admin, roles: ROLE_ADMIN }
```

Finally, run `dufadminbundle:install` using CLI.

```cli
	php bin/console dufadmin:install
```

This will :

* update database schema
* create `ROLE_ADMIN` and `ROLE_USER`
* create `User` entity with `ROLE_ADMIN` privileges
* create default languages
* import the bundle's native translations
* install assets
* dump assetic assets

# Usage

[TO DO]