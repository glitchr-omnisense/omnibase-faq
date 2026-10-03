<?php

namespace Base\Faq\Entity;

use Base\Database\Attribute\DiscriminatorEntry;
use Base\Entity\Thread;
use Base\Enum\ThreadState;
use Base\Faq\Repository\QuestionRepository;
use Base\Service\Model\LinkableInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A question and its answer: an omnibase thread whose title is the question
 * and whose text is the answer. Published, it is on /faq in its section (a
 * taxon); a draft waits in the back office - the ones visitors ask arrive
 * as drafts (Ask, FaqController::ask). Likes count the readers it helped.
 */
#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'faq_question')]
#[DiscriminatorEntry(value: 'faq_question')]
class Question extends Thread implements LinkableInterface
{
    public static function __iconizeStatic(): ?array
    {
        return ['fa-solid fa-circle-question'];
    }

    public function __toLink(array $routeParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): ?string
    {
        return $this->getRouter()->generate('faq_question', array_merge($routeParameters, ['slug' => $this->getSlug()]), $referenceType);
    }

    /** Where it comes in its section: lower first. */
    #[ORM\Column(type: 'integer')]
    protected int $position = 0;

    /** On the home page's short FAQ. */
    #[ORM\Column(type: 'boolean')]
    protected bool $featured = false;

    /** Who asked it, when a visitor did: the e-mail left, to tell them once it is answered. */
    #[ORM\Column(length: 180, nullable: true)]
    protected ?string $askedBy = null;

    public function __toString(): string
    {
        return $this->getTitle() ?? '';
    }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): self { $this->featured = $featured; return $this; }

    public function getAskedBy(): ?string { return $this->askedBy; }
    public function setAskedBy(?string $email): self { $this->askedBy = $email ? mb_strtolower(trim($email)) : null; return $this; }

    public function getSection(): ?Section
    {
        foreach ($this->getTaxa() as $taxon) {
            if ($taxon instanceof Section) {
                return $taxon;
            }
        }

        return null;
    }

    public function setSection(?Section $section): self
    {
        foreach ($this->getTaxa()->toArray() as $taxon) {
            if ($taxon instanceof Section) {
                $this->removeTaxon($taxon);
            }
        }
        if ($section) {
            $this->addTaxon($section);
        }

        return $this;
    }

    public function publish(bool $published = true): self
    {
        $this->setState($published ? ThreadState::PUBLISH : ThreadState::DRAFT);
        if ($published && !$this->getPublishedAt()) {
            $this->setPublishedAt(new \DateTime());
        }

        return $this;
    }
}
