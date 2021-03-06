<?php

namespace App\Controller;

use App\Entity\Pin;
use App\Entity\Tag;
use App\Form\PinType;
use App\Form\TagFormType;
use App\Repository\PinRepository;
use App\Repository\TagRepository;
use App\Security\Voter\PinVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

class PinsController extends AbstractController
{
    /**
     * @Route("/", name="app_home", methods="GET")
     */
    public function index(PinRepository $pinRepository): Response
    {
        $pins = $pinRepository->findBy([], ['createdAt' => 'DESC']);
        return $this->render('pins/index.html.twig', compact('pins'));
    }

    /**
     * @Route("/pins/create", name="app_pins_create", methods={"GET", "POST"})
     */
    public function create(Request $request, EntityManagerInterface $em, TagRepository $tagRepository, PinVoter $voter, Security $security): Response
    {

        $pin = new Pin;

        if($security->getUser() !== null && !$security->getUser()->isVerified()) {
            $this->addFlash('info', 'Please verify your account before creating a new pin. To send a new confirmation link, go to -> Account.');
            return $this->redirectToRoute('app_home');
        }
        $this->denyAccessUnlessGranted($voter::CREATE, $pin);
        $form = $this->createForm(PinType::class, $pin);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $pin->setUser($this->getUser());
            $em->persist($pin);


            preg_match_all('/#[^#|\s]+/', $pin->getDescription(),$tags);

            foreach($tags[0] as $t) {

                if(($existingTag = $tagRepository->findOneBy(['name'=>strtolower($t)])) === null) {
                    $tag = new Tag();
                    $tag->setName(strtolower($t));
                    $em->persist($tag);
                }
                else {
                    $tag = $existingTag;
                }
                $tag->addPin($pin);

            }
            $em->flush();

            $this->addFlash('success', 'Pin successfully created!');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('pins/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/pins/{id<[0-9]+>}", name="app_pins_show", methods="GET")
     */
    public function show(Pin $pin, PinVoter $voter): Response
    {
        $this->denyAccessUnlessGranted($voter::SHOW, $pin);
        return $this->render('pins/show.html.twig', compact('pin'));
    }

    /**
     * @Route("/pins/{id<[0-9]+>}/edit", name="app_pins_edit", methods={"GET", "PUT"})
     */
    public function edit(Request $request, Pin $pin, EntityManagerInterface $em, PinVoter $voter, TagRepository $tagRepository): Response
    {
        $this->denyAccessUnlessGranted($voter::EDIT, $pin);
        $form = $this->createForm(PinType::class, $pin, [
            'method' => 'PUT'
        ]);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            foreach($pin->getTag()->toArray() as $t) {
                $pin->removeTag($t);
            }

            preg_match_all('/#[^#|\s]+/', $pin->getDescription(),$tags);

            foreach($tags[0] as $t) {

                if(($existingTag = $tagRepository->findOneBy(['name'=>strtolower($t)])) === null) {
                    $tag = new Tag();
                    $tag->setName(strtolower($t));
                    $em->persist($tag);
                }
                else {
                    $tag = $existingTag;
                }
                $tag->addPin($pin);
                $pin->addTag($tag);
            }
            $em->flush();

            $this->addFlash('success', 'Pin successfully updated!');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('pins/edit.html.twig', [
            'pin' => $pin,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/pins/{id<[0-9]+>}", name="app_pins_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Pin $pin, EntityManagerInterface $em, PinVoter $voter): Response
    {
        $this->denyAccessUnlessGranted($voter::DELETE, $pin);
        if ($this->isCsrfTokenValid('pin_deletion_' . $pin->getId(), $request->request->get('csrf_token'))) {
            $em->remove($pin);
            $em->flush();

            $this->addFlash('info', 'Pin successfully deleted!');
        }

        return $this->redirectToRoute('app_home');
    }

    /**
     * @Route("/pins/tags/{tagName}", name="app_pins_tag")
     */
    public function indexTag(TagRepository $tagRepository, $tagName): Response
    {
        $tagName = '#'.$tagName;
        $tag = $tagRepository->findOneBy(['name' => $tagName]);
        $pins = [];
        if($tag !== null) {
            $pins = $tag->getPins()->toArray();
        }
        return $this->render('pins/tag.html.twig', ['pins' => $pins, 'tagName'=>$tagName]);
    }

    /**
     * @Route("/search/tags", name="app_tag_search")
     */
    public function searchTag(Request $request): Response
    {

        if(($tagName = $request->request->get('tagName')) === '')
        {
             $tagName = ' ';
        }
        return  $this->redirectToRoute('app_pins_tag', ['tagName' => $tagName]);
    }

}
