---
title: Questions and sections
order: 20
---

# Questions and sections

## `Question`

`Base\Faq\Entity\Question` extends the core's `Base\Entity\Thread` (table `faq_question`,
discriminator `faq_question`, repository `QuestionRepository`) and implements
`LinkableInterface`. What a question uses of the thread:

| Thread field | In the FAQ |
|---|---|
| `title` | the question (translated, as every thread's title) |
| `content` | the answer: EditorJS JSON or HTML |
| `excerpt` | for a visitor's question, the details they added (never shown on the site) |
| `slug` | the question's address, `/faq/{slug}` |
| `state` | `ThreadState::PUBLISH` online, `ThreadState::DRAFT` waiting in the back office |
| `publishedAt` | set the first time it is published (`publish()`) |
| `taxa` | holds its `Section` |
| likes | the readers it helped (the thread's own) |

Its own columns:

| Property | Column | Default | Meaning |
|---|---|---|---|
| `position` | `integer` | `0` | its place in its section, lower first |
| `featured` | `boolean` | `false` | on the home page's short FAQ (`faq_featured()`) |
| `askedBy` | `string(180)`, nullable | `null` | the e-mail a visitor left when asking it; `setAskedBy()` trims and lowercases it, an empty value is `null` |

Methods beyond the accessors:

- `getSection(): ?Section` - the first taxon of the question that is a `Section`.
  `setSection(?Section)` removes every `Section` from its taxa and adds the one given: a
  question is in one section at most (other kinds of taxa stay).
- `publish(bool $published = true): self` - `PUBLISH` or `DRAFT`, and `publishedAt` set to
  now if it was empty.
- `__toLink()` - the `faq_question` address of its slug; `__toString()` its title.
- `__iconizeStatic()` - `fa-solid fa-circle-question`.

## `Section`

`Base\Faq\Entity\Section` extends `Base\Entity\Thread\Taxon` (discriminator
`faq_section`): a translated label and description, an icon, a slug, a parent and children
- all the taxon's. It adds:

| Property | Column | Default | Meaning |
|---|---|---|---|
| `position` | `integer` | `0` | its place in the list, lower first |

`getPublishedQuestions(): list<Question>` returns the published questions among the
threads the section classifies, sorted by `position`, then by id. Its icon is
`fa-solid fa-folder-tree`.

## `Ask`

`Base\Faq\Entity\Ask` is the form's model, not an entity: nothing of it is stored as such.
Public properties and their constraints:

| Property | Type | Constraints |
|---|---|---|
| `question` | `?string` | `NotBlank`, `Length(min: 10, max: 300)` |
| `details` | `?string` | `Length(max: 2000)` |
| `email` | `?string` | `Email`, `Length(max: 180)` |
| `author` | `?App\Entity\User` | the signed-in user, when there is one |
| `spamScore` | `?int` | set by Akismet's callback |

As a `SpamProtectionInterface` it gives Akismet its author (`getSpamBlameable()`), the
question and details joined (`getSpamText()`), now (`getSpamDate()`), and receives the score
(`getSpamCallback(int $score)`). `isDoubtful()` is true when the score is at least the
core's `SpamScore::MAYBE_SPAM`.

## The repository

`Base\Faq\Repository\QuestionRepository` (a `ServiceEntityRepository`, aliased
`App\Repository\Faq\QuestionRepository`):

| Method | Returns |
|---|---|
| `findPublished(?Section $section = null, ?int $limit = null)` | the published questions, by `position` then id; of one section (through `taxa`) when given |
| `findFeatured(int $limit = 4)` | the published questions, featured ones first, then by `position` and id |
| `findOnePublished(string $slug)` | the published question of that slug, or `null` |
| `search(string $term, int $limit = 20)` | the published questions whose translated title or text contains the term (`LIKE %term%`) |
| `findSections()` | the top-level sections (no parent), by `position` then id, that have a published question or at least one child section |
| `countDrafts()` | the number of draft questions |
| `findDrafts(int $limit = 5)` | the oldest drafts first |

A sub-section with no published question is still kept by `findSections()` through its
parent; the page then shows only the children that have questions.
