<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Style;
use App\Repository\StyleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CrudStyleController extends AbstractController
{
    private $session;
    private $style;
    
    public function __construct(SessionInterface $session, StyleRepository $style)
    {
        $this->session = $session;
        $this->style = $style;
    }

    #[Route('/admin/styles', name: 'admin_styles')]
    public function index(): Response
    {
        return $this->render('admin_styles.html.twig', [
            'controller_name' => 'CrudStyleController',
        ]);
    }

    #[Route('admin/style/create', name:'create_style')]

    public function toStyleCreate(): Response
    {
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        if($successMessage !== null)
        {   
            $this->session->remove('successMessage');
            return $this->render('crud_style/create_style.html.twig',['successMessage' => $successMessage]);
        }
        if($errorMessage !== null)
        {
            $this->session->remove('errorMessage');
            return $this->render('crud_style/create_style.html.twig', ['errorMessage' => $errorMessage]);
        }else
        {
            return $this->render('crud_style/create_style.html.twig');
        }
    }

    #[Route('admin/styles/create/process', name:'create_style_process', methods:['POST'])]
    public function styleCreate(Request $request, EntityManagerInterface $em): Response
    {
        $name = $request->request->get('name');
        $existingStyle = $em->getRepository(Style::class)->findOneBy(['name' => $name]);
        if($existingStyle !== null)
        {
            $this->session->set('errorMessage', 'Ce style existe deja !');
            return $this->redirectToRoute('create_style');
        }
        else
        {
            $style = new Style();
            $style->setName($name);
            $em->persist($style);
            $em->flush();

            $this->session->set('successMessage', 'Style créé avec succès !');
            return $this->redirectToRoute('create_style');
        }
    }

    #[Route('admin/style/delete', name:'delete_style')]
    public function toStyleDelete(): Response
    {
        $styles = $this->style->findAll();
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        if($successMessage !== null)
        {   
            $this->session->remove('successMessage');
            return $this->render('crud_style/delete_style.html.twig',
            ['successMessage' => $successMessage, 'styles' => $styles]);
        }
        if($errorMessage !== null)
        {
            $this->session->remove('errorMessage');
            return $this->render('crud_style/delete_style.html.twig',
            ['errorMessage' => $errorMessage, 'styles' => $styles]);
        }else
        {
            return $this->render('crud_style/delete_style.html.twig', ['styles' => $styles]);
        }
    }

    #[Route('admin/style/delete/process', name:'delete_style_process', methods:['POST'])]
    public function styleDelete(Request $request, EntityManagerInterface $em): Response
    {
        $styleId = $request->request->get('styleId');
        $match = $em->getRepository(Style::class)->findOneBy(['id' => $styleId]);

        if($match !== null)
        {
            $em->remove($match);
            $em->flush();
            $this->session->set('successMessage', 'Style supprimé avec succès !');
            return $this->redirectToRoute('delete_style');
        }
        else
        {
            $this->session->set('errorMessage', 'Echec de la suppression');
            return $this->redirectToRoute('delete_style');
        }
    }
}