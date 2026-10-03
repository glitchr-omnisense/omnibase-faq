<?php

namespace Base\Faq\Twig;

use Base\Faq\Entity\Section;
use Base\Faq\Repository\QuestionRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/** What a host page asks the FAQ: a few questions for the home page, the sections for a menu. */
final class FaqExtension extends AbstractExtension
{
    public function __construct(private readonly QuestionRepository $questions)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('faq_questions', fn (?Section $section = null, ?int $limit = null): array => $this->questions->findPublished($section, $limit)),
            new TwigFunction('faq_featured', fn (int $limit = 4): array => $this->questions->findFeatured($limit)),
            new TwigFunction('faq_sections', fn (): array => $this->questions->findSections()),
        ];
    }
}
