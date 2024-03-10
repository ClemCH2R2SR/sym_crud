<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AlbumController extends AbstractController
{
    #[Route('/', name: 'album')]
    public function Album(AlbumRepository $albumRepository): Response
    {
        $recentAlbums = $albumRepository->findLast30Albums();

        return $this->render('index.html.twig', [
            'recentAlbums' => $recentAlbums
        ]);
    }
}