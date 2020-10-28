<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Repository\PinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="app_admin")
     * @IsGranted("ROLE_ADMIN")
     */
    public function index(PinRepository $pinRepository): Response
    {
        $pins = $pinRepository->findBy(['isVerified' => '0'], ['createdAt' => 'DESC']);
        return $this->render('admin/index.html.twig', [
            'pins' => $pins
        ]);
    }

    /**
     * @Route("/admin/verify/pins/{id<\d+>}", name="app_admin_verify")
     * @IsGranted("ROLE_ADMIN")
     */
    public function verify(Pin $pin, EntityManagerInterface $em): Response
    {
        $pin->setIsVerified(true);

        $em->persist($pin);
        $em->flush();
        return $this->redirectToRoute('app_admin');
    }

    /**
     * @Route("/admin/pins/delete/{id<[0-9]+>}", name="app_admin_delete")
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Pin $pin, EntityManagerInterface $em): Response
    {
            $em->remove($pin);
            $em->flush();

            $this->addFlash('info', 'Pin successfully deleted!');

        return $this->redirectToRoute('app_admin');
    }

}
