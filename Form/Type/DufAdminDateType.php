<?php
namespace Duf\Bundle\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class DufAdminDateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {

    }
 
    public function getDefaultOptions(array $options)
    {

    }

    public function getParent()
    {
        return DateTimeType::class;
    }
}