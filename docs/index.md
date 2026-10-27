# omnibase/faq

A FAQ for the sites built on [omnisense/omnibase](https://github.com/glitchr-omnisense/omnibase):
the questions visitors ask most, sorted into sections, answered in the back office, and a
form for the visitor whose question is missing. It is a Symfony bundle (`Base\Faq\FaqBundle`)
on the core 3 or 4, through omnibridge/symfony.

It adds no storage of its own kind: a `Question` **is** an omnibase `Thread` (a JOINED
subclass, table `faq_question`) - the question is the thread's title, the answer its text
(EditorJS or HTML), its state says whether it is online, its likes count the readers it
helped. A `Section` is an omnibase `Taxon`, so sections form a tree and a section may hold
sub-sections. What a visitor types in the "A question missing?" form is an `Ask`, a form
model (not an entity) that implements the core's `SpamProtectionInterface`: Akismet reads it,
and a question that passes lands as a draft `Question` in the back office.

What it gives a site:

- `/faq`: every section with its questions folded (`<details>`), a search, the form to ask a
  question; `/faq/{slug}`: one question at its own address with the others of its section.
  Both pages carry schema.org's `FAQPage` in JSON-LD.
- Three Twig functions for a host page (`faq_questions()`, `faq_featured()`,
  `faq_sections()`) and a partial that draws questions folded.
- With omnibase/admin: the `Question` and `Section` screens, open to `ROLE_ADMIN`, and the
  dashboard tile `faq_pending_questions`; every administrator is told in the app's bell
  when a visitor asks a question.

Use it when a site needs a handful to a few hundred questions with answers written by its
editors. It is not a help desk: there is no conversation with the visitor, who is only told
the question arrived (and leaves an e-mail address the editor may write to).

## How this manual is organised

- [Installation](installation.md): requirements, the bundle, the routes, the schema, the
  layout the templates extend.
- [Questions and sections](concepts.md): the entities and their fields, the form model, the
  repository's queries.
- [The pages and the form](usage-pages.md): `/faq`, the search, a question's page, asking a
  question (the guard, Akismet, what is kept), the JSON-LD.
- [In a host page](usage-twig.md): the Twig functions, the partial, the stylesheet and its
  custom properties.
- [The back office](back-office.md): the screens, who may write, the dashboard tile, the
  notification.
- [Configuration reference](configuration.md): `faq:`, the parameters, the services.
- [Testing](testing.md): the suite and what each test checks.
