<?php

namespace App\Controller;

use App\Repository\ArtistRepository;
use App\Entity\Artist;
use App\Repository\StyleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CrudArtistController extends AbstractController
{
    private ArtistRepository $artistRepository;
    private StyleRepository $styleRepository;
    private SessionInterface $session;
    public function __construct(ArtistRepository $artistRepository, StyleRepository $styleRepository, SessionInterface $session)
    {
        $this->artistRepository = $artistRepository;
        $this->styleRepository = $styleRepository;
        $this->session = $session;
    }


    #[Route('/admin/artists', name: 'admin_artists')]
    public function index(): Response
    {
        return $this->render('admin_artists.html.twig', [
            'controller_name' => 'CrudArtistController',
        ]);
    }

    #[Route('admin/artist/create', name:'create_artist')]
    public function toArtistCreate(): Response
    {
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');
        $this->session->remove('successMessage');
        $this->session->remove('errorMessage');

        $styles = $this->styleRepository->findAll();

        return $this->render('crud_artist/create_artist.html.twig',['successMessage'=>$successMessage, 'errorMessage'=>$errorMessage, 'styles'=>$styles]);
    }

    #[Route('admin/artist/create/process', name:'create_artist_process', methods:['POST'])]
    public function albumCreate(Request $request, EntityManagerInterface $em)
    {
        $artistName = $request->request->get('artist_name');
        $styleId = $request->request->get('style_id');
        $artistPicture = $request->files->get('artist_picture');

        $alreadyExist = $this->artistRepository->findOneBy(['name'=>$artistName]);
        if($alreadyExist)
        {
            $this->session->set('errorMessage', 'Le nom existe deja !');
            $this->redirectToRoute('create_artist');
        }
        else
        {
            $newArtist = new Artist();
            $newArtist->setName($artistName);
            $newArtist->setStyle($styleId);

            $fileName = md5(uniqid()) . '.' . $artistPicture->guessExtension();
            $artistPicture->move($this->getParameter('upload_directory'), $fileName);
            $fileUploadedUrl = $request->getUriForPath($this->getParameter('upload_directory') . '/' . $fileName);

            $newArtist->setPicture($fileUploadedUrl);

            $em->persist($newArtist);
            $em->flush();

            $this->session->set('successMessage', 'Artiste créé !');
            $this->redirectToRoute('create_artist');
        }

    }









































    #[Route('admin/artist/update/choose', name:'choose_update_artist_process')]
    public function toChooseArtist()
    {
        $artists = $this->artistRepository->findAll();
        return $this->render('crud_artist/choose_update_artist.html.twig',['artists' => $artists]);
    }

    #[Route('admin/artist/update', name:'update_artist', methods:['POST', 'GET'])]
    public function toArtistUpdate(Request $request): Response
    {
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        $styles = $this->styleRepository->findAll();


        $choseArtistId = $request->request->get('artistId');

        $catchArtist = $this->session->get('artist');
        $this->session->remove('artist');
       
        if($catchArtist !== null)
        {
            $choseArtistId = $catchArtist->getId();
            $choseArtist = $this->artistRepository->findOneBy(['id'=>$choseArtistId]);
            $this->session->remove('successMessage');
            return $this->render('crud_artist/update_artist.html.twig', ['choseArtist'=>$choseArtist, 'styles'=>$styles, 'successMessage'=>$successMessage]);
        }
        else
        {
            $this->session->remove('errorMessage');
            $choseArtist = $this->artistRepository->findOneBy(['id'=> $choseArtistId]);
            return $this->render('crud_artist/update_artist.html.twig', ['choseArtist'=>$choseArtist, 'styles'=>$styles, 'errorMessage'=>$errorMessage]);
        }
    }

    #[Route('admin/artist/update/process', name:'update_artist_process', methods:['POST'])]
    public function updateArtist(Request $request, EntityManagerInterface $em): Response
    {
        $artistName = $request->request->get('artistName');

        $artistId = $request->request->get('artistId');
        if(!$artistId)
        {
            throw $this->createNotFoundException('Aucun artiste ID trouvé !');
        }
        $artist = $this->artistRepository->findOneBy(['id' => $artistId]);

        if(!$artist)
        {
            throw $this->createNotFoundException('Aucun artiste trouvé !');
        }
        if($artist)
        {
            $uploadedFile = $request->files->get('artistPicture');

            if($uploadedFile)
            {
            $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move($this->getParameter('upload_directory'), $fileName);
            $fileUploadedUrl = $request->getUriForPath($this->getParameter('upload_directory') . '/' . $fileName);
            $artist->setPicture($fileUploadedUrl);
            $artist->setPicture($fileUploadedUrl);
            }

            $styleId = $request->request->get('styleId');
            
            $artist->setName($artistName);
            $artist->setStyle($styleId);
            $em->persist($artist);
            $em->flush();
            
            $this->session->set('successMessage', 'Modifications sauvegardées !');

            $this->session->set('artist',$artist);
            
            return $this->redirectToRoute('update_artist');
        }
        else
        {
            $this->session->set('errorMessage', 'Echec des modifications !');

            return $this->redirectToRoute('update_artist', ['artist' => $artist]);
        }
    }

    #[Route('admin/artist/delete', name:'delete_artist')]
    public function toArtistDelete(): Response
    {
        $artists = $this->artistRepository->findAll();

        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        if($successMessage !== null)
        {   
            $this->session->remove('successMessage');
            return $this->render('crud_style/delete_artist.html.twig',
            ['successMessage' => $successMessage, 'artists' => $artists]);
        }
        if($errorMessage !== null)
        {
            $this->session->remove('errorMessage');
            return $this->render('crud_style/delete_artist.html.twig',
            ['errorMessage' => $errorMessage, 'artists' => $artists]);
        }else
        {
            return $this->render('crud_artist/delete_artist.html.twig', ['artists'=>$artists]);
        }   
    }

    #[Route('admin/artist/delete/process', name:'delete_artist_process',methods:['POST'])]
    public function deleteArtist(Request $request, EntityManagerInterface $em)
    {
        $artistToDeleteId = $request->request->get('artistId');
        $artistToDelete = $this->artistRepository->find($artistToDeleteId);

        if($artistToDelete !== null)
        {
            $em->remove($artistToDelete);
            $em->flush();
            $this->session->set('successMessage', 'Artiste supprimé avec succès !');
            return $this->redirectToRoute('delete_artist');
        }
        else
        {
            $this->session->set('errorMessage', 'Echec de la suppression');
            return $this->redirectToRoute('delete_artist');
        }

    }
}
