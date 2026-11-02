<?php
/**
 * Migration idempotente de la base RGPH5.
 *
 * Usage : php database/migrate.php
 *
 * Peut être relancée sans risque : chaque modification est vérifiée avant
 * d'être appliquée.
 */

declare(strict_types=1);

// Ce script est réservé à la ligne de commande.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Accès interdit.');
}

$config = require __DIR__ . '/../config/config.php';
$dbConfig = $config['database'];

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $dbConfig['host'],
    (int) $dbConfig['port'],
    $dbConfig['database'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "Connexion impossible : " . $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * @return bool
 */
function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table'
    );
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * @return bool
 */
function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * @return bool
 */
function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index'
    );
    $stmt->execute(['table' => $table, 'index' => $index]);

    return (int) $stmt->fetchColumn() > 0;
}

$applied = [];

// --- Table `arrondissement` -------------------------------------------------
$tableExists = (int) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'arrondissement'"
)->fetchColumn();

if ($tableExists === 0) {
    $pdo->exec(
        "CREATE TABLE arrondissement (
            idarrondissement INT NOT NULL AUTO_INCREMENT,
            idcommune INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            PRIMARY KEY (idarrondissement),
            KEY idx_arrondissement_commune (idcommune),
            KEY idx_arrondissement_nom (nom),
            CONSTRAINT arrondissement_commune_fk
                FOREIGN KEY (idcommune) REFERENCES commune (idcommune)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
    );
    $applied[] = 'Table arrondissement créée.';
}

// --- Rattachement des villes à un arrondissement ----------------------------
if (!columnExists($pdo, 'ville', 'idarrondissement')) {
    $pdo->exec(
        "ALTER TABLE ville
         ADD COLUMN idarrondissement INT NULL AFTER idcommune,
         ADD KEY idx_ville_arrondissement (idarrondissement)"
    );
    $applied[] = 'Colonne ville.idarrondissement ajoutée.';
}

// --- Table `registre` -------------------------------------------------------
if (!tableExists($pdo, 'registre')) {
    $pdo->exec(
        "CREATE TABLE registre (
            idregistre INT NOT NULL AUTO_INCREMENT,
            idville INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            prenom VARCHAR(255) NOT NULL,
            date_ref DATE NOT NULL,
            genre ENUM('M','F') NOT NULL,
            PRIMARY KEY (idregistre),
            KEY idx_registre_ville (idville),
            KEY idx_registre_nom (nom),
            KEY idx_registre_prenom (prenom),
            KEY idx_registre_date_ref (date_ref),
            KEY idx_registre_genre (genre),
            CONSTRAINT registre_ville_fk
                FOREIGN KEY (idville) REFERENCES ville (idville)
                ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
    );
    $applied[] = 'Table registre créée.';
}

// --- Colonne `role` ---------------------------------------------------------
if (!columnExists($pdo, 'utilisateur', 'role')) {
    $pdo->exec(
        "ALTER TABLE utilisateur
         ADD COLUMN role ENUM('admin','agent','lecteur') NOT NULL DEFAULT 'agent' AFTER mdp"
    );
    $applied[] = 'Colonne utilisateur.role ajoutée.';
}

// --- Colonne `actif` --------------------------------------------------------
if (!columnExists($pdo, 'utilisateur', 'actif')) {
    $pdo->exec(
        "ALTER TABLE utilisateur
         ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1 AFTER role"
    );
    $applied[] = 'Colonne utilisateur.actif ajoutée.';
}

// --- Garantir la présence d'au moins un administrateur ---------------------
$admins = (int) $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'admin'")->fetchColumn();

if ($admins === 0) {
    $firstId = $pdo->query('SELECT idutilisateur FROM utilisateur ORDER BY idutilisateur ASC LIMIT 1')->fetchColumn();

    if ($firstId !== false) {
        $update = $pdo->prepare("UPDATE utilisateur SET role = 'admin' WHERE idutilisateur = :id");
        $update->execute(['id' => (int) $firstId]);
        $applied[] = 'Utilisateur #' . (int) $firstId . ' promu administrateur.';
    }
}

// --- Index de performance ---------------------------------------------------
$indexes = [
    ['ville', 'idx_ville_nom', 'CREATE INDEX idx_ville_nom ON ville (nom)'],
    ['ville', 'idx_ville_population', 'CREATE INDEX idx_ville_population ON ville (population)'],
    ['ville', 'idx_ville_arrondissement', 'CREATE INDEX idx_ville_arrondissement ON ville (idarrondissement)'],
    ['commune', 'idx_commune_nom', 'CREATE INDEX idx_commune_nom ON commune (nom)'],
    ['departement', 'idx_departement_nom', 'CREATE INDEX idx_departement_nom ON departement (nom)'],
    ['registre', 'idx_registre_nom', 'CREATE INDEX idx_registre_nom ON registre (nom)'],
    ['registre', 'idx_registre_prenom', 'CREATE INDEX idx_registre_prenom ON registre (prenom)'],
    ['registre', 'idx_registre_date_ref', 'CREATE INDEX idx_registre_date_ref ON registre (date_ref)'],
    ['registre', 'idx_registre_genre', 'CREATE INDEX idx_registre_genre ON registre (genre)'],
];

foreach ($indexes as $index) {
    [$table, $name, $sql] = $index;

    if (!indexExists($pdo, $table, $name)) {
        $pdo->exec($sql);
        $applied[] = 'Index ' . $name . ' créé sur ' . $table . '.';
    }
}

if ($applied === []) {
    echo "Aucune migration nécessaire : la base est déjà à jour." . PHP_EOL;
    exit(0);
}

echo "Migrations appliquées :" . PHP_EOL;
foreach ($applied as $line) {
    echo "  - " . $line . PHP_EOL;
}
echo "Terminé." . PHP_EOL;
