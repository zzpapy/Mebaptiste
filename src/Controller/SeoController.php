<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SeoController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(
        PageRepository $pages,
        ArticleRepository $articles,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $urls = [];

        // Page d'accueil
        $urls[] = [
            'loc' => $urlGenerator->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod' => null,
            'priority' => '1.0',
        ];

        // Liste des actualités
        $urls[] = [
            'loc' => $urlGenerator->generate('article_list', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'lastmod' => null,
            'priority' => '0.7',
        ];

        // Pages publiées (hors page d'accueil, déjà incluse)
        foreach ($pages->findBy(['isPublished' => true]) as $page) {
            if ($page->getSlug() === 'accueil') {
                continue;
            }
            $urls[] = [
                'loc' => $urlGenerator->generate('page_show', ['slug' => $page->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => null,
                'priority' => '0.8',
            ];
        }

        // Articles publiés
        foreach ($articles->findBy(['isPublished' => true]) as $article) {
            $urls[] = [
                'loc' => $urlGenerator->generate('article_show', ['slug' => $article->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $article->getPublishedAt()?->format('Y-m-d'),
                'priority' => '0.6',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            if ($url['lastmod']) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';

        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    #[Route('/robots.txt', name: 'robots')]
    public function robots(UrlGeneratorInterface $urlGenerator): Response
    {
        $sitemapUrl = $urlGenerator->generate('sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $content = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /admin\n";
        $content .= "Disallow: /rendez-vous/annuler\n";
        $content .= "\n";
        $content .= "Sitemap: {$sitemapUrl}\n";

        return new Response($content, 200, ['Content-Type' => 'text/plain']);
    }
}