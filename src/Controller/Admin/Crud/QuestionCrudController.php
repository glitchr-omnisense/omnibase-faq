<?php

namespace Base\Faq\Controller\Admin\Crud;

use Base\Faq\Controller\Admin\OpenToAdminsTrait;
use Base\Admin\Config\Actions;
use Base\Admin\Controller\AbstractCrudController;
use Base\Admin\Filter\Filters;
use Base\Faq\Entity\Question;
use Base\Faq\Entity\Section;
use Base\Field\BooleanField;
use Base\Field\EditorField;
use Base\Field\IdField;
use Base\Field\IntegerField;
use Base\Field\SelectField;
use Base\Field\SlugField;
use Base\Field\StateField;
use Base\Field\TextareaField;
use Base\Field\TextField;

/**
 * Answering the questions: the question (its title), the answer (its text),
 * its section, its place, "à la une" for the home page. A visitor's
 * question arrives as a draft, what they wrote in its preview, their e-mail
 * kept to tell them.
 */
class QuestionCrudController extends AbstractCrudController
{
    use OpenToAdminsTrait;

    public static function getEntityFqcn(): string
    {
        return Question::class;
    }

    public static function getPreferredIcon(): ?string
    {
        return 'fa-solid fa-circle-question';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add('state')->add('featured');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->openToAdmins(parent::configureActions($actions));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('title', '@faq.admin.question.title')->setColumns(8);
        yield StateField::new('state')->setColumns(4);
        yield SlugField::new('slug')->setTargetFieldName('title')->setColumns(4)->hideOnIndex();
        yield SelectField::new('section', '@faq.admin.question.section')->setClass(Section::class)->setRequired(false)->setColumns(4);
        yield IntegerField::new('position', '@faq.admin.question.position')->setColumns(2);
        yield BooleanField::new('featured', '@faq.admin.question.featured')->setColumns(2);
        yield TextareaField::new('excerpt', '@faq.admin.question.excerpt')->setRequired(false)->hideOnIndex()->setHelp('@faq.admin.question.excerpt_help');
        yield EditorField::new('content', '@faq.admin.question.content')->hideOnIndex()->setFormTypeOption('collab_autosave', true)->setFormTypeOption('collab_live', true);
        yield TextField::new('askedBy', '@faq.admin.question.asked_by')->setRequired(false)->setColumns(6)->hideOnIndex();
    }

    public function createEntity(string $entityFqcn): object
    {
        $question = new Question();
        $user = $this->getUser();
        if ($user instanceof \Base\Entity\User) {
            $question->addOwner($user);
        }

        return $question;
    }
}
