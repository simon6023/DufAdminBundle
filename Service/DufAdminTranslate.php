<?php
namespace Duf\AdminBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\ORM\EntityManager as EntityManager;

class DufAdminTranslate
{
    private $container;
    private $em;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em                   = $entityManager;
        $this->container            = $container;
    }

    public function getAvailableLangs()
    {
        $default_lang       = $this->container->getParameter('locale');
        $language_entity    = $this->container->get('duf_admin.dufadminconfig')->getDufAdminConfig(array('language_entity'));
        $langs              = $this->em->getRepository($language_entity)->findBy(
                                    array('enabled' => true),
                                    array('name'    => 'ASC')
                                );

        $available_langs    = array();

        $default_lg_entity  = $this->em->getRepository($language_entity)->findOneBy(array('code' => $default_lang));
        if (!empty($default_lg_entity)) {
            $available_langs[$default_lg_entity->getCode()] = array(
                    'label'     => $default_lg_entity->getName(),
                    'name'      => $default_lg_entity->getCode(),
                );
        }

        if (!empty($langs)) {
            foreach ($langs as $lang) {
                if (!array_key_exists($lang->getCode(), $available_langs)) {
                    $available_langs[$lang->getCode()] = array(
                            'label'     => $lang->getName(),
                            'name'      => $lang->getCode(),
                        );
                }
            }
        }

        return $available_langs;
    }

    public function getEntityTranslations($entity_class, $entity_id, $field_name)
    {
        $results    = array();
        $is_seo     = (strpos($field_name, 'seo_') !== false) ? true : false;

        if (null === $entity_class) {
            return null;
        }

        if (!$is_seo) {
            $entity         = $this->em->find($entity_class, $entity_id);
            $repository     = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');

            $translations   = $repository->findTranslations($entity);

            foreach ($translations as $lang_name => $entity_translations) {
                foreach ($entity_translations as $entity_field_name => $translation) {
                    if ($entity_field_name == $field_name) {
                        $results[$lang_name] = $translation;
                    }
                }
            }
        }
        else {
            // clean entity class
            if (substr($entity_class, 0, 1) !== '') {
                $entity_class = '\\' . $entity_class;
            }
            $entity_class   = str_replace('/', '\\', $entity_class);

            $seo_entities   = $this->em->getRepository('DufCoreBundle:DufCoreSeo')->findBy(array(
                    'seo_type'      => $field_name,
                    'entity_id'     => $entity_id,
                    'entity_class'  => $entity_class,
                )
            );

            if (!empty($seo_entities)) {
                foreach ($seo_entities as $seo_entity) {
                    $results[$seo_entity->getLocale()] = $seo_entity->getSeoValue();
                }
            }
        }

        return $results;
    }
}