# 🧠 Architecture de l'Intelligence Artificielle - Projet SGC DGI

Ce document décrit l'intégration de l'intelligence artificielle au sein du **Système de Gestion de Courrier (SGC)** de la **Direction Générale des Impôts (DGI) à Madagascar**.

## 🛠️ Composants Technologiques de l'IA

Pour répondre aux exigences de modernisation de l'administration, nous avons couplé deux puissances technologiques :

1.  **Le Modèle de Langage (LLM) : LLAMA**
    *   **Rôle** : Moteur de génération de texte et compréhension du langage naturel.
    *   **Application SGC** : Utilisé par l'**Assistant DGI** pour rédiger des courriers officiels, résumer des documents scannés et guider les agents de manière conversationnelle.
    *   **Avantages** : Capacité de raisonnement logique et respect des tons formels exigés par la DGI.

2.  **L'Outil d'Analyse : PANDASAI**
    *   **Rôle** : Intermédiaire entre le LLM et les données structurées.
    *   **Application SGC** : Utilisé pour générer des **rapports analytiques** complexes (statistiques de courrier, délais de traitement par service, volumes d'archivage).
    *   **Avantages** : Permet de diriger LLAMA spécifiquement vers l'analyse de données sans que l'utilisateur n'ait à manipuler de code.

---

## ⚙️ Workflow de l'Analyse Intelligente (Projet DGI)

Voici comment l'assistant transforme une base de données complexe en informations décisionnelles pour la hiérarchie de la DGI :

### 1. Phase de Récupération 📥
Le système interroge la base de données PostgreSQL pour extraire les données nécessaires (ex: tables `courrier`, `service`, `historique_action`).

### 2. Phase de Prétraitement (Dataframes) 📊
Les données sont converties en **Dataframes** (structures de données Python optimisées). Cette étape est cruciale pour que l'outil PandasAI puisse manipuler les statistiques du courrier.

### 3. Construction du Prompt (Instructions Métier) 🖋️
Le système crée un prompt contenant la question de l'agent (ex: *"Combien de courriers sont en attente au Service des Impôts ?"*) ainsi que le schéma des données DGI pour guider l'IA.

### 4. Génération de Code (Python) 💻
LLAMA, via PandasAI, écrit dynamiquement un script de traitement (calcul de moyennes, regroupements par service, tris par priorité).

### 5. Validation Initiale 🔎
Le système vérifie la sécurité et la syntaxe du code généré pour s'assurer qu'il respecte les contraintes du serveur de la DGI.

### 6. Exécution sur les Données 🚀
Le code est exécuté sur les Dataframes. C'est le moment où les statistiques réelles du projet sont calculées.

### 7. Seconde Validation (Validation Métier) ✅
Le résultat est analysé pour vérifier qu'il répond exactement à la demande initiale de l'utilisateur (cohérence des chiffres).

### 8. Affichage du Résultat 🖥️
La réponse finale est présentée sous forme de résumé textuel fluide ou de tableau dans le Dashboard du SGC.

---

*Grâce à cette architecture, l'administration de la DGI dispose d'un outil capable non seulement d'enregistrer le courrier, mais aussi d'analyser intelligemment les flux administratifs en temps réel.*
