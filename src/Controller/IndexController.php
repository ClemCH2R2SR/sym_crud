<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

use function PHPUnit\Framework\throwException;

class IndexController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private SessionInterface $session) {}


    #[Route('/', name: 'index')]
    public function toIndex(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/login', name: 'login')]
    public function login()
    {
        return $this->render('login.html.twig');
    }

    #[Route('/login/process', name:'login_process')]
    public function logUser()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST")
        {
            $userName = $_POST["username"];
            $user = $this->userRepository->findOneBy(['username' => $userName]);

            if($user->getUsername() === $userName && $user->getRole() === "ROLE_USER")
            {
                if (session_status() === PHP_SESSION_NONE){
                session_start();
                }
                $this->session->set('username', $userName);
                $this->session->set('role', $user->getRole());
                return $this->redirectToRoute('index');
            }
            
            elseif($user->getUsername() === $userName  && $user->getRole() === "ROLE_ADMIN")
            {
                if (session_status() === PHP_SESSION_NONE){
                session_start();
                }
                $this->session->set('username', $userName);
                $this->session->set('role', $user->getRole());
                return $this->redirectToRoute('admin');
            }
            else{

                return $this->redirectToRoute('fail');
            }
        }else{
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'error WTF');
        }
    }

    #[Route('/admin', name:'admin')]
    public function toAdmin(): Response
    {
        return $this->render('admin.html.twig');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        $this->session->clear();
        return $this->redirectToRoute('index');
    }
    
    #[Route('/fail', name: 'fail')]
    public function toFail(): Response
    {
        return $this->render('fail.html.twig');
    }
}