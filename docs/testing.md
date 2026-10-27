---
title: Testing
order: 60
---

# Testing

## The suite

```bash
vendor/bin/phpunit                                                  # checked out on its own
php vendor/bin/phpunit -c vendor/omnibase/faq/phpunit.xml.dist      # from an application that requires it
```

`tests/bootstrap.php` registers the test namespace and this checkout's classes on the
autoloader it finds (its own `vendor/`, the current directory's, or the application's), and
the classes of the omnibase/* packages it requires or suggests, read beside the checkout.

In omnisense/omnibase's harness, with this checkout mounted over the package:

```bash
docker run --rm --network none -v "$PWD":/srv/demo/vendor/omnibase/faq:ro omnisense/omnibase-harness test faq
```

```
..........S.......                                                18 / 18 (100%)

OK, but incomplete, skipped, or risky tests!
Tests: 18, Assertions: 38, Skipped: 1.
```

## What each test checks

- `tests/Form/AskGuardTest.php` - the form is guarded by the core's guard instead of its
  own trap; a question asked as a person asks it goes through; a filled trap, a form sent
  too fast and a form without the captcha's token are refused; without a captcha the trap
  and the time alone. The captcha test skips, saying so, where the host asks the captcha
  only after refused tries (`base.guard.captcha_after`, 3 by default in the harness).
- `tests/Service/JsonLdTest.php` - the questions are a `FAQPage`; a question without an
  answer is left out and none gives no page; a question listed twice is there once; an
  answer keeps paragraphs, lists, links and emphasis only; an EditorJS answer becomes
  paragraphs and lists; an empty answer.
- `tests/Controller/CrudRequiredFieldsTest.php` - on omnibase/admin's `CrudFieldsTestCase`:
  only what a record needs is required, an optional field takes an empty value, one field
  per property, the slug follows the title.

`tests/Fixtures/TestQuestion.php` is a `Question` the tests build without a database.

## In a site's tests

A functional test posts the form as a visitor would: open `/faq`, wait at least
`faq.min_delay` seconds (or set it to `0` in the test environment), submit with the
guard's fields as rendered. The question is then a draft `Question` with the e-mail in
`askedBy`, and each administrator has a `Notification`.
