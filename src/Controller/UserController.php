<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Form\ChangePasswordFormType;
use App\Security\EmailVerifier;
use App\Form\RegistrationFormType;
use App\Security\Voter\UserVoter;
use Symfony\Component\Mime\Address;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class UserController extends AbstractController
{
    private $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, GuardAuthenticatorHandler $guardHandler, LoginFormAuthenticator $authenticator, EntityManagerInterface $em, Security $security): Response
    {
        if($security->getUser() !== null) {
            $this->addFlash('error', 'Your are already connected ! Please log out to register a new account.');
            return $this->redirectToRoute('app_home');
        }
        $user = new User;
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form['plainPassword']->getData()
                )
            );

            $em->persist($user);
            $em->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('hello@panterest.com', 'Panterest Bot'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('emails/registration/confirmation.html.twig')
            );
            // do anything else you need here, like send an email
            $this->addFlash('info', 'Please check your mails to verify your account !');
            return $guardHandler->authenticateUserAndHandleSuccess(
                $user,
                $request,
                $authenticator,
                'main' // firewall name in security.yaml
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify/email", name="app_verify_email")
     */
    public function verifyUserEmail(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $this->getUser());
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_home');
    }

    /**
     * @Route("/send/email/{id<\d+>}", name="app_send_email")
     */
    public function sendEmail(User $user): Response
    {
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            (new TemplatedEmail())
                ->from(new Address('noreply@panterest.com', 'Panterest Bot'))
                ->to($user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('emails/registration/confirmation.html.twig')
        );
        $this->addFlash('info', 'A mail has been send to you ! Please check and click on the link to verify your account.');
        return $this->redirectToRoute('app_home');
    }

    /**
     * @Route("/account/{id<\d+>}", name="app_user_account")
     */
    public function show(User $user, UserVoter $voter, Request $request, EntityManagerInterface $em, Security $security): Response
    {
        if($security->getUser() !== null && !$security->getUser()->isVerified()) {
            $this->addFlash('info', 'Please remember to verify your account by checking your mail.');
        }
        $this->denyAccessUnlessGranted($voter::SHOW, $user);
        $form = $this->createForm(AccountType::class, $user, [
            'method' => 'PUT'
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();
            $this->addFlash('success', 'Your account have been successfully updated !');
            return $this->redirectToRoute('app_home');
        }
        return $this->render('account/account.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/account/change_password/{id<\d+>}", name="app_account_change_password")
     */
    public function changePassword(Request $request, UserPasswordEncoderInterface $passwordEncoder, User $user, UserVoter $userVoter): Response
    {
        $this->denyAccessUnlessGranted($userVoter::CHANGE_PASSWORD, $user);
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Your password have been change successfully !');
            return $this->redirectToRoute('app_user_account', ['id'=>$user->getId()]);
        }
        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }



}
