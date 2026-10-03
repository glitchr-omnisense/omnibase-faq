<?php

namespace Base\Faq\Controller\Admin\Crud;

use Base\Controller\Backoffice\Crud\Thread\TaxonCrudController;
use Base\Faq\Entity\Section;
use Base\Field\IntegerField;
use Base\Admin\Config\Actions;
use Base\Faq\Controller\Admin\OpenToAdminsTrait;

/** The FAQ's sections: omnibase's taxon screen, plus their order. */
class SectionCrudController extends TaxonCrudController
{
    use OpenToAdminsTrait;

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public static function getEntityFqcn(): string
    {
        return Section::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-folder-tree';
    }

    protected function taxonFields(string $pageName): iterable
    {
        yield IntegerField::new('position', '@faq.admin.section.position')->setColumns(4);
    }
}
