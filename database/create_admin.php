<?php
/**
 * Crée (ou réinitialise) un compte administrateur.
 *
 * Usage :
 *   php database/create_admin.php
 *   php database/create_admin.php admin@exemple.org "motdepasse"
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Accès interdit.');
}

$config   = require __DIR__ . '/../config/config.php';
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
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'Connexion impossible : ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$email = $argv[1] ?? '';
if ($email === '') {
    echo 'Adresse e-mail de l\'administrateur : ';
    $email = trim((string) fgets(STDIN));
}

$password = $argv[2] ?? '';
if ($password === '') {
    echo 'Mot de passe (6 caractères minimum) : ';
    $password = trim((string) fgets(STDIN));
}

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, 'Adresse e-mail invalide.' . PHP_EOL);
    exit(1);
}

if (mb_strlen($password) < 6) {
    fwrite(STDERR, 'Le mot de passe doit contenir au moins 6 caractères.' . PHP_EOL);
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT idutilisateur FROM utilisateur WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$existingId = $stmt->fetchColumn();

if ($existingId !== false) {
    $update = $pdo->prepare(
        "UPDATE utilisateur SET mdp = :mdp, role = 'admin', actif = 1 WHERE idutilisateur = :id"
    );
    $update->execute(['mdp' => $hash, 'id' => (int) $existingId]);
    echo 'Compte administrateur mis à jour : ' . $email . PHP_EOL;
} else {
    $insert = $pdo->prepare(
        "INSERT INTO utilisateur (nom, email, mdp, role, actif)
         VALUES (:nom, :email, :mdp, 'admin', 1)"
    );
    $insert->execute([
        'nom'   => 'Administrateur',
        'email' => $email,
        'mdp'   => $hash,
    ]);
    echo 'Compte administrateur créé : ' . $email . PHP_EOL;
}

echo 'Vous pouvez maintenant vous connecter à l\'application.' . PHP_EOL;
