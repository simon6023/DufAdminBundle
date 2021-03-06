<?php
namespace Duf\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class DufAdminNumberType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'number_type'      => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('number_type', $options['number_type']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['number_type']  = $options['number_type'];
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'number_type'   => true,
        );
 
        return array_replace($defaultOptions, $options);
    }

    public function getParent()
    {
        return NumberType::class;
    }
}