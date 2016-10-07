##### Table of Contents  
[Headers](#headers)  
[Emphasis](#emphasis)  
...snip...    
<a name="headers"/>
## Headers

# Description

```diff
- WARNING : this bundle is still under development. You should not use it in production environment
```

The purpose of this bundle is to provide a quick way to get up-and-running with a fully fonctionning admin panel, and to make this admin quickly customizable by avoiding manual CRUD generation and multiplication of files. Create your entities, set some annotations and configuration variables, and you have a dynamic CRUD available in your admin panel.

This bundle also comes with a bunch of built-in functionalities : messaging system, file manager, multi-lang support, sitemap generation, Redmine integration...


# Installation

Add `simonduflos/dufadminbundle` to your `composer.json`

```json
"require": {
	"simonduflos/dufadminbundle":"dev-master"
}
```

Run `composer.phar update` to install the bundle and its dependencies.

Then add `DufAdminBundle` and its dependencies to your `AppKernel.php` :

```php
// app/AppKernel.php

public function registerBundles()
{
	$bundles = [
		// your other bundles

        new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
        new Bmatzner\FontAwesomeBundle\BmatznerFontAwesomeBundle(),
        new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
        new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
        new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
        new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
        new Symfony\Bundle\AsseticBundle\AsseticBundle(),
        new Duf\AdminBundle\DufAdminBundle(),
        new Duf\CoreBundle\DufCoreBundle(),
        new Duf\MessagingBundle\DufMessagingBundle(),
	];

    if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
        // your other test bundles
        
        $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
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
                always_use_default_target_path: true
                default_target_path: /site-admin
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


# Configuration

In order to customize this bundle, you have to set some basic informations in your `config.yml` file :

```yml
# app/config/config.yml

duf_admin:
    site_name: "My website"                 # the name of your website
    upload_dir: "uploads/"                  # directory used by the file manager
    allowed_upload_extensions:              # extensions accepted by the file manager
        images:
            - jpg
            - png
        documents:
            - txt
            - pdf
        videos:
            - avi
            - mp4
    file_system_items_per_page: 4           # number of items to display in the file manager
    user_entity: "MyBundle:User"            # your User entity
    user_role_entity: "MyBundle:UserRole"   # your UserRole entity
    language_entity: "MyBundle:Language"    # your language entity

```

# Usage

* Dynamically generate a CRUD for an entity

First, add this to your configuration :

```yml
duf_admin:
    entities:
        'AppBundle:MyEntity':
            title: 'My Entity'
            icon: 'fa-newspaper-o'
            title_field: 'title'

```

Then, in `/src/AppBundle/Entity/MyEntity.php` configure your entity to build the CRUD :

Import these annotations :

```php
use Duf\AdminBundle\Entity\DufAdminEntity;
use Duf\AdminBundle\Annotations\IndexableAnnotation as Indexable;
use Duf\AdminBundle\Annotations\EditableAnnotation as Editable;
```

Your `MyEntity` class must extend `DufAdminEntity`. Remove the `$id` property from your entity, since `DufAdminEntity` already contains this field.

```php
class MyEntity extends DufAdminEntity
{

}
```

Add annotations to your entities properties

```php
/**
 * @var string
 *
 * @ORM\Column(name="title", type="string", length=255)
 * @Indexable(index_column=true, index_column_name="Title")
 * @Editable(is_editable=true, label="Title", required=true, type="text", order=1, placeholder="Write your title")
 */
private $title;
```

That's it ! If you go to `my-domain/site-admin`, you should see a menu item with the name of your entity in the left sidebar, under the section "Content".