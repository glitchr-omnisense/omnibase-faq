<?php

namespace Base\Faq\Entity;

use App\Entity\User;
use Base\Enum\SpamScore;
use Base\Service\Model\SpamProtectionInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What a visitor types in "Il manque une question ?": not an entity - a
 * form model Akismet reads (SpamProtectionInterface) before it becomes a
 * draft Question in the back office. A doubtful one is refused outright:
 * the teacher's inbox is not a moderation queue.
 */
final class Ask implements SpamProtectionInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 300)]
    public ?string $question = null;

    #[Assert\Length(max: 2000)]
    public ?string $details = null;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    public ?User $author = null;
    public ?int $spamScore = null;

    public function getSpamBlameable(): ?User { return $this->author; }
    public function getSpamText(): ?string { return trim($this->question.' '.$this->details); }
    public function getSpamDate(): \DateTime { return new \DateTime(); }

    public function getSpamCallback(int $score): void
    {
        $this->spamScore = $score;
    }

    public function isDoubtful(): bool
    {
        return null !== $this->spamScore && $this->spamScore >= SpamScore::__toInt()[SpamScore::MAYBE_SPAM];
    }
}
