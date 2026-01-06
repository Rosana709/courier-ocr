# 🚀 Guide de Déploiement sur Render.com

Ce projet étant composé d'une application **Symfony (PHP)** et d'un micro-service **OCR (Python)**, nous utilisons la fonctionnalité **Blueprints** de Render pour déployer l'ensemble de l'infrastructure en une seule fois.

## 📋 Prérequis

1.  Un compte sur [Render.com](https://render.com).
2.  Votre projet doit être hébergé sur un dépôt GitHub ou GitLab.

## 🛠️ Étapes de Déploiement

### 1. Utiliser le Blueprint
Le fichier `render.yaml` à la racine de votre projet contient déjà toute la configuration nécessaire.

1.  Connectez-vous à votre dashboard Render.
2.  Cliquez sur **"New"** puis sur **"Blueprint"**.
3.  Connectez votre dépôt GitHub.
4.  Render détectera automatiquement le fichier `render.yaml`.
5.  Donnez un nom au groupe de ressources (ex: `sgc-dgi`) et cliquez sur **Approve**.

### 2. Configurer les Variables Secrètes
Certaines variables ne sont pas incluses dans le fichier pour des raisons de sécurité. Une fois les services créés :

#### Pour `sgc-ocr-backend` (Service Python) :
*   Allez dans **Environment**.
*   Ajoutez la clé `LLAMA_CLOUD_API_KEY` avec votre clé API LlamaCloud.

#### Pour `sgc-symfony-app` (Service PHP) :
*   La variable `DATABASE_URL` est connectée automatiquement à la base de données créée.
*   La variable `OCR_BACKEND_URL` est connectée automatiquement au service Python.
*   (Optionnel) Ajoutez `MAILER_DSN` pour l'envoi d'emails.

### 3. Initialiser la Base de Données
Une fois que le service Symfony est déployé avec succès :

1.  Allez dans l'onglet **Shell** du service Symfony (`sgc-symfony-app`).
2.  Exécutez la commande suivante pour créer les tables :
    ```bash
    php bin/console doctrine:migrations:migrate --no-interaction
    ```
3.  (Optionnel) Chargez les données initiales :
    ```bash
    php bin/console doctrine:fixtures:load --append --no-interaction
    ```

## 🔍 Architecture du Déploiement

*   **Database (PostgreSQL)** : Instance gérée par Render.
*   **Système OCR (Python)** : Isolé dans son propre container, accessible uniquement en interne par Symfony via `OCR_BACKEND_URL`.
*   **App Symfony (PHP 8.2)** : Serveur Apache optimisé pour la production.

## ⚠️ Notes Importantes pour la Production

*   **Stockage des Fichiers** : Par défaut, Render a un système de fichiers éphémère. Pour conserver les pièces jointes des courriers entre les déploiements, vous devriez ajouter un **Disk** (Persistent Storage) de 1 Go monté sur `/var/www/html/public/uploads` (ou le dossier où vous stockez vos fichiers).
*   **HTTPS** : Render gère automatiquement les certificats SSL TLS.
*   **Port** : Le service Python utilise automatiquement la variable `$PORT` fournie par Render.

---
*Besoin d'aide ? Consultez la documentation de [Render Blueprints](https://render.com/docs/blueprints).*
