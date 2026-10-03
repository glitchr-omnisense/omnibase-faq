<?php

namespace Base\Faq\Admin\Widget;

use Base\Admin\Config\Menu\MenuItem;
use Base\Admin\Widget\DashboardWidgetTypeInterface;
use Base\Faq\Repository\QuestionRepository;

/** The dashboard's tile: the questions still drafts - asked by visitors, or started and not finished. */
final class PendingQuestionsWidgetType implements DashboardWidgetTypeInterface
{
    public function __construct(private readonly QuestionRepository $questions)
    {
    }

    public static function getName(): string
    {
        return 'faq_pending_questions';
    }

    public function getTemplate(): string
    {
        return '@Faq/admin/widget/pending_questions.html.twig';
    }

    public function getTemplateVars(MenuItem $widget): array
    {
        return ['pending' => $this->questions->countDrafts(), 'questions' => $this->questions->findDrafts(5)];
    }
}
