# 🏛️ Nabd Al-Madina (نبض المدينة) - Backend RESTful API

**Nabd Al-Madina** est une plateforme moderne permettant aux citoyens de signaler les problèmes urbains (nids-de-poule, éclairage cassé, fuite d'eau, déchets, obstacles PMR, etc.) en langage naturel. 

Contrairement à un ticketing classique, chaque signalement est automatiquement analysé par un service d'**Intelligence Artificielle** (via `laravel/ai` / OpenAI / Gemini / Heuristique structurée) qui en extrait une catégorie, une priorité, une urgence (1-5), un résumé et le département municipal concerné. L'IA détecte également les doublons potentiels proches géographiquement et propose un regroupement en un **Incident** unique soumis à la validation d'un agent municipal.

---

## 🛠️ Stack Technique

- **Framework**: Laravel 13.x (PHP 8.3+)
- **Base de Données**: MySQL (`nabd_almadina`)
- **Authentification & Sécurité**: Laravel Sanctum (Tokens API), Gates & Policies par rôle (`citoyen`, `agent`)
- **Intégration IA**: `SignalementAnalyzer` (Classification & extraction JSON structuré), `SimilarityService` (Formule d'Haversine + similarité sémantique pour détection de doublons)
- **API**: RESTful API Resources & Form Requests

---

## 🚀 Installation & Configuration

### 1. Cloner le projet & installer les dépendances
```bash
cd laravel
composer install
```

### 2. Configuration des Variables d'Environnement (`.env`)
Copiez `.env.example` vers `.env` (ou éditez `.env`) et vérifiez la configuration de la base de données **MySQL** et des clés **IA** :

```ini
APP_NAME=NabdAlMadina
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Données MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nabd_almadina
DB_USERNAME=root
DB_PASSWORD=

# Service IA (OpenAI / Gemini API)
AI_API_KEY=votre_cle_api_openai_ou_gemini
AI_ENDPOINT=https://api.openai.com/v1/chat/completions
AI_MODEL=gpt-4o-mini
```

### 3. Migration & Données de Test (Seeders)
```bash
# Générer la clé d'application
php artisan key:generate

# Exécuter les migrations et alimenter la base de données avec des données municipales réalistes
php artisan migrate:fresh --seed
```

---

## 👥 Comptes de Démonstration (Seeders)

| Rôle | Nom | Email | Mot de passe | Département |
| :--- | :--- | :--- | :--- | :--- |
| **Citoyen** | Fatima Zahra | `citoyen@nabd.ma` | `password` | - |
| **Citoyen** | Ahmed Alami | `ahmed@nabd.ma` | `password` | - |
| **Agent Municipal** | Agent Said | `agent.voirie@nabd.ma` | `password` | Voirie |
| **Agent Municipal** | Agent Karim | `agent.eclairage@nabd.ma` | `password` | Éclairage public |
| **Agent Municipal** | Agent Rachid | `agent.eau@nabd.ma` | `password` | Eau et assainissement |

---

## 📌 Architecture & Endpoints API

### 🔐 Authentification (`/api/auth`)
- `POST /api/auth/register` : Inscription d'un citoyen ou agent municipal.
- `POST /api/auth/login` : Connexion et génération du token Sanctum.
- `GET /api/auth/me` : Profil de l'utilisateur connecté.
- `POST /api/auth/logout` : Révocation du token.

### 📍 Signalements (`/api/signalements`)
- `GET /api/signalements` : Liste des signalements (filtré par le rôle selon la Policy).
- `POST /api/signalements` : Création d'un signalement par un citoyen avec texte libre & coordonnées (déclenche l'analyse IA automatique).
- `GET /api/signalements/{id}` : Consultation détaillée d'un signalement.
- `PATCH /api/signalements/{id}/statut` : Modification du statut (`nouveau`, `en_cours`, `resolu`, `rejete`) - **Agent uniquement**.
- `PATCH /api/signalements/{id}/departement` : Assignation d'un département - **Agent uniquement**.
- `GET /api/signalements/{id}/similaires` : Détection IA des doublons/signalements proches (Rapprochement).

### 🚨 Incidents (`/api/incidents`)
- `GET /api/incidents` : Liste des incidents groupés.
- `POST /api/incidents/regrouper` : Validation par l'agent municipal du regroupement de signalements en un Incident - **Agent uniquement**.
- `GET /api/incidents/{id}` : Détails d'un incident et signalements rattachés.
- `DELETE /api/incidents/{id}` : Suppression avec contrôle d'intégrité référentielle (rejeté si des signalements y sont encore rattachés).

---

## 🧪 Tests Automatisés

Le projet comprend une suite complète de tests automatisés couvrant l'authentification, les politiques d'accès, la classification IA et l'intégrité référentielle :

```bash
php artisan test
```

---

## 🎬 Scénario de Démonstration (Bout-en-Bout)

1. **Création d'un signalement par un citoyen (US2 & US3)** :
   Connectez-vous en tant que citoyen (`citoyen@nabd.ma`) et envoyez une requête `POST /api/signalements` avec :
   ```json
   {
     "description": "Un grand nid-de-poule très dangereux s'est formé au milieu de l'avenue Hassan II près du croisement.",
     "latitude": 33.57311,
     "longitude": -7.58984
   }
   ```
   L'IA enrichit immédiatement l'objet créé avec `category: "Voirie"`, `priority: "high"`, `urgency: 4`, et `summary`.

2. **Détection des doublons par l'IA (US4)** :
   Appelez `GET /api/signalements/1/similaires`. Le système renvoie les signalements ouverts à proximité (rayon < 2km) avec un score de similarité et la recommandation de regroupement.

3. **Validation du regroupement par un agent (US5 & US6)** :
   Connectez-vous en tant qu'agent municipal (`agent.voirie@nabd.ma`) et validez la création de l'incident via `POST /api/incidents/regrouper` :
   ```json
   {
     "signalement_ids": [1, 2],
     "title": "Incident Nid-de-poule Avenue Hassan II"
   }
   ```

4. **Test d'Intégrité Référentielle** :
   Tentez de supprimer l'incident via `DELETE /api/incidents/1`. L'API renvoie un code HTTP `422 Unprocessable Entity` interdisant la suppression tant que des signalements restent rattachés.

---

## 📑 Collection Postman

Fichier disponible à la racine : `Nabd_Almadina.postman_collection.json`.
Importez-le dans Postman pour tester tous les endpoints pré-configurés !
