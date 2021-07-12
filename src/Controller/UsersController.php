<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Users;
use App\Service\FilterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UsersController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/users/{page}", name="users", defaults={"page": 1},  requirements={"page"="\d+"})
     */
    public function users(Request $request, FilterService $filterService): Response
    {
        $options = ['name_asc' => 'By name (ASC)',
            'name_desc' => 'By name (DESC)',
            'email_asc' => 'By email (ASC)',
            'email_desc' => 'By email (DESC)'];

        // Search
        $searchParameter = '';
        if ($request->get('search') !== null) {
            $searchParameter = $request->get('search');
        }

        // Order
        $userOrderOption = $request->get('order');
        $order = $filterService->orderDropdownProcess($userOrderOption);

        // View
        $itemsPerPage = $request->get('itemsPerPage', 5);
        $page = (int)max(0, $request->get('page', 0));
        $offset = ($page - 1) * $itemsPerPage;
        $numberOfProducts = $this->entityManager->getRepository(Users::class)->getUsersForOnePage($offset, $itemsPerPage, 1, $searchParameter, $order['orderBy'], $order['orderType']);
        $numberOfPages = ceil($numberOfProducts / $itemsPerPage);
        $users = $this->entityManager->getRepository(Users::class)->getUsersForOnePage($offset, $itemsPerPage, 0, $searchParameter, $order['orderBy'], $order['orderType']);
        $addresses = $this->entityManager->getRepository(Addresses::class)->findAll();

        return $this->render('users/users.html.twig', [
            'users' => $users,
            'numberOfPages' => $numberOfPages,
            'numberOfProducts' => $numberOfProducts,
            'searchParameter' => $searchParameter,
            'userOrderOption' => $userOrderOption,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'offset' => $offset,
            'options' => $options,
            'addresses' => $addresses
        ]);
    }

}
