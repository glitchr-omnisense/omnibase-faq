<?php

namespace Base\Faq\Tests\Fixtures;

use Base\Faq\Entity\Question;

/**
 * A Question that keeps its texts in plain properties: omnibase's Thread
 * reads them from a translation entity through the kernel's localizer,
 * which a unit test has none of.
 */
class TestQuestion extends Question
{
    public function __construct(private ?string $testTitle = null, private ?string $testContent = null, ?string $slug = null)
    {
        // Thread's constructor is not run: it needs the kernel.
        $this->slug = $slug;
    }

    public function getTitle(?string $locale = null, int $inheritanceDepthIfNotSet = 0): ?string { return $this->testTitle; }
    public function getContent(?string $locale = null, int $inheritanceDepthIfNotSet = 0): ?string { return $this->testContent; }
}
