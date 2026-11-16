<?php

namespace Base\Faq\Repository;

use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Repositories;
use Base\Enum\ThreadState;
use Base\Faq\Entity\Question;
use Base\Faq\Entity\Section;
use Base\Repository\ThreadTranslations;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * The questions, on omnibase's finder language: the same queries whatever persistence runs them.
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(
        Finders $finders,
        private readonly Repositories $repositories,
        private readonly ThreadTranslations $translations,
    ) {
        parent::__construct($finders, Question::class);
    }

    /** @return list<Question> the published ones, of one section when given, in order */
    public function findPublished(?Section $section = null, ?int $limit = null): array
    {
        $criteria = $this->published();
        if ($section) {
            // The section among the question's taxa: a join of them, on that one.
            $criteria['taxa'] = $section;
        }

        return $this->findBy($criteria, ['position' => 'ASC', 'id' => 'ASC'], $limit ?: null);
    }

    /** @return list<Question> the featured ones first, then the most helpful */
    public function findFeatured(int $limit = 4): array
    {
        return $this->findBy($this->published(), ['featured' => 'DESC', 'position' => 'ASC', 'id' => 'ASC'], $limit);
    }

    public function findOnePublished(string $slug): ?Question
    {
        return $this->findOneBy([...$this->published(), 'slug' => $slug]);
    }

    /**
     * A plain search in the questions and answers: the translations whose title or content holds the term (the
     * core's ThreadTranslations::search(), lower-cased LIKE), then the published questions among their threads.
     * Each question once (the join on the translations could give one twice).
     *
     * @return list<Question>
     */
    public function search(string $term, int $limit = 20): array
    {
        $ids = [];
        foreach ($this->translations->search(['title' => $term, 'content' => $term]) as $translation) {
            $thread = $translation->getTranslatable();
            if ($thread instanceof Question && null !== $thread->getId()) {
                $ids[$thread->getId()] = $thread->getId();
            }
        }
        if (!$ids) {
            return [];
        }

        return $this->findBy([...$this->published(), 'id' => array_values($ids)], null, $limit);
    }

    /** @return list<Section> the sections that have a published question, in order */
    public function findSections(): array
    {
        // The top level: no parent, its key compared (`parent IS NULL`).
        $sections = $this->repositories->of(Section::class)->findBy(['parent' => Key::of(null)], ['position' => 'ASC', 'id' => 'ASC']);

        return array_values(array_filter($sections, fn (Section $section) => \count($section->getPublishedQuestions()) > 0 || $section->getChildren()->count() > 0));
    }

    public function countDrafts(): int
    {
        return $this->count(['state' => ThreadState::DRAFT]);
    }

    /** @return list<Question> */
    public function findDrafts(int $limit = 5): array
    {
        return $this->findBy(['state' => ThreadState::DRAFT], ['createdAt' => 'ASC'], $limit);
    }

    /** @return array<string, mixed> the published ones */
    private function published(): array
    {
        return ['state' => ThreadState::PUBLISH];
    }
}
