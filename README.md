# FAQ

The questions a site's visitors ask most, by section, for
[omnibase](https://github.com/glitchr-studio/omnibase) sites: a teacher's
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
    min_delay: 4           # seconds a human needs to fill the form
```

Then `bin/console doctrine:migrations:diff && bin/console doctrine:migrations:migrate`
and `bin/console assets:install` (`public/css/faq.css`).

## What the host provides

The templates extend `layout1.html.twig` (`title`, `description`, `content`,
`stylesheets`, `javascripts`). `faq_questions(section = null, limit)` returns
the published questions for a home page; `{% include '@Faq/client/_questions.html.twig' with {questions: ...} %}`
draws them as `<details>`.

The back office gets `Question` and `Section` CRUDs and the dashboard widget
`faq_pending_questions` (the drafts waiting for an answer).
