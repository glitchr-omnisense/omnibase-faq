<?php

namespace Base\Faq\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread\Taxon;
use Doctrine\ORM\Mapping as ORM;

/**
 * A section of the FAQ ("Utiliser mes ressources", "La boutique"): an
 * omnibase taxon, so it has a translated label and description, an icon,
 * and may hold sub-sections. Its questions are the threads it classifies.
 */
#[ORM\Entity]
#[DiscriminatorEntry(value: 'faq_section')]
class Section extends Taxon
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-folder-tree'];
    }

    /** Where it comes in the list: lower first. */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    /** @return list<Question> the published questions of this section, in order */
    public function getPublishedQuestions(): array
    {
        $questions = array_filter($this->getThreads()->toArray(), fn ($thread) => $thread instanceof Question && $thread->isPublished());
        usort($questions, fn (Question $a, Question $b) => [$a->getPosition(), $a->getId()] <=> [$b->getPosition(), $b->getId()]);

        return array_values($questions);
    }
}
