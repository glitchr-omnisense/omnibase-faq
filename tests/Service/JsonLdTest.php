<?php

namespace Base\Faq\Tests\Service;

use Base\Faq\Service\JsonLd;
use Base\Faq\Tests\Fixtures\TestQuestion;
use PHPUnit\Framework\TestCase;

final class JsonLdTest extends TestCase
{
    public function testTheQuestionsAreAFaqPage(): void
    {
        $data = (new JsonLd())->page([
            new TestQuestion('Quels sont vos délais ?', '<p class="wp-block-paragraph">De <strong>5 à 15 jours</strong> ouvrés.</p>', 'delais'),
            new TestQuestion('Posez-vous les enseignes ?', '<p>Oui, par nos poseurs.</p>', 'pose'),
        ], 'https://example.org/faq');

        self::assertSame('https://schema.org', $data['@context']);
        self::assertSame('FAQPage', $data['@type']);
        self::assertSame('https://example.org/faq', $data['url']);
        self::assertCount(2, $data['mainEntity']);
        self::assertSame([
            '@type' => 'Question',
            'name' => 'Quels sont vos délais ?',
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => '<p>De <strong>5 à 15 jours</strong> ouvrés.</p>'],
        ], $data['mainEntity'][0]);
    }

    public function testAQuestionWithoutAnAnswerIsLeftOutAndNoneIsNoPage(): void
    {
        $jsonLd = new JsonLd();

        $data = $jsonLd->page([new TestQuestion('Sans réponse', null, 'a'), new TestQuestion('Avec', '<p>Oui.</p>', 'b'), new TestQuestion('', '<p>Sans titre.</p>', 'c')]);
        self::assertCount(1, $data['mainEntity']);
        self::assertSame('Avec', $data['mainEntity'][0]['name']);
        self::assertArrayNotHasKey('url', $data);

        self::assertNull($jsonLd->page([new TestQuestion('Sans réponse', '  ', 'a')]));
        self::assertNull($jsonLd->page([]));
    }

    public function testAQuestionListedTwiceIsThereOnce(): void
    {
        $question = new TestQuestion('Une fois', '<p>Oui.</p>', 'une-fois');

        self::assertCount(1, (new JsonLd())->page([$question, $question])['mainEntity']);
    }

    public function testAnAnswerKeepsParagraphsListsLinksAndEmphasisOnly(): void
    {
        $html = '<div class="x"><p style="color:red">Un <em>film</em> <span>dépoli</span>.</p><script>alert(1)</script><ul class="l"><li>Un</li></ul>'
            .'<a href="https://example.org/devis" target="_blank" onclick="x()">Devis</a> <a href="javascript:alert(1)">non</a><img src="/a.jpg"></div>';

        self::assertSame(
            '<p>Un <em>film</em> dépoli.</p><ul><li>Un</li></ul><a href="https://example.org/devis">Devis</a> <a>non</a>',
            JsonLd::answer($html),
        );
    }

    public function testAnEditorJsAnswerBecomesParagraphsAndLists(): void
    {
        $json = json_encode(['time' => 1, 'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Trois supports :']],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['PVC', ['content' => 'Dibond'], '']]],
            ['type' => 'header', 'data' => ['text' => 'Et la pose', 'level' => 2]],
            ['type' => 'list', 'data' => ['style' => 'ordered', 'items' => ['Repérage']]],
            ['type' => 'image', 'data' => ['file' => ['url' => '/a.jpg']]],
        ]]);

        self::assertSame('<p>Trois supports :</p><ul><li>PVC</li><li>Dibond</li></ul><h3>Et la pose</h3><ol><li>Repérage</li></ol>', JsonLd::answer($json));
    }

    public function testAnEmptyAnswer(): void
    {
        self::assertSame('', JsonLd::answer(null));
        self::assertSame('', JsonLd::answer("  \n"));
        self::assertSame('', JsonLd::answer('<p> </p>'), 'tags around nothing');
        self::assertSame('', JsonLd::answer('<script>x</script>'));
    }
}
