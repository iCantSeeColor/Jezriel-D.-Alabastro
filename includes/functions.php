<?php
// Escape output so user-entered text can never run as HTML or JavaScript (XSS).
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_profile(PDO $pdo): array
{
    $row = $pdo->query('SELECT * FROM profile LIMIT 1')->fetch();
    return $row ?: [];
}

function get_settings(PDO $pdo): array
{
    $row = $pdo->query('SELECT * FROM settings LIMIT 1')->fetch();
    return $row ?: [];
}

// Count rows in a table (table names are whitelisted, never taken from user input).
function count_rows(PDO $pdo, string $table): int
{
    $allowed = ['skills', 'projects', 'education', 'experience', 'achievements', 'messages'];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }
    return (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

function get_education(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM education ORDER BY id ASC')->fetchAll();
}