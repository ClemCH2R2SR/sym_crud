<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Entity\Album;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CrudAlbumController extends AbstractController
{
    private AlbumRepository $albumRepository;
    private ArtistRepository $artistRepository;
    private SessionInterface $session;
    public function __construct(AlbumRepository $albumRepository, ArtistRepository $artistRepository, SessionInterface $session)
    {
        $this->albumRepository = $albumRepository;
        $this->artistRepository = $artistRepository;
        $this->session = $session;
    }


    #[Route('/admin/albums', name: 'admin_albums')]
    public function index(): Response
    {
        return $this->render('admin_albums.html.twig', [
            'controller_name' => 'AdminAlbumController',
        ]);
    }

    #[Route('admin/album/create', name:'create_album')]
    public function toAlbumCreate(): Response
    {
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');
        $this->session->remove('successMessage');
        $this->session->remove('errorMessage');

        $artists = $this->artistRepository->findAll();

        return $this->render('crud_album/create_album.html.twig', ['successMessage'=>$successMessage, 'errorMessage'=>$errorMessage, 'artists'=>$artists]);
    }

    #[Route('admin/album/create/process', name:'create_album_process', methods:['POST'])]
    public function albumCreate(Request $request, EntityManagerInterface $em)
    {
        $albumName = $request->request->get('album_name');
        $artistId = $request->request->get('artist_id');
        $artistId = $this->artistRepository->find($artistId);
        $releaseYear = $request->request->get('release_year');
        $cover = $request->files->get('cover');

        $alreadyExist = $this->albumRepository->findOneBy(['name'=>$albumName]);
        if($alreadyExist)
        {
            $this->session->set('errorMessage', 'Le nom existe deja !');
            $this->redirectToRoute('create_album');
        }
        else
        {
            $newAlbum = new Album();
            $newAlbum->setName($albumName);
            $newAlbum->setArtist($artistId);
            $newAlbum->setReleaseYear($releaseYear);

            $fileName = md5(uniqid()) . '.' . $cover->guessExtension();
            $cover->move($this->getParameter('upload_directory'), $fileName);
            $fileUploadedUrl = $request->getUriForPath($this->getParameter('upload_directory') . '/' . $fileName);

            $newAlbum->setCover($fileUploadedUrl);

            $em->persist($newAlbum);
            $em->flush();

            $this->session->set('successMessage', 'Album créé !');
            $this->redirectToRoute('create_album');
        }

        $this->redirectToRoute('index');
    }



































    #[Route('admin/album/update/choose', name:'choose_album')]
    public function toChooseAlbum()
    {
        $albums = $this->albumRepository->findAll();
        return $this->render('crud_album/choose_update_album.html.twig', ['albums' => $albums]);
    }

    #[Route('admin/album/update', name:'update_album', methods:['POST', 'GET'])]
    public function toAlbumUpdate(Request $request): Response
    {
        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        $artists = $this->artistRepository->findAll();

        
        $choseAlbumId = $request->request->get('albumId');
        
        $catchAlbum = $this->session->get('album');
        $this->session->remove('album');

        if($catchAlbum !== null)
        {
            $choseAlbumId = $catchAlbum->getId();
            $choseAlbum = $this->albumRepository->findOneBy(['id'=>$choseAlbumId]);
            $this->session->remove('successMessage');
            return $this->render('crud_album/update_album.html.twig', ['choseAlbum'=>$choseAlbum, 'artists'=>$artists, 'successMessage'=>$successMessage]);
        }
        else
        {
            $this->session->remove('errorMessage');
            $choseAlbum = $this->albumRepository->findOneBy(['id'=> $choseAlbumId]);
            return $this->render('crud_album/update_album.html.twig', ['choseAlbum'=>$choseAlbum, 'artists'=>$artists, 'errorMessage'=>$errorMessage]);
        }
    }


    #[Route('admin/album/update/process', name:'update_album_process', methods:['POST'])]
    public function updateAlbum(Request $request, EntityManagerInterface $em): Response
    {
        $albumName = $request->request->get('albumName');
        $albumId = $request->request->get('albumId');
        $album = $this->albumRepository->findOneBy(['id' => $albumId]);

        if(!$album)
        {
            throw $this->createNotFoundException('Aucun album trouvé !');
        }
        if($album)
        {
            $uploadedFile = $request->files->get('albumCover');

            if($uploadedFile)
            {
            $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();
            $uploadedFile->move($this->getParameter('upload_directory'), $fileName);
            $fileUploadedUrl = $request->getUriForPath($this->getParameter('upload_directory') . '/' . $fileName);
            $album->setCover($fileUploadedUrl);
            $album->setCover($fileUploadedUrl);
            }
            $releaseYear = $request->request->get('albumYear');
            $artistId = $request->request->get('artistId');
            
            $album->setName($albumName);
            // $album->setArtist($artistId);
            ///////////////////////////////////////JE FAIS UN TRUC ICI ///////////////////////////////////////////////////////////
            $artist = $this->artistRepository->findOneBy(['id' => $artistId]);
            $album->setArtist($artist);
            $album->setReleaseYear($releaseYear);
            
            
            $em->persist($album);
            $em->flush();
            
            $this->session->set('successMessage', 'Modifications sauvegardées !');

            $this->session->set('album',$album);
            
        return $this->redirectToRoute('update_album', /*['album' => $album]*/);

        }
        else
        {
            $this->session->set('errorMessage', 'Echec des modifications !');

            return $this->redirectToRoute('update_album', ['album' => $album]);
        }
    }

    #[Route('admin/album/delete', name:'delete_album')]
    public function toAlbumDelete(): Response
    {
        $albums = $this->albumRepository->findAll();

        $successMessage = $this->session->get('successMessage');
        $errorMessage = $this->session->get('errorMessage');

        if($successMessage !== null)
        {   
            $this->session->remove('successMessage');
            return $this->render('crud_style/delete_album.html.twig',
            ['successMessage' => $successMessage, 'albums' => $albums]);
        }
        if($errorMessage !== null)
        {
            $this->session->remove('errorMessage');
            return $this->render('crud_style/delete_album.html.twig',
            ['errorMessage' => $errorMessage, 'albums' => $albums]);
        }else
        {
            return $this->render('crud_album/delete_album.html.twig', ['albums'=>$albums]);
        }
    }

    #[Route('admin/album/delete/process', name:'delete_album_process',methods:['POST'])]
    public function deleteArtist(Request $request, EntityManagerInterface $em)
    {
        $albumToDeleteId = $request->request->get('albumId');
        $albumToDelete = $this->albumRepository->find($albumToDeleteId);

        if($albumToDelete !== null)
        {
            $em->remove($albumToDelete);
            $em->flush();
            $this->session->set('successMessage', 'Album supprimé avec succès !');
            return $this->redirectToRoute('delete_album');
        }
        else
        {
            $this->session->set('errorMessage', 'Echec de la suppression');
            return $this->redirectToRoute('delete_album');
        }

    }
}