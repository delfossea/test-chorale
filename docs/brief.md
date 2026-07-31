# Brief — Plateforme de covoiturage pour chorale

## Objectif

Créer une petite application web privée qui aide les membres d'une chorale à se mettre en relation pour organiser leurs covoiturages vers les répétitions, concerts et autres rendez-vous du groupe.

L'application doit permettre de visualiser les membres disponibles, de leur adresser une demande de covoiturage et de suivre la réponse à cette demande, sans rendre les données personnelles accessibles au public.

## Contexte et contraintes techniques initiales

- Application PHP, pensée au départ pour rester simple à installer et exécuter localement.
- Une première version peut être concentrée dans `index.php`; des fichiers PHP supplémentaires sont acceptés si cela rend l'authentification, l'administration et les actions métier plus sûres et maintenables.
- Base de données locale SQLite (fichier `.sqlite` hors de l'accès public si l'application est ensuite déployée).
- Carte basée sur OpenStreetMap, via une bibliothèque gratuite telle que Leaflet.
- Envoi d'e-mails de test via Mailpit en environnement local, sans livraison vers de vraies boîtes mail.
- Interface en français, adaptée aussi bien à un usage ordinateur que mobile.

## Rôles

### Administrateur

- Gère les membres autorisés à rejoindre la chorale sur la plateforme.
- Invite un membre par e-mail.
- Consulte, modifie, désactive ou supprime les comptes et informations des membres lorsque nécessaire.
- Peut suivre les demandes de covoiturage, notamment en cas de besoin de modération ou d'assistance.

### Membre de la chorale

- S'inscrit uniquement après invitation de l'administrateur.
- Se connecte avec son adresse e-mail et son mot de passe.
- Renseigne et met à jour son prénom, son nom, son adresse physique et ses coordonnées utiles au covoiturage.
- Indique sa disponibilité (conducteur, passager, ou les deux) et, si pertinent, des précisions comme les horaires possibles ou le nombre de places.
- Consulte la liste et la carte des autres membres disponibles.
- Envoie une demande de covoiturage à un autre membre.
- Reçoit, répond et ajuste les propositions reçues.

## Parcours fonctionnels

### Invitation et création de compte

1. L'administrateur crée une invitation à partir de l'adresse e-mail d'un futur membre.
2. L'application envoie un e-mail Mailpit contenant un lien d'invitation à usage limité dans le temps.
3. Le destinataire ouvre le lien, complète son profil et choisit son mot de passe.
4. Son compte est activé et il peut se connecter.

### Authentification et récupération de mot de passe

- Une page de connexion demande l'adresse e-mail et le mot de passe.
- Les mots de passe sont stockés de façon sécurisée avec les fonctions PHP prévues à cet effet (`password_hash` / `password_verify`).
- En cas d'oubli, un membre saisit son adresse e-mail.
- L'application envoie, dans Mailpit en local, un lien de réinitialisation temporaire.
- Le lien permet de définir un nouveau mot de passe puis d'accéder au compte.

### Profil et disponibilités

Chaque membre peut gérer au minimum :

- Prénom et nom ;
- Adresse e-mail ;
- Numéro de téléphone facultatif ;
- Adresse physique ;
- Position géographique associée à l'adresse (géocodée pour la carte, avec possibilité de correction) ;
- Statut : propose une place, cherche une place, ou les deux ;
- Informations pratiques facultatives : nombre de places, zone/détour acceptable, horaires ou commentaire.

Les coordonnées exactes et les coordonnées de contact ne doivent être visibles que par les membres authentifiés de la chorale. Une évolution possible serait d'afficher une position approximative sur la carte avant l'acceptation d'une demande.

### Recherche de covoiturage

- Une page principale liste les membres actuellement disponibles et leurs informations de covoiturage pertinentes.
- Une carte OpenStreetMap affiche les membres avec des marqueurs cliquables.
- Des filtres simples sont souhaités : conducteur/passager, disponibilité, éventuellement zone ou distance.
- Depuis une fiche membre, un bouton « Demander un covoiturage » ouvre un formulaire.

Le formulaire de demande doit permettre d'indiquer au moins :

- Le rendez-vous concerné (répétition, concert, date/heure) ;
- Le besoin ou la proposition ;
- Un lieu et une heure de passage souhaités ;
- Un message libre.

### Notifications et réponse à une demande

1. Lorsqu'un membre envoie une demande, le destinataire reçoit un e-mail de notification dans Mailpit.
2. L'e-mail contient les détails principaux et un lien sécurisé vers la demande sur la plateforme.
3. Une fois connecté, le destinataire peut : accepter, refuser ou proposer un ajustement (par exemple une autre heure ou un autre point de passage).
4. Le demandeur reçoit à son tour une notification e-mail avec la réponse et les éventuels ajustements.
5. La demande conserve un statut, par exemple : en attente, acceptée, refusée, contre-proposition, annulée.

## Panneau d'administration

Le panneau d'administration, accessible uniquement au rôle administrateur, doit inclure :

- La liste des membres et l'état de leur compte (invité, actif, désactivé) ;
- La création et le renvoi d'invitations ;
- L'édition ou la désactivation d'un membre ;
- Une vue des demandes de covoiturage et de leur statut ;
- À terme, une gestion des événements de la chorale pour préremplir les demandes de covoiturage.

## Données principales à prévoir

- **Utilisateurs** : identité, e-mail, mot de passe haché, rôle, statut, dates de création et de dernière connexion.
- **Profils de covoiturage** : adresse, latitude, longitude, disponibilités, places, préférences et commentaire.
- **Invitations** : e-mail, jeton sécurisé, expiration, statut, créateur.
- **Réinitialisations de mot de passe** : jeton sécurisé, expiration, utilisateur concerné, consommation du jeton.
- **Demandes de covoiturage** : émetteur, destinataire, événement/date, détails de trajet, message, statut et réponses.
- **Événements** : répétitions et concerts créés et administrés par l'administrateur ; chaque demande est rattachée à l'un d'eux.
- **Journal de notifications** (souhaitable) : type, destinataire, date d'envoi et statut technique.

## Sécurité et confidentialité

- Aucune page de consultation des membres, de carte ou de demande ne doit être accessible sans session authentifiée.
- Seuls les administrateurs accèdent aux fonctions d'administration.
- Les jetons d'invitation et de réinitialisation doivent être aléatoires, à usage unique et expirer.
- Les entrées utilisateur doivent être validées côté serveur et échappées à l'affichage pour limiter les injections et XSS.
- Les actions sensibles doivent être protégées contre les requêtes CSRF.
- La base SQLite, les secrets et la configuration Mailpit ne doivent pas être exposés par le serveur web.
- La carte n'affiche qu'une position ou une zone approximative avant la mise en relation, jamais l'adresse exacte.
- Dans le cadre de cette petite chorale, les membres authentifiés peuvent consulter les adresses e-mail et numéros de téléphone des personnes impliquées dans le covoiturage. La collecte de ces coordonnées doit néanmoins être expliquée aux membres.

## Première version livrable (MVP)

1. Installation locale PHP + SQLite et structure de base de données initiale.
2. Connexion, invitation par administrateur, inscription par invitation et réinitialisation de mot de passe avec Mailpit.
3. Gestion du profil et du statut conducteur/passager.
4. Annuaire privé avec carte OpenStreetMap/Leaflet.
5. Création d'une demande de covoiturage et notifications e-mail de test.
6. Acceptation, refus et contre-proposition avec notification au demandeur.
7. Panneau administrateur minimal de gestion des membres, invitations et événements.

## Décisions de périmètre validées

- Chaque demande de covoiturage concerne obligatoirement un événement précis (répétition ou concert).
- Le calendrier des répétitions et des concerts est géré par l'administrateur.
- La carte montre uniquement une zone approximative pour chaque membre avant la mise en relation ; l'adresse exacte n'est pas affichée sur la carte.
- Le profil comprend aussi un numéro de téléphone facultatif.
- Dans ce contexte de petite chorale, les coordonnées de contact peuvent être consultées par les membres authentifiés impliqués dans le covoiturage.
- L'application est conçue pour une seule chorale, sans gestion multi-groupes.
- La gestion des comptes inactifs, des anciens membres et de la conservation ou suppression de leurs données est volontairement hors périmètre de cette première version, afin de livrer rapidement une solution utilisable cette semaine.
