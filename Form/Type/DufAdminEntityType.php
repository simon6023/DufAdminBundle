<?php
namespace Duf\Bundle\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DufAdminEntityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'entity_empty'     => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('entity_empty', $options['entity_empty']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entity_empty']        = $options['entity_empty'];
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'entity_empty'         => true,
        );
 
        return array_replace($defaultOptions, $options);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}