<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Files;
use App\Entity\Orders;
use App\Entity\OrdersProducts;
use App\Entity\Products;
use App\Form\AddNewAddressFormType;
use App\Form\ProductFormType;
use App\Service\ExportService;
use App\Service\FileUploaderService;
use App\Service\FilterService;
use App\Service\OrderConfirmationEmailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


class ProductsController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/products/{page}", name="products", defaults={"page": 1},  requirements={"page"="\d+"})
     */
    public function products(Request $request, FilterService $filterService): Response
    {
        if($request->get('export_products') !== null)
        {
            return new RedirectResponse($this->generateUrl('export_products', [
                'search'=>$request->get('search'),
                'order'=>$request->get('order'),
                'selection'=>$request->get('selection')
            ]));
        }

        $options = [
            'product_asc' => 'By product name (ASC)',
            'product_desc' => 'By product name (DESC)',
            'price_asc' => 'By price (ASC)',
            'price_desc' => 'By price (DESC)',
        ];

        // Search
        $searchParameter = $request->get('search') !== null ? $request->get('search') : '';

        // Order
        $userOrderOption = $request->get('order');
        $order = $filterService->orderDropdownProducts($userOrderOption);

        // View
        $itemsPerPage = $request->get('itemsPerPage', 6);
        $page = (int)max(0, $request->get('page', 0));
        $offset = ($page - 1) * $itemsPerPage;
        $numberOfProducts = $this->entityManager->getRepository(Products::class)
            ->getProductsForOnePage($offset, $itemsPerPage, 1, $searchParameter, $order['orderBy'], $order['orderType']);
        $numberOfPages = ceil($numberOfProducts / $itemsPerPage);
        $products = $this->entityManager->getRepository(Products::class)
            ->getProductsForOnePage($offset, $itemsPerPage, 0, $searchParameter, $order['orderBy'], $order['orderType']);

        return $this->render('products/products.html.twig', [
            'products'=>$products,
            'numberOfPages' => $numberOfPages,
            'numberOfProducts' => $numberOfProducts,
            'itemsPerPage' => $itemsPerPage,
            'page' => $page,
            'searchParameter' => $searchParameter,
            'userOrderOption' => $userOrderOption,
            'options' => $options,
        ]);
    }

    /**
     * @Route("/products/product/{slug}", name="product")
     */
    public function product(Request $request): Response
    {
        $product = $this->entityManager->getRepository(Products::class)->findOneBy(['slug'=>$request->get('slug')]);
        $files = $this->entityManager->getRepository(Files::class)->findBy(['product'=>$product->getId()]);

        return $this->render('products/product.html.twig', [
            'product'=>$product,
            'files'=>$files
        ]);
    }

    /**
     * @Route("/products/cart", name="cart")
     */
    public function cart(Request $request):Response {
        $response = new Response();
        $defaultAddress = $this->entityManager->getRepository(Addresses::class)
            ->findOneBy(['user'=>$this->getUser(), 'isDefault'=>1]);
        $cart = unserialize($request->cookies->get('cart'));
        $totalPrice = 0; $numberOfProducts = 0; $products = []; $form = null;

        // Add destination address
        $destinationAddress = $defaultAddress;
        if($request->cookies->get('destinationAddress'))
        {
            $destinationAddress = $this->entityManager->getRepository(Addresses::class)
                ->findOneBy(['id'=>$request->cookies->get('destinationAddress')]);
        }

        if($destinationAddress === null)
        {
            $form = $this->createForm(AddNewAddressFormType::class, (new Addresses()));
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid())
            {
                $destinationAddress = $this->setDestinationAddressIntoDatabaseAndCookie($form);
            }
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

        return $this->render('products/cart.html.twig', [
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
        $destinationAddress = $this->entityManager->getRepository(Addresses::class)
            ->findOneBy(['id'=>$request->cookies->get('destinationAddress')]);
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
                    ->setOrderDate(new DateTime());
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
        $orders = $this->entityManager->getRepository(Orders::class)->findBy(['user'=>$this->getUser()]);

        foreach ($orders as $order)
        {
            $fullOrders[$order->getId()] = $this->entityManager->getRepository(OrdersProducts::class)
                ->findBy(['parentOrder'=>$order->getId()]);
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

    /**
     * @Route("/products/add_product", name="add_product")
     */
    public function new(Request $request, FileUploaderService $fileUploader, SluggerInterface $slugger)
    {
        $product = new Products();
        $form = $this->createForm(ProductFormType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imagesFile = $form->get('image')->getData();
            $product = $form->getData();
            if (count($imagesFile) > 0) {
                foreach ($imagesFile as $key=>$imageFile)
                {
                    if($imageFile)
                    {
                        $fileName = $fileUploader->upload($imageFile['name']);
                        $file = new Files();
                        $file->setName($fileName)
                            ->setType(Files::TYPE['image']);
                        if ($imageFile['position'] !== null)
                        {
                            $file->setPosition($imageFile['position']);
                        }
                        else
                        {
                            $file->setPosition($key);
                        }
                        $product->addFile($file);
                    }
                }
            }

            $documentFile = $form->get('document')->getData();
            if($documentFile)
            {
                $fileName = $fileUploader->upload($documentFile);
                $file = new Files();
                $file->setName($fileName)
                    ->setType(Files::TYPE['document'])
                    ->setPosition(0);
                $product->addFile($file);
            }

            $sameNameProductCount = count((array)$this->entityManager->getRepository(Products::class)->findBy(['name'=>$product->getName()]));
            $slug = $sameNameProductCount >= 1 ? $slugger->slug($product->getName()) . '-' . $sameNameProductCount : $slugger->slug($product->getName());
            $product->setSlug($slug);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            return new RedirectResponse($this->generateUrl('products'));
        }

        return $this->render('products/add_product.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/products/update_product", name="update_product")
     */
    public function updateProduct(Request $request, FileUploaderService $fileUploader, SluggerInterface $slugger, ParameterBagInterface $parameterBag): Response
    {
        $productUpdate = $this->entityManager->getRepository(Products::class)->findOneBy(['slug'=>$request->get('product')]);
        $filesUpdate = $this->entityManager->getRepository(Files::class)->findBy(['product'=>$productUpdate->getId()]);
        $form = $this->createForm(ProductFormType::class, $productUpdate);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imagesFile = $form->get('image')->getData();
            $product = $form->getData();
            if (count($imagesFile) > 0) {
                foreach ($imagesFile as $key => $imageFile) {
                    if ($imageFile) {
                        $fileName = $fileUploader->upload($imageFile['name']);
                        $file = new Files();
                        $file->setName($fileName)
                            ->setType(Files::TYPE['image']);
                        if ($imageFile['position'] !== null) {
                            $file->setPosition($imageFile['position']);
                        } else {
                            $file->setPosition($key);
                        }
                        $product->addFile($file);
                    }
                }
            }

            $documentFile = $form->get('document')->getData();
            if ($documentFile) {
                $fileName = $fileUploader->upload($documentFile);
                $file = new Files();
                $file->setName($fileName)
                    ->setType(Files::TYPE['document'])
                    ->setPosition(0);
                $product->addFile($file);
            }

            $sameNameProductCount = count((array)$this->entityManager->getRepository(Products::class)->findBy(['name'=>$product->getName()]));
            $slug = $sameNameProductCount >= 1 ? $slugger->slug($product->getName()) . '-' . $sameNameProductCount : $slugger->slug($product->getName());
            $product->setSlug($slug);
            $this->entityManager->persist($product);
            $this->entityManager->flush();
            return new RedirectResponse($this->generateUrl('update_product', ['product'=>$product->getSlug()]));
        }

        if($request->request->get('delete_file') !== null)
        {
            $file = $this->entityManager->getRepository(Files::class)->findOneBy(['name'=>$request->request->get('delete_file')]);
            $fs = new Filesystem();
            $fs->remove($parameterBag->get('brochures_directory').'/'.$file->getName());
            $this->entityManager->remove($file);
            $this->entityManager->flush();
        }

        if($request->request->get('change_position') !== null)
        {
            $file = $this->entityManager->getRepository(Files::class)->findOneBy(['name'=>$request->request->get('change_position')]);
            $file->setPosition($request->request->get('position'));
            $this->entityManager->persist($file);
            $this->entityManager->flush();
        }

        return $this->render('products/update_product.html.twig', [
            'form'=>$form->createView(),
            'product'=>$productUpdate,
            'filesUpdate'=>$filesUpdate
        ]);
    }

    /**
     * @Route("/products/preview_document", name="preview_document")
     */
    public function previewDocument(Request $request, ParameterBagInterface $parameterBag): BinaryFileResponse
    {
        return $this->file($parameterBag->get('brochures_directory').'/'.
            $request->get('documentName'), $request->get('documentName'), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/products/download_document", name="download_document")
     */
    public function downloadDocument(Request $request, ParameterBagInterface $parameterBag): BinaryFileResponse
    {
        return $this->file(new File($parameterBag->get('brochures_directory').'/'.$request->get('documentName')));
    }

    /**
     * @Route("/products/export_products", name="export_products")
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportProducts(Request $request, FilterService $filterService, ExportService $exportService): RedirectResponse
    {
        // Search
        $searchParameter = $request->get('search') !== null ? $request->get('search') : '';

        // Order
        $userOrderOption = $request->get('order');
        $order = $filterService->orderDropdownProducts($userOrderOption);

        // Get products
        $products = $this->entityManager->getRepository(Products::class)
            ->getProductsForOnePage(null, null, 0, $searchParameter, $order['orderBy'], $order['orderType']);

        // Export
        $spreadsheet = new Spreadsheet();
        $headerFile = $request->get('selection');
        $spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()
            ->fromArray(
                $headerFile
            );

        foreach ($products as $index=>$product)
        {
            $rowArray = [];
            foreach ($headerFile as $head)
            {
                $rowArray[$head] = $exportService->exportSelection($head, $product, $request);
            }
            $spreadsheet->getActiveSheet()
                ->fromArray(
                    $rowArray,
                    null,
                    'A'.($index+2)
                );
        }

        foreach(range('A','G') as $columnID)
        {
            $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="products '.(new DateTime())->format('Y-m-d H-i-s').'.xlsx"');
        $writer->save('php://output');

        return new RedirectResponse($this->generateUrl('products'));
    }

}
