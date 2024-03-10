<?php

namespace App\Controller;

use App\Repository\ArtistRepository;
use App\Repository\StyleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArtistController extends AbstractController
{
    #[Route('/artists', name: 'artist_all')]
    public function artists(ArtistRepository $artistRepository): Response
    {
        $artists = $artistRepository->findAll();
        return $this->render('artists.html.twig', [
            'artists' => $artists,
        ]);
    }

    #[Route('/artist/{id}', name: 'artist_one')]
    public function artist(ArtistRepository $artistRepository,
     $id): Response
    {
        $artist = $artistRepository->find($id);

        $styleId = $artist->getStyle()->getId();
        $similarArtists = $artistRepository->findSimilarArtists($styleId, $artist->getId());
        return $this->render('artist_template.html.twig',[
            'artist' => $artist,
            'similarArtists' => $similarArtists
        ]);
    }

}