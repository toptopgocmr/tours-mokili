# Smart Solar Energy Africa — Backend Laravel

Backend Laravel pour la gestion des fiches d'expertise technique terrain,
des devis et des factures (SSEA Premium).

## Fonctionnalités

- Authentification API (Laravel Sanctum) pour l'application mobile Flutter (agents terrain).
- Authentification web (session) pour l'espace admin.
- API REST : création/consultation des fiches d'expertise (dossiers), upload de photos/documents.
- Espace admin (Blade) : consultation des fiches remontées, création de devis et factures, génération PDF, suivi des paiements.
- Modèle de données couvrant les 11 sections de la fiche "Expertise Technique Premium".

## Installation

```bash
composer install
cp .env.example .env      # déjà fait, à adapter (DB_*, SSEA_*)
php artisan key:generate
```

Configurer la base de données MySQL dans `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Deux comptes sont créés par le seeder (mot de passe `password`) :

- Admin : `admin@smartsolarenergyafrica.com`
- Agent terrain : `agent@smartsolarenergyafrica.com`

**Pensez à changer ces mots de passe avant toute mise en production.**

Espace admin : http://localhost:8000/admin

## API mobile (résumé)

| Méthode | Route                              | Description                          |
|---------|-------------------------------------|---------------------------------------|
| POST    | /api/login                          | Connexion agent, retourne un token   |
| POST    | /api/logout                         | Déconnexion (révoque le token)       |
| GET     | /api/me                             | Utilisateur connecté                 |
| GET     | /api/dossiers                       | Liste des fiches (agent : les siennes)|
| POST    | /api/dossiers                       | Création d'une fiche                 |
| GET     | /api/dossiers/{id}                  | Détail d'une fiche                   |
| PUT     | /api/dossiers/{id}                  | Mise à jour d'une fiche              |
| POST    | /api/dossiers/{id}/documents        | Upload d'une photo/document          |
| GET     | /api/clients?q=                     | Recherche de clients existants       |

Toutes les routes (sauf `/api/login`) nécessitent l'en-tête
`Authorization: Bearer {token}`.

## Devis / Facture

- Devise : FCFA, TVA par défaut 18% (modifiable dans `.env`, clés `SSEA_DEVISE` / `SSEA_TAUX_TVA`).
- Numérotation automatique : `SSEA-2026-0001` (dossiers), `DEV-2026-0001` (devis), `FAC-2026-0001` (factures).
- PDF générés via `barryvdh/laravel-dompdf`.

## Rôles

- `admin` : accès complet à l'espace web, consultation de tous les dossiers, création devis/factures.
- `agent` : accès à l'API mobile uniquement, limité à ses propres dossiers.
