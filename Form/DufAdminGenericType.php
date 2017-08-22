<?php

namespace Duf\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Duf\AdminBundle\Form\Type\DufAdminTextType;
use Duf\AdminBundle\Form\Type\DufAdminEmailType;
use Duf\AdminBundle\Form\Type\DufAdminUrlType;
use Duf\AdminBundle\Form\Type\DufAdminTranslatableTextType;
use Duf\AdminBundle\Form\Type\DufAdminTextareaType;
use Duf\AdminBundle\Form\Type\DufAdminTranslatableTextareaType;
use Duf\AdminBundle\Form\Type\DufAdminChoiceType;
use Duf\AdminBundle\Form\Type\DufAdminDateType;
use Duf\AdminBundle\Form\Type\DufAdminDatetimeType;
use Duf\AdminBundle\Form\Type\DufAdminDayPickerType;
use Duf\AdminBundle\Form\Type\DufAdminHourPickerType;
use Duf\AdminBundle\Form\Type\DufAdminMinutePickerType;
use Duf\AdminBundle\Form\Type\DufAdminCheckboxType;
use Duf\AdminBundle\Form\Type\DufAdminEntityType;
use Duf\AdminBundle\Form\Type\DufAdminNumberType;
use Duf\AdminBundle\Form\Type\DufAdminPricesType;
use Duf\AdminBundle\Form\Type\DufAdminFileType;
use Duf\AdminBundle\Form\Type\DufAdminMultipleFileType;
use Duf\AdminBundle\Form\Type\DufAdminEntityHiddenType;

use Duf\AdminBundle\Form\DataTransformer\EntityToIdTransformer;

class DufAdminGenericType extends AbstractType
{
    private $manager;
    private $requestStack;
    private $container;

    public function __construct(ObjectManager $manager, RequestStack $requestStack, Container $container)
    {
        $this->manager      = $manager;
        $this->requestStack = $requestStack;
        $this->container    = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	if (isset($options['duf_options'])) {
    		foreach ($options['duf_options'] as $form_option) {
                if (isset($form_option['property_name'])) {
                    $builder->add($form_option['property_name'], $this->getFormType($form_option['type']), $form_option['parameters']);

                    if ($form_option['type'] === 'entity_hidden') {
                        $builder->get($form_option['property_name'])->addModelTransformer(new EntityToIdTransformer($this->manager, $form_option['class']));
                    }
                }
    		}
    	}

        $builder->add('duf_admin_form_token', HiddenType::class, array(
                'mapped' => false,
                'attr' => array(
                    'value'     => md5(uniqid() . date('d/m/Y H:i:s')),
                ),
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $config_service     = $this->container->get('duf_admin.dufadminconfig');
        $request            = $this->requestStack->getCurrentRequest();
        $config_entities    = $config_service->getDufAdminConfig('ecommerce_entities');

        if (null !== $request->get('path')) {
            $entity_name        = $this->container->get('duf_admin.dufadminrouting')->getEntityName($request->get('path'));
        }
        elseif (null !== $request->get('_route') && 'duf_admin_render_modal' === $request->get('_route')) {
            $entity_class       = $request->get('parent_entity_class');
            $entity_name        = $this->container->get('duf_admin.dufadminrouting')->getEntityNameFromBundle($entity_class);
        }

        $data_class         = 'Duf\AdminBundle\Entity\DufAdminEntity';
        if (isset($config_entities[$entity_name]))
            $data_class     = 'Duf\ECommerceBundle\Entity\DufECommerceProduct';

        if (isset($config_entities[$entity_name]['is_store']) && true === $config_entities[$entity_name]['is_store'])
            $data_class     = 'Duf\ECommerceBundle\Entity\DufECommerceStore';

        // if entity is User entity
        $user_entity        = $config_service->getDufAdminConfig('user_entity');
        if ($user_entity === $entity_name)
            $data_class     = 'Duf\AdminBundle\Entity\DufAdminUser';


        $resolver->setDefaults(array(
            'data_class' 	=> $data_class,
            'duf_options'	=> array(),
        ));
    }

    private function getFormType($string_type)
    {
    	switch ($string_type) {
    		case 'text':
    			$type = DufAdminTextType::class;
    			break;
            case 'translatable_text':
                $type = DufAdminTranslatableTextType::class;
                break;
            case 'date':
                $type = DufAdminDateType::class;
                break;
            case 'datetime':
                $type = DufAdminDatetimeType::class;
                break;
    		case 'email':
    			$type = DufAdminEmailType::class;
    			break;
            case 'url':
                $type = DufAdminUrlType::class;
                break;
    		case 'checkbox':
    			$type = DufAdminCheckboxType::class;
    			break;
    		case 'textarea':
    			$type = DufAdminTextareaType::class;
    			break;
            case 'translatable_textarea':
                $type = DufAdminTranslatableTextareaType::class;
                break;
   			case 'password':
    			$type = PasswordType::class;
    			break;
   			case 'choice':
    			$type = DufAdminChoiceType::class;
    			break;
   			case 'entity':
    			$type = DufAdminEntityType::class;
    			break;
            case 'file':
                $type = DufAdminFileType::class;
                break;
            case 'multiple_file':
                $type = DufAdminMultipleFileType::class;
                break;
            case 'hidden':
                $type = HiddenType::class;
                break;
            case 'entity_hidden':
                $type = DufAdminEntityHiddenType::class;
                break;
            case 'number':
            case 'integer':
            case 'float':
                $type = DufAdminNumberType::class;
                break;
            case 'prices':
                $type = DufAdminPricesType::class;
                break;
            case 'day_picker':
                $type = DufAdminDayPickerType::class;
                break;
            case 'hour_picker':
                $type = DufAdminHourPickerType::class;
                break;
            case 'minute_picker':
                $type = DufAdminMinutePickerType::class;
                break;
    		default:
    			$type = TextType::class;
    			break;
    	}

    	return $type;
    }
}