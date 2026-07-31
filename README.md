# Covoiturage Chorale

Application PHP privée de mise en relation pour le covoiturage d’une chorale.

## Lancement local (SQLite + Mailpit)

Prérequis : PHP 8.2+ avec `pdo_sqlite`, et [Mailpit](https://mailpit.axllent.org/).

```sh
mailpit
php -S localhost:8000 -t public
```

Ouvrez `http://localhost:8000`. La base SQLite est créée automatiquement dans `data/` au premier accès. Les e-mails de test sont visibles dans Mailpit sur `http://localhost:8025`.

Administrateur initial : `admin@chorale.test` / `ChangeMe!2026`. Changez ce mot de passe avant tout usage non local.

Pour remettre les données locales à zéro, supprimez uniquement `data/chorale.sqlite` puis rechargez l’application.

## Configuration production (MariaDB/MySQL)

Définissez les variables d’environnement du serveur web, sans les mettre dans Git :

```sh
APP_URL=https://covoiturage.example.org
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=chorale
DB_USER=chorale
DB_PASSWORD=un-secret-fort
SMTP_HOST=mail.example.org
SMTP_PORT=25
```

La première requête initialise les tables MariaDB/MySQL. Utilisez un compte SQL limité à cette base. Pour le développement SQLite, ne définissez pas `DB_DRIVER` (ou définissez `sqlite`) ; `DB_PATH` permet de choisir un autre chemin hors de la racine web.

Pour un vrai service SMTP, prévoyez un relais accessible sans authentification SMTP ou adaptez le client SMTP pour ajouter TLS/authentification. Mailpit fonctionne directement avec la configuration SMTP par défaut (`127.0.0.1:1025`).

## Sécurité

Servez exclusivement le dossier `public/` comme racine du site, activez HTTPS en production et gardez les variables d’environnement ainsi que la base SQLite hors de la racine web.
