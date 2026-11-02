# RGPH5 — Gestion du recensement

Application web de gestion du **5e Recensement Général de la Population et de l'Habitat** :
consultation, saisie et analyse des villes, communes, arrondissements, départements
et des profils du **registre** (M / F / Junior).

Réécrite autour d'une architecture **MVC maison** (sans framework), sécurisée et maintenable.

---

## 1. Fonctionnalités

- **Authentification** par e-mail / mot de passe (bcrypt) avec migration transparente
  des anciens mots de passe stockés en clair.
- **Page de connexion illustrée** : formulaire à gauche et, à droite, le Monument de
  l'Amazone (Esplanade des Amazones, Cotonou) sans texte superposé. L'esplanade
  occupe l'arrière-plan de toute l'interface sous un voile uniforme ; le drapeau du
  Bénin flotte au vent dans le ciel du monument (SVG animé, désactivé si
  `prefers-reduced-motion`). Photos libres de droits, crédits :
  `public/img/amazone/CREDITS.txt`.
- **Rôles** : `admin`, `agent`, `lecteur` avec permissions granulaires.
- **Villes** : liste paginée, recherche, filtres (département / commune), tri,
  création, consultation, modification, suppression. Rattachement optionnel à un
  arrondissement, avec déduction automatique de la commune et du département, et
  suivi de la capacité (population / profils enregistrés).
- **Communes** et **départements** : CRUD complet avec protections d'intégrité.
- **Arrondissements** : CRUD complet rattaché aux communes, avec filtre par
  département / commune. La sélection de la commune renseigne automatiquement le
  département.
- **Registre** : profils individuels (`nom`, `prenom`, `date_ref`, `genre` M/F)
  rattachés à une ville, avec recherche, filtres M/F/Junior, pagination et CRUD.
- **Capacité du registre** : le nombre de profils d'une ville ne peut jamais dépasser
  sa population. La population ne peut pas être abaissée sous le nombre de profils
  déjà saisis, et une ville contenant des profils ne peut pas être supprimée.
- **Saisie multiple** : création de 2 à 50 villes, communes ou arrondissements en une
  seule opération, via un assistant pas à pas (une fiche à la fois) et un
  enregistrement global transactionnel.
- **Cascades automatiques** : arrondissement → commune → département, commune →
  département, avec conservation de la cohérence côté serveur.
- **Utilisateurs** : CRUD réservé aux administrateurs (protection du dernier admin
  et de son propre compte).
- **Tableau de bord** : indicateurs clés, population par département, villes les plus
  peuplées, dernières saisies et répartition **M / F / Junior** des profils du
  registre pour chaque ville, arrondissement, commune et département.
- **Rapports** : par département, par commune, par ville, et rapport dédié au registre.
- **Exports CSV** (UTF-8 + BOM, séparateur `;`) compatibles Excel : villes, communes,
  arrondissements, départements et registre.
- **API JSON** pour les listes déroulantes en cascade (département → commune).
- **Interface responsive** : design system dédié, menu latéral mobile, alertes,
  confirmations, états vides.

---

## 2. Prérequis

| Élément | Version conseillée |
|---|---|
| PHP | 7.4 minimum (testé avec 8.3) |
| MySQL / MariaDB | 8.0+ (testé avec MySQL 9.1) |
| Apache | avec `mod_rewrite` |
| Extensions PHP | `pdo_mysql`, `mbstring`, `json` |

---

## 3. Installation

### 3.1. Base de données

La base doit s'appeler `rgph5` et contenir les tables `utilisateur`, `departement`,
`commune`, `arrondissement`, `ville` et `registre`.

Le schéma de référence est fourni dans `database/schema.sql` :

```bash
mysql -u root -p rgph5 < database/schema.sql
```

### 3.2. Configuration

Éditez `config/config.php` :

```php
'database' => [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'rgph5',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
],
```

Puis, si nécessaire, ajustez `RewriteBase` dans `.htaccess` pour qu'il corresponde
à l'URL du dossier :

```apache
RewriteBase /td7/td7/
```

### 3.3. Migration

Ajoute la table `arrondissement` si elle manque, les colonnes `role` / `actif`,
la colonne `ville.idarrondissement`, la table `registre` (avec sa clé étrangère vers
`ville`), les index de performance et promeut un administrateur si aucun n'existe.
Le script est **idempotent** (relançable sans risque) :

```bash
php database/migrate.php
```

### 3.4. Compte administrateur

```bash
# Interactif
php database/create_admin.php

# Ou en une ligne
php database/create_admin.php admin@exemple.org "motdepasse"
```

### 3.5. Lancement

Placez le projet dans le dossier web de WAMP/XAMPP puis ouvrez :

```
http://localhost/td7/td7/
```

---

## 4. Structure du projet

```
td7/
├── index.php                 # Contrôleur frontal (point d'entrée unique)
├── bootstrap.php             # Amorçage : autoload, config, session, erreurs
├── .htaccess                 # Réécriture d'URL + protections
│
├── app/
│   ├── routes.php            # Table de routage
│   ├── Core/                 # Noyau : Router, Database, Auth, Validator...
│   ├── Controllers/          # Contrôleurs HTTP
│   ├── Models/               # Accès aux données (PDO)
│   ├── Views/                # Templates PHP
│   │   ├── layouts/          # Gabarits (application, authentification)
│   │   ├── partials/         # Fragments (sidebar, topbar, flash, drapeau-benin)
│   │   ├── auth/ dashboard/ villes/ communes/ arrondissements/ registres/
│   │   ├── departements/ utilisateurs/ rapports/ errors/
│   └── Exceptions/           # HttpException
│
├── config/
│   └── config.php            # Configuration globale
│
├── database/
│   ├── schema.sql            # Schéma de référence
│   ├── migrate.php           # Migration idempotente
│   ├── create_admin.php      # Création / réinitialisation d'un admin
│   └── .htaccess             # Accès web interdit
│
├── public/
│   ├── css/app.css           # Design system complet (aucune dépendance CSS externe)
│   ├── js/app.js             # Interactions (vanilla JS)
│   └── img/amazone/          # Photos de la page de connexion + CREDITS.txt
│
└── storage/logs/             # Journaux applicatifs
```

---

La page de connexion est assemblée par `app/Views/layouts/auth.php` : la scène
(esplanade) reste en arrière-plan fixe sous un voile uniforme (le formulaire et la
statue partagent le même contraste), le formulaire vit dans la colonne de gauche
(`.rg-auth-panel`) et la colonne de droite (`.rg-auth-visual`) affiche le Monument de
l'Amazone, sans aucun texte superposé. Le drapeau du Bénin
(`app/Views/partials/drapeau-benin.php`, SVG animé autonome) flotte dans le ciel du
monument sur les grands écrans ; sous 980 px, la statue complète seule devient le
fond de tout l'écran et le drapeau se place juste au-dessus du formulaire.
Pour remplacer les photos, écraser les fichiers de `public/img/amazone/`
(en conservant les crédits à jour dans `CREDITS.txt`) ; drapeau et mise en page
s'adaptent automatiquement.

---

## 5. Architecture

1. Toutes les requêtes passent par **`index.php`**.
2. `bootstrap.php` initialise l'autoloader PSR-4 (`App\`), la configuration, la
   session et le gestionnaire d'erreurs.
3. **`Router`** résout la route (`index.php?route=villes` ou URL propre),
   vérifie l'authentification, les permissions (`can`) et le jeton **CSRF**.
4. Le **contrôleur** valide les données, appelle le **modèle** puis rend une **vue**.
5. Les erreurs sont journalisées dans `storage/logs/app.log` et rendues via une page
   d'erreur dédiée (ou une page de debug si `app.debug = true`).

### Routage

Par défaut, les URLs utilisent le paramètre `route` :

```
index.php?route=villes
index.php?route=villes/12/editer
```

Pour activer les URLs propres (`/villes`), passez `app.pretty_urls` à `true` dans
`config/config.php` (nécessite `mod_rewrite` et un `.htaccess` fonctionnel).

### Modèle de données et règles métier

- **`ville.idarrondissement`** (optionnel) : rattache une ville à un arrondissement.
  Lorsqu'il est renseigné, le serveur déduit `idcommune` depuis l'arrondissement ;
  une ville sans arrondissement reste valide et apparaît comme « Non rattaché »
  dans les statistiques par arrondissement.
- **`registre`** : `idregistre` (PK), `idville` (FK vers `ville`, suppression
  restreinte), `nom`, `prenom`, `date_ref` (`DATE`) et `genre` (`ENUM('M','F')`).
- **Junior** : un profil est considéré junior lorsque
  `TIMESTAMPDIFF(YEAR, date_ref, CURDATE()) < 18`.
- **Capacité** : le nombre total de lignes `registre` pour une ville ne peut jamais
  dépasser `ville.population`. Les créations et modifications utilisent une
  transaction avec `SELECT ... FOR UPDATE` sur la ville cible ; la population ne
  peut pas être réduite sous le nombre de profils existants et la suppression d'une
  ville ou d'un arrondissement lié est protégée.
- **Intégrité** : la clé étrangère `registre.idville` protège la suppression des
  villes ; les suppressions d'arrondissements liés à des villes sont contrôlées
  côté application (la table `arrondissement` historique est en MyISAM).

---

## 6. Rôles et permissions

| Permission | admin | agent | lecteur |
|---|:---:|:---:|:---:|
| Voir villes / communes / arrondissements / départements | ✅ | ✅ | ✅ |
| Voir le registre | ✅ | ✅ | ✅ |
| Créer / modifier villes, communes et arrondissements | ✅ | ✅ | ❌ |
| Créer / modifier un profil du registre | ✅ | ✅ | ❌ |
| Supprimer villes, communes et arrondissements | ✅ | ❌ | ❌ |
| Supprimer un profil du registre | ✅ | ❌ | ❌ |
| Saisie multiple (villes / communes / arrondissements) | ✅ | ✅ | ❌ |
| Gérer les départements | ✅ | ❌ | ❌ |
| Consulter les rapports (dont le registre) | ✅ | ✅ | ✅ |
| Exporter en CSV (dont le registre) | ✅ | ✅ | ❌ |
| Gérer les utilisateurs | ✅ | ❌ | ❌ |

L'administrateur possède la permission spéciale `*`.

---

## 7. Routes principales

| Méthode | Route | Description |
|---|---|---|
| GET | `login` | Formulaire de connexion |
| POST | `login` | Traitement de la connexion |
| GET | `logout` | Déconnexion |
| GET | *(vide)* | Tableau de bord |
| GET | `villes` | Liste des villes |
| GET | `villes/creer` | Formulaire de création |
| GET | `villes/creer-multiple?nombre=N` | Assistant de saisie multiple de villes |
| POST | `villes` | Enregistrement |
| POST | `villes/multiple` | Enregistrement groupé de villes (transaction) |
| GET | `villes/{id}` | Fiche détaillée |
| GET | `villes/{id}/editer` | Formulaire de modification |
| POST | `villes/{id}` | Mise à jour |
| POST | `villes/{id}/supprimer` | Suppression |
| GET/POST | `communes/...` | Idem pour les communes |
| GET | `communes/creer-multiple?nombre=N` | Assistant de saisie multiple |
| POST | `communes/multiple` | Enregistrement groupé (transaction) |
| GET/POST | `arrondissements/...` | CRUD des arrondissements |
| GET | `arrondissements/creer-multiple?nombre=N` | Saisie multiple d'arrondissements |
| POST | `arrondissements/multiple` | Enregistrement groupé (transaction) |
| GET | `registres` | Liste paginée et filtrée du registre |
| GET | `registres/creer` | Formulaire de création d'un profil |
| POST | `registres` | Enregistrement d'un profil (capacité vérifiée) |
| GET | `registres/{id}/editer` | Formulaire de modification |
| POST | `registres/{id}` | Mise à jour (capacité vérifiée) |
| POST | `registres/{id}/supprimer` | Suppression (admin) |
| GET/POST | `departements/...` | Idem pour les départements |
| GET/POST | `utilisateurs/...` | Gestion des comptes (admin) |
| GET | `rapports` | Vue d'ensemble des rapports |
| GET | `rapports/departements` | Analyse par département |
| GET | `rapports/communes` | Analyse par commune |
| GET | `rapports/villes` | Analyse par ville |
| GET | `rapports/registre` | Analyse M / F / Junior du registre |
| GET | `export/villes` | Export CSV des villes |
| GET | `export/communes` | Export CSV des communes |
| GET | `export/arrondissements` | Export CSV des arrondissements |
| GET | `export/departements` | Export CSV des départements |
| GET | `export/registres` | Export CSV des profils du registre |
| GET | `api/communes?departement={id}` | Communes d'un département (JSON) |
| GET | `api/villes?q={terme}` | Recherche de villes (JSON) |

---

## 8. Sécurité

- **PDO** avec requêtes préparées uniquement (aucune concaténation de valeurs).
- **Mots de passe** hachés avec `password_hash()` / vérifiés avec `password_verify()`.
- **CSRF** : jeton vérifié sur toutes les requêtes mutantes (`POST`, `PUT`, `PATCH`, `DELETE`).
- **XSS** : toute sortie passe par `e()` (`htmlspecialchars`).
- **Sessions** : cookie `HttpOnly`, `SameSite=Lax`, `Secure` automatique en HTTPS,
  régénération de l'identifiant à la connexion, durée de vie de 8 h (configurable
  via `session.lifetime`).
- **Contrôle d'accès** centralisé dans le routeur (rôles et permissions).
- **Capacité du registre** : vérification transactionnelle avec verrou de ligne sur
  la ville (`SELECT ... FOR UPDATE`) à la création/modification d'un profil, lors de
  la réduction de population et lors de la suppression d'une ville.
- **Intégrité référentielle** : clé étrangère `registre.idville` et contrôles
  applicatifs sur les arrondissements liés aux villes.
- **Dossiers sensibles** (`app/`, `config/`, `database/`, `storage/`) inaccessibles
  depuis le navigateur.
- **Tri** : colonnes autorisées via liste blanche (pas d'injection `ORDER BY`).
- **En-têtes de sécurité** : `X-Frame-Options: SAMEORIGIN`,
  `X-Content-Type-Options: nosniff`,
  `Referrer-Policy: strict-origin-when-cross-origin` et HSTS en HTTPS, envoyés
  par `index.php` (le bloc équivalent de `.htaccess` s'activera aussi dès que
  `mod_headers` sera chargé).
- **Anti-force brute** : au-delà de 6 échecs de connexion en 60 s, la connexion
  est bloquée temporairement pour la session.
- **Exports CSV** : neutralisation de l'injection de formules Excel (cellules
  commençant par `=`, `+`, `-`, `@`), nom de fichier assaini, sortie en BOM UTF-8.
- **Front-end** : aucune dépendance CSS externe (Bootstrap retiré, ~188 Ko
  économisés par page), scripts non bloquants en fin de page, raccourci clavier
  « Aller au contenu principal », `aria-current` sur la navigation, `role`
  de notification sur les alertes et fermeture du menu utilisateur à `Échap`.
- **Identité visuelle** : vert émeraude `#047857` (couleur primaire, barre de
  navigation), jaune or `#fbc414` (barre du haut, onglet actif du menu) et rouge
  carmin `#c1121f` (burger mobile, session utilisateur, logo), tokens centralisés
  dans la section 1 de `public/css/app.css`.

> En production : passez `app.debug` à `false` et définissez un utilisateur MySQL
> dédié avec des droits limités.

---

## 9. Conventions de code

- PHP **7.4+** (`declare(strict_types=1)` en tête de chaque fichier).
- Autoload PSR-4 : namespace `App\` → dossier `app/`.
- Un contrôleur = une ressource ; les vues ne contiennent pas de logique métier.
- Accès aux données exclusivement via les modèles (`App\Models`).
- Helpers de vue : `e()`, `url()`, `asset()`, `csrf_field()`, `route_field()`,
  `old()`, `can()`, `active()`, `format_number()`, `icon()`, `paginate_links()`.
- Classes CSS préfixées `rg-`.

### Vérifications

```bash
# Syntaxe de tous les fichiers PHP
php -l index.php

# Migration (idempotente)
php database/migrate.php
```

---

## 10. Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| Erreur 404 sur toutes les pages | `RewriteBase` incorrect | Ajuster `.htaccess` ou passer `pretty_urls` à `false` |
| Page blanche | `app.debug = true` et erreur fatale | Consulter `storage/logs/app.log` |
| Les styles ne se chargent pas | Chemin `public/` | Vérifier `asset()` et la présence de `public/css/app.css` |
| Connexion impossible | Identifiants / base | Vérifier `config/config.php` et relancer `create_admin.php` |
| Export CSV qui ne démarre pas | Session expirée | Se reconnecter ; la session dure désormais 8 h |
| Liste de communes vide | Aucune commune pour ce département | Normal : le département sélectionné n'en contient pas |

---

## 11. Recette rapide

1. Connexion avec un compte administrateur.
2. Tableau de bord : indicateurs, graphiques et tableaux M / F / Junior visibles.
3. Villes : filtrer, trier, créer, consulter, modifier, supprimer.
4. Cascades : choisir une commune → le département se renseigne ; choisir un
   arrondissement → la commune et le département se renseignent.
5. Saisie multiple : créer 3 villes (dont une avec arrondissement), vérifier
   l'apparition progressive des fiches, puis l'enregistrement global et le message
   de confirmation.
6. Arrondissements : créer, modifier, filtrer, supprimer ; vérifier qu'une commune
   possédant des arrondissements et un arrondissement possédant des villes ne
   peuvent pas être supprimés.
7. Registre : créer des profils M, F et Junior dans une ville, vérifier les filtres,
   puis tenter de dépasser la population : l'enregistrement doit être refusé.
8. Vérifier qu'on ne peut pas abaisser la population sous le nombre de profils, ni
   supprimer une ville qui contient des profils.
9. Rapports : les vues par département, commune, ville et registre s'affichent et
   les totaux M / F / Junior sont cohérents.
10. Exports CSV : les cinq exports téléchargent un fichier qui s'ouvre correctement
    dans Excel.
11. Créer un compte `lecteur` : les boutons de création/modification disparaissent
    et les routes protégées renvoient une erreur 403.
