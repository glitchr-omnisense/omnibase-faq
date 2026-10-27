---
title: In a host page
order: 35
---

# In a host page

## Twig functions

`Base\Faq\Twig\FaqExtension`, autoconfigured:

| Function | Returns |
|---|---|
| `faq_questions(section = null, limit = null)` | `QuestionRepository::findPublished()`: the published questions, of one `Section` when given, by position |
| `faq_featured(limit = 4)` | `findFeatured()`: the featured questions first, then the others by position |
| `faq_sections()` | `findSections()`: the top-level sections that have something to show |

## The partial

`@Faq/client/_questions.html.twig` draws a list of questions as `<details>` elements, each
with the id `q-<slug>`, the answer through `|wysiwyg` and a link to the question's page.
`open: true` unfolds them.

A short FAQ on a home page:

```twig
<section class="faq">
    <h2>Questions</h2>
    {% include '@Faq/client/_questions.html.twig' with {questions: faq_featured(3)} %}
    <a href="{{ path('faq_index') }}">Every question</a>
</section>
<link rel="stylesheet" href="{{ asset('bundles/faq/css/faq.css') }}">
```

## The stylesheet

`bundles/faq/css/faq.css` styles the `.faq` block and reads the host's custom properties
when they are set:

| Property | Default | Use |
|---|---|---|
| `--faq-accent` | `#b5411f` | the accent: the buttons, the summary's marker, hovered links |
| `--faq-soft` | `#6b6b6b` | secondary text (eyebrow, leads) |
| `--faq-line` | `rgba(127,127,127,.25)` | borders |
| `--faq-surface` | `rgba(127,127,127,.06)` | the panels' background |
| `--faq-measure` | `52rem` | the block's maximum width |

## Templates an application overrides

Every template is in the `@Faq` namespace: `client/index.html.twig`,
`client/question.html.twig`, `client/_questions.html.twig`,
`admin/widget/pending_questions.html.twig`. An application overrides one the Symfony way,
in `templates/bundles/FaqBundle/<path>`.

## Translations

The domain `faq` (ICU message format), in English, French and German
(`translations/faq+intl-icu.{en,fr,de}.yaml`): the pages' texts (`index.*`, `question.*`),
the form (`ask.*`), the notification (`notify.asked`) and the back office's labels
(`admin.*`). The templates and the CRUDs refer to them as `@faq.<key>`.
