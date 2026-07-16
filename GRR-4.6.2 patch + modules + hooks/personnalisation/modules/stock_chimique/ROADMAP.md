# Roadmap du module Stock chimique

Document de référence pour le développement progressif du module local GRR
`stock_chimique`.

- Projet cible : GRR 4.6.2 avec patchs, modules et hooks
- Environnement de recette : Synology DSM 7, Apache 2.4, PHP 8.4,
  MariaDB 10 et phpMyAdmin
- Création du document : 19 juin 2026
- Dernière mise à jour : 15 juillet 2026
- État global : version 0.12.0 implémentée, validation FDS depuis fiche produit, édition admin des documents et annulation admin des inventaires ouverts, en attente de recette NAS

## Règles de développement

- Ne pas modifier le cœur de GRR si les hooks existants suffisent.
- Respecter les conventions des modules locaux déjà présents.
- Privilégier des changements petits, réversibles et testables.
- Séparer le catalogue des produits des contenants et lots réellement stockés.
- Ne jamais recalculer ou corriger silencieusement un ancien mouvement de stock.
- Conserver les anciennes versions des FDS.
- Contrôler les droits avant toute consultation, modification ou récupération
  de document.
- À la fin de chaque étape :
  1. mettre à jour ce document ;
  2. produire une synthèse des changements ;
  3. fournir un protocole de test sur le NAS ;
  4. attendre la validation avant de commencer l’étape suivante.

## Objectif du module

Le module doit permettre de connaître les produits chimiques présents, leurs
quantités, leurs emplacements et leurs documents de sécurité. Il doit conserver
un historique fiable des mouvements et signaler les situations qui nécessitent
une action.

Le module aide à la gestion du risque chimique, mais ne remplace pas
l’évaluation réglementaire du risque, la validation des classifications ni les
procédures de sécurité de l’établissement.

## Périmètre fonctionnel

### MVP

- Catalogue des produits chimiques.
- Gestion des fournisseurs et références commerciales.
- Gestion des emplacements de stockage.
- Gestion des contenants ou lots.
- Entrées, sorties, transferts, corrections et éliminations.
- Quantité disponible par produit et emplacement.
- Ajout, consultation et archivage des FDS.
- Ajout d’autres documents utiles.
- Alertes pour stock faible, péremption et FDS manquante ou obsolète.
- Recherche, filtres et export CSV.
- Administration du module et gestion des droits.
- Journalisation des opérations sensibles.

### Évolutions possibles après le MVP

- QR codes ou codes-barres sur les contenants.
- Inventaires périodiques guidés.
- Alertes par courrier électronique et tâche planifiée Synology.
- Contrôle assisté des incompatibilités de stockage.
- Import initial depuis un fichier CSV. Implémenté dans la version 0.8.0.
- Impression d’étiquettes internes.
- Liaison avec un outil spécialisé d’évaluation du risque chimique.

### Hors périmètre initial

- Rédaction automatique ou validation réglementaire des FDS.
- Détermination automatique de la dangerosité d’un produit.
- Gestion du transport de matières dangereuses.
- Calcul réglementaire complet des expositions professionnelles.
- Remplacement d’un logiciel spécialisé comme Seirich.

## Principes d’architecture

Le module sera autonome dans :

`personnalisation/modules/stock_chimique/`

Architecture envisagée :

- `infos.php` : déclaration et versions du module ;
- `installation.php` : activation, paramètres initiaux et migrations ;
- `controleur.php` : intégration aux hooks GRR ;
- `admin.php` : configuration et diagnostic ;
- `download.php` : téléchargement sécurisé des documents ;
- `index.php` : éventuel point d’entrée autonome ;
- `lib/Config.php` : paramètres du module ;
- `lib/Repository.php` : accès aux données ;
- `lib/Renderer.php` : contrôleur léger et rendu HTML ;
- `lib/bootstrap.php` : initialisation des pages autonomes ;
- `storage/documents/` : stockage protégé des fichiers ;
- `README.md` : installation, versions et procédures de recette ;
- `SPECIFICATIONS.md` : règles fonctionnelles validées ;
- `SCHEMA_SQL.md` : schéma de données et stratégie de migration ;
- `ROADMAP.md` : présent document.

Le module `gestion_materiel` sert de référence pour l’intégration aux hooks,
les droits, les diagnostics et le stockage documentaire. Son code ne sera pas
refactorisé pendant le MVP. Une mutualisation ne pourra être étudiée qu’après
stabilisation des deux modules.

## Modèle de données envisagé

La proposition détaillée des champs, index et migrations est disponible dans
`SCHEMA_SQL.md`. Les règles fonctionnelles associées sont décrites dans
`SPECIFICATIONS.md`. Leur validation clôturera l’étape 0.

### Produit

Table envisagée : `*_stock_chimique_produit`

- nom commercial ;
- référence interne ;
- fournisseur et référence fournisseur ;
- fabricant ;
- numéro CAS, CE et UFI, lorsqu’ils sont applicables ;
- état physique ;
- unité de gestion par défaut ;
- catégorie ou famille ;
- pictogrammes CLP ;
- mentions de danger H et conseils de prudence P ;
- indicateurs CMR et autres informations de vigilance ;
- seuil de stock minimal ;
- statut actif ou archivé ;
- auteur et dates de création et modification.

Les données de danger saisies dans la fiche produit devront rester présentées
comme des informations administratives issues de l’étiquette ou de la FDS.

### Emplacement

Table envisagée : `*_stock_chimique_emplacement`

- nom ;
- type : site, bâtiment, local, armoire, réfrigérateur ou étagère ;
- emplacement parent facultatif ;
- description ;
- responsable ;
- statut actif ou archivé.

### Contenant ou lot

Table envisagée : `*_stock_chimique_contenant`

- produit associé ;
- numéro de lot ;
- code interne unique ;
- emplacement courant ;
- quantité initiale ;
- quantité courante ;
- unité ;
- date de réception ;
- date d’ouverture ;
- date de péremption ;
- fournisseur ;
- statut : disponible, vide, périmé, éliminé ou archivé ;
- auteur et dates de création et modification.

Un produit peut posséder plusieurs contenants, lots, péremptions et
emplacements.

### Mouvement

Table envisagée : `*_stock_chimique_mouvement`

- contenant associé ;
- type : réception, consommation, transfert, correction, élimination ou retour ;
- quantité et unité ;
- emplacement de départ et d’arrivée selon le type ;
- motif ou commentaire ;
- date effective ;
- auteur et date d’enregistrement.

Les mouvements validés seront immuables. Une erreur sera compensée par un
nouveau mouvement explicitement identifié comme correction. Le choix du moteur
de table devra tenir compte du besoin de transactions et des mouvements
simultanés ; la préférence technique sera InnoDB si sa coexistence avec GRR est
validée sur MariaDB 10.

### Document

Table envisagée : `*_stock_chimique_document`

- produit associé ;
- type : FDS, certificat d’analyse, fiche technique, mode opératoire, notice,
  étiquette ou autre ;
- langue ;
- fournisseur ou émetteur ;
- date ou numéro de révision ;
- statut de version courante ou archivée ;
- description ;
- nom original ;
- nom physique aléatoire ;
- type MIME ;
- taille ;
- empreinte SHA-256 ;
- auteur et date de dépôt.

Les anciennes FDS ne seront pas supprimées lors du dépôt d’une nouvelle
version. Elles seront archivées et resteront consultables selon les droits.

### Journal

Table envisagée : `*_stock_chimique_journal`

- type d’événement ;
- objet et identifiant concernés ;
- utilisateur ;
- date ;
- résumé non sensible de l’opération.

Le journal devra couvrir au minimum les créations, modifications, archivages,
mouvements, dépôts et suppressions de documents.

## Rôles envisagés

- Administrateur général GRR : configuration, diagnostic et tous les droits.
- Gestionnaire du module : catalogue, emplacements, stocks, documents et
  inventaires.
- Opérateur : mouvements de stock et consultation des informations autorisées.
- Lecteur : consultation du catalogue, du stock et des documents autorisés.

Le modèle exact des droits sera validé à l’étape 0. Les contrôles devront être
effectués côté serveur, même si l’interface masque les actions interdites.

## Sécurité documentaire

- Stockage hors accès HTTP direct ou protégé par configuration Apache.
- Nom physique aléatoire sans reprendre le nom fourni par l’utilisateur.
- Liste blanche d’extensions et taille maximale configurables.
- Vérification de la taille réellement enregistrée.
- Détection du type MIME avec `fileinfo`.
- Calcul d’une empreinte SHA-256.
- Téléchargement uniquement par un script contrôlant la session et les droits.
- En-têtes HTTP protégeant le nom du fichier et empêchant l’interprétation
  active du contenu.
- Sauvegarde conjointe de la base et du dossier de stockage.
- Suppression physique réservée à une opération explicitement autorisée et
  journalisée.
- Protection CSRF à prévoir pour toutes les opérations d’écriture.

## Découpage du développement

### Étape 0 — Cadrage fonctionnel et technique

État : validée

Livrables :

- liste définitive des champs ;
- règles sur les unités et conversions ;
- types de mouvements et règles de calcul ;
- modèle des rôles et autorisations ;
- stratégie d’archivage ;
- choix du moteur SQL et stratégie de migration ;
- schéma des tables, index et relations ;
- scénarios de recette du MVP.

Livrables produits le 19 juin 2026 :

- `SPECIFICATIONS.md` ;
- `SCHEMA_SQL.md` ;
- mise à jour de la présente roadmap.

Décisions proposées :

- stock négatif interdit ;
- aucune conversion automatique d’unité ;
- quantité sur quatre décimales ;
- transfert du contenant entier uniquement ;
- historique des mouvements immuable ;
- rôles globaux au module pour le MVP ;
- FDS signalée « à vérifier » après un délai configurable de 36 mois ;
- péremption proche signalée 90 jours à l’avance ;
- tables du module en InnoDB pour permettre les transactions ;
- aucune suppression physique dans les écrans normaux du MVP.

Points à décider :

- gestion d’une seule unité par contenant ou conversions contrôlées ;
- possibilité d’un stock négatif ;
- niveau de précision des quantités ;
- définition d’une FDS obsolète ;
- durée de conservation des documents et journaux ;
- gestion d’un produit réparti dans plusieurs sites ;
- accès des lecteurs aux quantités exactes.

Validation :

- revue du modèle fonctionnel ;
- validation du schéma SQL ;
- validation des scénarios de test avant toute création de table.

Validation utilisateur : 19 juin 2026.

### Étape 1 — Socle du module

État : à tester sur NAS

Livrables :

- déclaration et installation du module ;
- activation dans les modules externes GRR ;
- intégration dans « Gérer mon compte » ;
- page d’accueil initiale ;
- page d’administration ;
- activation et nom affiché configurables ;
- sélection des gestionnaires ;
- diagnostic des tables, hooks et répertoire documentaire.

Fichiers principaux :

- `infos.php` ;
- `installation.php` ;
- `controleur.php` ;
- `admin.php` ;
- `lib/bootstrap.php` ;
- `lib/Config.php` ;
- `lib/Repository.php` ;
- `lib/Renderer.php` ;
- `README.md`.

Risques :

- mauvaise coexistence avec les autres modules utilisant les mêmes hooks ;
- chemins relatifs différents entre les pages GRR et les pages autonomes ;
- migration rejouée lors d’une réinstallation.

Recette NAS :

1. importer ou déposer le module ;
2. l’activer dans GRR ;
3. vérifier son entrée dans « Gérer mon compte » ;
4. vérifier la page autonome et la page intégrée ;
5. vérifier le diagnostic administrateur ;
6. vérifier que les autres modules restent accessibles.

### Étape 2 — Catalogue et emplacements

État : à tester sur NAS

Livrables :

- création, consultation et modification d’un produit ;
- archivage sans suppression de l’historique ;
- gestion des fournisseurs ;
- gestion hiérarchique simple des emplacements ;
- recherche, filtres, tri et export CSV ;
- saisie des informations CLP sans interprétation automatique.

Risques :

- formulaire trop complexe ;
- doublons de produits ;
- confusion entre produit générique et référence commerciale ;
- informations de danger incomplètes ou erronées.

Recette NAS :

1. créer plusieurs emplacements imbriqués ;
2. créer un produit avec les seuls champs obligatoires ;
3. créer un produit avec toutes les informations facultatives ;
4. rechercher, filtrer et exporter ;
5. modifier puis archiver un produit ;
6. contrôler les droits avec chaque rôle.

### Étape 3 — Contenants et mouvements de stock

État : à tester sur NAS

Livrables :

- réception d’un contenant ou lot ;
- consommation partielle ou totale ;
- transfert entre emplacements ;
- correction d’inventaire ;
- élimination ou retour ;
- calcul des quantités disponibles ;
- historique complet et non modifiable ;
- contrôles contre les quantités incohérentes.

Risques :

- double soumission d’un formulaire ;
- mouvements simultanés ;
- erreurs d’arrondi ;
- mélange d’unités incompatibles ;
- écart entre quantité calculée et quantité enregistrée.

Recette NAS :

1. réceptionner deux lots d’un même produit ;
2. effectuer une sortie partielle ;
3. transférer un contenant ;
4. tenter une sortie supérieure au stock ;
5. effectuer une correction motivée ;
6. éliminer un contenant ;
7. contrôler les totaux et l’historique après chaque opération ;
8. vérifier qu’un ancien mouvement ne peut pas être modifié.

### Étape 4 — FDS et autres documents

État : à tester sur NAS

Livrables :

- ajout d’une FDS à un produit ;
- ajout des autres types de documents ;
- versionnement et archivage des FDS ;
- téléchargement sécurisé ;
- contrôle des extensions, tailles et types MIME ;
- empreinte SHA-256 ;
- diagnostic du stockage ;
- documents consultables sur les produits archivés.

Risques :

- accès direct au fichier ;
- téléversement d’un contenu dangereux ;
- incohérence entre fichier et ligne en base ;
- perte des documents lors d’une sauvegarde incomplète.

Recette NAS :

1. déposer un PDF valide ;
2. déposer une nouvelle version de la même FDS ;
3. vérifier que l’ancienne version reste disponible ;
4. tenter une extension interdite et un fichier trop volumineux ;
5. tenter un accès direct au répertoire de stockage ;
6. tester le téléchargement avec chaque rôle ;
7. contrôler l’empreinte et les métadonnées dans phpMyAdmin ;
8. tester la consultation d’un document d’un produit archivé.

### Étape 5 — Alertes et tableau de bord

État : à tester sur NAS

Livrables :

- stock sous le seuil minimal ;
- produits ou contenants proches de la péremption ;
- produits ou contenants périmés ;
- produits actifs sans FDS courante ;
- indicateur de FDS à revoir selon la règle validée ;
- tableau de bord par niveau de droit ;
- liens d’alerte via `hookDemandesStatus`, si leur coexistence est confirmée.

Risques :

- trop grand nombre d’alertes ;
- définition ambiguë d’une FDS obsolète ;
- fuite d’informations via les compteurs ou liens d’alerte ;
- conflit visuel avec les autres modules.

Recette NAS :

1. préparer des données couvrant chaque type d’alerte ;
2. vérifier les compteurs et les liens ;
3. vérifier les différences selon les rôles ;
4. corriger une situation et vérifier la disparition de l’alerte ;
5. vérifier la coexistence avec les alertes de `gestion_materiel`.

### Étape 6 — Inventaire, exports et ergonomie

État : à tester sur NAS

Livrables :

- campagne d’inventaire ;
- saisie de la quantité constatée ;
- génération contrôlée des corrections ;
- filtres et exports selon les droits ;
- liste imprimable des produits par emplacement ;
- amélioration de l’accessibilité et de l’usage mobile.

Risques :

- corrections massives accidentelles ;
- exposition de données dans les exports ;
- écrasement d’un mouvement survenu pendant l’inventaire.

Recette NAS :

1. ouvrir une campagne d’inventaire ;
2. saisir des quantités identiques et différentes ;
3. contrôler les corrections proposées avant validation ;
4. simuler un mouvement pendant l’inventaire ;
5. contrôler les exports et impressions avec chaque rôle.

### Étape 7 — Notifications et exploitation

État : à tester sur NAS

Livrables :

- notifications électroniques configurables ;
- anti-doublon des envois ;
- journal des notifications ;
- point d’entrée protégé pour une tâche planifiée Synology ;
- documentation de sauvegarde et restauration ;
- procédure de mise à jour du module.

Risques :

- URL planifiée insuffisamment protégée ;
- envois répétés ;
- divulgation d’informations sensibles dans les courriels ;
- restauration partielle de la base ou des documents.

Recette NAS :

1. générer un jeton de tâche planifiée ;
2. tester un appel invalide puis valide ;
3. vérifier l’anti-doublon ;
4. vérifier le journal d’envoi ;
5. sauvegarder puis restaurer une copie de recette ;
6. vérifier la cohérence entre base et fichiers restaurés.

### Étape 8 — Stabilisation du MVP

État : en cours, à tester sur NAS

Livrables :

- recette complète avec administrateur, gestionnaire, opérateur et lecteur ;
- vérification de compatibilité PHP 8.4 et MariaDB 10 ;
- revue des contrôles d’accès et opérations d’écriture ;
- revue des migrations et de leur rejouabilité ;
- documentation d’installation et d’exploitation finalisée ;
- recette de l’import initial de l’inventaire 2026 et de ses FDS ;
- inventaire des évolutions reportées après le MVP.

Recette NAS :

1. repartir d’une copie propre de la version de production ;
2. installer puis mettre à jour le module ;
3. exécuter tous les scénarios des étapes précédentes ;
4. contrôler les journaux Apache, PHP et MariaDB ;
5. vérifier les autres fonctions et modules GRR ;
6. valider la procédure de retour arrière.

## Commandes de validation

Les commandes exactes devront être adaptées aux chemins PHP du NAS.

Vérification syntaxique :

```sh
find personnalisation/modules/stock_chimique -type f -name '*.php' \
  -exec php -l {} \;
```

Vérification des fichiers du module :

```sh
find personnalisation/modules/stock_chimique -maxdepth 3 -type f -print
```

Les tests fonctionnels et les contrôles de base seront réalisés sur le NAS et
dans phpMyAdmin. Aucun test local ne sera considéré comme une validation de
déploiement.

## Suivi d’avancement

| Étape | État | Version cible | Date de validation | Observations |
|---|---|---:|---|---|
| 0. Cadrage | Validée | 0.1.0 | 19/06/2026 | Spécifications et schéma validés |
| 1. Socle | À tester sur NAS | 0.1.0 | — | Implémenté dans la version 0.7.0 |
| 2. Catalogue | À tester sur NAS | 0.2.0 | — | Implémenté dans la version 0.7.0 |
| 3. Stocks | À tester sur NAS | 0.3.0 | — | Implémenté dans la version 0.7.0 |
| 4. Documents | À tester sur NAS | 0.4.0 | — | Implémenté dans la version 0.7.0 |
| 5. Alertes | À tester sur NAS | 0.5.0 | — | Implémenté dans la version 0.7.0 |
| 6. Inventaire | À tester sur NAS | 0.6.0 | — | Implémenté dans la version 0.7.0 |
| 7. Notifications | À tester sur NAS | 0.7.0 | — | Implémenté dans la version 0.7.0 |
| 8. Stabilisation | À tester sur NAS | 1.0.0 | — | Import initial livré dans la version 0.8.0 |
| 9. Harmonisation popups | À tester sur NAS | 0.11.6 | — | Administration et notifications manuelles harmonisées |

États autorisés :

- À faire ;
- En cours ;
- À tester sur NAS ;
- À corriger ;
- Validée ;
- Bloquée ;
- En attente.

## Journal des décisions

| Date | Décision | Motif |
|---|---|---|
| 19/06/2026 | Créer un module autonome `stock_chimique` | Éviter de mélanger la gestion des produits chimiques avec celle du matériel |
| 19/06/2026 | Séparer produit et contenant ou lot | Gérer plusieurs lots, quantités, péremptions et emplacements pour un même produit |
| 19/06/2026 | Conserver les anciennes versions des FDS | Maintenir la traçabilité documentaire |
| 19/06/2026 | Prendre `gestion_materiel` comme référence sans le refactoriser pendant le MVP | Limiter les risques de régression |
| 19/06/2026 | Interdire le stock négatif et les conversions automatiques | Éviter les incohérences et conversions dangereuses |
| 19/06/2026 | Utiliser une unité unique par produit et contenant | Garder les calculs simples et vérifiables |
| 19/06/2026 | Rendre les mouvements immuables | Préserver un historique fiable |
| 19/06/2026 | Proposer InnoDB pour les tables du module | Permettre les transactions et verrouillages de contenant |
| 19/06/2026 | Ne pas ajouter de clés étrangères SQL dans la première version | Rester proche des conventions des modules GRR existants |
| 19/06/2026 | Stocker les rôles dans une table dédiée | Gérer proprement lecteur, opérateur et gestionnaire |
| 19/06/2026 | Développer les étapes 1 à 7 sans validation intermédiaire | Nouvelle méthode de développement demandée après validation du cadrage |
| 19/06/2026 | Limiter les notifications aux gestionnaires | Éviter de diffuser automatiquement les alertes à tous les lecteurs et opérateurs |
| 19/06/2026 | Préparer un paquet CSV accompagné d’un classeur de contrôle | Rendre l’inventaire historique vérifiable avant toute écriture en base |
| 19/06/2026 | Exiger une prévisualisation et journaliser chaque ligne importée | Réduire les risques de doublon et permettre une reprise après erreur |

## Historique du document

### 19 juin 2026

- Création de la roadmap.
- Définition du périmètre initial et des évolutions possibles.
- Proposition du modèle de données.
- Découpage du développement en neuf étapes validables.
- Ajout des risques et protocoles de recette associés.
- Rédaction des spécifications fonctionnelles de l’étape 0.
- Rédaction du schéma SQL proposé et de la stratégie de migration.
- Passage de l’étape 0 à l’état « À valider ».
- Validation utilisateur de l’étape 0.
- Changement de méthode : développement continu des étapes 1 à 7.
- Création du module fonctionnel version 0.7.0.
- Passage des étapes 1 à 7 à l’état « À tester sur NAS ».
- Analyse et normalisation de l’inventaire chimique 2026 et de ses FDS.
- Création du paquet CSV et du classeur de contrôle.
- Ajout de l’import administrateur et passage du module en version 0.8.0 / BDD 8.
- Passage de l’étape 8 à l’état « À tester sur NAS ».
- Correction en version 0.8.1 de la détection de l’en-tête `action` dans les CSV UTF-8 avec BOM.
- Correction en version 0.8.2 de la validation des PDF anciens comportant un préambule avant `%PDF-`.
- Correction en version 0.8.3 du rapprochement des noms français et anglais partageant la même référence fournisseur.
- Ajout en version 0.9.0 de l’activation globale et par famille des alertes, ainsi que de l’activation indépendante des notifications.
- Ajout en version 0.10.0 de la validation tracée des alertes « FDS à vérifier ».
- Ajout en version 0.10.1 de la validation groupée transactionnelle de toutes les alertes FDS depuis l’administration.
- Ajout en version 0.11.0 des formulaires modaux, des mouvements depuis la fiche produit et de l’évacuation transactionnelle vers les déchets chimiques.
- Ajustement en version 0.11.1 : les produits sans contenant en stock ne génèrent plus d’alerte.
- Ajustement en version 0.11.2 : les lignes des mouvements futurs sont affichées en orange.
- Ajustement en version 0.11.3 : ajout du bouton de modification dans la fiche produit.
- Ajustement en version 0.11.4 : ajout d’un contenant depuis la fiche produit avec produit présélectionné.
- Ajustement en version 0.11.6 : harmonisation des popups d’administration et de l’envoi manuel des notifications.

- Correction en version 0.11.8 : le lien résumé des alertes pointe vers `compte/compte.php?pc=stock_chimique#sc-alerts` hors de la page compte.
- Correction en version 0.11.9 : les gestionnaires peuvent valider les alertes « FDS à vérifier » depuis le module, sans accès à la page Administration.
- Ajout en version 0.12.0 : validation FDS depuis la fiche produit, édition administrateur des informations documentaires et annulation administrateur d’un inventaire ouvert.

## Références

- INRS, stockage des produits chimiques :
  <https://www.inrs.fr/risques/chimiques/stockage-produits-chimiques.html>
- INRS, logiciel Seirich :
  <https://www.inrs.fr/media.html?refINRS=outil47>
- Agence européenne des produits chimiques, fiches de données de sécurité :
  <https://echa.europa.eu/safety-data-sheets>
