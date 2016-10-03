<?php
namespace Duf\Bundle\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DufAdminFileType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'parent_entity'      => null,
            'parent_property'    => null,
            'filetype'          => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('parent_entity', $options['parent_entity']);
        $builder->setAttribute('parent_property', $options['parent_property']);
        $builder->setAttribute('filetype', $options['filetype']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['parent_entity']        = $options['parent_entity'];
        $view->vars['parent_property']      = $options['parent_property'];
        $view->vars['filetype']             = $options['filetype'];
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'parent_entity'         => true,
            'parent_property'       => true,
            'filetype'              => true,
        );
 
        return array_replace($defaultOptions, $options);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}