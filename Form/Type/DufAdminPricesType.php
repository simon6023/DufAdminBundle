<?php
namespace Duf\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;

class DufAdminPricesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'currencies'     => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('currencies', $options['currencies']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['currencies']        = $options['currencies'];
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'currencies'         => array(),
        );
 
        return array_replace($defaultOptions, $options);
    }

    public function getParent()
    {
        return TextType::class;
    }
}