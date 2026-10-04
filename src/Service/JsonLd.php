<?php

namespace Base\Faq\Service;

use Base\Faq\Entity\Question;

/**
 * The questions as schema.org reads them: a FAQPage whose mainEntity is
 * each Question with its acceptedAnswer. Plain arrays: the template
 * encodes them. A question without an answer is left out - a FAQPage
 * entry needs one.
 */
final class JsonLd
{
    /** What an answer's text may keep (the tags search engines read in an Answer). */
    private const ALLOWED = '<p><br><ul><ol><li><a><strong><b><em><i><h2><h3><h4>';

    /**
     * @param iterable<Question> $questions
     *
     * @return array<string, mixed>|null null when no question has an answer
     */
    public function page(iterable $questions, ?string $url = null): ?array
    {
        $entities = [];
        $seen = [];
        foreach ($questions as $question) {
            $key = $question->getSlug() ?? spl_object_id($question);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $answer = self::answer($question->getContent());
            $title = trim((string) $question->getTitle());
            if ('' === $answer || '' === $title) {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $title,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
            ];
        }
        if (!$entities) {
            return null;
        }

        $data = ['@context' => 'https://schema.org', '@type' => 'FAQPage'];
        if (null !== $url) {
            $data['url'] = $url;
        }
        $data['mainEntity'] = $entities;

        return $data;
    }

    /**
     * An answer as a FAQPage takes it: its HTML kept to paragraphs, lists,
     * links and emphasis, without attributes other than a link's address;
     * an EditorJS document (JSON) turned into paragraphs and lists.
     */
    public static function answer(?string $content): string
    {
        $content = trim((string) $content);
        if ('' === $content) {
            return '';
        }
        if (str_starts_with($content, '{')) {
            $data = json_decode($content, true);
            if (\is_array($data) && isset($data['blocks']) && \is_array($data['blocks'])) {
                $content = self::blocks($data['blocks']);
            }
        }
        // Scripts and styles go with what they hold; then only the allowed tags stay.
        $content = (string) preg_replace('#<(script|style|iframe|form)\b[^>]*>.*?</\1>#is', '', $content);
        $content = strip_tags($content, self::ALLOWED);
        // No attribute but a link's href (http, https, mailto, tel, or a path).
        $content = (string) preg_replace_callback('#<([a-z0-9]+)\b([^>]*)>#i', static function (array $m): string {
            $tag = strtolower($m[1]);
            if ('a' === $tag && preg_match('#\bhref\s*=\s*("|\')(.*?)\1#is', $m[2], $href) && preg_match('#^(https?://|mailto:|tel:|/|\#)#i', trim($href[2]))) {
                return '<a href="'.htmlspecialchars(html_entity_decode(trim($href[2]), \ENT_QUOTES | \ENT_HTML5), \ENT_QUOTES).'">';
            }

            return '<'.$tag.'>';
        }, $content);

        $content = trim((string) preg_replace('/\s+/u', ' ', $content));

        // Tags around nothing are no answer.
        return '' === trim(html_entity_decode(strip_tags($content), \ENT_QUOTES | \ENT_HTML5)) ? '' : $content;
    }

    /** @param list<array<string, mixed>> $blocks EditorJS blocks */
    private static function blocks(array $blocks): string
    {
        $html = '';
        foreach ($blocks as $block) {
            $data = \is_array($block['data'] ?? null) ? $block['data'] : [];
            switch ($block['type'] ?? '') {
                case 'list':
                    $tag = 'ordered' === ($data['style'] ?? '') ? 'ol' : 'ul';
                    $items = '';
                    foreach ((array) ($data['items'] ?? []) as $item) {
                        $text = \is_array($item) ? (string) ($item['content'] ?? $item['text'] ?? '') : (string) $item;
                        if ('' !== trim($text)) {
                            $items .= '<li>'.$text.'</li>';
                        }
                    }
                    $html .= '' !== $items ? "<$tag>$items</$tag>" : '';
                    break;
                case 'header':
                    $html .= '' !== trim((string) ($data['text'] ?? '')) ? '<h3>'.$data['text'].'</h3>' : '';
                    break;
                default:
                    $text = (string) ($data['text'] ?? '');
                    $html .= '' !== trim($text) ? '<p>'.$text.'</p>' : '';
            }
        }

        return $html;
    }
}
