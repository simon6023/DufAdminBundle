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

