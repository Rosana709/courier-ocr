# 🦅 Système de Gestion de Courrier Intégré (SGC) - DGI

Bienvenue dans le projet de gestion de courrier de la **Direction Générale des Impôts (DGI)**. Ce projet est une solution hybride combinant la puissance de **Symfony 7** pour la gestion métier et un micro-service **FastAPI (Python)** pour l'intelligence artificielle et l'OCR.

---

## �️ Architecture du Projet

Le projet est divisé en deux composants principaux qui communiquent via API :

### 1. Application Principale (`/gestion_courier`)
*   **Technologie** : PHP 8.2+ / Symfony 7.2
*   **Rôle** : Cœur de l'application, gestion des utilisateurs, des services, de la base de données, des workflows de validation et des notifications.
*   **Base de Données** : PostgreSQL 16.

### 2. Système OCR & IA (`/ocr-system/backend`)
*   **Technologie** : Python 3.10+ / FastAPI
*   **Rôle** : Micro-service spécialisé dans le traitement de documents.
    *   **Extraction OCR** : Utilisation de Tesseract/PaddleOCR pour lire les informations des fichiers PDF scannés.
    *   **Génération de Contenu** : Intégration d'IA pour assister à la rédaction des courriers.
    *   **Génération de PDF** : Moteur de rendu PDF pour les courriers officiels sortants.

---

## ✨ Fonctionnalités Clés

### 📁 Gestion Métier (Symfony)
*   **Workflow Complet** : Enregistrement, diffusion (aiguillage), suivi et archivage des courriers.
*   **Accusés de Réception** : Système de décharge numérique pour garantir la traçabilité.
*   **Notifications Dynamiques** : Alertes sonores et visuelles en temps réel dès la réception d'un courrier.
*   **Tableaux de Bord** : Rapports d'activité détaillés pour les chefs de service et administrateurs.

### 🤖 Intelligence & Automatisation (Python)
*   **OCR Intelligent** : Importez un PDF scanné, et le système remplit automatiquement l'objet, la date et le numéro de référence.
*   **Aide à la Rédaction** : Génération automatique du corps de texte basé sur un prompt.
*   **Certification PDF** : Création de documents PDF normalisés avec en-tête DGI officiel.

---

## � Structure des Dossiers

```text
gestion_courier/
├── src/                      # Code source Symfony (Architecture DDD)
│   ├── Domain/               # Entités, Repositories (Logique métier)
│   ├── Application/          # Cas d'utilisation (UseCases)
│   ├── Infrastructure/       # Services externes (OCR Integration, Mailer)
│   └── Presentation/         # Contrôleurs et templates Twig
├── templates/                # Vues Twig (Frontend)
├── config/                   # Configuration Symfony
└── public/                   # Point d'entrée web

ocr-system/
├── backend/                  # API Python FastAPI
│   ├── app/
│   │   ├── api/              # Endpoints (extract, generate-pdf)
│   │   ├── services/         # Logique OCR et IA
│   │   └── utils/            # Moteur de génération PDF
│   └── main.py               # Lancement du serveur Python (Port 8001)
└── frontend/                 # (Optionnel) Dashboard de monitoring OCR
```

---

## 🚀 Installation & Configuration

### 1. Configuration de l'Application Symfony
```bash
cd gestion_courier
composer install

# Configurer .env.local
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/gestion_courier?serverVersion=16&charset=utf8"
OCR_BACKEND_URL="http://127.0.0.1:8001"

# Initialiser la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### 2. Configuration du Backend OCR (Python)
```bash
cd ocr-system/backend
python -m venv venv
source venv/bin/activate  # ou venv\Scripts\activate sur Windows
pip install -r requirements.txt

# Lancer le serveur
python main.py
```

---

## 🔌 Fonctionnement de l'Intégration

L'application Symfony communique avec le service Python via le service `OcrIntegrationService.php`. 

1.  **Extraction** : Quand vous uploadez un fichier dans `Courrier Entrant`, le fichier est envoyé à `POST http://127.0.0.1:8001/api/extract`.
2.  **Résultat** : Le JSON retourné (objet, date, etc.) est utilisé pour pré-remplir le formulaire Symfony en JavaScript.
3.  **Génération PDF** : Pour les courriers sortants, Symfony envoie les données à `POST /api/generate-pdf` pour récupérer un fichier PDF formaté.

---

## 🔐 Sécurité & Maintenance

*   **Rôles** : `ROLE_ADMIN` (Gestion totale, archivage) et `ROLE_USER` (Gestion par service).
*   **Tranches de vie** : Les courriers ne sont jamais supprimés physiquement mais passés au statut `ARCHIVE`.
*   **Logs** : Le backend Python génère des logs dans `ocr_backend.log` pour le débogage des extractions.

---

## 👨‍� Crédits & Contact

Développé pour la **Direction Générale des Impôts**.  
**Technologies** : Symfony 7, FastAPI, PostgreSQL, Tesseract OCR.

---
*Dernière mise à jour : 06 Janvier 2026*
