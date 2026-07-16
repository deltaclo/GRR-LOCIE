# Stock chimique

Module local pour GRR 4.6.2.

Version applicative : 0.12.0  
Version BDD : 9

## Version 0.12.0

- Validation d’une alerte « FDS à vérifier » directement depuis la fiche produit.
- Bouton administrateur pour modifier les informations d’un document sans remplacer le fichier.
- Annulation d’un inventaire ouvert par un administrateur GRR, sans appliquer de correction de stock.
- Aucun changement de schéma SQL : la version BDD reste `9`.

## Version 0.11.9

- Alignement du droit de validation des alertes « FDS à vérifier » : les gestionnaires peuvent maintenant valider les FDS depuis le tableau de bord et la page Notifications, comme les administrateurs.
- L’accès à la page Administration reste réservé aux administrateurs GRR.
- Aucun changement de schéma SQL : la version BDD reste `9`.

## Version 0.11.8

- Correction du lien résumé des alertes stock chimique affiché hors de la page compte.
- Le lien pointe maintenant vers `compte/compte.php?pc=stock_chimique#sc-alerts` au lieu de `compte.php?pc=stock_chimique#sc-alerts`.
- Aucun changement de schéma SQL : la version BDD reste `9`.

## Version 0.11.7

- Affichage fluide en pleine largeur dans l'administration et dans `Gerer mon compte`.
- Tableaux, filtres, actions et popups adaptes aux petits ecrans.
- Bouton du menu gauche `Gerer mon compte` force en pleine largeur disponible.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 0.11.6

- Harmonisation de l'administration avec les modules `boutons_perso`,
  `gestion_materiel` et `informatique_materiel`.
- Remplacement des formulaires visibles de configuration, attribution de role,
  choix du paquet d'import, confirmation d'import et token planifie par des
  popups.
- Reouverture automatique de la popup concernee en cas d'erreur de validation
  dans l'administration.
- Remplacement du formulaire visible d'envoi manuel des notifications par une
  popup de confirmation.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 0.11.5

- Ajout de `lib/Navigation.php`.
- Exposition d'une definition standardisee du bouton du module.
- Le bouton reprend le nom configure, l'activation du module et le role du compte GRR.
- Aucun changement d'affichage avant l'integration dans `boutons_perso`.

## Fonctions

- catalogue des produits et fournisseurs ;
- emplacements hiérarchiques ;
- contenants, lots, péremptions et quantités ;
- réception, consommation, transfert, correction, élimination et retour ;
- mouvements transactionnels et immuables ;
- FDS versionnées et documents sécurisés ;
- alertes stock faible, péremption et FDS ;
- rôles lecteur, opérateur et gestionnaire ;
- inventaires avec détection des mouvements concurrents ;
- export CSV ;
- journal fonctionnel ;
- notifications manuelles ou planifiées ;
- import initial contrôlé des produits, contenants et FDS ;
- diagnostic des tables, moteurs et cohérences principales.

## Installation

1. Sauvegarder la base GRR et les fichiers.
2. Copier le dossier `stock_chimique` dans
   `personnalisation/modules/`.
3. Installer ou activer le module depuis l’administration des modules externes.
4. Ouvrir l’administration du module.
5. Vérifier que toutes les tables sont indiquées en état `OK` et utilisent
   `InnoDB`.
6. Attribuer au moins un rôle de gestionnaire.
7. Vérifier les droits d’écriture de `storage/documents`.

Le module ne modifie aucun fichier du cœur GRR. Il utilise les hooks
`hookCompteMenu`, `hookComptePage` et `hookDemandesStatus` déjà présents dans le
projet.

## Mise à jour

1. Sauvegarder la base et `storage/documents`.
2. Remplacer les fichiers du module en conservant le contenu de
   `storage/documents`.
3. Relancer l’installation ou la mise à jour depuis la gestion des modules.
4. Ouvrir l’administration du module afin de rejouer les créations manquantes.
5. Vérifier la version BDD, les moteurs et les diagnostics.
6. Exécuter la recette correspondant à la nouvelle version.

Les migrations sont conçues pour être rejouables. Elles ne suppriment ni table,
ni colonne, ni donnée.

## Rôles

- Lecteur : consultation et exports.
- Opérateur : fonctions lecteur, réception, consommation et transfert.
- Gestionnaire : fonctions opérateur, catalogue, corrections, éliminations,
  documents, inventaires, notifications et journal.
- Administrateur général GRR : tous les droits et configuration.

## Règles de stock

- le stock négatif est interdit ;
- aucune conversion automatique d’unité ;
- l’unité d’un produit est figée après le premier mouvement ;
- un transfert déplace le contenant entier ;
- les mouvements ne sont ni modifiés ni supprimés ;
- une erreur est compensée par une correction motivée ;
- les quantités utilisent quatre décimales ;
- réception et mouvement initial sont enregistrés dans une transaction ;
- chaque mouvement verrouille le contenant avec `SELECT ... FOR UPDATE`.
- l’évacuation vers les déchets chimiques solde le contenant par un mouvement
  d’élimination immuable ;
- l’évacuation d’un produit traite tous ses contenants en stock dans une
  transaction unique.

## Ergonomie des formulaires

Les formulaires de création et d’action sont ouverts dans des fenêtres modales
depuis les boutons placés en haut des pages concernées. Cela couvre les
fournisseurs, emplacements, produits, contenants, mouvements, inventaires et
documents.

La fiche produit affiche tous ses contenants, y compris les contenants soldés,
avec un bouton de mouvement sur chaque contenant encore en stock.

Un produit sans contenant actuellement en stock ne participe pas au calcul des
alertes de stock ou de FDS. Son historique et ses documents restent
consultables.

## Documents

- les FDS sont obligatoirement des PDF ;
- la date de révision est obligatoire pour une FDS ;
- une seule FDS courante est conservée par produit et langue ;
- l’ajout d’une nouvelle FDS courante archive automatiquement l’ancienne ;
- les anciennes versions restent téléchargeables ;
- les fichiers sont stockés sous un nom aléatoire dans `storage/documents` ;
- l’accès HTTP direct est bloqué par `.htaccess` ;
- les téléchargements passent par `download.php` ;
- chaque fichier possède une empreinte SHA-256.

La sauvegarde doit toujours inclure la base de données et le répertoire
`storage/documents`.

## Import initial

L’administration du module détecte les paquets déposés dans
`storage/import/`. Chaque paquet doit être un dossier contenant :

- `import_stock_chimique.csv` ;
- le sous-dossier `FDS` référencé par le CSV.

Le paquet préparé pour l’inventaire 2026 se trouve dans le dossier projet
`Stock et FDS`. Copier ce dossier complet sur le NAS, par exemple sous :

```text
personnalisation/modules/stock_chimique/storage/import/Stock_et_FDS_2026/
```

Dans l’administration, sélectionner le paquet puis lancer la prévisualisation.
L’import est bloqué si une ligne, une quantité, une unité, une date ou un chemin
de FDS est invalide. Les avertissements doivent être comparés au classeur
`Import_stock_chimique_2026_pret_import.xlsx` avant confirmation.

L’exécution est rejouable : une ligne déjà importée avec le même CSV est
ignorée. Les produits sont retrouvés par référence interne, les contenants par
un code stable lié au paquet et à la ligne source, et les documents par leur
empreinte SHA-256. Le journal d’import conserve les succès et les erreurs.
L’administration traite au maximum 50 lignes par lancement afin de rester
compatible avec les limites de temps PHP ; relancer le lot jusqu’à ce que le
compteur de lignes restantes atteigne zéro.

Le répertoire `storage/import` est une zone de transit. Il doit être inclus dans
la sauvegarde tant que l’import n’est pas validé, puis le paquet peut être
archivé hors du serveur web.

## Notifications planifiées

L’administration permet d’activer ou désactiver séparément :

- l’ensemble des alertes affichées dans le module ;
- les alertes de stock faible ;
- les alertes de péremption ;
- les alertes de FDS absente ou ancienne ;
- les notifications électroniques.

La désactivation des notifications n’efface pas les alertes et ne modifie pas
le journal des anciens envois. L’envoi manuel et l’URL Synology sont bloqués.
La désactivation globale des alertes masque les compteurs et empêche aussi la
production de nouvelles notifications.

Un gestionnaire du module ou un administrateur général GRR peut valider une
alerte « FDS à vérifier » depuis le tableau de bord ou la page Notifications.
Cette action enregistre l’auteur
et la date du contrôle interne sans modifier la date de révision de la FDS.
L’alerte réapparaît lorsque le délai FDS configuré est de nouveau dépassé.
La page d’administration permet également de valider en une seule opération
toutes les FDS actuellement signalées. La liste est recalculée côté serveur et
l’opération groupée est transactionnelle.

L’administration du module permet de générer une URL protégée. Elle peut être
appelée par le planificateur de tâches Synology :

```text
https://serveur/grr/personnalisation/modules/stock_chimique/cron_notifications.php?token=TOKEN
```

Les notifications sont envoyées aux gestionnaires ayant une adresse
électronique active dans GRR. Le journal anti-doublon empêche le renvoi de la
même alerte au même compte.

## Validation syntaxique sur le NAS

Depuis la racine de GRR :

```sh
find personnalisation/modules/stock_chimique -type f -name '*.php' \
  -exec php -l {} \;
```

## Protocole de recette

### Installation et droits

1. Installer le module et ouvrir son administration.
2. Vérifier les tables et leur moteur InnoDB.
3. Attribuer les trois rôles à des comptes de test.
4. Vérifier qu’un compte sans rôle ne voit pas le module.
5. Vérifier les actions disponibles pour chaque rôle.

### Catalogue

1. Créer un fournisseur.
2. Créer un site, un local et une armoire avec relations parent-enfant.
3. Créer un produit avec une unité et un seuil.
4. Modifier le produit.
5. Vérifier les filtres et l’export CSV.
6. Vérifier qu’un emplacement utilisé ne peut pas être archivé.

### Stock

1. Réceptionner deux contenants du même produit.
2. Vérifier le mouvement d’entrée et le stock total.
3. Consommer une quantité partielle.
4. Tenter une consommation supérieure au stock.
5. Transférer un contenant.
6. Effectuer une correction motivée.
7. Éliminer un contenant.
8. Vérifier qu’aucun ancien mouvement n’est modifiable.
9. Soumettre deux fois le même formulaire et vérifier l’absence de doublon.
10. Depuis la fiche produit, ouvrir un mouvement pour chacun de ses contenants.
11. Évacuer un contenant et vérifier le mouvement `elimination`.
12. Évacuer un produit comportant plusieurs contenants.
13. Vérifier que tous les contenants sont soldés ou qu’aucun ne l’est en cas
    d’erreur.
14. Contrôler le motif, la date et le journal des évacuations.

### Documents

1. Ajouter une FDS PDF avec sa date de révision.
2. Ajouter une nouvelle version courante.
3. Vérifier l’archivage de l’ancienne version.
4. Télécharger les deux versions.
5. Tenter un fichier non PDF comme FDS.
6. Tenter un fichier trop volumineux.
7. Tenter un accès HTTP direct à `storage/documents`.
8. Avec un administrateur GRR, modifier les informations d’un document.
9. Vérifier que le fichier téléchargé reste le même et que le journal trace la modification.

### Alertes

1. Créer un produit sans FDS.
2. Passer son stock sous le seuil.
3. Créer des contenants périmés et proches de la péremption.
4. Vérifier le tableau de bord et le lien haut de page.
5. Désactiver successivement les alertes stock, péremption et FDS.
6. Vérifier que seule la famille concernée disparaît.
7. Désactiver globalement les alertes et vérifier que le compteur reste à zéro.
8. Réactiver les alertes et vérifier leur retour.
9. Avec un gestionnaire, vérifier que le bouton de validation FDS est présent.
10. Avec un gestionnaire, valider une alerte « FDS à vérifier ».
11. Depuis la fiche produit, valider une alerte « FDS à vérifier ».
12. Vérifier la disparition de l’alerte et la trace sur la fiche du produit.
13. Depuis l’administration, utiliser « Valider toutes les FDS ».
14. Vérifier que toutes les alertes concernées disparaissent et sont journalisées.
15. Vérifier la coexistence avec les alertes des autres modules.

### Import initial

1. Copier le paquet `Stock_et_FDS_2026` dans `storage/import`.
2. Vérifier qu’il apparaît dans l’administration.
3. Lancer la prévisualisation sans confirmer l’import.
4. Comparer les totaux affichés au classeur de contrôle.
5. Vérifier les avertissements et les chemins FDS.
6. Sauvegarder la base et `storage/documents`.
7. Confirmer les lots successifs jusqu’à zéro ligne restante.
8. Relever les compteurs de chaque lot.
9. Contrôler quelques produits, contenants, emplacements et FDS.
10. Relancer le même import et vérifier qu’aucun doublon n’est créé.
11. Contrôler le journal d’import et les journaux PHP/MariaDB.

### Inventaire

1. Ouvrir un inventaire global.
2. Saisir les quantités comptées.
3. Enregistrer un mouvement après l’ouverture.
4. Vérifier la détection du conflit.
5. Ressaisir la ligne en conflit.
6. Terminer l’inventaire et contrôler les corrections générées.
7. Ouvrir un autre inventaire.
8. Avec un administrateur GRR, annuler cet inventaire ouvert.
9. Vérifier que le statut passe à `annule` et qu’aucune correction de stock n’est appliquée.

### Notifications

1. Activer les courriels GRR.
2. Vérifier l’adresse d’un gestionnaire.
3. Envoyer manuellement les alertes.
4. Répéter l’envoi et vérifier l’anti-doublon.
5. Générer le token et appeler l’URL planifiée.
6. Désactiver les notifications dans l’administration.
7. Vérifier que le bouton d’envoi disparaît et que l’URL planifiée est refusée.
8. Vérifier que les alertes restent visibles si elles sont encore activées.

### Sauvegarde et restauration

1. Sauvegarder la base et `storage/documents`.
2. Restaurer dans une instance de recette.
3. Vérifier les stocks, mouvements, documents et empreintes.
4. Ouvrir le diagnostic et vérifier toutes les lignes.

## Versions

### 0.12.0

- validation FDS accessible depuis la fiche produit ;
- édition administrateur des informations documentaires sans remplacement de fichier ;
- annulation administrateur d’un inventaire ouvert ;
- aucun changement de schéma SQL.

### 0.11.4

- ajout du bouton « Ajouter un contenant » dans la fiche produit ;
- popup de réception avec produit présélectionné.

### 0.11.3

- ajout du bouton « Modifier » dans l’en-tête de la fiche produit.

### 0.11.2

- affichage en orange des mouvements dont la date effective est future.

### 0.11.1

- exclusion des produits sans contenant en stock du calcul des alertes ;
- conservation des produits concernés dans le catalogue et les historiques.

### 0.11.0

- déplacement des formulaires de création dans des fenêtres modales ;
- boutons d’action placés en haut des pages concernées ;
- affichage de tous les contenants depuis la fiche produit ;
- bouton de mouvement sur chaque contenant actif ;
- évacuation d’un contenant vers les déchets chimiques ;
- évacuation transactionnelle de tous les contenants actifs d’un produit ;
- journalisation des mouvements et des évacuations de produits.

### 0.10.1

- bouton administrateur « Valider toutes les FDS » ;
- confirmation affichant le nombre de FDS concernées ;
- recalcul serveur de la liste avant validation ;
- validation groupée transactionnelle et journalisée.

### 0.10.0

- validation tracée des alertes « FDS à vérifier » ;
- conservation de l’auteur et de la date du contrôle interne ;
- nouvelle apparition automatique après le délai FDS configuré ;
- affichage du dernier contrôle dans la liste des documents ;
- migration BDD 9 rejouable et sans suppression de données.

### 0.9.0

- configuration globale des alertes dans l’administration ;
- activation séparée des alertes stock faible, péremption et FDS ;
- activation indépendante des notifications électroniques ;
- blocage cohérent des envois manuels et planifiés lorsqu’ils sont désactivés ;
- conservation des journaux et des paramètres lors d’une désactivation.

### 0.8.3

- autorisation des variantes de nom commercial pour un même produit importé ;
- contrôle de compatibilité maintenu sur l’unité, le fournisseur, la référence
  fournisseur et le numéro CAS.

### 0.8.2

- validation structurelle des PDF importés en complément de `fileinfo` ;
- prise en charge des anciens PDF dont l’en-tête est précédé d’un court
  préambule parasite.

### 0.8.1

- correction de la lecture de la première colonne des CSV UTF-8 avec BOM.

### 0.8.0

- ajout de l’import initial administrateur avec prévisualisation obligatoire ;
- création automatique des fournisseurs, emplacements, produits et contenants ;
- import, copie sécurisée et déduplication SHA-256 des FDS ;
- journal par ligne et protection contre la répétition du même paquet ;
- migration BDD 8 et extension du champ CAS à 190 caractères ;
- préparation du paquet d’inventaire 2026 et de son classeur de contrôle.

### 0.7.0

- implémentation initiale des étapes 1 à 7 de la roadmap ;
- socle, droits et administration ;
- catalogue, emplacements et fournisseurs ;
- contenants et mouvements transactionnels ;
- FDS et documents sécurisés ;
- alertes et tableau de bord ;
- inventaires, exports et journal ;
- notifications manuelles et planifiées.
