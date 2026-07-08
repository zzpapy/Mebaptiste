<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/article/upload-image')]
#[IsGranted('ROLE_ADMIN')]
class ArticleImageUploadController extends AbstractController
{
    #[Route('', name: 'admin_article_upload_image', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier reçu.'], 400);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName);
        $newFilename = sprintf('%s-%s.%s', $safeName, uniqid(), $file->guessExtension());

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/articles/content';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $file->move($uploadDir, $newFilename);

        return new JsonResponse([
            'url' => '/uploads/articles/content/'.$newFilename,
        ]);
    }
}