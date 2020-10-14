<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Form\PinType;
use App\Repository\PinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PinsController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(PinRepository $repo): Response
    {
        return $this->render('pins/index.html.twig', ['pins'=>$repo->findBy([],['createAt' => 'desc'])]);
    }

    /**
     * @Route("/pins/{id<\d+>}", name="app_pins_show")
     */
    public function show(Pin $pin): Response
    {

        return $this->render('pins/show.html.twig', compact('pin'));
    }


    /**
     * @Route("/pins/create", name="app_pins_create", methods="GET|POST")
     */
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $pin = new Pin();

        $form = $this->createForm(PinType::class, $pin);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            $em->persist($pin);
            $em->flush();

            return $this->redirectToRoute('app_pins_show', ['id'=>$pin->getId()]);
        }

        return $this->render('pins/create.html.twig', ['form'=>$form->createView()]);
    }

    /**
     * @Route("/pins/{id<\d+>}/edit", name="app_pins_edit", methods="GET|PUT")
     */
    public function edit(Pin $pin, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PinType::class, $pin, ['method'=>'PUT']);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_pins_show', ['id' => $pin->getId()]);
        }
        return $this->render('pins/edit.html.twig', ['form' => $form->createView(), 'pin' => $pin]);
    }
}
