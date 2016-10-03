<?php
namespace Duf\Bundle\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Doctrine\Common\Persistence\ObjectManager;

use Duf\AdminBundle\Form\DataTransformer\EntityToIdTransformer;

class DufAdminEntityHiddenType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'class'      => null,
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('class', $options['class']);
    }
 
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['class']        = $options['class'];
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array('class'))
            ->setDefaults(array(
                'invalid_message' => 'The entity does not exist.',
            ))
        ;
    }
 
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'class'         => null,
        );
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}