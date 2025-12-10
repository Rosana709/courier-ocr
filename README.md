# Système de Gestion de Courrier - DGI

Application web de gestion de courrier développée avec Symfony 7 pour la Direction Générale des Impôts.

## 📋 Description

Cette application permet de gérer les courriers entrants et sortants au sein de l'organisation, avec un système de numérotation automatique, de suivi et de notifications.

## ✨ Fonctionnalités principales

### Gestion des courriers
- ✅ **Création de courriers** (entrants/sortants, internes/externes)
- ✅ **Double numérotation** pour les courriers entrants :
  - Numéro de référence (de l'expéditeur)
  - Numéro d'arrivée (auto-généré : N° XXX-2025/DG/SIGLE)
- ✅ **Types d'acteurs** : Services internes ou Personnes externes
- ✅ **Gestion des priorités** : Basse, Normale, Haute, Urgente
- ✅ **Statuts multiples** : Enregistré, En cours, En attente, Clos, Archivé
- ✅ **Services en copie** : Notification de plusieurs services
- ✅ **Pièces jointes** : Upload et gestion de documents
- ✅ **Accusé de réception** : Pour les courriers vers des services

### Notifications
- ✅ **Système de notifications** en temps réel
- ✅ **Types de notifications** :
  - Nouveau courrier reçu
  - Accusé de réception confirmé
  - Changement de statut
- ✅ **Filtrage** : Non lues, récentes
- ✅ **Marquage** : Lire/tout marquer comme lu

### Gestion des utilisateurs
- ✅ **Authentification** sécurisée
- ✅ **Rôles** : Admin, Utilisateur de service
- ✅ **Profils** : Gestion des informations personnelles
- ✅ **Services** : Rattachement à un service

### Exports
- ✅ **Export Excel** : Liste complète avec tous les champs
- ✅ **Export PDF** : Tableau formaté pour impression
- ✅ Disponible pour : Courriers, Services, Personnes externes, Utilisateurs

### Filtres et recherche
- ✅ **Vues séparées** pour utilisateurs de service :
  - Tous les courriers
  - Courriers entrants (avec badge "En copie")
  - Courriers sortants
- ✅ **Filtres** : Type, statut, priorité, date, service
- ✅ **Recherche** : Par numéro, objet, contenu

## 🛠️ Technologies utilisées

- **Backend** : PHP 8.2+ avec Symfony 7
- **Base de données** : PostgreSQL 16
- **Architecture** : Domain-Driven Design (DDD)
- **Frontend** : Twig + Bootstrap 5 + Bootstrap Icons
- **Exports** : PhpSpreadsheet (Excel) + Dompdf (PDF)

## 📁 Structure du projet

```
src/
├── Application/        # Use Cases et DTOs
│   ├── DTO/
│   └── UseCase/
├── Domain/            # Entités et logique métier
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   └── Exception/
├── Infrastructure/    # Implémentation technique
│   └── Repository/
└── Presentation/      # Contrôleurs et vues
    └── Controller/
templates/             # Vues Twig
migrations/           # Migrations de base de données
```

## 🚀 Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- PostgreSQL 16
- Extension PDO PostgreSQL

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone <url-du-repo>
   cd gestion_courier
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configuration de la base de données**

   Créer un fichier `.env.local` :
   ```env
   DATABASE_URL="postgresql://user:password@127.0.0.1:5432/gestion_courier?serverVersion=16&charset=utf8"
   ```

4. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   ```

5. **Exécuter les migrations**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

6. **Charger les données de test (optionnel)**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Lancer le serveur de développement**
   ```bash
   symfony server:start
   # ou
   php -S localhost:8000 -t public/
   ```

8. **Accéder à l'application**
   ```
   https://localhost:8000
   ```

## 👤 Comptes de test

Après le chargement des fixtures :

- **Administrateur** :
  - Email : `admin@dgi.gov`
  - Mot de passe : `admin123`

- **Utilisateur de service** :
  - Email : `user@dgi.gov`
  - Mot de passe : `user123`

## 📊 Modèle de données

### Entités principales

- **Courrier** : Gestion des courriers (entrants/sortants)
- **Service** : Services de l'organisation
- **Utilisateur** : Comptes utilisateurs
- **PersonneExterne** : Contacts externes
- **PieceJointe** : Documents attachés
- **AccuseReception** : Confirmations de réception
- **Notification** : Système de notifications
- **HistoriqueAction** : Traçabilité des actions

## 🔐 Sécurité

- Authentification par formulaire
- Hashage des mots de passe (bcrypt)
- Contrôle d'accès basé sur les rôles (Voter)
- Protection CSRF
- Validation des données (DTO + Contraintes)

## 📝 Fonctionnalités avancées

### Système de numérotation
- **Courriers sortants** : N° 001 – 2025/DGI/SERVICE (compteur par service/année)
- **Courriers entrants** : N° 001-2025/DG/SERVICE (numéro d'arrivée auto-généré)

### Workflow de validation
1. Courrier créé → Statut initial selon type/destinataire
2. Si destinataire = SERVICE → Nécessite accusé de réception
3. Confirmation → Notification à l'expéditeur
4. Suivi du statut tout au long du cycle de vie

## 🔧 Commandes utiles

```bash
# Vider le cache
php bin/console cache:clear

# Créer un nouvel utilisateur admin
php bin/console app:create-admin

# Voir les routes
php bin/console debug:router

# Vérifier la configuration
php bin/console debug:config

# Mettre à jour le schéma DB (dev uniquement)
php bin/console doctrine:schema:update --force
```

## 📦 Dépendances principales

- symfony/framework-bundle
- doctrine/orm
- symfony/security-bundle
- symfony/twig-bundle
- symfony/form
- phpoffice/phpspreadsheet
- dompdf/dompdf

## 🤝 Contribution

Ce projet a été développé pour la Direction Générale des Impôts.

## 📄 Licence

Projet propriétaire - Direction Générale des Impôts

## 🆘 Support

Pour toute question ou problème, contactez l'équipe de développement.

---

**Version** : 1.0.0
**Date** : Décembre 2025
**Développé avec** : Claude Code & Symfony 7
