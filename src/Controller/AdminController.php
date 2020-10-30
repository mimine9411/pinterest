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
}
