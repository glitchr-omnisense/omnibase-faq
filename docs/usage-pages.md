---
title: The pages and the form
order: 30
---

# The pages and the form

## `/faq`

`FaqController::index()` renders `@Faq/client/index.html.twig` with:

| Variable | Content |
|---|---|
| `sections` | `findSections()`: the top-level sections, each drawn with its first `per_section` published questions, then each child section that has questions under an h3 |
| `orphans` | the published questions in no section, under "Other questions" (not cut to `per_section`) |
| `results` | the search's results when `?q=` holds 3 characters or more, `null` otherwise |
| `term` | the search term, trimmed |
| `per_section` | `faq.per_section` |
| `ask_form` | the form's view, or `null` when `faq.ask_enabled` is `false` |
| `jsonld` | the `FAQPage` of every published question (see below) |

A table of contents of the sections (anchors on each section's slug) shows when there are
two sections or more. With no section and no orphan question, the page says "No question
published yet.".

## The search

`GET /faq?q=term`: with 3 characters or more, `QuestionRepository::search()` looks for the
term in the translated titles and texts of the published questions, 20 at most. The results
come first on the page, unfolded (`open`), under "N answers for "term"". Shorter terms are
ignored.

## A question's page

`/faq/{slug}` renders `@Faq/client/question.html.twig`: the question as h1, its section as a
breadcrumb, the answer, then the other published questions of its section (`siblings`). A
slug that matches no published question is a 404. The page's description block is the
question's excerpt.

## Asking a question

The form (`Base\Faq\Form\AskType`, translation domain `faq`) has three fields: `question`
(300 characters at most), `details` (optional, 2000) and `email` (optional, "to tell you once
the answer is online"). A signed-in visitor's e-mail is filled in and the user kept as the
author. The form posts to `/faq#demander`.

### The guard

The form is guarded as the core guards a form (its `guard` option; the core's manual,
"The forms' guard"): `FaqController` passes `action: ask`, `min_delay: faq.min_delay`, and
`challenge: false` when Google reCAPTCHA v3 is on. The guard keeps a trap field, the time
between the form being opened and sent, the core's address lists (`base.guard.reputation`),
and a captcha when the site has omnisense/omnishield (the core asks it after a number of
refused tries, `base.guard.captcha_after`). A robot is refused on the form: the page is
rendered again with status 422.

With glitchr/ux-google installed and `google.recaptcha.enable` on
(`Google\Service\GrService::isEnabled()`), the form gets a `captcha` field
(`ReCaptchaV3Type`) and the guard asks no captcha of its own.

With a core that has no `Base\Service\FormGuard` (before the forms' guard), `AskType` adds
its own trap (`url`, hidden) and a timestamp (`opened`); the controller refuses a form sent
less than `min_delay` seconds after it was opened ("A little fast!"), and thanks a filled
trap without keeping anything.

### What is kept

A valid form that is not trapped and that Akismet does not doubt (`Ask::isDoubtful()`)
becomes a `Question`:

- the title is the question, trimmed and cut to 255 characters;
- the excerpt is the details;
- `askedBy` is the e-mail;
- the state is `DRAFT`, the author the signed-in user if any.

It is persisted, and `AskNotifier::questionAsked()` tells the administrators
([The back office](back-office.md#the-notification)). In every case - kept or not - the
visitor is redirected to `/faq#demander` with the flash "Thank you! Your question arrived."
(flash bag `faq`): a doubtful text is thanked and dropped, never queued for moderation.

## JSON-LD

Both pages print a `<script type="application/ld+json">` from `Base\Faq\Service\JsonLd`:
`/faq` with every published question (whatever the search shows), a question's page with
that question only, each with the page's absolute URL.

`JsonLd::page(iterable $questions, ?string $url = null): ?array` returns
`{"@context": "https://schema.org", "@type": "FAQPage", "url": ..., "mainEntity": [...]}`,
each entry `{"@type": "Question", "name": <title>, "acceptedAnswer": {"@type": "Answer",
"text": <answer>}}`. A question listed twice (same slug) is there once; a question whose
title or answer is empty is left out; with no entry left, `page()` returns `null` and no
script is printed.

`JsonLd::answer(?string $content): string` turns an answer into what search engines read
in one:

- an EditorJS document (JSON starting with `{` and holding `blocks`) becomes HTML: a `list`
  block a `<ul>` or `<ol>` (style `ordered`), a `header` block an `<h3>`, any other block a
  `<p>` of its `text`; empty blocks are dropped;
- `<script>`, `<style>`, `<iframe>` and `<form>` go with their content;
- only `<p> <br> <ul> <ol> <li> <a> <strong> <b> <em> <i> <h2> <h3> <h4>` stay, without
  attributes, except a link's `href` when it is `http(s)://`, `mailto:`, `tel:`, a path or
  an anchor;
- white space is collapsed; tags around nothing give `''`.

Run in omnisense/omnibase's harness image, with this checkout's classes on the autoloader:

```php
use Base\Faq\Service\JsonLd;

echo JsonLd::answer(json_encode(['blocks' => [
    ['type' => 'paragraph', 'data' => ['text' => 'Yes, in your own class.']],
    ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['Print it', 'Project it']]],
]])), "\n";

echo JsonLd::answer('<p class="lead" onclick="x()">See <a href="/terms" target="_blank">the terms</a>.</p><script>alert(1)</script>'), "\n";

var_dump(JsonLd::answer('<p> </p>'));
```

```
<p>Yes, in your own class.</p><ul><li>Print it</li><li>Project it</li></ul>
<p>See <a href="/terms">the terms</a>.</p>
string(0) ""
```

`JsonLd` is autowired in the container; the controller takes it as an optional argument and
prints no JSON-LD without it.
