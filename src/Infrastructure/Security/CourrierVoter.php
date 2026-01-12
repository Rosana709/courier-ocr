<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\Utilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour contr??ler l'acc??s aux courriers selon les r??gles m??tier:
 * - Admin: acc??s complet ?? tous les courriers
 * - Service: acc??s uniquement aux courriers o?? il est impliqu?? (exp??diteur, destinataire, ou en copie)
 */
class CourrierVoter extends Voter
{
    public const VIEW = 'courrier_view';
    public const EDIT = 'courrier_edit';
    public const DELETE = 'courrier_delete';
    public const ARCHIVE = 'courrier_archive';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::ARCHIVE])
            && $subject instanceof Courrier;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return false;
        }

        /** @var Courrier $courrier */
        $courrier = $subject;

        // L'admin a acc??s ?? tout
        if ($user->isAdmin()) {
            return true;
        }

        // Pour les utilisateurs de service
        if ($user->isServiceUser()) {
            return match ($attribute) {
                self::VIEW => $this->canView($courrier, $user),
                self::EDIT => $this->canEdit($courrier, $user),
                self::DELETE => false,
                self::ARCHIVE => $this->canView($courrier, $user) && $courrier->getStatut() !== Courrier::STATUT_ARCHIVE,
                default => false,
            };
        }

        return false;
    }

    private function canView(Courrier $courrier, Utilisateur $user): bool
    {
        $service = $user->getService();

        if ($service === null) {
            return false;
        }

        // Le service peut voir le courrier s'il est exp??diteur
        if ($courrier->getTypeExpediteur() === Courrier::ACTEUR_SERVICE
            && $courrier->getServiceExpediteur()
            && $courrier->getServiceExpediteur()->getId() === $service->getId()) {
            return true;
        }

        // Le service peut voir le courrier s'il est destinataire
        if ($courrier->getTypeDestinataire() === Courrier::ACTEUR_SERVICE
            && $courrier->getServiceDestinataire()
            && $courrier->getServiceDestinataire()->getId() === $service->getId()) {
            return true;
        }

        // Le service peut voir le courrier s'il est en copie
        foreach ($courrier->getDestinatairesCopie() as $destinataireCopie) {
            if ($destinataireCopie->getId() === $service->getId()) {
                return true;
            }
        }

        return false;
    }

    private function canEdit(Courrier $courrier, Utilisateur $user): bool
    {
        $service = $user->getService();

        if ($service === null) {
            return false;
        }

        // V??rifier d'abord si le service peut voir le courrier
        if (!$this->canView($courrier, $user)) {
            return false;
        }

        // Ne peut pas modifier un courrier clos ou archivé
        if (in_array($courrier->getStatut(), [Courrier::STATUT_CLOS, Courrier::STATUT_ARCHIVE])) {
            return false;
        }

        return true;
    }
}

