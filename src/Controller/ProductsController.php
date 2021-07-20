<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Entity\Products;
use App\Form\AddNewAddressFormType;
use App\Service\OrderConfirmationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductsController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/products", name="products")
     */
    public function products(): Response
    {
        $message = ['message'=>'', 'with'=>''];
        $products = $this->entityManager->getRepository(Products::class)->findAll();

        return $this->render('products/products.html.twig', [
            'message'=>$message,
            'products'=>$products,
        ]);
    }

    /**
     * @Route("/products/cart", name="cart")
     */
    public function cart(Request $request):Response {
        $message = ['message'=>'', 'with'=>''];
        $response = new Response();
        $defaultAddress = $this->entityManager->getRepository(Addresses::class)->findOneBy(['user'=>$this->getUser(), 'isDefault'=>1]);
        $cart = unserialize($request->cookies->get('cart'));
        $totalPrice = 0; $numberOfProducts = 0; $products = []; $form = null;

        // Add destination address
        $destinationAddress = $defaultAddress;
        if($destinationAddress === null)
        {
            $form = $this->createForm(AddNewAddressFormType::class, (new Addresses()));
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid())
            {
                $destinationAddress = $this->setDestinationAddressIntoDatabaseAndCookie($form);
            }
            $message = ['message'=>'Your dont have a default address', 'with'=>'danger'];
        }
        else
        {
            $response->headers->setCookie($this->setMyCookie('destinationAddress', $destinationAddress->getId()));
            $response->sendHeaders();
        }

        // Get products, price and quantity
        if($cart !== false and $cart !== [])
        {
            foreach ($cart as $id => $quantity)
            {
                $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id'=>$id]);
                if($product)
                {
                    $totalPrice += ((float)$product->getPrice()) * $quantity;
                    $numberOfProducts += $quantity;
                    $products[$id] = $product;
                }
            }
        }
        else
        {
            $message = ['message'=>'Your cart is empty. First add your product from "Online Store"', 'with'=>'danger'];
        }

        return $this->render('products/cart.html.twig', [
            'message'=>$message,
            'products'=>$products,
            'cart'=>$cart,
            'destinationAddress'=>$destinationAddress,
            'totalPrice'=>$totalPrice,
            'numberOfProducts'=>$numberOfProducts,
            'form'=>$form !== null ? $form->createView() : null
        ]);
    }

    /**
     * @Route("/products/complete_order", name="complete_order")
     */
    public function completeOrder(Request $request, OrderConfirmationEmailService $orderConfirmationEmailService):Response
    {
        $cart = unserialize($request->cookies->get('cart'));
        $destinationAddress = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id'=>$request->cookies->get('destinationAddress')]);
        $totalPrice = 0; $numberOfProducts = 0;
        $order = new Orders();

        if($cart !== false and $cart !== [] and $destinationAddress)
        {
            // Add order in database
            foreach ($cart as $id => $quantity)
            {
                $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id'=>$id]);
                $order->setUser($this->getUser())
                    ->setAddress($destinationAddress)
                    ->setTotal(0)
                    ->setOrderDate(new \DateTime());
                $this->entityManager->persist($order);
                $this->entityManager->flush();

                if($product)
                {
                    $totalPrice += ((float)$product->getPrice())*$quantity;
                    $numberOfProducts += $quantity;
                    $orderProducts = new OrdersProducts();
                    $orderProducts->setProduct($product)
                        ->setQty($quantity)
                        ->setParentOrder($order);
                    $this->entityManager->persist($orderProducts);
                    $this->entityManager->flush();
                }
            }
            $order->setTotal($totalPrice);
            $this->entityManager->persist($order);
            $this->entityManager->flush();

            // Delete cookies
            $response = new Response();
            $response->headers->setCookie($this->setMyCookie('cart', ''));
            $response->headers->setCookie($this->setMyCookie('destinationAddress', ''));
            $response->sendHeaders();

            // Send email
            $orderConfirmationEmailService->sendOrderConfirmationEmail($this->getUser(), $order->getId());
        }
        else
        {
            return new RedirectResponse($this->generateUrl('cart'));
        }

        return $this->render('products/complete_order.html.twig', [
            'destinationAddress'=>$destinationAddress,
            'totalPrice'=>$totalPrice,
            'numberOfProducts'=>$numberOfProducts,
        ]);
    }

    /**
     * @Route("/products/my_orders", name="my_orders")
     */
    public function myOrders(): Response
    {
        $fullOrders = [];
        $orders = $this->entityManager->getRepository(Orders::class)->findAll();

        foreach ($orders as $order)
        {
            $fullOrders[$order->getId()] = $this->entityManager->getRepository(OrdersProducts::class)->findBy(['parentOrder'=>$order->getId()]);
        }

        return $this->render('products/orders.html.twig', [
            'orders'=>$orders,
            'fullOrders'=>$fullOrders
        ]);
    }

    /**
     * @Route("/products/add_to_card_product", name="add_to_card_product")
     */
    public function addToCart(Request $request):Response
    {
        $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id'=>$request->get('id')]);
        $response = new Response();

        if($product !== null) {
            $cart = unserialize($request->cookies->get('cart'));
            if($cart !== false)
            {
                if (array_key_exists($request->get('id'), $cart))
                {
                    $cart[$request->get('id')] += (int)$request->get('quantity');
                }
                else
                {
                    $cart[$request->get('id')] = (int)$request->get('quantity');
                }
            }
            else
            {
                $cart = [$request->get('id') => (int)$request->get('quantity')];
            }
            $response->headers->setCookie($this->setMyCookie('cart', serialize($cart)));
            $response->sendHeaders();
        }
        return new Response($response);
    }

    /**
     * @Route("/products/delete_product_from_cart", name="delete_product_from_cart")
     */
    public function deleteProductFromCart(Request $request):Response
    {
        $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id'=>$request->get('id')]);
        $response = new Response();

        if($product !== null) {
            $cart = unserialize($request->cookies->get('cart'));
            if($cart !== false and array_key_exists($request->get('id'), $cart))
            {
                unset($cart[$request->get('id')]);
            }
            $response->headers->setCookie($this->setMyCookie('cart', serialize($cart)));
            $response->sendHeaders();
        }
        return new Response($response);
    }

    /**
     * @Route("/products/modify_product_from_cart", name="modify_product_from_cart")
     */
    public function modifyProductFromCart(Request $request):Response
    {
        $product = $this->entityManager->getRepository(Products::class)->findOneBy(['id'=>$request->get('id')]);
        $response = new Response();

        if($product !== null) {
            $cart = unserialize($request->cookies->get('cart'));
            if($cart !== false and array_key_exists($request->get('id'), $cart))
            {
                $cart[$request->get('id')] = (int)$request->get('quantity');
            }
            $response->headers->setCookie($this->setMyCookie('cart', serialize($cart)));
            $response->sendHeaders();
        }
        return new Response($response);
    }

    // Set cookie
    public function setMyCookie($name, $value)
    {
        return Cookie::create($name)
            ->withValue($value)
            ->withExpires(strtotime('now + 1 year'))
            ->withHttpOnly(false);
    }

    // Set destination address into database
    public function setDestinationAddressIntoDatabaseAndCookie($form)
    {
        $response = new Response();
        $address = $form->getData();
        if ($address->getIsDefault() !== false ) {
            foreach ($this->getUser()->getAddress() as $adr) {
                $adr->setIsDefault(0);
                $this->entityManager->persist($adr);
                $this->entityManager->flush();
            }
        }
        $address->setUser($this->getUser());
        $this->entityManager->persist($address);
        $this->entityManager->flush();
        $response->headers->setCookie($this->setMyCookie('destinationAddress', $address->getId()));
        $response->sendHeaders();
        return $address;
    }

}
