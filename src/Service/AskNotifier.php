<?php

namespace Base\Faq\Service;

use App\Entity\User;
use Base\Entity\User\Notification;
use Base\Faq\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** The administrators told of a question asked: the bell, and an e-mail, with a link to the draft. */
final class AskNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RoleHierarchyInterface $roles,
        private readonly UrlGeneratorInterface $urls,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function questionAsked(Question $question): void
    {
        $title = $this->translator->trans('notify.asked', ['question' => mb_strimwidth((string) $question->getTitle(), 0, 80, '…')], 'faq');
        $url = $this->urls->generate('admin_crud_questions_edit', ['entityId' => $question->getId()]);
        foreach ($this->administrators() as $admin) {
            $notification = new Notification($title);
            $notification->setUser($admin);
            $notification->setTitle($title);
            $notification->setSubject($title);
            $notification->setContent(mb_strimwidth((string) $question->getExcerpt(), 0, 240, '…') ?: $title);
            $notification->setUrl($url);
            // 'notify': the in-app channel (the bell); an e-mail would need a template of its own.
            $notification->send('notify');
        }
    }

    /** @return list<User> */
    private function administrators(): array
    {
        $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles <> :member')->setParameter('member', 'ROLE_USER')
            ->getQuery()->getResult();

        return array_values(array_filter($users, fn (User $user) => \in_array('ROLE_ADMIN', $this->roles->getReachableRoleNames($user->getRoles()), true)));
    }
}
