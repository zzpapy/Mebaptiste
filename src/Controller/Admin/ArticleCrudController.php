<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles');
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('admin_article_editor.js');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre'),
            SlugField::new('slug')->setTargetFieldName('title'),
            TextareaField::new('excerpt', 'Résumé')->hideOnIndex(),
            TextEditorField::new('content', 'Contenu')
                ->setHelp('Vous pouvez glisser-déposer une image directement dans le texte, à l\'endroit souhaité.'),
            ImageField::new('featuredImage', 'Image mise en avant')
                ->setBasePath('uploads/articles')
                ->setUploadDir('public/uploads/articles')
                ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
                ->setRequired(false),
            TextField::new('metaDescription', 'Meta description (SEO)')->hideOnIndex(),
            DateTimeField::new('publishedAt', 'Date de publication'),
            BooleanField::new('isPublished', 'Publié'),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewOnSite = Action::new('viewOnSite', 'Voir la page publique', 'fas fa-external-link-alt')
            ->linkToUrl(function (Article $article): string {
                return $this->generateUrl('article_show', ['slug' => $article->getSlug()]);
            })
            ->setHtmlAttributes(['target' => '_blank']);

        return $actions
            ->add(Crud::PAGE_INDEX, $viewOnSite)
            ->add(Crud::PAGE_EDIT, $viewOnSite)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }
}