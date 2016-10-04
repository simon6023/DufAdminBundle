# Installation

Add `simonduflos/dufadminbundle` to your `composer.json`

```json
{
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

Then run `dufadminbundle:install` using CLI

```cli
	php bin/console dufadmin:install
```

Finally, add following configuration to your project

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

# Usage

[TO DO]