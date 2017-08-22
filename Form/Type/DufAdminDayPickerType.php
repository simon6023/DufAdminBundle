<?php
namespace Duf\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class DufAdminDayPickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'multiple'  => false,
            'days'      => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('multiple', $options['multiple']);
        $builder->setAttribute('days', $options['days']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['multiple']     = $options['multiple'];
        $view->vars['days']         = $options['days'];
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
            'days'      => null,
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}