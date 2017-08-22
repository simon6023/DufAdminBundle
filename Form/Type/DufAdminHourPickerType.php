<?php
namespace Duf\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class DufAdminHourPickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'multiple'  => false,
            'hours'     => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('multiple', $options['multiple']);
        $builder->setAttribute('hours', $options['hours']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['hours']    = $options['hours'];
    }
 
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array('multiple'))
            ->setDefaults(array())
        ;
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'multiple'  => false,
            'hours'     => null,
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}