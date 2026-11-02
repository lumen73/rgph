<?php
declare(strict_types=1);

namespace App\Core;

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Accès interdit.');
}

/**
 * Contrôleur de base : factorise les réponses courantes
 * (rendu de vue, redirection, messages flash, export CSV, JSON).
 */
abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $data = array_merge([
            'pageTitle'   => (string) Config::get('app.name'),
            'currentUser' => Auth::user(),
        ], $data);

        View::render($view, $data, $layout);
    }

    /**
     * @param array<string, mixed> $query
     */
    protected function redirect(string $path, array $query = []): void
    {
        Response::redirect(url($path, $query));
    }

    protected function back(): void
    {
        $current = Request::current();
        $referer = $current !== null ? $current->referer() : null;

        Response::redirect($referer !== null && $referer !== '' ? $referer : url(''));
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * Enregistre un message d'erreur, met en mémoire la saisie puis redirige.
     *
     * @param array<string, mixed> $oldInput
     * @param array<string, mixed> $query
     */
    protected function fail(string $path, string $message, array $oldInput = [], array $query = []): void
    {
        $this->flash('danger', $message);

        if ($oldInput !== []) {
            Session::flashInput($oldInput);
        }

        $this->redirect($path, $query);
    }

    protected function succeed(string $path, string $message): void
    {
        $this->flash('success', $message);
        $this->redirect($path);
    }

    /**
     * @param mixed $data
     */
    protected function json($data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    /**
     * Envoie un fichier CSV téléchargeable.
     *
     * Le contenu est mis en mémoire afin de pouvoir envoyer un Content-Length
     * fiable : certains navigateurs interrompent le téléchargement lorsque la
     * taille est inconnue. Le BOM UTF-8 et le séparateur « ; » garantissent un
     * affichage correct des accents dans Excel.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $headers
     */
    protected function streamCsv(string $filename, array $headers, array $rows): void
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            Response::text('Export impossible.', 500);
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ';', '"', '\\');

        foreach ($rows as $row) {
            fputcsv(
                $handle,
                array_map(static function ($cell) {
                    // Neutralise l'injection de formule (Excel / LibreOffice) :
                    // toute cellule texte commençant par = + - @ est préfixée
                    // d'une apostrophe (les nombres négatifs restent lisibles).
                    if (is_string($cell) && $cell !== ''
                        && in_array($cell[0], ['=', '+', '@'], true)) {
                        return "'" . $cell;
                    }
                    if (is_string($cell) && $cell !== '' && $cell[0] === '-'
                        && !is_numeric($cell)) {
                        return "'" . $cell;
                    }
                    return $cell;
                }, array_values($row)),
                ';',
                '"',
                '\\'
            );
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        // Nom de fichier sûr (ASCII, sans guillemets ni saut de ligne).
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.csv';

        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Content-Type-Options: nosniff');
        }

        echo $content;

        exit;
    }
}