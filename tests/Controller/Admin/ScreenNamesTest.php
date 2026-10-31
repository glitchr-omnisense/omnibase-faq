<?php

namespace Base\Faq\Tests\Controller\Admin;

use Base\Admin\Twig\EntityNameTwigExtension;
use Base\Faq\Entity\Question;
use Base\Faq\Entity\Section;
use Base\Service\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The back office's screens of the FAQ are titled in the reader's language
 * (entities+intl-icu.*.yaml): they read "Question" and "Section" in every
 * language.
 */
final class ScreenNamesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        if (!class_exists('App\\Kernel')) {
            self::markTestSkipped('Requires a host application.');
        }

        self::bootKernel();
    }

    /** @return array<string, array{string, string, string}> */
    public static function languages(): array
    {
        return [
            'fr' => ['fr', 'Questions', 'Rubriques'],
            'en' => ['en', 'Questions', 'Sections'],
            'de' => ['de', 'Fragen', 'Rubriken'],
        ];
    }

    /** @dataProvider languages */
    #[\PHPUnit\Framework\Attributes\DataProvider('languages')]
    public function testTheScreensAreNamed(string $locale, string $questions, string $sections): void
    {
        static::getContainer()->get('translator')->setLocale($locale);

        $names = new EntityNameTwigExtension(static::getContainer()->get(TranslatorInterface::class));
        $this->assertSame($questions, $names->entityName(Question::class, true));
        $this->assertSame($sections, $names->entityName(Section::class, true));
    }
}
