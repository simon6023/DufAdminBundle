<?php

namespace Duf\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use AshleyDawson\MultiBundle\AbstractMultiBundle;

class DufAdminBundle extends AbstractMultiBundle
{
	protected static function getBundles()
	{
		return array(
				'prod' 	=> array(
		            new Braincrafted\Bundle\BootstrapBundle\BraincraftedBootstrapBundle(),
		            new Bmatzner\FontAwesomeBundle\BmatznerFontAwesomeBundle(),
		            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
		            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
		            new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
		            new HWI\Bundle\OAuthBundle\HWIOAuthBundle(),
		            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
		            new Duf\CoreBundle\DufCoreBundle(),
		            new Duf\MessagingBundle\DufMessagingBundle(),
				),
				'dev' 	=> array(
					new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
				),
			);
	}
}
