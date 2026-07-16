# Roadmap du module Informatique materiel

Document de reference pour le developpement progressif du module local GRR
`informatique_materiel`.

- Projet cible : GRR 4.6.2 avec patchs, modules et hooks
- Environnement de recette : Synology DSM 7, Apache 2.4, PHP 8.4,
  MariaDB 10 et phpMyAdmin
- Creation du document : 25 juin 2026
- Derniere mise a jour : 30 juin 2026
- Etat global : version 1.3.5 implementee, alignement des fins de prets sur la date de depart en attente de recette NAS

## Regles de developpement

- Ne pas modifier le coeur de GRR si les hooks existants suffisent.
- Respecter les conventions des modules locaux deja presents.
- Prendre `gestion_materiel` comme reference pour le cycle de vie, les actions,
  les documents, les alertes et l'integration dans "Gerer mon compte".
- Prendre `stock_chimique` comme reference pour le cadrage, les roles, le CSRF,
  les imports controles et les diagnostics.
- Garder le module autonome pour eviter de casser `gestion_materiel`.
- Realiser des changements petits, reversibles et testables.
- A la fin de chaque etape :
  1. produire une synthese des changements ;
  2. fournir un protocole de test sur le NAS ;
  3. attendre la validation avant de commencer l'etape suivante.

## Objectif du module

Le module doit remplacer progressivement le classeur Excel
`P5DOC03 v2.0 - Informatique materiel - gestion quotidienne.xlsx` pour gerer :

- l'inventaire du materiel informatique du laboratoire ;
- les personnes auxquelles le materiel est affecte ou prete ;
- les prets ouverts, les restitutions et les retards ;
- la disponibilite du materiel ;
- les localisations de stockage ou d'utilisation ;
- l'historique et les documents associes au materiel.

Le module ne remplace pas un outil de ticketing support, un outil d'achat, ni
les procedures metier de la direction numerique.

## Lecture du classeur source

Analyse du classeur du 25 juin 2026 :

| Feuille | Role | Donnees utiles |
|---|---|---|
| `Lisez-moi` | Contexte, version, indicateurs | lien GRR, nombre de restitutions attendues |
| `personnels` | Personnes LOCIE | 79 personnes renseignees |
| `materiels` | Inventaire | 478 materiels renseignes |
| `prets` | Affectations et restitutions | 483 prets, 442 ouverts, 29 en attente de restitution |
| `_categories` | Listes de reference | types de materiel, prefixes, cadres d'usage |
| `_utiles` | Aide Excel | vues calculees, non a importer directement |

Le modele doit conserver les identifiants historiques Excel pour permettre la
verification de l'import et la transition progressive.

## Perimetre MVP

- Catalogue des categories de materiel informatique.
- Referentiel des personnes issues du classeur.
- Inventaire des materiels : designation, precision, MAC, marque, numero de
  serie, code-barres USMB, OS, annee, commentaire, localisation de stockage.
- Generation controlee des nouveaux identifiants materiel par prefixe.
- Prets et restitutions, avec historique consultable.
- Disponibilite calculee a partir des prets ouverts.
- Alertes sur prets en retard et personnes parties avec materiel non restitue.
- Recherche, filtres et export CSV.
- Import initial depuis CSV issu de l'Excel, avec previsualisation et journal.
- Administration du module et gestion des droits.
- Documents associes au materiel.
- Diagnostics techniques et fonctionnels.

## Hors perimetre initial

- Gestion des achats, budgets et amortissements.
- Ticketing support informatique.
- Synchronisation automatique avec un annuaire externe.
- Reservation temps reel de salles ou ressources GRR existantes.
- Gestion fine multi-site au-dela d'une localisation texte.
- Impression d'etiquettes code-barres.
- Scan code-barres mobile.

## Principes d'architecture

Le module sera autonome dans :

`personnalisation/modules/informatique_materiel/`

Architecture envisagee :

- `infos.php` : declaration et versions du module ;
- `installation.php` : activation, parametres initiaux et migrations ;
- `controleur.php` : integration aux hooks GRR ;
- `admin.php` : configuration, roles et diagnostic ;
- `index.php` : point d'entree autonome ;
- `download.php` : telechargement securise des documents ;
- `lib/bootstrap.php` : initialisation commune ;
- `lib/Config.php` : parametres du module ;
- `lib/Security.php` : droits, roles, CSRF et jetons ;
- `lib/Repository.php` : acces aux donnees et migrations ;
- `lib/Renderer.php` : controleur leger et rendu HTML ;
- `lib/Navigation.php` : definition du bouton pour `boutons_perso` ;
- `lib/Import.php` : previsualisation et import initial ;
- `storage/documents/` : stockage protege des fichiers ;
- `README.md` : installation, versions et recette ;
- `SPECIFICATIONS.md` : regles fonctionnelles validees ;
- `SCHEMA_SQL.md` : schema de donnees et migrations ;
- `ROADMAP.md` : present document.

## Modele de donnees cible

La proposition detaillee est disponible dans `SCHEMA_SQL.md`.

Entites principales :

- role ;
- personne ;
- categorie ;
- sequence d'identifiants ;
- materiel ;
- pret ;
- document ;
- journal ;
- journal d'import.

La disponibilite ne doit pas etre saisie manuellement : elle est deduite des
prets ouverts. Un materiel ne peut avoir qu'un seul pret ouvert a la fois.

## Roles envisages

- Administrateur GRR : configuration, diagnostic et tous les droits.
- Gestionnaire informatique : gestion complete du catalogue, du parc, des
  prets, des imports et des documents.
- Operateur : creation de prets, retours et consultation active.
- Lecteur : consultation et export des listes autorisees.
- Personnel associe : consultation limitee a ses propres prets si rattachement
  futur a un compte GRR.

Le MVP stocke les roles par login GRR, comme `stock_chimique`.

## Decoupage du developpement

### Etape 0 - Cadrage fonctionnel et technique

Etat : validee le 25 juin 2026.

Livrables :

- roadmap du module ;
- specifications fonctionnelles ;
- schema SQL propose ;
- liste des risques ;
- protocole de validation de l'etape 0.

Fichiers produits :

- `ROADMAP.md` ;
- `SPECIFICATIONS.md` ;
- `SCHEMA_SQL.md`.

Risques :

- mauvais choix entre extension de `gestion_materiel` et module autonome ;
- import initial trop permissif ;
- confusion entre personne Excel et utilisateur GRR ;
- disponibilite incoherente si elle est stockee au lieu d'etre calculee.

Validation attendue :

- validation du module autonome `informatique_materiel` ;
- validation du perimetre MVP ;
- validation des champs et regles de pret ;
- validation du schema SQL avant toute creation de table.

### Etape 1 - Socle du module

Etat : validee le 25 juin 2026.

Livrables :

- declaration du module ;
- installation et activation dans les modules externes ;
- integration dans "Gerer mon compte" ;
- page d'accueil initiale ;
- page d'administration ;
- activation, nom affiche et roles configurables ;
- diagnostic minimal ;
- definition de navigation pour une future integration dans `boutons_perso`.

Fichiers principaux :

- `infos.php` ;
- `installation.php` ;
- `controleur.php` ;
- `admin.php` ;
- `index.php` ;
- `lib/bootstrap.php` ;
- `lib/Config.php` ;
- `lib/Security.php` ;
- `lib/Repository.php` ;
- `lib/Renderer.php` ;
- `lib/Navigation.php` ;
- `README.md`.

Recette NAS :

1. deposer le module ;
2. l'activer dans GRR ;
3. verifier son entree dans "Gerer mon compte" ;
4. verifier la page autonome ;
5. verifier le diagnostic administrateur ;
6. verifier que les autres modules restent accessibles.

### Etape 2 - Referentiels

Etat : validee le 25 juin 2026.

Livrables :

- categories de materiel et prefixes ;
- personnes issues du classeur ;
- cadres d'usage ;
- localisations texte ;
- saisie manuelle simple ;
- listes et export CSV ;
- initialisation des categories historiques lorsque la table est vide.

Fichiers principaux :

- `infos.php` ;
- `installation.php` ;
- `export.php` ;
- `lib/Repository.php` ;
- `lib/Renderer.php` ;
- `README.md`.

Recette NAS :

1. relancer l'installation du module ;
2. verifier la version BDD 2 ;
3. verifier les tables personnes, categories et sequences ;
4. creer une categorie ;
5. modifier et archiver une categorie ;
6. creer une personne ;
7. modifier et archiver une personne ;
8. verifier les exports CSV ;
9. verifier les droits par role.

### Etape 3 - Inventaire materiel

Etat : validee le 25 juin 2026.

Livrables :

- creation, consultation, modification et archivage du materiel ;
- generation d'identifiant par prefixe ;
- fiche materiel ;
- liste filtrable ;
- statut materiel ;
- diagnostic de doublons.

Recette NAS :

1. relancer l'installation du module ;
2. verifier la version BDD 3 ;
3. verifier la table materiels ;
4. creer un ordinateur portable ;
5. creer un accessoire ;
6. verifier les identifiants generes ;
7. modifier les champs principaux ;
8. tester les filtres ;
9. ouvrir la fiche materiel ;
10. tester l'export CSV ;
11. archiver un materiel ;
12. verifier les droits.

### Etape 4 - Prets et restitutions

Etat : validee le 25 juin 2026.

Livrables :

- creation d'un pret ;
- retour avec date de restitution effective ;
- blocage de deux prets ouverts pour le meme materiel non generique ;
- historique des prets par materiel et par personne ;
- commentaires, annulation et corrections journalisees ;
- export CSV et diagnostics de coherence.

Recette NAS :

1. relancer l'installation du module ;
2. verifier la version BDD 4 ;
3. verifier la table prets ;
4. preter un materiel disponible ;
5. tenter de preter le meme materiel une seconde fois ;
6. restituer le materiel ;
7. verifier le retour du materiel au statut stocke ;
8. verifier l'historique par materiel et par personne ;
9. tester l'export CSV ;
10. verifier les droits par role.

### Etape 5 - Import initial Excel/CSV

Etat : validee le 26 juin 2026.

Livrables :

- format CSV attendu ;
- previsualisation ;
- validation des personnes, materiels et prets ;
- journal d'import ;
- anti-doublon par paquet et ligne source ;
- rapport des anomalies ;
- stockage protege des CSV importes.

Recette NAS :

1. importer un petit jeu de donnees ;
2. verifier la previsualisation ;
3. executer l'import ;
4. relancer le meme import et verifier l'anti-doublon ;
5. verifier les donnees dans phpMyAdmin ;
6. ouvrir `compte.php?pc=informatique_materiel&view=import` et confirmer
   l'absence d'erreur 500.

### Etape 6 - Alertes et tableau de bord

Etat : validee le 26 juin 2026.

Livrables :

- prets en retard ;
- personnes parties avec materiel non restitue ;
- materiels sans informations critiques ;
- tableau de bord ;
- integration `hookDemandesStatus` concatenee avec les autres modules.

Recette NAS :

1. creer un pret en retard ;
2. creer une personne avec date de depart passee ;
3. verifier les alertes ;
4. corriger la situation ;
5. verifier la disparition des alertes ;
6. verifier le lien `hookDemandesStatus` en coexistence avec les autres modules.

### Etape 7 - Documents

Etat : validee le 26 juin 2026.

Livrables :

- depot de documents par materiel ;
- stockage protege ;
- telechargement securise ;
- types de documents configurables ;
- controle extension, taille et MIME ;
- suppression ou archivage selon les droits.

Recette NAS :

1. deposer un PDF ;
2. deposer un fichier interdit ;
3. telecharger avec un utilisateur autorise ;
4. tenter un acces direct au stockage ;
5. archiver un document ;
6. verifier les droits.

### Etape 8 - Stabilisation MVP

Etat : a tester sur NAS.

Livrables :

- recette complete ;
- verification PHP 8.4 et MariaDB 10 ;
- documentation d'installation ;
- procedure de sauvegarde et restauration ;
- bilan des evolutions post-MVP.

Recette NAS :

1. partir d'une copie de recette propre ;
2. installer le module ;
3. executer tous les scenarios precedents ;
4. verifier les journaux Apache, PHP et MariaDB ;
5. verifier la non-regression des autres modules.

## Commandes de validation

L'etape 0 ne cree pas de PHP. La validation porte sur la presence et la
relecture des documents :

```sh
find personnalisation/modules/informatique_materiel -maxdepth 1 -type f -name '*.md' -print
```

A partir de l'etape 1, la validation syntaxique PHP sera :

```sh
find personnalisation/modules/informatique_materiel -type f -name '*.php' \
  -exec php -l {} \;
```

Les tests fonctionnels et les controles de base seront realises sur le NAS et
dans phpMyAdmin. Aucun test local ne sera considere comme une validation de
deploiement.

## Suivi d'avancement

| Etape | Etat | Version cible | Date de validation | Observations |
|---|---|---:|---|---|
| 0. Cadrage | Validee | 0.1.0 | 25/06/2026 | Documents valides |
| 1. Socle | Validee | 0.1.0 | 25/06/2026 | Socle valide |
| 2. Referentiels | Validee | 0.2.0 | 25/06/2026 | Referentiels valides |
| 3. Inventaire | Validee | 0.3.0 | 25/06/2026 | Inventaire valide |
| 4. Prets | Validee | 0.4.0 | 25/06/2026 | Prets et restitutions valides |
| 5. Import | Validee | 0.5.0 | 26/06/2026 | Import CSV valide |
| 6. Alertes | Validee | 0.6.0 | 26/06/2026 | Alertes validees |
| 7. Documents | Validee | 0.7.0 | 26/06/2026 | Documents valides |
| 8. Stabilisation | A tester sur NAS | 1.0.0 | - | Documentation MVP finalisee |
| 9. LDAP et ergonomie | A tester sur NAS | 1.1.0 | - | Association LDAP, boutons perso, remise a zero |
| 10. Actions alertes | A tester sur NAS | 1.1.1 | - | Prolongation retour/depart depuis les alertes |
| 11. Conflits imports prets | A tester sur NAS | 1.2.0 | - | Table de conflits independante pour doublons de prets |
| 12. Resolution conflits prets | A tester sur NAS | 1.2.1 | - | Page de decision et boutons de resolution |
| 13. Associations GRR LDAP | A tester sur NAS | 1.2.2 | - | Test LDAP admin et association groupable des personnes |
| 14. Email personnes LDAP | A tester sur NAS | 1.2.3 | - | Champ email, creation LDAP et popup de chargement |
| 15. Visibilite bouton mon materiel | A tester sur NAS | 1.2.4 | - | Bouton visible seulement avec pret ouvert |
| 16. Ergonomie tableaux et actions | A tester sur NAS | 1.2.5 | - | Tri, filtres dynamiques, suppressions controlees et fiches personne |
| 17. Alertes conflits et actions fiches | A tester sur NAS | 1.2.6 | - | Bandeau conflits, couleurs alertes et actions de suppression sur fiches |
| 18. Harmonisation popups | A tester sur NAS | 1.2.7 | - | Administration harmonisee et confirmation import en popup |
| 19. Prets multiples generiques | A tester sur NAS | 1.3.0 | - | Materiels generiques pretables simultanement et statut `pret_multiple` |
| 20. Transferts de prets | A tester sur NAS | 1.3.2 | - | Transfert individuel et transfert global depuis une fiche personne |

## Journal des decisions

| Date | Decision | Motif |
|---|---|---|
| 25/06/2026 | Proposer un module autonome `informatique_materiel` | Eviter de casser `gestion_materiel` et separer le metier informatique |
| 25/06/2026 | Conserver les identifiants Excel historiques | Faciliter la recette de l'import et la transition |
| 25/06/2026 | Calculer la disponibilite depuis les prets ouverts | Eviter les incoherences entre statut et historique |
| 25/06/2026 | Stocker les roles par login GRR | Reprendre le modele de `stock_chimique` |
| 25/06/2026 | Ne pas creer de code avant validation de l'etape 0 | Respecter la procedure de validation progressive |
| 25/06/2026 | Livrer uniquement le socle en version 0.1.0 | Permettre une recette courte avant les referentiels |
| 25/06/2026 | Initialiser les categories historiques uniquement si la table est vide | Eviter les doublons lors des mises a jour |
| 25/06/2026 | Reporter la disponibilite calculee a l'etape Prets | La disponibilite fiable depend des prets ouverts |
| 25/06/2026 | Bloquer le deuxieme pret ouvert par transaction applicative | Rester compatible avec les conventions SQL du projet sans index partiel |
| 25/06/2026 | Importer depuis CSV et non directement depuis XLSX | Garder un format auditable et rejouable sur le NAS sans dependance externe |
| 26/06/2026 | Ajouter les alertes via calcul dynamique et `hookDemandesStatus` concatene | Eviter une nouvelle table et respecter la coexistence des modules |
| 26/06/2026 | Archiver les documents plutot que supprimer le fichier physique | Conserver la tracabilite et limiter les pertes en recette |
| 26/06/2026 | Stabiliser le MVP en version applicative 1.0.0 sans nouvelle migration SQL | Marquer la fin du perimetre initial tout en gardant la BDD en version 6 |
| 26/06/2026 | Ajouter une version 1.1.0 sans migration SQL pour LDAP, boutons perso et ergonomie | Repondre aux evolutions post-MVP sans changer le schema BDD |
| 26/06/2026 | Ajouter une version 1.1.1 sans migration SQL pour prolonger les alertes | Traiter les alertes courantes sans sortir du tableau des alertes |
| 26/06/2026 | Ajouter une version 1.2.0 avec table de conflits de prets | Importer les doublons sans modifier les prets existants |
| 26/06/2026 | Ajouter une version 1.2.1 sans migration SQL pour resoudre les conflits | Permettre les decisions metier depuis une page dediee |
| 26/06/2026 | Ajouter une version 1.2.2 sans migration SQL pour tester LDAP et associer en masse | Faciliter le rapprochement des personnes Excel avec les comptes GRR/LDAP |
| 26/06/2026 | Ajouter une version 1.2.3 / BDD 8 pour stocker l'email personne | Conserver l'email LDAP et faciliter la creation depuis l'annuaire |
| 26/06/2026 | Ajouter une version 1.2.4 sans migration SQL pour filtrer le bouton utilisateur | Eviter d'afficher `Mon materiel informatique` aux comptes sans pret ouvert |
| 30/06/2026 | Ajouter une version 1.3.0 / BDD 9 pour les prets multiples generiques | Permettre les accessoires partages sans conflit de pret |
| 30/06/2026 | Ajouter une version 1.3.2 sans migration SQL pour les transferts de prets | Conserver l'historique en cloturant puis recreant les prets |
| 26/06/2026 | Ajouter une version 1.2.5 sans migration SQL pour les actions et tableaux dynamiques | Ameliorer l'exploitation quotidienne sans modifier le schema BDD |
| 29/06/2026 | Ajouter une version 1.2.6 sans migration SQL pour le bandeau conflits et les couleurs d alertes | Rendre les conflits visibles partout et clarifier les actions de resolution |
| 29/06/2026 | Ajouter une version 1.2.7 sans migration SQL pour harmoniser les popups | Rendre l'administration et l'import plus coherents avec les autres modules |

## Historique du document

### 25 juin 2026

- Creation de la roadmap, des specifications et du schema SQL de l'etape 0.
- Validation utilisateur de l'etape 0.
- Creation du socle du module `informatique_materiel` en version 0.1.0.
- Passage de l'etape 1 a l'etat "A tester sur NAS".
- Validation utilisateur de l'etape 1.
- Ajout des referentiels personnes et categories en version 0.2.0 / BDD 2.
- Passage de l'etape 2 a l'etat "A tester sur NAS".
- Validation utilisateur de l'etape 2.
- Ajout de l'inventaire materiel en version 0.3.0 / BDD 3.
- Passage de l'etape 3 a l'etat "A tester sur NAS".
- Validation utilisateur de l'etape 3.
- Ajout des prets et restitutions en version 0.4.0 / BDD 4.
- Passage de l'etape 4 a l'etat "A tester sur NAS".
- Validation utilisateur de l'etape 4.
- Ajout de l'import CSV en version 0.5.0 / BDD 5.
- Passage de l'etape 5 a l'etat "A tester sur NAS".

### 26 juin 2026

- Validation utilisateur de l'etape 5.
- Ajout des alertes en version 0.6.0 / BDD 5.
- Validation utilisateur de l'etape 6.
- Ajout des documents en version 0.7.0 / BDD 6.
- Passage de l'etape 7 a l'etat "A tester sur NAS".
- Validation utilisateur de l'etape 7.
- Ajout de la documentation de stabilisation MVP.
- Passage du module en version applicative 1.0.0 / BDD 6.
- Passage de l'etape 8 a l'etat "A tester sur NAS".
- Ajout des evolutions LDAP, boutons perso, fiche utilisateur, remise a zero et popups en version 1.1.0 / BDD 6.
- Passage de l'etape 9 a l'etat "A tester sur NAS".
- Ajout de la prolongation des dates de retour et de depart depuis les alertes en version 1.1.1 / BDD 6.
- Passage de l'etape 10 a l'etat "A tester sur NAS".
- Ajout de la table des conflits de prets importes en version 1.2.0 / BDD 7.
- Passage de l'etape 11 a l'etat "A tester sur NAS".
- Ajout de la page de resolution des conflits de prets en version 1.2.1 / BDD 7.
- Passage de l'etape 12 a l'etat "A tester sur NAS".
- Ajout du test LDAP admin et de la page d'associations GRR/LDAP en version 1.2.2 / BDD 7.
- Passage de l'etape 13 a l'etat "A tester sur NAS".
- Ajout du champ email, de la creation personne depuis LDAP et de la popup de chargement en version 1.2.3 / BDD 8.
- Passage de l'etape 14 a l'etat "A tester sur NAS".
- Restriction du bouton perso `Mon materiel informatique` aux comptes avec pret ouvert en version 1.2.4 / BDD 8.
- Passage de l'etape 15 a l'etat "A tester sur NAS".
- Ajout des prets multiples generiques et du statut `pret_multiple` en version 1.3.0 / BDD 9.
- Ajout des transferts de prets individuel et global en version 1.3.2 / BDD 9.
