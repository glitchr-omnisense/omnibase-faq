---
title: Installation
order: 10
---

# Installation

## Requirements

What `composer.json` requires: PHP 8.2 or later, `doctrine/orm` 2.15 or 3,
`omnisense/omnibase` 3.0 or 4.0, `omnibridge/symfony` 1.x, `omnibase/admin`, and
`symfony/config`, `dependency-injection`, `form`, `http-kernel` and `security-bundle` 6, 7
or 8.

The core provides what the bundle stands on: `Base\Entity\Thread` and
`Base\Entity\Thread\Taxon` (the questions and the sections), the forms' guard
(`Base\Service\FormGuard`), Akismet through `SpamProtectionInterface`, the `|wysiwyg` Twig
filter that renders an answer, the `#[Sitemap]` attribute, the notifications
(`Base\Entity\User\Notification`).

## The bundle

```bash
composer require omnibase/faq:dev-main
```

```php
// config/bundles.php
return [
    // ...
    Base\BaseBundle::class => ['all' => true],
    Base\Admin\AdminBundle::class => ['all' => true],
    Base\Faq\FaqBundle::class => ['all' => true],
];
```

`FaqBundle` extends the core's `AbstractBaseBundle`. Its `build()` maps the classes of
`src/Entity` to `App\Entity\Faq\*` and those of `src/Repository` to `App\Repository\Faq\*`
(`setMapping()`): an application that declares `App\Entity\Faq\Question` extending
`Base\Faq\Entity\Question` gets its own class used in place of the bundle's; otherwise the
name is an alias of the bundle's class. `getPath()` is the package's root, so its
`templates/` is the `@Faq` namespace and its `public/` is installed as `bundles/faq/`.

The extension (`Base\Faq\DependencyInjection\FaqExtension`) loads `config/services.php` and
turns the `faq:` tree into parameters ([Configuration reference](configuration.md)).

## The routes

The public pages are attribute routes in `src/Controller/Client`:

```yaml
# config/routes.yaml
faq_controller:
    resource: "@FaqBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

| Route | Path | Methods | Controller |
|---|---|---|---|
| `faq_index` | `/faq` | GET, POST | `FaqController::index()` - in the sitemap (priority 0.6, weekly) |
| `faq_question` | `/faq/{slug}` (`slug`: `[a-z0-9\-]+`) | any | `FaqController::question()` |

The back office's screens need no import of their own: omnibase/admin's `base_admin` loader
generates the routes of every registered CRUD ([The back office](back-office.md)).

## The schema

The bundle maps two entities, `faq_question` (joined to the core's thread table) and the
`Section` discriminator entry of the taxon table. Generate and run a migration:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

## The public files

```bash
bin/console assets:install
```

copies `public/css/faq.css` to `public/bundles/faq/css/faq.css`, which both pages link.

## What the host provides

The two pages extend `layout1.html.twig` and fill its blocks `title`, `description`,
`stylesheets` (calling `parent()`) and `content`. The site's layout must define them.

An answer is printed through the core's `|wysiwyg` filter (EditorJS JSON or HTML).
