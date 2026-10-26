# FAQ

The questions a site's visitors ask most, by section, for
[omnibase](https://github.com/glitchr-omnisense/omnibase) sites: a teacher's
("May I use your cards in my class?"), a game's, a shop's.

A `Question` **is** an omnibase `Thread` (a JOINED subclass): the question is
its title, the answer its text (EditorJS or HTML), the publish state says
whether it is online, likes count the readers it helped. A `Section` is a
`Taxon` (a tree, so a section may hold sub-sections). An `Ask` is what a
visitor types in the "Il manque une question ?" form: it implements omnibase's
`SpamProtectionInterface`, so Akismet reads it before it lands - as a draft
Question - in the back office, where it is answered and published.

## Install

```bash
composer require omnibase/faq:dev-main
```

```php
// config/bundles.php
Base\Faq\FaqBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml
faq_controller:
    resource: "@FaqBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

```yaml
# config/packages/faq.yaml (every key optional)
faq:
    ask_enabled: true      # the form to ask a question
    per_section: 20        # questions shown per section
    min_delay: 4           # seconds a human needs to fill the form (omnisense/omnibase's forms' guard)
```

Then `bin/console doctrine:migrations:diff && bin/console doctrine:migrations:migrate`
and `bin/console assets:install` (`public/css/faq.css`).

The form to ask a question is guarded as omnisense/omnibase guards a form - its
option `guard` (`action: ask`; omnisense/omnibase's `docs/20-architecture/guard.md`):
a trap, the time it takes (`min_delay`), the lists of `base.guard.reputation`,
the captcha when the site has glitchr/omnishield (none where ux-google's
reCAPTCHA is on). A robot is refused on the form; a text Akismet doubts is
thanked and not kept.

## JSON-LD

`/faq` carries schema.org's `FAQPage` with every published question and its
answer, and a question's own page the same for that question
(`Service\JsonLd::page()`, in a `<script type="application/ld+json">`). An
answer is kept to what search engines read in one - paragraphs, lists, links,
emphasis -, an EditorJS answer is turned into paragraphs and lists, and a
question without an answer is left out.

## Tests

```bash
vendor/bin/phpunit                                                  # checked out on its own
php vendor/bin/phpunit -c vendor/omnibase/faq/phpunit.xml.dist      # from an application that requires it
```

## What the host provides

The templates extend `layout1.html.twig` (`title`, `description`, `content`,
`stylesheets`, `javascripts`). `faq_questions(section = null, limit)` returns
the published questions for a home page; `{% include '@Faq/client/_questions.html.twig' with {questions: ...} %}`
draws them as `<details>`.

The back office gets `Question` and `Section` CRUDs and the dashboard widget
`faq_pending_questions` (the drafts waiting for an answer).

## License

MIT since 2026-10-09; earlier versions remain published under LGPL-3.0-or-later.
