<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DocumentManagerController extends AbstractController
{
    /**
     * @Route("/document/manager", name="document_manager")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/DocumentManagerController.php',
        ]);
    }
}
