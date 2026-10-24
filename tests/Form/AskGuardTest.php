<?php

namespace Base\Faq\Tests\Form;

use Base\Faq\Entity\Ask;
use Base\Faq\Form\AskType;
use Base\Service\FormGuard;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The form to ask a question is guarded as glitchr/omnibase guards a form (option `guard`, action
 * "ask"), in place of its own `url` trap and `opened` time: a filled trap, a question sent faster than
 * its delay and a missing captcha token are refused on the form; a question asked as a person asks it
 * goes through. Without a captcha (no glitchr/omnishield, or ux-google's reCAPTCHA in its place:
 * `challenge: false`), the trap and the time alone. Run by a host application's PHPUnit, its test
 * environment's captcha being omnishield's "fixed" gateway (token omnishield-fixed-token).
 */
final class AskGuardTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $_SERVER['KERNEL_CLASS'] ??= $_ENV['KERNEL_CLASS'] ?? 'App\\Kernel';
        if (!class_exists($_SERVER['KERNEL_CLASS'])) {
            self::markTestSkipped('Needs a host application (its kernel).');
        }
        if (!class_exists(FormGuard::class)) {
            self::markTestSkipped('Needs a glitchr/omnibase with the forms\' guard.');
        }
        self::bootKernel();
        static::getContainer()->get('request_stack')->push(Request::create('https://localhost/faq', 'POST', server: ['REMOTE_ADDR' => '203.0.113.9']));
    }

    /** @param array<string, mixed> $guard */
    private function form(array $guard = []): FormInterface
    {
        $options = ['csrf_protection' => false];
        if ($guard) {
            $options['guard'] = $guard + ['action' => 'ask'];
        }

        return static::getContainer()->get('form.factory')->create(AskType::class, new Ask(), $options);
    }

    /** @param array<string, ?string> $overrides */
    private function send(FormInterface $form, array $overrides = []): FormInterface
    {
        $data = [
            'question' => 'Quels sont les délais de fabrication ?',
            'details' => 'Pour une enseigne drapeau lumineuse.',
            'email' => 'camille@example.org',
            'guard_website' => '',
            'guard_opened' => static::getContainer()->get(FormGuard::class)->stamp(time() - 10),
        ];
        if ($form->has('guard_captcha')) {
            $data['guard_captcha'] = 'omnishield-fixed-token';
        }
        $form->submit(array_filter($overrides + $data, static fn ($value) => null !== $value));

        return $form;
    }

    /** @return list<string> what refused the form: the guard's reasons, or the field with an error */
    private function refusals(FormInterface $form): array
    {
        $found = [];
        foreach ($form->getErrors(true) as $error) {
            $found[] = \is_string($error->getCause()) ? $error->getCause() : (string) $error->getOrigin()?->getName();
        }

        return $found;
    }

    public function testTheFormIsGuardedInPlaceOfItsOwnTrap(): void
    {
        $form = $this->form();
        self::assertSame('ask', $form->getConfig()->getOption('guard')['action']);
        self::assertFalse($form->has('url'), 'the form\'s own trap is gone');
        self::assertFalse($form->has('opened'), 'and its own time');
        self::assertTrue($form->has(FormGuard::TRAP_FIELD));
        self::assertTrue($form->has(FormGuard::STAMP_FIELD));
    }

    public function testAQuestionAskedAsAPersonAsksItGoesThrough(): void
    {
        $form = $this->send($this->form());
        self::assertTrue($form->isValid(), implode(', ', $this->refusals($form)));
    }

    public function testAFilledTrapIsRefused(): void
    {
        $form = $this->send($this->form(), ['guard_website' => 'https://spam.example']);
        self::assertFalse($form->isValid());
        self::assertContains(FormGuard::TRAPPED, $this->refusals($form));
    }

    public function testAQuestionSentTooFastIsRefused(): void
    {
        $form = $this->send($this->form(['min_delay' => 4]), ['guard_opened' => static::getContainer()->get(FormGuard::class)->stamp(time() - 1)]);
        self::assertFalse($form->isValid());
        self::assertContains(FormGuard::TOO_FAST, $this->refusals($form));
    }

    public function testAQuestionWithoutTheCaptchasTokenIsRefused(): void
    {
        $form = $this->form();
        if (!$form->has('guard_captcha')) {
            self::markTestSkipped('The host application has no captcha (glitchr/omnishield).');
        }
        // Asked from the first question only where the host says so (base.guard.captcha_after: 0); by default
        // after three refused tries (the omnibase harness), and a first question is then not asked it.
        $guard = static::getContainer()->get(FormGuard::class);
        $request = static::getContainer()->get('request_stack')->getCurrentRequest();
        if (!$guard->captchaAsked($form->getName(), (int) ($form->getConfig()->getAttribute('guard_captcha_after') ?? $guard->captchaAfter()), $request)) {
            self::markTestSkipped('The host asks the captcha after refused tries only (base.guard.captcha_after).');
        }
        $this->send($form, ['guard_captcha' => '']);
        self::assertFalse($form->isValid());
        self::assertGreaterThan(0, $form->get('guard_captcha')->getErrors()->count(), 'refused on the captcha');
    }

    public function testWithoutACaptchaTheTrapAndTheTimeAlone(): void
    {
        $form = $this->send($this->form(['challenge' => false]));
        self::assertFalse($form->has('guard_captcha'));
        self::assertTrue($form->isValid(), implode(', ', $this->refusals($form)));

        self::assertFalse($this->send($this->form(['challenge' => false]), ['guard_website' => 'x'])->isValid(), 'the trap still holds');
        self::assertFalse($this->send($this->form(['challenge' => false, 'min_delay' => 4]), ['guard_opened' => static::getContainer()->get(FormGuard::class)->stamp(time())])->isValid(), 'the time still holds');
    }
}
