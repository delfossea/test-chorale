# Plan de réalisation — MVP covoiturage chorale

## Principes de réalisation

Le MVP privilégie une application PHP simple, rapide à lancer localement et sans dépendance applicative lourde : PHP avec PDO/SQLite, HTML/CSS/JavaScript léger, Leaflet chargé depuis CDN pour la carte et Mailpit pour les e-mails de test.

Même si le point d'entrée peut être `index.php`, le code sera séparé dans quelques fichiers PHP (configuration, base de données, authentification, pages et actions) afin de conserver des contrôles d'accès et des formulaires sûrs. Il s'agit d'une application mono-chorale.

## Étape 1 — Initialisation locale et socle technique

- Créer l'arborescence du projet (`public` ou point d'entrée, données SQLite, fichiers d'aide et styles).
- Ajouter une configuration locale documentée : URL de l'application, chemin de la base SQLite et paramètres SMTP Mailpit (`localhost:1025`).
- Prévoir un script ou une initialisation automatique de la base de données.
- Ajouter des données de démonstration minimales, dont un administrateur initial, afin de tester le parcours complet.
- Documenter le lancement avec le serveur PHP intégré et l'accès à l'interface Mailpit (habituellement `http://localhost:8025`).

**Vérification :** l'application démarre localement, SQLite est créé et un administrateur peut se connecter.

## Étape 2 — Modèle de données SQLite

Créer les tables et index nécessaires :

- `users` : identité, e-mail unique, téléphone, mot de passe haché, rôle (`admin` / `member`), état du compte et dates utiles ;
- `profiles` : adresse postale, latitude/longitude, coordonnées approximatives pour la carte, statut conducteur/passager, places et commentaire ;
- `events` : titre, type (répétition/concert), date et heure, lieu, informations complémentaires ;
- `invitations` : e-mail, jeton haché, expiration, statut et administrateur émetteur ;
- `password_resets` : jeton haché, expiration, utilisateur et date d'utilisation ;
- `carpool_requests` : événement obligatoire, demandeur, destinataire, détails de passage, message, statut et dates ;
- `request_responses` ou champs de réponse dédiés : décision, message d'ajustement, horaire/lieu proposés ;
- `notification_log` (si utile au diagnostic) : e-mail généré, destinataire, type et date.

Prévoir les contraintes d'intégrité (e-mail unique, clés étrangères, événements obligatoires pour une demande, valeurs de statut limitées) et les index sur les recherches fréquentes.

**Vérification :** la création de la base est rejouable sans erreur et toutes les relations du brief sont représentées.

## Étape 3 — Fondations de sécurité et d'interface

- Mettre en place les sessions PHP et les gardes d'accès : membre connecté, puis administrateur.
- Centraliser les fonctions de validation, échappement HTML, génération/contrôle des jetons CSRF et messages utilisateur.
- Utiliser `password_hash` et `password_verify` pour les mots de passe.
- Définir une mise en page française, sobre et responsive : navigation, alertes, formulaires et états vides.
- Vérifier que le fichier SQLite et toute configuration sensible ne sont pas servis directement par le serveur web.

**Vérification :** les URL privées redirigent vers la connexion et les actions POST échouent sans jeton CSRF valide.

## Étape 4 — Administration, événements et invitations

- Créer le panneau administrateur avec une liste des membres et leur état (invité, actif, désactivé).
- Ajouter la création, modification et désactivation d'un membre ainsi que la création et le renvoi d'invitations.
- Implémenter l'envoi d'un e-mail d'invitation via Mailpit avec lien contenant un jeton à usage unique et expirant.
- Créer la gestion des événements : ajouter, modifier, lister et, si nécessaire, annuler une répétition ou un concert.

**Vérification :** un administrateur crée un événement puis invite un nouveau membre ; l'e-mail apparaît dans Mailpit et le lien est utilisable une seule fois.

## Étape 5 — Inscription, connexion et mot de passe oublié

- Permettre à un invité de définir son identité, son téléphone facultatif et son mot de passe depuis son lien d'invitation.
- Ajouter les écrans de connexion et de déconnexion.
- Ajouter la demande de réinitialisation et l'écran de choix d'un nouveau mot de passe.
- Envoyer le lien de réinitialisation dans Mailpit, avec jeton expirant et à usage unique.

**Vérification :** un nouveau membre peut finaliser son compte, se connecter, réinitialiser son mot de passe puis se reconnecter.

## Étape 6 — Profil, disponibilités et position approximative

- Créer la page « Mon profil » pour modifier coordonnées, adresse et disponibilités.
- Proposer les statuts conducteur, passager ou les deux, avec nombre de places, zone/détour et commentaire.
- Géocoder l'adresse au moment de l'enregistrement (via un service OpenStreetMap/Nominatim, en respectant sa politique d'utilisation) ou proposer une saisie/correction de position si le géocodage échoue.
- Calculer et enregistrer une position approximative distincte de la position précise, utilisée uniquement par la carte publique aux membres.

**Vérification :** le profil enregistré est retrouvé au rechargement et la carte ne peut utiliser que les coordonnées approximatives.

## Étape 7 — Annuaire privé et carte

- Construire la page d'accueil des membres connectés : liste des personnes disponibles, résumé de leurs profils et filtres conducteur/passager.
- Ajouter la carte Leaflet/OpenStreetMap avec marqueurs correspondant aux positions approximatives.
- Proposer une fiche membre permettant de consulter les coordonnées de contact des personnes concernées et d'ouvrir une demande de covoiturage.
- Prévoir des messages utiles lorsqu'il n'y a aucun événement à venir ou aucune personne disponible.

**Vérification :** la liste, les filtres et les marqueurs correspondent aux profils, sans exposer l'adresse exacte sur la carte.

## Étape 8 — Demandes de covoiturage et réponses

- Créer le formulaire de demande avec sélection obligatoire d'un événement, lieu/heure de passage et message libre.
- Enregistrer la demande avec le statut `en_attente` et envoyer une notification Mailpit au destinataire.
- Créer une page de détail réservée aux deux personnes concernées (et aux administrateurs).
- Permettre au destinataire d'accepter, refuser ou proposer un ajustement ; conserver une trace de la réponse et modifier le statut.
- Notifier le demandeur par e-mail de chaque réponse.
- Ajouter une vue « Mes demandes » pour chaque membre et une vue de suivi dans l'administration.

**Vérification :** le scénario complet « demande → notification → contre-proposition → notification → acceptation » fonctionne de bout en bout avec Mailpit.

## Étape 9 — Contrôles finaux et livraison locale

- Tester les parcours administrateur, membre invité, membre connecté et personne non authentifiée.
- Vérifier les cas d'erreur : liens expirés/utilisés, e-mail inconnu, événement absent, demande à soi-même, droits insuffisants et formulaire invalide.
- Vérifier visuellement l'interface sur largeur mobile et ordinateur.
- Revoir les libellés français, les informations de confidentialité et les messages e-mail.
- Compléter un `README.md` : prérequis PHP/SQLite/Mailpit, démarrage, compte administrateur de démonstration et procédure de réinitialisation des données de test.

**Critère de fin :** sur une installation locale neuve, un administrateur peut créer un événement, inviter deux membres, et ces membres peuvent compléter leur profil, se repérer approximativement sur la carte, échanger puis confirmer une demande de covoiturage avec les e-mails visibles dans Mailpit.

## Hors périmètre du MVP

- Gestion de plusieurs chorales ou organisations.
- Politique d'archivage, anonymisation ou suppression des anciens comptes.
- Envoi d'e-mails vers de vraies boîtes mail et configuration de production.
- Optimisation d'itinéraires, calcul de distance avancé ou partage de trajets en temps réel.
- Gestion complexe des récurrences d'événements et calendrier externe.
