<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\City;
use App\Form\AddNewAddressFormType;
use App\Service\DeleteAddressService;
use App\Service\FilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $options = [
            'country_asc' => 'By country (ASC)',
            'country_desc' => 'By country (DESC)',
            'city_asc' => 'By city (ASC)',
            'city_desc' => 'By city (DESC)',
            'address_asc' => 'By address (ASC)',
            'address_desc' => 'By address (DESC)'
        ];

        // Set as default
        if($request->request->get('set_default') !== null)
        {
            $addressDefault = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id'=>$request->request->get('set_default')]);
            if($addressDefault !== null)
            {
                foreach ($this->getUser()->getAddress() as $address)
                {
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
        if ($request->request->get('update_address') !== null)
        {
            return new RedirectResponse($this->generateUrl('update_address', ['slug' => $request->request->get('update_address')]));
        }

        // Delete
        if ($request->request->get('delete_address') !== null)
        {
            $deleteAddress = ['0'=>$request->request->get('delete_address')];
            $deleteAddressService->deleteAddresses($deleteAddress);
            $message = ['message'=>'You delete an address', 'with'=>'success'];
        }

        // Search
        $searchParameter = '';
        if ($request->get('search') !== null)
        {
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
        $message = ['message' => '', 'with' => 'danger'];
        $user = $this->getUser();
        $form = $this->createForm(AddNewAddressFormType::class, (new Addresses()));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $address = $form->getData();
            if ($address->getIsDefault() !== false )
            {
                foreach ($user->getAddress() as $adr)
                {
                    $adr->setIsDefault(0);
                    $this->entityManager->persist($adr);
                    $this->entityManager->flush();
                }
            }
            $address->setUser($user);
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
    public function updateAddress(Request $request): Response
    {
        $message = ['message' => '', 'with' => 'danger'];
        $user = $this->getUser();

        if ($request->get('slug') !== null)
        {
            $addressId = is_string($request->get('slug')) ? $request->get('slug') :  $request->get('slug')[0];
            $addressUpdate = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id' => $addressId, 'user'=>$user]);

            if($addressUpdate === null)
            {
                return new RedirectResponse($this->generateUrl('profile'));
            }
        }
        else
        {
            return new RedirectResponse($this->generateUrl('profile'));
        }

        $form = $this->createForm(AddNewAddressFormType::class, $addressUpdate);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $address = $form->getData();
            if ($address->getIsDefault() !== false )
            {
                foreach ($user->getAddress() as $adr)
                {
                    $adr->setIsDefault(0);
                    $this->entityManager->persist($adr);
                    $this->entityManager->flush();
                }
            }
            $address->setUser($user);
            $this->entityManager->persist($address);
            $this->entityManager->flush();
            $message = ['message' => 'You update the address', 'with' => 'success'];
        }

        return $this->render('update_address/update_address.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }

    /**
     * @Route("addresses/getCityByCountry", name="getcity")
     */
    public function getCities(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $citiesRepository = $em->getRepository(City::class);

        $cities = $citiesRepository->createQueryBuilder("q")
            ->where("q.country = :cityid")
            ->setParameter("cityid", $request->get("countryId"))
            ->getQuery()
            ->getResult()
        ;

        $responseArray = array();
        foreach($cities as $city)
        {
            $responseArray[] = array(
                "id" => $city->getId(),
                "name" => $city->getName()
            );
        }

        return new JsonResponse($responseArray);
    }
}
