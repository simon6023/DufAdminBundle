<?php
namespace Duf\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DufAdminChoiceType extends AbstractType
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
        return ChoiceType::class;
    }
}