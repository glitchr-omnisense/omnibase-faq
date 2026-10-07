<?php

namespace Base\Faq\Form;

use Base\Faq\Entity\Ask;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * "Il manque une question ?": the question, a few details, an e-mail to be
 * told once it is answered. Guarded as glitchr/omnibase guards a form (its
 * option `guard`, action "ask"): a trap, the time it takes, the lists, the
 * captcha when the site has glitchr/omniguard; Akismet reads what is written
 * (the data_class implements SpamProtectionInterface); reCAPTCHA v3 in place
 * of the guard's captcha when the host turned it on (glitchr/ux-google).
 */
final class AskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, ['label' => '@faq.ask.form.question', 'attr' => ['maxlength' => 300]])
            ->add('details', TextareaType::class, ['label' => '@faq.ask.form.details', 'required' => false, 'attr' => ['rows' => 4, 'maxlength' => 2000]])
            ->add('email', EmailType::class, ['label' => '@faq.ask.form.email', 'help' => '@faq.ask.form.email_help', 'required' => false, 'attr' => ['autocomplete' => 'email', 'maxlength' => 180]]);

        if ($options['recaptcha'] && class_exists(\Google\Form\Type\ReCaptchaV3Type::class)) {
            $builder->add('captcha', \Google\Form\Type\ReCaptchaV3Type::class, ['mapped' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Ask::class, 'translation_domain' => 'faq', 'recaptcha' => false, 'guard' => ['action' => 'ask']]);
        $resolver->setAllowedTypes('recaptcha', 'bool');
    }
}
