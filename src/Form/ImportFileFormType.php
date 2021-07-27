<?php


namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class ImportFileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', FileType::class, [
                'label' => 'Import countries and cities',
                'mapped' => false,
                'attr' => ['class' => 'form-control mt-3'],
                'constraints' => [
                    new File([
                        'maxSize' => '5024k',
                        'mimeTypes' => [
                            'text/csv',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid cvs or excel',
                    ])
                ],
            ])
            ->add('radio', ChoiceType::class,[
                'label'=>'Choose countries or cities',
                'choices'=>[
                    'Countries'=>'Countries',
                    'Cities'=>'Cities'
                ]
            ])
            ->add('import', SubmitType::class, [
                'attr' => ['class' => 'btn btn-light mt-3'],
            ])
        ;
    }

}