<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Form\AddNewAddressFormType;
use App\Service\DeleteAddressService;
use App\Service\FilterService;
use App\Service\UpdateAddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddressesController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/addresses/{page}", name="addresses", defaults={"page": 1},  requirements={"page"="\d+"})
     */
    public function users(Request $request, FilterService $filterService, DeleteAddressService $deleteAddressService): Response
    {
        $message = ['message'=>'', 'with'=>'danger'];
        $options = ['country_asc' => 'By country (ASC)',
            'country_desc' => 'By country (DESC)',
            'city_asc' => 'By city (ASC)',
            'city_desc' => 'By city (DESC)',
            'address_asc' => 'By address (ASC)',
            'address_desc' => 'By address (DESC)'];

        // Set as default
        if($request->request->get('set_default') !== null) {
            $addressDefault = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id'=>$request->request->get('set_default')]);
            if($addressDefault !== null) {
                foreach ($this->getUser()->getAddress() as $address) {
                    $address->setIsDefault(0);
                    $this->entityManager->persist($address);
                    $this->entityManager->flush();
                }
                $addressDefault->setIsDefault(1);
                $this->entityManager->persist($addressDefault);
                $this->entityManager->flush();
            }
            $message = ['message'=>'You set a new default address', 'with'=>'success'];
        }

        // Update
        if ($request->request->get('update_address') !== null) {
            return new RedirectResponse($this->generateUrl('update_address', ['slug' => $request->request->get('update_address')]));
        }

        // Delete
        if ($request->request->get('delete_address') !== null) {
            $deleteAddress = ['0'=>$request->request->get('delete_address')];
            $deleteAddressService->deleteAddresses($deleteAddress);
            $message = ['message'=>'You delete an address', 'with'=>'success'];
        }

        // Search
        $searchParameter = '';
        if ($request->get('search') !== null) {
            $searchParameter = $request->get('search');
        }

        // Order
        $userOrderOption = $request->get('order');
        $order = $filterService->orderDropdownProcessInAddresses($userOrderOption);

        // View
        $itemsPerPage = $request->get('itemsPerPage', 5);
        $page = (int)max(0, $request->get('page', 0));
        $offset = ($page - 1) * $itemsPerPage;
        $numberOfProducts = $this->entityManager->getRepository(Addresses::class)->getAddressesForOnePage($offset, $itemsPerPage, 1, $searchParameter, $order['orderBy'], $order['orderType']);
        $numberOfPages = ceil($numberOfProducts / $itemsPerPage);
        $addresses = $this->entityManager->getRepository(Addresses::class)->getAddressesForOnePage($offset, $itemsPerPage, 0, $searchParameter, $order['orderBy'], $order['orderType']);

        return $this->render('addresses/addresses.html.twig', [
            'addresses' => $addresses,
            'numberOfPages' => $numberOfPages,
            'numberOfProducts' => $numberOfProducts,
            'searchParameter' => $searchParameter,
            'userOrderOption' => $userOrderOption,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'offset' => $offset,
            'options' => $options,
            'message' => $message
        ]);
    }

    /**
     * @Route("/addresses/add_new_address", name="add_new_address")
     */
    public function addNewAddress(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(AddNewAddressFormType::class);
        $message = ['message' => '', 'with' => 'danger'];

        if ($request->request->get('add') !== null) {
            $input = $request->request->get('add_new_address_form');
            $address = new Addresses();
            $address->setUser($user)
                ->setCountry($input['country'])
                ->setCity($input['city'])
                ->setAddress($input['address']);
            if (isset($input['default']) ) {
                $addressDefault = $this->entityManager->getRepository(Addresses::class)->findOneBy(['user' => $user, 'isDefault' => 1]);
                if($addressDefault !== null) {
                    foreach ($user->getAddress() as $adr) {
                        $adr->setIsDefault(0);
                        $this->entityManager->persist($adr);
                        $this->entityManager->flush();
                    }
                }
                $address->setIsDefault(1);
            }
            else {
                $address->setIsDefault(0);
            }
            $this->entityManager->persist($address);
            $this->entityManager->flush();
            $message = ['message' => 'You set a new address', 'with' => 'success'];
        }

        return $this->render('add_new_address/add_new_address.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }

    /**
     * @Route("addresses/update_address", name="update_address")
     */
    public function updateAddress(Request $request, UpdateAddressService $updateAddressService): Response
    {
        $form = $this->createForm(AddNewAddressFormType::class);
        $message = ['message' => '', 'with' => 'danger'];

        if ($request->get('slug') !== null) {
            $address = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id' => $request->get('slug')[0]]);
            if(is_string($request->get('slug'))) { // verific pt ca din profile se trimite array si din addresses string
                $address = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id' => $request->get('slug')]);
            }
        } else {
            return new RedirectResponse($this->generateUrl('profile'));
        }

        if ($request->request->get('update_address') !== null) {
            if(is_string($request->get('slug'))) {
                $message = $updateAddressService->updateAddress($request->request->get('add_new_address_form'), $request->get('slug'), $this->getUser());
            }
            else {
                $message = $updateAddressService->updateAddress($request->request->get('add_new_address_form'), $request->get('slug')[0], $this->getUser());
            }
        }

        return $this->render('update_address/update_address.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
            'address' => $address,
        ]);
    }
}
