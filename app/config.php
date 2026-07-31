<?php
declare(strict_types=1);

const APP_NAME = 'Covoiturage Chorale';
const APP_URL = ''; // Définir APP_URL dans l'environnement en production (ex. https://covoiturage.example.org)
const DB_DRIVER = ''; // sqlite (local) ou mysql (MariaDB/MySQL) ; vide = valeur de l'environnement ou sqlite
const DB_PATH = __DIR__ . '/../data/chorale.sqlite';
const SMTP_HOST = '127.0.0.1'; // À surcharger avec SMTP_HOST en production
const SMTP_PORT = 1025;
const TOKEN_TTL = 86400;

date_default_timezone_set('Europe/Paris');

function env(string $name, string $default = ''): string { $value = getenv($name); return $value === false || $value === '' ? $default : $value; }
function app_url(): string { if ($url = env('APP_URL', APP_URL)) return rtrim($url, '/'); $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000'); }
