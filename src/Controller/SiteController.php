<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SiteController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(PageRepository $pages): Response
    {
        $home = $pages->findOneBy(['slug' => 'accueil', 'isPublished' => true]);

        return $this->render('site/home.html.twig', ['page' => $home]);
    }

    #[Route('/actualites', name: 'article_list')]
    public function articleList(ArticleRepository $articles): Response
    {
        $list = $articles->findBy(['isPublished' => true], ['publishedAt' => 'DESC']);

        return $this->render('site/article_list.html.twig', ['articles' => $list]);
    }

    #[Route('/actualites/{slug}', name: 'article_show')]
    public function articleShow(string $slug, ArticleRepository $articles): Response
    {
        $article = $articles->findOneBy(['slug' => $slug, 'isPublished' => true]);
        if (!$article) {
            throw $this->createNotFoundException();
        }

        return $this->render('site/article_show.html.twig', ['article' => $article]);
    }

    #[Route('/{slug}', name: 'page_show', priority: -1)]
    public function pageShow(string $slug, PageRepository $pages): Response
    {
        $page = $pages->findOneBy(['slug' => $slug, 'isPublished' => true]);
        if (!$page) {
            throw $this->createNotFoundException();
        }

        return $this->render('site/page_show.html.twig', ['page' => $page]);
    }
}