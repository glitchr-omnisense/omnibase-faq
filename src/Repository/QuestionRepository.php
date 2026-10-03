<?php

namespace Base\Faq\Repository;

use Base\Enum\ThreadState;
use Base\Faq\Entity\Question;
use Base\Faq\Entity\Section;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Question> */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /** @return list<Question> the published ones, of one section when given, in order */
    public function findPublished(?Section $section = null, ?int $limit = null): array
    {
        $query = $this->published()->orderBy('q.position', 'ASC')->addOrderBy('q.id', 'ASC');
        if ($section) {
            $query->innerJoin('q.taxa', 's')->andWhere('s = :section')->setParameter('section', $section);
        }
        if ($limit) {
            $query->setMaxResults($limit);
        }

        return $query->getQuery()->getResult();
    }

    /** @return list<Question> the featured ones first, then the most helpful */
    public function findFeatured(int $limit = 4): array
    {
        return $this->published()
            ->orderBy('q.featured', 'DESC')->addOrderBy('q.position', 'ASC')->addOrderBy('q.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function findOnePublished(string $slug): ?Question
    {
        return $this->published()->andWhere('q.slug = :slug')->setParameter('slug', $slug)->getQuery()->getOneOrNullResult();
    }

    /** A plain search in the questions and answers. @return list<Question> */
    public function search(string $term, int $limit = 20): array
    {
        return $this->published()
            ->innerJoin('q.translations', 'tr')
            ->andWhere('tr.title LIKE :term OR tr.content LIKE :term')->setParameter('term', '%'.$term.'%')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return list<Section> the sections that have a published question, in order */
    public function findSections(): array
    {
        $sections = $this->getEntityManager()->getRepository(Section::class)->findBy(['parent' => null], ['position' => 'ASC', 'id' => 'ASC']);

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

    private function published(): QueryBuilder
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.state = :published')->setParameter('published', ThreadState::PUBLISH);
    }
}
