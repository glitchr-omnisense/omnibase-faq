---
title: Configuration reference
order: 40
---

# Configuration reference

## `faq:`

`Base\Faq\DependencyInjection\FaqConfiguration`, dumped with Symfony's
`YamlReferenceDumper` in omnisense/omnibase's harness image:

```yaml
faq:

    # The form to ask a question that is missing.
    ask_enabled:          true

    # Questions shown per section.
    per_section:          20

    # Seconds between the form being opened and sent: faster is a robot.
    min_delay:            4
```

| Option | Type | Default | Effect |
|---|---|---|---|
| `ask_enabled` | bool | `true` | `false`: `/faq` has no form, and a POST to it only renders the page |
| `per_section` | int, 1 or more | `20` | the questions drawn per section (and per child section) on `/faq`; the questions in no section are not cut |
| `min_delay` | int, 0 or more | `4` | given to the forms' guard as `min_delay`; with a core older than the guard, the controller's own check (`0` turns it off) |

Every key is optional; the file can be left out.

## Parameters

The extension sets one container parameter per option (the core's
`AbstractBaseExtension::setConfiguration()`): `faq.ask_enabled`, `faq.per_section`,
`faq.min_delay`. `FaqController` reads them with `#[Autowire('%faq.…%')]`.

## Services

`config/services.php` autowires and autoconfigures every class of `src/`, except
`DependencyInjection/`, `Entity/`, `Controller/Admin/`, `Admin/` and the bundle class. The
controllers of `Controller/Client/` are tagged `controller.service_arguments`. When
omnibase/admin is installed, `Controller/Admin/` (tagged `controller.service_arguments`)
and `Admin/` (the widget type) are loaded too.

| Service | Role |
|---|---|
| `Base\Faq\Controller\Client\FaqController` | the two pages |
| `Base\Faq\Repository\QuestionRepository` | the queries ([Questions and sections](concepts.md#the-repository)) |
| `Base\Faq\Form\AskType` | the form |
| `Base\Faq\Service\JsonLd` | the `FAQPage` |
| `Base\Faq\Service\AskNotifier` | the administrators' notification |
| `Base\Faq\Twig\FaqExtension` | the Twig functions |
| `Base\Faq\Admin\Widget\PendingQuestionsWidgetType` | the dashboard tile (with omnibase/admin) |

Optional collaborators of the controller: `Google\Service\GrService` (glitchr/ux-google),
used when present.
