<?php


namespace App\Service;


use App\Entity\Orders;
use App\Entity\OrdersProducts;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class OrderConfirmationEmailService
{
    private $mailer;
    private $entityManager;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $entityManager)
    {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    public function sendOrderConfirmationEmail($user, $orderId)
    {
        $numberOfProducts = 0;
        $ordersProducts = $this->entityManager->getRepository(OrdersProducts::class)->findBy(['parentOrder'=>$orderId]);
        $order = $this->entityManager->getRepository(Orders::class)->findOneBy(['id'=>$orderId]);
        $totalPrice = $order->getTotal();
        foreach($ordersProducts as $product)
        {
            $numberOfProducts += $product->getQty();
        }

        try
        {
            $email = (new TemplatedEmail())
                ->from($_ENV['MAIN_EMAIL_ADDRESS'])
                ->to($user->getUsername())
                ->priority(Email::PRIORITY_HIGH)
                ->subject('Order confirmation')
                ->htmlTemplate('emails/order_confirmation.html.twig')
                ->context([
                    'products'=>$ordersProducts,
                    'totalPrice'=>$totalPrice,
                    'numberOfProducts'=>$numberOfProducts,
                    'destinationAddress'=>$order->getAddress()
                ]);

            $this->mailer->send($email);
        }
        catch (TransportExceptionInterface $e)
        {
            throw new Exception('The email was not send');
        }
    }
}