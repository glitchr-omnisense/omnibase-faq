<?php

namespace Base\Faq\Controller\Client;

use Base\Attributes\Attribute\Sitemap;
use Base\Faq\Entity\Ask;
use Base\Faq\Entity\Question;
use Base\Faq\Form\AskType;
use Base\Faq\Repository\QuestionRepository;
use Base\Faq\Service\AskNotifier;
use Base\Faq\Service\JsonLd;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The FAQ: every section with its questions folded (<details>), a search,
 * one question at its own address, and the form to ask another - which
 * lands as a draft in the back office once Akismet has read it. Both pages
 * carry schema.org's FAQPage (Service\JsonLd).
 */
class FaqController extends AbstractController
{
    public function __construct(
        private readonly QuestionRepository $questions,
        private readonly EntityManagerInterface $entityManager,
        private readonly AskNotifier $notifier,
        #[Autowire('%faq.ask_enabled%')] private readonly bool $askEnabled = true,
        #[Autowire('%faq.per_section%')] private readonly int $perSection = 20,
        #[Autowire('%faq.min_delay%')] private readonly int $minDelay = 4,
        // glitchr/ux-google, when installed: reCAPTCHA v3 on the form once google.recaptcha.enable is on.
        private readonly ?\Google\Service\GrService $recaptcha = null,
        private readonly ?JsonLd $jsonLd = null,
    ) {
    }

    #[Sitemap(priority: 0.6, changefreq: 'weekly')]
    #[Route('/faq', name: 'faq_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $term = trim((string) $request->query->get('q', ''));
        $form = $this->askEnabled ? $this->askForm() : null;

        if ($form && 'POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $response = $this->handleAsk($form, $request);
                if ($response) {
                    return $response;
                }
            }
        }

        return $this->render('@Faq/client/index.html.twig', [
            'sections' => $this->questions->findSections(),
            'orphans' => array_values(array_filter($this->questions->findPublished(), fn (Question $q) => null === $q->getSection())),
            'results' => mb_strlen($term) >= 3 ? $this->questions->search($term) : null,
            'term' => $term,
            'per_section' => $this->perSection,
            'ask_form' => $form?->createView(),
            // schema.org's FAQPage: every published question with its answer, whatever the search shows.
            'jsonld' => $this->jsonLd?->page($this->questions->findPublished(), $this->generateUrl('faq_index', [], UrlGeneratorInterface::ABSOLUTE_URL)),
        ], new Response(null, $form && $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/faq/{slug}', name: 'faq_question', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function question(string $slug): Response
    {
        $question = $this->questions->findOnePublished($slug) ?? throw $this->createNotFoundException();

        return $this->render('@Faq/client/question.html.twig', [
            'question' => $question,
            'siblings' => array_values(array_filter($question->getSection()?->getPublishedQuestions() ?? [], fn (Question $q) => $q !== $question)),
            'jsonld' => $this->jsonLd?->page([$question], $this->generateUrl('faq_question', ['slug' => $question->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)),
        ]);
    }

    private function askForm(): \Symfony\Component\Form\FormInterface
    {
        $ask = new Ask();
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User) {
            $ask->author = $user;
            $ask->email = $user->getEmail();
        }

        return $this->createForm(AskType::class, $ask, ['action' => $this->generateUrl('faq_index').'#demander', 'recaptcha' => (bool) $this->recaptcha?->isEnabled()]);
    }

    /** The draft Question written, the administrators told, the visitor thanked; null when the form has errors. */
    private function handleAsk(\Symfony\Component\Form\FormInterface $form, Request $request): ?Response
    {
        // A robot in the trap, or a doubtful text: thanked, and nothing kept.
        $trapped = '' !== trim((string) $form->get('url')->getData());
        $opened = (int) $form->get('opened')->getData();
        if ($this->minDelay > 0 && ($opened <= 0 || time() - $opened < $this->minDelay)) {
            $form->addError(new FormError('too_fast', '@faq.ask.error.too_fast'));
        }
        if (!$form->isValid()) {
            return null;
        }
        /** @var Ask $ask */
        $ask = $form->getData();
        if (!$trapped && !$ask->isDoubtful()) {
            $question = new Question($ask->author);
            $question->setTitle(mb_strimwidth(trim((string) $ask->question), 0, 255, '…'));
            $question->setExcerpt($ask->details ? trim($ask->details) : null);
            $question->setAskedBy($ask->email);
            $question->setState(\Base\Enum\ThreadState::DRAFT);
            $this->entityManager->persist($question);
            $this->entityManager->flush();
            $this->notifier->questionAsked($question);
        }
        $this->addFlash('faq', 'ask.flash.received');

        return $this->redirect($this->generateUrl('faq_index').'#demander');
    }
}
