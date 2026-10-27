---
title: The back office
order: 38
---

# The back office

The screens are loaded only when omnibase/admin is installed
(`Base\Admin\Controller\AbstractCrudController` exists, `config/services.php`).

## The screens

| CRUD | Entity | Slug | First route |
|---|---|---|---|
| `Base\Faq\Controller\Admin\Crud\QuestionCrudController` | `Question` | `questions` | `admin_crud_questions_index` (`/admin/questions`) |
| `Base\Faq\Controller\Admin\Crud\SectionCrudController` | `Section` | `sections` | `admin_crud_sections_index` (`/admin/sections`) |

omnibase/admin's loader gives each the usual routes (`_new`, `_edit`, `_detail`, `_delete`,
`_batchDelete`, `_toggle`...).

`QuestionCrudController`'s fields, in order:

| Field | Type | Notes |
|---|---|---|
| `id` | `IdField` | index only |
| `title` | `TextField` | the question |
| `state` | `StateField` | draft or published |
| `slug` | `SlugField` | follows the title as it is typed; not on the index |
| `section` | `SelectField` of `Section` | optional |
| `position` | `IntegerField` | |
| `featured` | `BooleanField` | |
| `excerpt` | `TextareaField` | optional: what the visitor added to the question; not shown on the site |
| `content` | `EditorField` | the answer, with collaborative autosave and live editing (`collab_autosave`, `collab_live`) |
| `askedBy` | `TextField` | optional: the visitor's e-mail |

Filters: `state` and `featured`. A question created in the back office has the signed-in
user as an owner (`createEntity()`).

`SectionCrudController` extends omnibase/admin's
`Base\Admin\Controller\Crud\Thread\TaxonCrudController` (the label, description, icon,
parent... of a taxon) and adds `position`.

## Who may write

omnibase/admin requires the super-administrator for any action that writes, by default.
Both screens open `NEW`, `EDIT`, `DELETE`, `BATCH_DELETE` and the three save actions to
`ROLE_ADMIN` (`Base\Faq\Controller\Admin\OpenToAdminsTrait::openToAdmins()`): a site's
editors answer the questions.

## The dashboard tile

`Base\Faq\Admin\Widget\PendingQuestionsWidgetType`, named `faq_pending_questions`
(template `@Faq/admin/widget/pending_questions.html.twig`): the number of draft questions,
which links to the questions' list filtered on drafts, and the five oldest drafts, each
linking to its edit page. With none: "Everything is answered.". A dashboard adds it as any
widget type of omnibase/admin.

## The notification

When a visitor's question is kept, `Base\Faq\Service\AskNotifier::questionAsked()` writes a
`Base\Entity\User\Notification` for every user whose reachable roles include `ROLE_ADMIN`
(through the role hierarchy): title and subject "A question asked: "<the first 80
characters>"", the content the details (240 characters) or the title, the URL the question's
edit page (`admin_crud_questions_edit`). It is sent on the `notify` channel, the app's bell;
no e-mail is sent.
