# 🦅 Système de Gestion de Courrier Intégré (SGC) - DGI Madagascar

Bienvenue dans la solution officielle de gestion et d'automatisation de courrier de la **Direction Générale des Impôts (DGI) à Madagascar**. Ce projet est une plateforme premium combinant la robustesse du framework **Symfony 7** et l'intelligence artificielle de pointe via **FastAPI (Python)**.

> **"Bienvenue sur notre plateforme d'automatisation d'enregistrement de courrier au sein de la DGI à Madagascar."**

---

## 🏗️ Architecture du Système

Le projet repose sur une architecture moderne à deux piliers, garantissant performance et évolutivité :

### 1. Cœur Applicatif (`/gestion_courier`)
*   **Technologie** : PHP 8.2+ / Symfony 7.2
*   **Base de Données** : PostgreSQL 16
*   **Rôle** : Gestion métier, sécurité (RBAC), workflows de validation, notifications en temps réel et interface utilisateur premium.

### 2. Cerveau IA & OCR (`/ocr-system/backend`)
*   **Technologie** : Python 3.10+ / FastAPI / Groq AI (Llama 3.3)
*   **Rôle** : Micro-service d'intelligence pour le traitement de documents.
    *   **Assistant DGI** : Agent conversationnel intelligent spécialisé dans le contexte administratif malgache.
    *   **Lecture de document (OCR)** : Extraction automatique des données depuis des fichiers scannés.
    *   **Rédaction assistée** : Génération de contenu de courrier conforme aux standards administratifs.

---

## ✨ Fonctionnalités Majeures

### 🤖 Assistant DGI Intelligent
Un compagnon interactif intégré à la plateforme pour guider les utilisateurs :
*   **Identité Forte** : Expert DGI Madagascar utilisant un langage courant et non-technique.
*   **Conscience du Rôle** : Ses réponses s'adaptent selon que vous soyez Administrateur ou Agent.
*   **Interaction Fluide** : Effets sonores de frappe et notifications vocales pour une expérience "vivante".

### 📁 Gestion Métier Avancée
*   **Workflow Courrier** : Enregistrement, diffusion, suivi des décharges et archivage systématique.
*   **Numérotation Automatique** : Génération de numéros de référence et d'arrivée selon les formats officiels de la DGI.
*   **Tableau de Bord & Audit** : Surveillance globale pour les administrateurs et rapports d'activité pour les services.

### ⚡ Automatisation & Productivité
*   **Lecture Automatique** : Importez un ancien courrier, et le système "lit" le document pour remplir les champs à votre place.
*   **Rédaction Expert** : L'IA vous aide à écrire vos lettres de départ en respectant le ton officiel.
*   **Notifications Smart** : Système d'alerte sonore cristallin pour les nouveaux courriers et les messages de l'assistant.

---

## 🚀 Installation Rapide

### 1. Application Symfony
```bash
cd gestion_courier
composer install
# Configuration du .env.local (Database & API Key)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
symfont server:start
```

### 2. Backend IA (Python)
```bash
cd ocr-system/backend
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
# Configurer le GROQ_API_KEY dans le fichier .env
python main.py
```

---

## 🔐 Gouvernance & Rôles

Le système applique une séparation stricte des privilèges :
*   **Administrateur** : Gestion des utilisateurs, des services/bureaux, audit complet et archivage. Pas de création directe de courrier.
*   **Agent de Service** : Enregistrement des courriers (Entrants/Sortants), réponse aux notifications, rédaction assistée et validation des reçus.

---

## 👨‍💻 Crédits
Propriété de la **Direction Générale des Impôts (DGI)**.  
Conçu pour moderniser et sécuriser les échanges administratifs à Madagascar.

---
*Dernière mise à jour : 08 Janvier 2026*
