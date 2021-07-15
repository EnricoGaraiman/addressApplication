<?php


namespace App\Controller;

use App\Entity\Users;
use App\Form\ChangePasswordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\forgotPasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class EmailerController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/forgot_password", name="forgot_password")
     */
    public function forgotPassword(Request $request, MailerInterface $mailer) : Response
    {
        $message = ['message'=>'', 'with'=>'danger'];
        $form = $this->createForm(forgotPasswordFormType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $requiredEmail = $form->getData()['email'];
            $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email'=>$requiredEmail]);
            if($user !== null)
            {
                JWT::$leeway = 60;
                $key = $_ENV['FORGOT_KEY'];

                $payload = [
                    'id'=>$user->getId(),
                    'email'=>$user->getEmail(),
                    'date' => (new \DateTime())->modify('+2 hour')->format('Y-m-d H-i-s')
                ];

                $jwtToken = JWT::encode($payload, $key);
                try
                {
                    $email = (new TemplatedEmail())
                        ->from($_ENV['MAIN_EMAIL_ADDRESS'])
                        ->to($requiredEmail)
                        ->priority(Email::PRIORITY_HIGH)
                        ->subject('Change password on Address Application')
                        ->html('<p>We received your request to reset your password. To continue, please access the link bellow. The link is available only for two hours</p><br>
                        <a href="http://localhost:8000/change_password/' . $jwtToken . '" target="_blank">Change password now</a>');
                    $mailer->send($email);
                    $message = ['message'=>'The email was send. Check your inbox and follow the instructions', 'with'=>'success'];
                }
                catch (\Exception $e)
                {
                    $message = ['message'=>'The email was not send. Please try again', 'with'=>'danger'];
                }
            }
            else
            {
                $message = ['message'=>'The email not found', 'with'=>'danger'];
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'form'=>$form->createView(),
            'message'=>$message
        ]);
    }

    /**
     * @Route("/change_password/{token}", name="change_user_password")
     */
    public function changePassword($token, Request $request, UserPasswordEncoderInterface $encoder, MailerInterface $mailer) : Response
    {
        $message = ['message' => '', 'with' => 'danger'];
        $form = $this->createForm(ChangePasswordFormType::class);
        $key = $_ENV['FORGOT_KEY'];
        $decodedToken = (array) JWT::decode($token, $key, array('HS256'));
        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['email'=>$decodedToken['email'], 'id'=>$decodedToken['id']]);

        if( $user->getId() === $decodedToken['id'] and
            $user->getEmail() === $decodedToken['email'] and
            (new \DateTime())->format('Y-m-d H-i-s') <=
            \DateTime::createFromFormat('Y-m-d H-i-s', $decodedToken['date'])->format("Y-m-d H:i:s"))
        {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid())
            {
                $formData = $form->getData();
                $newPassword = $formData['password'];
                $newPasswordAgain = $formData['passwordcheck'];
                if ($newPassword === $newPasswordAgain)
                {
                    try
                    {
                        $user->setPassword($encoder->encodePassword($user, $newPassword));
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();
                        $email = (new TemplatedEmail())
                            ->from($_ENV['MAIN_EMAIL_ADDRESS'])
                            ->to($user->getEmail())
                            ->priority(Email::PRIORITY_HIGH)
                            ->subject('Your password was changed')
                            ->html('<p> Your password was changed for Address Application with success. </p>');
                        $mailer->send($email);
                        return new RedirectResponse($this->generateUrl('app_login'));
                    }
                    catch (\Exception $e)
                    {
                        $message = ['message'=>'The password was not change. Please try again', 'with'=>'danger'];
                    }
                }
                else
                {
                    $message = ['message' => 'Password dont match', 'with' => 'danger'];
                }
            }
        }
        else
        {
            $message = ['message' => 'This link is no longer available', 'with' => 'danger'];
        }

        return $this->render('security/change_password.html.twig', [
            'form'=>$form->createView(),
            'message'=>$message
        ]);
    }
}