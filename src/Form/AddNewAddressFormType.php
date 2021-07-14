<?php


namespace App\Form;

use App\Entity\Addresses;
use App\Entity\Country;
use App\Entity\City;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddNewAddressFormType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('country', null, [
                'mapped'=>false
            ])
            ->add('city')
            ->add('address', TextType::class)
            ->add('isDefault', CheckboxType::class, [
                'required' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'))
            ->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'))
        ;
    }

    protected function addElements(FormInterface $form, Country $country = null) {
        $form->add('country', EntityType::class, array(
            'required' => true,
            'mapped'=>false,
            'data' => $country,
            'placeholder' => 'Select a country...',
            'class' => Country::class
        ));

        $cities = array();

        // If there is a city stored in the Addresses entity, load the cities of it
        if ($country) {
            // Fetch Cities of the Country if there's a selected city
            $repoCities = $this->em->getRepository(City::class);
            $cities = $repoCities->createQueryBuilder("c")
                ->where("c.country = :countryid")
                ->setParameter("countryid", $country->getId())
                ->getQuery()
                ->getResult();
        }

        // Add the City field with the properly data
        $form->add('city', EntityType::class, array(
            'required' => true,
            'placeholder' => 'Select a Country first ...',
            'class' => City::class,
            'choices' => $cities
        ));
    }

    function onPreSubmit(FormEvent $event) {
        $form = $event->getForm();
        $data = $event->getData();
        $country = $this->em->getRepository(Country::class)->find($data['country']);
        $this->addElements($form, $country);
    }

    function onPreSetData(FormEvent $event) {
        $address = $event->getData();
        $form = $event->getForm();
        $country = $address->getCity() ? $address->getCity()->getCountry() : null;
        $this->addElements($form, $country);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Addresses::class
        ]);
    }
}