# RGPH5

Application web PHP de gestion et de consultation des populations par ville, commune et département. L'interface permet de saisir la population d'une ville, de rechercher une ville ou une commune, et de consulter les totaux par département.

## Fonctionnalités

- Connexion d'un utilisateur existant.
- Ajout d'une ville et de sa population, avec choix du département et de la commune.
- Recherche d'une ville et affichage de sa commune, de son département et de sa population.
- Recherche des villes d'une commune et calcul de leur population totale.
- Calcul des totaux par département et du total général.

## Technologies

- PHP avec PDO.
- MySQL ou MariaDB.
- Bootstrap 4.3.1 fourni dans `css/bootstrap.css`.

## Structure du projet

| Fichier ou dossier | Rôle |
| --- | --- |
| `index.php` | Page de connexion. |
| `connexion.php` | Ouverture de la session et connexion PDO à la base. |
| `ajoutpopulation.php` | Formulaire d'ajout d'une ville et de sa population. |
| `getcommune.php` | Chargement des communes d'un département pour le formulaire. |
| `getville.php` | Recherche dynamique d'une ville. |
| `populationparville.php` | Recherche et total de population par commune. |
| `populationgeneral.php` | Totaux par département et total général. |
| `deconnexion.php` | Fermeture de la session utilisateur. |
| `menu.php` | Menu de navigation. |
| `css/` | Feuilles de style, dont Bootstrap. |

## Installation locale avec WAMP

1. Placez le dossier du projet dans le répertoire `www` de WAMP et démarrez Apache et MySQL.
2. Créez la base MySQL `rgph1`.
3. Créez ou importez les tables et les données nécessaires. Le code attend les tables et colonnes suivantes :
   - `departement` : `iddepartement`, `nom` ;
   - `commune` : `idcommune`, `iddepartement`, `nom` ;
   - `ville` : `idcommune`, `nom`, `population` ;
   - `utilisateur` : `email`, `mdp`.
4. Configurez l'accès à la base dans `connexion.php` selon votre installation MySQL.
5. Ouvrez `http://localhost/rgph5/` dans votre navigateur et connectez-vous avec un compte déjà présent dans `utilisateur`.

Le dépôt ne contient actuellement ni script de création de la base ni jeu de données initial. Il faut donc préparer ces tables et créer un compte utilisateur avant de pouvoir utiliser l'application.

## Fichiers à exclure de GitHub

Le fichier `.gitignore` exclut les secrets et configurations locales (`.env`, fichiers `*.local.php`), les exports et bases locales, les journaux, caches, dépendances téléchargées, fichiers temporaires et fichiers propres aux éditeurs ou au système. Les fichiers PHP, CSS et ressources nécessaires à l'application doivent rester versionnés.

À ne pas publier :

- les fichiers de secrets et de configuration locale : `.env`, `.env.*` (sauf `.env.example`), `config.local.php`, `connexion.local.php` et `config/*.local.php` ;
- les sauvegardes de base locales : `database/dumps/`, `database/local/`, les fichiers `*.dump`, `*.dump.sql`, `*.sql.gz`, `*.sqlite`, `*.sqlite3` et `*.db` ;
- les journaux, caches et sorties générées : `logs/`, `tmp/`, `cache/`, `*.log`, `.phpunit.cache/`, `coverage/`, `build/` et `dist/` ;
- les dépendances installées et fichiers locaux d'éditeur ou de système : `vendor/`, `node_modules/`, `.vscode/`, `.idea/`, `.DS_Store`, `Thumbs.db` et `desktop.ini`.

Points relevés dans l'état actuel du projet :

- `connexion.php` contient les paramètres locaux `localhost`, base `rgph1`, utilisateur MySQL `root` et mot de passe vide. Ce fichier est requis par les pages de l'application : ne l'excluez pas simplement du dépôt, car l'application ne pourrait plus se connecter. Avant de publier le dépôt, remplacez ces valeurs par une configuration adaptée et ne publiez aucun vrai secret. Si vous séparez la configuration dans un fichier local, gardez ce fichier local hors de Git et fournissez une configuration modèle sans secret pour les autres développeurs.
- Aucun fichier `.env`, export SQL, sauvegarde de base ou fichier de mot de passe n'a été trouvé dans les fichiers actuels du projet.
- `css/bootstrap.css` est une dépendance statique utilisée par les pages et porte une mention de licence MIT ; elle n'est pas un fichier local à exclure.
- Le dossier `img/` est vide actuellement ; Git ne versionne pas les dossiers vides.

## Avant une mise en production

La configuration actuelle convient uniquement à un environnement local. Avant un déploiement, utilisez un compte MySQL dédié avec des droits limités, des requêtes PDO préparées pour les valeurs utilisateur, et des mots de passe stockés avec un hachage adapté. Le code actuel ne fournit pas ces protections.
