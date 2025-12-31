<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\CreateCourrierDTO;
use App\Application\DTO\UpdateCourrierDTO;
use App\Application\UseCase\Courrier\CreateCourrierUseCase;
use App\Application\UseCase\Courrier\DeleteCourrierUseCase;
use App\Application\UseCase\Courrier\GetCourrierUseCase;
use App\Application\UseCase\Courrier\ListCourriersUseCase;
use App\Application\UseCase\Courrier\UpdateCourrierUseCase;
use App\Application\UseCase\Courrier\AccuseReceptionUseCase;
use App\Application\UseCase\PieceJointe\ListPiecesJointesUseCase;
use App\Domain\Entity\Courrier;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\PersonneExterneRepositoryInterface;
use App\Domain\Repository\PieceJointeRepositoryInterface;
use App\Domain\Entity\PieceJointe;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use App\Infrastructure\Service\OcrIntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courriers')]
#[IsGranted('ROLE_USER')]
class CourrierController extends AbstractController
{
    public function __construct(
        private readonly CreateCourrierUseCase $createCourrierUseCase,
        private readonly GetCourrierUseCase $getCourrierUseCase,
        private readonly UpdateCourrierUseCase $updateCourrierUseCase,
        private readonly DeleteCourrierUseCase $deleteCourrierUseCase,
        private readonly ListCourriersUseCase $listCourriersUseCase,
        private readonly AccuseReceptionUseCase $accuseReceptionUseCase,
        private readonly ListPiecesJointesUseCase $listPiecesJointesUseCase,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService,
        private readonly OcrIntegrationService $ocrIntegrationService,
        private readonly PieceJointeRepositoryInterface $pieceJointeRepository
    ) {
    }

    #[Route('/', name: 'courrier_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'tous',
            'pageTitle' => 'Tous les Courriers',
        ]);
    }

    #[Route('/entrants', name: 'courrier_entrants', methods: ['GET'])]
    public function entrants(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
            $courriers = array_filter($courriers, fn($c) => $c->getType() === Courrier::TYPE_ENTRANT);
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeEntrantsByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'entrants',
            'pageTitle' => 'Courriers Entrants',
        ]);
    }

    #[Route('/sortants', name: 'courrier_sortants', methods: ['GET'])]
    public function sortants(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
            $courriers = array_filter($courriers, fn($c) => $c->getType() === Courrier::TYPE_SORTANT);
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeSortantsByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'sortants',
            'pageTitle' => 'Courriers Sortants',
        ]);
    }

    #[Route('/archives', name: 'courrier_archives', methods: ['GET'])]
    public function archives(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeArchivedAll();
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeArchivedByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'archives',
            'pageTitle' => 'Courriers Archivés',
        ]);
    }

    #[Route('/create', name: 'courrier_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        $userService = $user?->getService();

        if (!$userService) {
            $this->addFlash('error', "Votre compte n'est associ?? ?? aucun service.");
            return $this->redirectToRoute('courrier_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $type = $request->request->get('type', '');

                // Exp??diteur : verrouill?? sur service connect?? pour SORTANT, s??lectionnable pour ENTRANT
                $typeExpediteur = $request->request->get('typeExpediteur', Courrier::ACTEUR_SERVICE);
                $serviceExpediteurId = $request->request->get('serviceExpediteurId');
                $personneExterneExpediteurId = $request->request->get('personneExterneExpediteurId');
                // Gestion "Autre" pour l'expéditeur
                if ($personneExterneExpediteurId === 'NEW_EXTERNAL') {
                    $nom = $request->request->get('nomNouvellePersonne');
                    $typePers = $request->request->get('typeNouvellePersonne', \App\Domain\Entity\PersonneExterne::TYPE_ORGANISME);
                    if ($nom) {
                        $nouvellePersonne = new \App\Domain\Entity\PersonneExterne($nom, $typePers);
                        $this->personneExterneRepository->save($nouvellePersonne);
                        $personneExterneExpediteurId = $nouvellePersonne->getId();
                    }
                }

                // Gestion "Autre" pour le destinataire
                $personneExterneDestinataireId = $request->request->get('personneExterneDestinataireId');
                if ($personneExterneDestinataireId === 'NEW_EXTERNAL') {
                    $nom = $request->request->get('nomNouvellePersonne'); // On peut utiliser le même champ ou un autre
                    $typePers = $request->request->get('typeNouvellePersonne', \App\Domain\Entity\PersonneExterne::TYPE_ORGANISME);
                    if ($nom) {
                        $nouvellePersonne = new \App\Domain\Entity\PersonneExterne($nom, $typePers);
                        $this->personneExterneRepository->save($nouvellePersonne);
                        $personneExterneDestinataireId = $nouvellePersonne->getId();
                    }
                }

                if ($type === Courrier::TYPE_SORTANT) {
                    $typeExpediteur = Courrier::ACTEUR_SERVICE;
                    $serviceExpediteurId = $userService->getId();
                    $personneExterneExpediteurId = null;
                }

                $dto = new CreateCourrierDTO(
                    type: $type,
                    objet: $request->request->get('objet', ''),
                    contenu: $request->request->get('contenu'),
                    dateCourrier: $request->request->get('dateCourrier', date('Y-m-d')),
                    priorite: $request->request->get('priorite', Courrier::PRIORITE_NORMALE),
                    typeExpediteur: $typeExpediteur,
                    serviceExpediteurId: $serviceExpediteurId,
                    personneExterneExpediteurId: $personneExterneExpediteurId,
                    typeDestinataire: $request->request->get('typeDestinataire', ''),
                    serviceDestinataireId: $request->request->get('serviceDestinataireId'),
                    personneExterneDestinataireId: $request->request->get('personneExterneDestinataireId'),
                    destinatairesCopieIds: $request->request->all('destinatairesCopieIds'),
                    courrierParentId: $request->request->get('courrierParentId'),
                    notes: $request->request->get('notes'),
                    numeroReference: $request->request->get('numeroReference')
                );

                $courrier = $this->createCourrierUseCase->execute($dto, $user->getId());

                $this->addFlash('success', 'Courrier cr???? avec succ??s.');
                return $this->redirectToRoute('courrier_show', ['id' => $courrier->getId()]);

            } catch (DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $services = $this->serviceRepository->findAll();
        $personnesExternes = $this->personneExterneRepository->findAll();

        $type = $request->query->get('type') ?? $request->request->get('type');
        $template = 'courrier/create.html.twig';
        
        if ($type === Courrier::TYPE_ENTRANT || $request->query->has('ocr_data')) {
            $template = 'courrier/create_entrant.html.twig';
        } elseif ($type === Courrier::TYPE_SORTANT) {
            $template = 'courrier/create_sortant.html.twig';
        }

        return $this->render($template, [
            'services' => $services,
            'personnesExternes' => $personnesExternes,
            'priorites' => Courrier::getValidPriorites(),
            'types' => Courrier::getValidTypes(),
            'userService' => $userService,
        ]);
    }

    #[Route('/{id}', name: 'courrier_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        try {
            $courrier = $this->getCourrierUseCase->execute($id);

            $this->denyAccessUnlessGranted('courrier_view', $courrier);

            $piecesJointes = $this->listPiecesJointesUseCase->execute($id);

            return $this->render('courrier/show.html.twig', [
                'courrier' => $courrier,
                'piecesJointes' => $piecesJointes,
            ]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_index');
        }
    }

    #[Route('/{id}/edit', name: 'courrier_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        try {
            $courrier = $this->getCourrierUseCase->execute($id);

            $this->denyAccessUnlessGranted('courrier_edit', $courrier);

            if ($request->isMethod('POST')) {
                $dto = new UpdateCourrierDTO(
                    objet: $request->request->get('objet'),
                    contenu: $request->request->get('contenu'),
                    priorite: $request->request->get('priorite'),
                    statut: $request->request->get('statut'),
                    notes: $request->request->get('notes'),
                    destinatairesCopieIds: $request->request->all('destinatairesCopieIds')
                );

                try {
                    $this->updateCourrierUseCase->execute($id, $dto, $this->getUser()->getId());
                    $this->addFlash('success', 'Courrier modifié avec succès.');
                    return $this->redirectToRoute('courrier_show', ['id' => $id]);
                } catch (DomainException $e) {
                    $this->addFlash('error', $e->getMessage());
                }
            }

            $services = $this->serviceRepository->findAll();

            return $this->render('courrier/edit.html.twig', [
                'courrier' => $courrier,
                'services' => $services,
                'priorites' => Courrier::getValidPriorites(),
                'statuts' => Courrier::getValidStatuts(),
            ]);

        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_index');
        }
    }

    #[Route('/{id}/accuse-reception', name: 'courrier_accuse_reception', methods: ['POST'])]
    public function accuseReception(string $id): Response
    {
        try {
            $user = $this->getUser();
            $serviceId = $user->getService()->getId();

            $this->accuseReceptionUseCase->execute($id, $serviceId, $user->getId());

            $this->addFlash('success', 'Accus?? de r??ception confirm?? avec succ??s.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $id]);
    }

    #[Route('/export/excel', name: 'courrier_export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $courriers = $this->listCourriersUseCase->executeByService($user->getService()->getId());
        }

        $data = [];
        foreach ($courriers as $courrier) {
            $data[] = [
                $courrier->getNumeroArrivee() ?? '-',
                $courrier->getNumeroReference(),
                $courrier->getType(),
                $courrier->getObjet(),
                $courrier->getPriorite(),
                $courrier->getStatut(),
                $courrier->getDateCourrier()->format('d/m/Y'),
                $courrier->getDateEnregistrement()->format('d/m/Y H:i'),
            ];
        }

        $headers = ['N?? Arriv??e', 'N?? R??f??rence', 'Type', 'Objet', 'Priorit??', 'Statut', 'Date Courrier', 'Date Enregistrement'];

        return $this->excelExportService->export($data, $headers, 'Liste_Courriers_' . date('Y-m-d'));
    }

    #[Route('/export/pdf', name: 'courrier_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $courriers = $this->listCourriersUseCase->executeByService($user->getService()->getId());
        }

        $data = [];
        foreach ($courriers as $courrier) {
            $data[] = [
                $courrier->getNumeroArrivee() ?? '-',
                $courrier->getNumeroReference(),
                $courrier->getType(),
                mb_substr($courrier->getObjet(), 0, 40) . (strlen($courrier->getObjet()) > 40 ? '...' : ''),
                $courrier->getPriorite(),
                $courrier->getStatut(),
                $courrier->getDateCourrier()->format('d/m/Y'),
            ];
        }

        $headers = ['N?? Arriv??e', 'N?? R??f??rence', 'Type', 'Objet', 'Priorit??', 'Statut', 'Date'];

        return $this->pdfExportService->export($data, $headers, 'Liste_Courriers_' . date('Y-m-d'), 'Liste des Courriers');
    }

    #[Route('/{id}/archiver', name: 'courrier_archiver', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archiver(string $id): Response
    {
        try {
            $this->getCourrierUseCase->execute($id); // assure existence
            $dto = new UpdateCourrierDTO(
                statut: Courrier::STATUT_ARCHIVE
            );
            $this->updateCourrierUseCase->execute($id, $dto, $this->getUser()->getId());
            $this->addFlash('success', 'Courrier archivé.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $id]);
    }

    #[Route('/{id}/generate-official-pdf', name: 'courrier_generate_official_pdf', methods: ['GET'])]
    public function generateOfficialPdf(string $id): Response
    {
        try {
            $courrier = $this->getCourrierUseCase->execute($id);
            
            if ($courrier->getType() !== Courrier::TYPE_SORTANT) {
                throw new \App\Domain\Exception\DomainException("La génération de PDF officiel n'est disponible que pour les courriers sortants.");
            }

            $expLabel = $courrier->getTypeExpediteur() === \App\Domain\Entity\Courrier::ACTEUR_SERVICE 
                ? ($courrier->getServiceExpediteur()?->getNom() ?? 'Service')
                : ($courrier->getPersonneExterneExpediteur()?->getNomOuRaisonSociale() ?? 'Externe');

            $destLabel = $courrier->getTypeDestinataire() === \App\Domain\Entity\Courrier::ACTEUR_SERVICE
                ? ($courrier->getServiceDestinataire()?->getNom() ?? 'Service')
                : ($courrier->getPersonneExterneDestinataire()?->getNomOuRaisonSociale() ?? 'Externe');

            $data = [
                'senderService' => $expLabel,
                'receiverService' => $destLabel,
                'date' => $courrier->getDateCourrier()->format('Y-m-d'),
                'letterNumber' => $courrier->getNumeroReference(),
                'subject' => $courrier->getObjet(),
                'importance' => $courrier->getPriorite(),
                'body' => $courrier->getContenu() ?? '',
            ];

            $pdfContent = $this->ocrIntegrationService->generatePdf($data);

            // Sauvegarder comme pièce jointe officielle
            $user = $this->getUser();
            $pieceJointe = new \App\Domain\Entity\PieceJointe(
                courrier: $courrier,
                nomFichierOriginal: 'Courrier_Officiel_' . str_replace('/', '_', $courrier->getNumeroReference()) . '.pdf',
                typeMime: 'application/pdf',
                tailleFichier: strlen($pdfContent),
                contenuFichier: $pdfContent,
                telechargeParUtilisateur: $user
            );
            $this->pieceJointeRepository->save($pieceJointe);

            $this->addFlash('success', 'Le courrier officiel a été généré et archivé avec succès.');
            
            // On redirige avec un flag pour déclencher le téléchargement côté client si besoin
            return $this->redirectToRoute('courrier_show', ['id' => $id, 'download_pdf' => 1]);
        } catch (\App\Domain\Exception\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_show', ['id' => $id]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
            return $this->redirectToRoute('courrier_show', ['id' => $id]);
        }
    }


    #[Route('/{id}/verify-ocr', name: 'courrier_verify_ocr', methods: ['POST'])]
    public function verifyOcr(string $id, Request $request): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $courrier = $this->getCourrierUseCase->execute($id);
            $file = $request->files->get('fichier');

            if (!$file) {
                return $this->json(['success' => false, 'message' => 'Aucun fichier fourni.'], 400);
            }

            // Extraction OCR
            $ocrData = $this->ocrIntegrationService->extractText($file);
            $extractedReference = $ocrData['letterNumber'] ?? null;

            // Nettoyage pour comparaison (enlever espaces et caractères spéciaux)
            $cleanExtracted = $extractedReference ? strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $extractedReference)) : '';
            $cleanStored = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $courrier->getNumeroReference()));

            // Comparaison de la référence (plus permissive)
            if (!$extractedReference || (strlen($cleanStored) > 3 && !str_contains($cleanExtracted, $cleanStored) && !str_contains($cleanStored, $cleanExtracted))) {
                return $this->json([
                    'success' => false, 
                    'message' => 'La référence extraite (' . ($extractedReference ?? 'aucune') . ') ne correspond pas à la référence attendue (' . $courrier->getNumeroReference() . '). Veuillez importer la bonne version numérique.'
                ], 400);
            }

            // Si cohérent, on confirme la réception
            $this->accuseReceptionUseCase->execute(
                $courrier->getId(),
                $user->getService()->getId(),
                $user->getId()
            );

            // On enregistre le fichier comme pièce jointe "Courrier Officiel Reçu"
            $pieceJointe = new PieceJointe(
                courrier: $courrier,
                nomFichierOriginal: 'Courrier_Recu_Confirme_' . $file->getClientOriginalName(),
                typeMime: $file->getClientMimeType(),
                tailleFichier: $file->getSize(),
                contenuFichier: file_get_contents($file->getRealPath()),
                telechargeParUtilisateur: $user
            );
            $this->pieceJointeRepository->save($pieceJointe);

            return $this->json([
                'success' => true,
                'message' => 'Courrier vérifié et réception confirmée avec succès.'
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/delete', name: 'courrier_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): Response
    {
        try {
            $this->deleteCourrierUseCase->execute($id, $this->getUser()->getId());
            $this->addFlash('success', 'Courrier supprimé avec succès.');
            return $this->redirectToRoute('courrier_index');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_show', ['id' => $id]);
        }
    }
}


