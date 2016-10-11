<?php

namespace Duf\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Duf\AdminBundle\Form\Type\DufAdminTextType;
use Duf\AdminBundle\Form\Type\DufAdminTranslatableTextType;
use Duf\AdminBundle\Form\Type\DufAdminTextareaType;
use Duf\AdminBundle\Form\Type\DufAdminTranslatableTextareaType;
use Duf\AdminBundle\Form\Type\DufAdminChoiceType;
use Duf\AdminBundle\Form\Type\DufAdminDateType;
use Duf\AdminBundle\Form\Type\DufAdminDatetimeType;
use Duf\AdminBundle\Form\Type\DufAdminCheckboxType;
use Duf\AdminBundle\Form\Type\DufAdminEntityType;
use Duf\AdminBundle\Form\Type\DufAdminNumberType;
use Duf\AdminBundle\Form\Type\DufAdminFileType;
use Duf\AdminBundle\Form\Type\DufAdminEntityHiddenType;

use Duf\AdminBundle\Form\DataTransformer\EntityToIdTransformer;

class DufAdminGenericNestedTreeType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
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
        $resolver->setDefaults(array(
            'data_class' 	=> 'Duf\AdminBundle\Entity\DufAdminNestedTreeEntity',
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
    			$type = EmailType::class;
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
    		default:
    			$type = TextType::class;
    			break;
    	}

    	return $type;
    }
}