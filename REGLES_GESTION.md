# Règles de Gestion (RG) - Système de Gestion de Courrier (SGC)

Ce document détaille les règles métier et les comportements attendus du système de gestion de courrier pour la DGI Madagascar.

---

## 1. Gestion des Utilisateurs et Accès

### RG.01 : Authentification Unique
Chaque utilisateur doit s'authentifier avec son adresse email professionnelle et un mot de passe sécurisé.

### RG.02 : Hiérarchie des Rôles
Le système distingue deux types d'acteurs principaux :
- **ADMINISTRATEUR (ROLE_ADMIN)** : Gestion des services, des utilisateurs, consultation de l'audit système, archivage global.
- **AGENT DE SERVICE (ROLE_SERVICE)** : Enregistrement, consultation et traitement des courriers de son propre service. Un agent ne peut pas voir les courriers des autres services.

### RG.03 : Association au Service
Chaque Agent est obligatoirement lié à **un seul** service. Toutes ses actions (envoi/réception) sont automatiquement rattachées à l'identité de ce service.

---

## 2. Cycle de Vie des Courriers

### RG.04 : Types de Courriers
- **ENTRANT** : Document provenant de l'extérieur (Personne Externe) ou d'un autre service interne à la DGI.
- **SORTANT** : Document émis par le service vers l'extérieur ou vers un autre service interne.

### RG.05 : Statuts du Courrier
- **EN_ATTENTE** : Statut initial lors de la création.
- **TRAITE** : Le courrier a fait l'objet d'une réponse ou d'une action définitive.
- **ARCHIVE** : Le courrier n'est plus actif mais reste consultable par les administrateurs.

---

## 3. Numérotation et Références

### RG.06 : Numéro de Référence (Courrier SORTANT)
Le numéro de référence est **géré exclusivement par le système** pour garantir l'intégrité de la numérotation.
- **Format** : `XXX/MEF/SG/DGI/SIGLE_SERVICE`
- **Règle de génération** : Automatisée lors de la confirmation d'enregistrement.
- **Contrainte** : Le champ est verrouillé (lecture seule) pour l'agent.

### RG.07 : Numéro d'Arrivée (Courrier ENTRANT)
Lorsqu'un courrier entrant est créé, le système génère un numéro d'arrivée interne.
- **Format** : `REF-ARRIVEE-YYYY-XXXX`
- **Contrainte** : L'agent doit obligatoirement saisir le numéro de référence porté sur le document physique (référence de l'expéditeur).

---

## 4. Intelligence Artificielle et OCR

### RG.08 : Assistant OCR
Le système permet d'extraire automatiquement les données d'un document scanné (PDF/Image) pour pré-remplir le formulaire.
- **Validation** : Les données extraites (Objet, Date, Référence) doivent être vérifiées et validées par l'agent avant enregistrement.

### RG.09 : Aide à la Rédaction
L'intelligence artificielle peut générer un brouillon de lettre administrative à partir d'un objet ou de notes.
- **Standard DGI** : Le texte généré respecte les formules de politesse et le ton professionnel requis par l'administration.

---

## 5. Audit et Notifications

### RG.10 : Journal d'Audit (Audit Log)
Toute action sensible (Création, Archivage, Connexion) est enregistrée dans l'historique des actions.
- **Données stockées** : Type d'action, Date, Utilisateur, Service, Description.

### RG.11 : Notifications Temps Réel
Le destinataire d'un courrier interne reçoit une notification (visuelle et sonore) dès l'enregistrement du courrier par le service expéditeur.

### RG.12 : Confirmation d'Action
Toute action d'enregistrement ou d'annulation majeure doit faire l'objet d'une **validation explicite via une fenêtre modale**, rappelant le numéro de référence qui sera attribué.

---

*Ce document est la source de vérité pour les spécifications fonctionnelles du projet SGC.*
