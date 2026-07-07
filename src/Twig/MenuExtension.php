<?php

namespace App\Twig;

use App\Repository\PageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('menu_pages', [$this, 'getMenuPages']),
        ];
    }

    public function getMenuPages(): array
    {
        return $this->pageRepository->findMenuPages();
    }
}
