<?php

namespace App\Controller;

use App\Entity\Addresses;
use App\Entity\Users;
use App\Form\EditProfileFormType;
use App\Service\DeleteAddressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class ProfileController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/profile", name="profile")
     */
    public function profile(Request $request, DeleteAddressService $deleteAddressService): Response
    {
        $message = ['message' => '', 'with' => 'danger'];
        $user = $this->getUser(); //userul logat
        $userAddresses = $this->entityManager->getRepository(Addresses::class)->findBy(['user' => $user->getId()]);
        $defaultAddress = $this->entityManager->getRepository(Addresses::class)->findOneBy(['user' => $user, 'isDefault' => 1]);

        // Add to default address
        if ($request->request->get('check') !== null and $request->request->get('set_default') !== null) {
            if (count($request->request->get('check')) > 1) {
                $message = ['message' => 'You can add only one default address', 'with' => 'danger'];
            } else {
                $addressDefault = $this->entityManager->getRepository(Addresses::class)->findOneBy(['id'=>$request->request->get('check')[0]]);
                if($addressDefault !== null) {
                    foreach ($user->getAddress() as $address) {
                        $address->setIsDefault(0);
                        $this->entityManager->persist($address);
                        $this->entityManager->flush();
                    }
                    $addressDefault->setIsDefault(1);
                    $this->entityManager->persist($addressDefault);
                    $this->entityManager->flush();
                }
                $defaultAddress = $addressDefault;
                $message = ['message' => 'You set a new default address', 'with' => 'success'];
            }
        }

        // Delete default address
        if ($request->request->get('delete_default_address') !== null) {
            if($user->getDefaultAddress() !== null) {
                foreach ($user->getDefaultAddress() as $adr) {
                    $adr->setIsDefault(0);
                    $this->entityManager->persist($adr);
                    $this->entityManager->flush();
                }
            }
            $defaultAddress = 0;
            $message = ['message' => 'The default address was been deleted with success', 'with' => 'success'];
        }

        // Delete addresses
        if ($request->request->get('delete') !== null) {
            if ($request->request->get('check') !== null) {
                $message = $deleteAddressService->deleteAddresses($request->request->get('check'));
                $defaultAddress = $this->entityManager->getRepository(Addresses::class)->findOneBy(['user' => $user, 'isDefault' => 1]);
                $userAddresses = $this->entityManager->getRepository(Addresses::class)->findBy(['user' => $user->getId()]);
            } else {
                $message = ['message' => 'Select one or more addresses', 'with' => 'danger'];
            }
        }

        // Update address
        if ($request->request->get('update') !== null) {
            if ($request->request->get('check') !== null) {
                if (count($request->request->get('check')) == 1) {
                    return new RedirectResponse($this->generateUrl('update_address', ['slug' => $request->request->get('check')]));
                } else {
                    $message = ['message' => 'You can update only one address at the same time', 'with' => 'danger'];
                }
            } else {
                $message = ['message' => 'Select an address', 'with' => 'danger'];
            }
        }

        return $this->render('profile/profile.html.twig', [
            'message' => $message,
            'user' => $user,
            'userAddresses' => $userAddresses,
            'defaultAddress' => $defaultAddress
        ]);
    }

    /**
     * @Route("/profile/edit_profile", name="edit_profile")
     */
    public function editProfile(Request $request): Response
    {
        $user = $this->getUser(); //userul logat
        $message = ['message' => '', 'with' => 'danger'];
        $form = $this->createForm(EditProfileFormType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setName($form->get('name')->getData());
            $user->setEmail($form->get('email')->getData());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $message = ['message' => 'Profile was changed', 'with' => 'success'];
        }

        return $this->render('profile/edit_profile.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }

    /**
     * @Route("/profile/change_password", name="change_password")
     */
    public function changePassword(UserPasswordEncoderInterface $encoder, Request $request): Response
    {
        $user = $this->getUser(); //userul logat
        $message = ['message' => '', 'with' => 'danger'];

        $old_password = $request->request->get('old-password');
        $new_password = $request->request->get('new-password');
        $new_password_again = $request->request->get('new-password-again');

        if ($old_password !== null) {
            if ($new_password === $new_password_again) {
                if ($encoder->isPasswordValid($user, $old_password)) {
                    $user->setPassword($encoder->encodePassword($user, $new_password));
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    $message = ['message' => 'Password was change', 'with' => 'success'];
                } else {
                    $message = ['message' => 'Old password is not correct', 'with' => 'danger'];
                }
            } else {
                $message = ['message' => 'Password dont match', 'with' => 'danger'];
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'message' => $message
        ]);
    }

    /**
     * @Route("/profile/delete_account", name="delete_account")
     */
    public function deleteAccount(Request $request): Response
    {
        $user = $this->getUser(); //userul logat
        $adrs = $this->entityManager->getRepository(Addresses::class)->findBy(['user' => $user->getId()]);

        foreach ($adrs as $item) {
            $this->entityManager->remove($item);
            $this->entityManager->flush();
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // manual logout
        $this->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        return new RedirectResponse($this->generateUrl('app_login'));
    }
}
