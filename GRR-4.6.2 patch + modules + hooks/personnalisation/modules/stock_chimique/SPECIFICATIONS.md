# Spécifications fonctionnelles du module Stock chimique

Version de travail du 19 juin 2026.

Statut : proposition issue de l’étape 0, à valider avant le développement du
socle du module.

## 1. Objectif

Le module `stock_chimique` doit fournir dans GRR :

- un catalogue des produits chimiques ;
- un inventaire des contenants réellement stockés ;
- la localisation et la quantité disponible de chaque contenant ;
- un historique fiable des mouvements ;
- un accès sécurisé aux FDS et aux autres documents ;
- des alertes opérationnelles ;
- des droits adaptés aux différents utilisateurs.

Le module est un outil de gestion et de traçabilité. Il ne valide pas la
classification réglementaire d’un produit et ne remplace pas l’évaluation du
risque chimique.

## 2. Définitions

### Produit

Référence commerciale décrivant une substance ou un mélange : nom commercial,
fabricant, fournisseur principal, référence, informations d’identification et
informations de danger.

### Contenant

Unité physique réellement stockée : flacon, bidon, bouteille, sac ou autre
conditionnement. Un produit peut avoir plusieurs contenants, lots,
emplacements et dates de péremption.

### Stock

Somme des quantités courantes des contenants actifs d’un produit. Le stock peut
être calculé globalement ou par emplacement.

### Mouvement

Événement immuable expliquant une variation de quantité ou un changement
d’emplacement.

### FDS courante

Document de type FDS marqué comme version courante pour un produit et une
langue. Une ancienne version reste archivée.

## 3. Décisions proposées pour le MVP

Ces décisions forment la base recommandée. Leur validation clôturera
l’étape 0.

| Sujet | Décision proposée |
|---|---|
| Stock négatif | Interdit |
| Précision | Quatre décimales avec `DECIMAL(15,4)` |
| Unités | `mg`, `g`, `kg`, `mL`, `L` ou `unité` |
| Conversion | Aucune conversion automatique |
| Unité d’un produit | Une unité de stock de référence |
| Unité d’un contenant | Identique à celle de son produit |
| Transfert | Transfert du contenant entier uniquement |
| Historique | Mouvements non modifiables et non supprimables |
| Correction | Nouveau mouvement motivé, jamais modification d’un ancien |
| Archivage | Préféré à toute suppression |
| FDS ancienne | Alerte « à vérifier », pas déclaration automatique d’invalidité |
| Délai FDS à vérifier | Configurable, 36 mois par défaut |
| Alerte péremption | Configurable, 90 jours par défaut |
| Périmètre des rôles | Global au module pour le MVP |
| Accès aux quantités | Tous les utilisateurs autorisés voient les quantités |
| Multi-site | Géré par une hiérarchie d’emplacements |
| Moteur SQL | InnoDB proposé pour permettre les transactions |
| Encodage SQL | `utf8`, conforme aux modules locaux existants |
| Suppression physique | Absente des écrans normaux du MVP |

## 4. Rôles et autorisations

Un administrateur général GRR possède automatiquement tous les droits. Les
autres rôles sont stockés par login GRR.

| Fonction | Lecteur | Opérateur | Gestionnaire | Admin GRR |
|---|:---:|:---:|:---:|:---:|
| Accéder au module | Oui | Oui | Oui | Oui |
| Voir catalogue et emplacements | Oui | Oui | Oui | Oui |
| Voir quantités et alertes | Oui | Oui | Oui | Oui |
| Télécharger les documents | Oui | Oui | Oui | Oui |
| Exporter les listes autorisées | Oui | Oui | Oui | Oui |
| Réceptionner un contenant | Non | Oui | Oui | Oui |
| Enregistrer une consommation | Non | Oui | Oui | Oui |
| Transférer un contenant | Non | Oui | Oui | Oui |
| Créer une correction | Non | Non | Oui | Oui |
| Éliminer ou retourner un contenant | Non | Non | Oui | Oui |
| Gérer produits et fournisseurs | Non | Non | Oui | Oui |
| Gérer les emplacements | Non | Non | Oui | Oui |
| Déposer ou archiver un document | Non | Non | Oui | Oui |
| Archiver produit ou contenant | Non | Non | Oui | Oui |
| Gérer les rôles et la configuration | Non | Non | Non | Oui |
| Consulter le diagnostic technique | Non | Non | Non | Oui |

Un utilisateur sans rôle et qui n’est pas administrateur général ne voit pas le
module.

Les droits sont toujours contrôlés côté serveur. Masquer un bouton ne constitue
pas un contrôle d’accès.

## 5. Catalogue

### 5.1 Fournisseur

Champs :

- nom, obligatoire ;
- adresse ;
- contact ;
- téléphone ;
- courrier électronique ;
- site internet ;
- notes ;
- statut actif ou archivé ;
- auteur et dates de création et modification.

Règles :

- deux fournisseurs actifs ne devraient pas avoir le même nom ;
- l’application affiche un avertissement de doublon avant création ;
- un fournisseur utilisé ne peut pas être supprimé ;
- l’archivage reste possible et ne modifie pas l’historique.

### 5.2 Produit

Champs obligatoires :

- nom commercial ;
- unité de stock ;
- statut actif.

Champs facultatifs :

- référence interne unique ;
- fournisseur principal ;
- référence fournisseur ;
- fabricant ;
- numéro CAS ;
- numéro CE ;
- UFI ;
- état physique ;
- catégorie ;
- pictogrammes CLP ;
- mentions de danger H ;
- conseils de prudence P ;
- statut CMR : non renseigné, non ou oui ;
- conditions particulières de stockage ;
- seuil de stock minimal ;
- description et notes ;
- auteur et dates de création et modification.

Règles :

- la référence interne, lorsqu’elle est renseignée, est unique ;
- un avertissement de doublon est affiché sur la combinaison nom, fabricant et
  référence fournisseur ;
- l’unité d’un produit ne peut plus être modifiée après le premier mouvement ;
- les informations CLP sont saisies comme des données provenant de l’étiquette
  ou de la FDS ;
- le module ne déduit pas automatiquement une classe de danger ;
- un produit ayant des contenants, mouvements ou documents est archivé au lieu
  d’être supprimé.

## 6. Emplacements

Types proposés :

- site ;
- bâtiment ;
- local ;
- armoire ;
- réfrigérateur ;
- étagère ;
- autre.

Champs :

- code unique ;
- nom ;
- type ;
- emplacement parent facultatif ;
- responsable facultatif ;
- description ;
- statut actif ou archivé ;
- auteur et dates de création et modification.

Règles :

- la hiérarchie ne doit contenir aucune boucle ;
- un emplacement ne peut pas être son propre parent ;
- un emplacement avec des enfants actifs ou des contenants actifs ne peut pas
  être archivé ;
- la profondeur conseillée est limitée à six niveaux ;
- le chemin complet est affiché dans les listes et formulaires ;
- la gestion multi-site utilise cette même hiérarchie.

## 7. Contenants

Champs :

- produit, obligatoire ;
- code interne unique, obligatoire ;
- fournisseur réel facultatif ;
- numéro de lot ;
- description du conditionnement ;
- emplacement courant ;
- quantité courante ;
- unité, reprise du produit ;
- date de réception ;
- date d’ouverture ;
- date de péremption ;
- statut ;
- notes ;
- auteur et dates de création et modification.

Statuts stockés :

- `en_stock` ;
- `vide` ;
- `elimine` ;
- `retourne` ;
- `archive`.

La péremption est calculée à partir de la date et n’est pas un statut stocké.
Un contenant périmé peut rester physiquement en stock tant que son élimination
n’a pas été enregistrée.

Règles :

- la création d’un contenant et son mouvement d’entrée sont atomiques ;
- la quantité initiale est strictement positive ;
- la quantité courante ne peut jamais être négative ;
- un contenant vide reçoit automatiquement le statut `vide` ;
- une nouvelle entrée sur un contenant vide n’est pas permise : un nouveau
  contenant doit être créé ;
- un contenant éliminé, retourné ou archivé ne reçoit plus de mouvement ;
- le code interne peut être saisi ou généré par le module ;
- la date d’ouverture ne peut pas précéder la réception ;
- la date de péremption ne peut pas précéder la réception ;
- le contenant conserve son fournisseur et son lot historiques même si la fiche
  produit évolue.

## 8. Mouvements

### 8.1 Types et effets

| Type | Variation | Rôle minimal | Règle |
|---|---:|---|---|
| Entrée | Positive | Opérateur | Création d’un nouveau contenant |
| Consommation | Négative | Opérateur | Quantité inférieure ou égale au stock |
| Transfert | Nulle | Opérateur | Déplacement du contenant entier |
| Correction positive | Positive | Gestionnaire | Motif obligatoire |
| Correction négative | Négative | Gestionnaire | Motif obligatoire, stock suffisant |
| Élimination | Négative | Gestionnaire | Solde le contenant |
| Retour fournisseur | Négative | Gestionnaire | Solde le contenant |

### 8.2 Données conservées

Chaque mouvement conserve :

- le contenant ;
- le type ;
- la quantité concernée ;
- l’unité ;
- la quantité avant ;
- la quantité après ;
- l’emplacement source ;
- l’emplacement destination ;
- le mouvement corrigé, lorsque cela s’applique ;
- le motif ou commentaire ;
- la date effective ;
- l’auteur et la date d’enregistrement ;
- une clé de requête unique empêchant une double soumission.

### 8.3 Règles transactionnelles

Pour chaque mouvement :

1. ouvrir une transaction InnoDB ;
2. verrouiller le contenant avec `SELECT ... FOR UPDATE` ;
3. relire sa quantité, son statut et son emplacement ;
4. vérifier les droits et règles métier ;
5. insérer le mouvement ;
6. mettre à jour le contenant ;
7. écrire le journal ;
8. valider la transaction ;
9. annuler entièrement en cas d’erreur.

La quantité courante du contenant est conservée pour les affichages rapides.
Le diagnostic du module doit pouvoir la comparer à la somme des mouvements.

Une évacuation vers les déchets chimiques est enregistrée comme une
élimination avec un motif obligatoire. L’évacuation d’un produit applique cette
opération à tous ses contenants encore en stock dans une transaction unique.
Elle ne supprime ni le produit, ni les contenants, ni leur historique.

Un produit ne génère une alerte de stock ou de FDS que s’il possède au moins un
contenant au statut `en_stock`.

### 8.4 Immutabilité

- aucun écran ne modifie un mouvement validé ;
- aucun écran ne supprime un mouvement ;
- une erreur est corrigée par un nouveau mouvement ;
- une correction référence si possible le mouvement concerné ;
- l’auteur et la date d’enregistrement ne sont jamais remplacés.

## 9. Documents

Types proposés :

- FDS ;
- certificat d’analyse ;
- fiche technique ;
- mode opératoire ;
- notice ;
- étiquette ;
- autre.

Champs métier :

- produit ;
- type ;
- langue ;
- fournisseur ou émetteur ;
- date de révision ;
- numéro de version ;
- indicateur de version courante ;
- description.

Métadonnées techniques :

- nom original ;
- nom physique aléatoire ;
- type MIME détecté ;
- taille ;
- empreinte SHA-256 ;
- auteur et date du dépôt ;
- auteur et date d’archivage.

Règles :

- une FDS est liée à un produit ;
- la date de révision est obligatoire pour une FDS ;
- une seule FDS courante est autorisée par produit et par langue ;
- déposer une nouvelle FDS courante archive l’ancienne dans la même transaction ;
- l’ancienne version reste téléchargeable ;
- l’âge d’une FDS génère seulement une alerte « à vérifier » ;
- le délai de vérification est configurable, avec 36 mois par défaut ;
- les FDS utilisent le format PDF dans le MVP ;
- les autres documents utilisent une liste blanche configurable ;
- la taille maximale par défaut est de 10 Mo ;
- aucun fichier n’est distribué directement par Apache ;
- le téléchargement passe par un script vérifiant session et autorisation ;
- un document est archivé au lieu d’être supprimé dans le fonctionnement normal.

Extensions proposées pour les autres documents :

`pdf`, `txt`, `csv`, `jpg`, `jpeg`, `png`, `odt`, `ods`, `doc`, `docx`,
`xls`, `xlsx`.

Les formats exécutables, scripts, pages web et archives sont exclus par défaut.

## 10. Alertes

### Stock faible

Déclenchée lorsque la somme des quantités des contenants actifs est strictement
inférieure au seuil du produit. Un seuil nul désactive cette alerte.

### Péremption proche

Déclenchée pour un contenant actif dont la date de péremption est comprise entre
la date courante et le délai configurable, fixé à 90 jours par défaut.

### Produit périmé

Déclenchée lorsque la date de péremption d’un contenant actif est antérieure à
la date courante.

### FDS manquante

Déclenchée lorsqu’un produit actif ne possède aucune FDS courante.

### FDS à vérifier

Déclenchée lorsque la date de révision de la FDS courante dépasse le délai de
vérification configuré. Le libellé ne doit pas laisser entendre que le document
est automatiquement invalide.

Les alertes sont visibles dans le tableau de bord. L’intégration dans
`hookDemandesStatus` n’est réalisée qu’après vérification de la coexistence avec
les autres modules.

## 11. Archivage et conservation

- produits, fournisseurs et emplacements sont archivés ;
- les contenants terminés restent consultables ;
- les mouvements sont conservés sans limite dans le MVP ;
- les anciennes FDS sont conservées ;
- les documents archivés restent téléchargeables selon les droits ;
- aucune purge automatique n’est prévue ;
- une éventuelle purge future nécessitera une fonction distincte, une
  confirmation renforcée, un journal et une sauvegarde préalable.

## 12. Sécurité

- contrôle de session sur toutes les pages autonomes ;
- contrôle d’autorisation côté serveur pour chaque action ;
- requêtes préparées pour toutes les données utilisateur ;
- échappement HTML systématique ;
- jeton CSRF propre au module pour chaque formulaire d’écriture ;
- clé d’idempotence pour les mouvements ;
- validation stricte des identifiants, quantités, unités et dates ;
- stockage documentaire protégé ;
- type MIME détecté côté serveur ;
- noms physiques aléatoires ;
- empreinte SHA-256 ;
- en-têtes de téléchargement sécurisés ;
- aucune information sensible dans les journaux techniques ;
- messages utilisateur distincts des détails consignés dans les logs PHP.

GRR ne fournit pas actuellement de mécanisme CSRF utilisé par les modules
locaux inspectés. Le module devra donc inclure un petit composant dédié, sans
modifier le cœur de GRR.

## 13. Contraintes techniques

- GRR 4.6.2 avec les hooks présents dans le projet ;
- PHP 8.4 ;
- MariaDB 10 ;
- Apache 2.4 sur Synology DSM 7 ;
- pas de dépendance Composer supplémentaire ;
- pas de modification du cœur prévue pour le MVP ;
- pages intégrées dans « Gérer mon compte » ;
- page d’administration autonome conservée ;
- pagination côté serveur pour les mouvements et documents ;
- requêtes compatibles avec le préfixe de tables GRR.

Dimensionnement de référence pour concevoir les index :

- 5 000 produits ;
- 50 000 contenants ;
- 500 000 mouvements ;
- 20 000 documents.

Ces valeurs ne sont pas une limite fonctionnelle, mais une cible de conception
pour éviter les listes non paginées et les requêtes sans index.

## 14. Critères d’acceptation du MVP

Le MVP sera accepté lorsque les scénarios suivants seront validés sur le NAS :

1. un administrateur installe et active le module sans erreur ;
2. un utilisateur sans rôle ne peut pas accéder au module ;
3. chaque rôle ne voit et n’exécute que les actions autorisées ;
4. un gestionnaire crée fournisseur, emplacement et produit ;
5. un opérateur réceptionne deux lots du même produit ;
6. les quantités globales et par emplacement sont correctes ;
7. une consommation partielle met à jour le bon contenant ;
8. une sortie supérieure au stock est refusée ;
9. une double soumission ne crée qu’un seul mouvement ;
10. un transfert déplace le contenant sans changer sa quantité ;
11. une correction exige un motif et reste visible dans l’historique ;
12. un ancien mouvement ne peut pas être modifié ou supprimé ;
13. une élimination solde et verrouille le contenant ;
14. une FDS PDF est déposée et téléchargée par un utilisateur autorisé ;
15. une nouvelle FDS archive automatiquement la version précédente ;
16. l’accès HTTP direct au stockage documentaire échoue ;
17. un fichier interdit ou trop volumineux est refusé ;
18. les alertes stock, péremption et FDS sont correctes ;
19. les exports respectent les filtres et les droits ;
20. les diagnostics ne détectent aucun stock incohérent ou enregistrement
    orphelin ;
21. les autres modules GRR continuent à fonctionner ;
22. une sauvegarde et une restauration conservent la cohérence entre la base et
    les documents.

## 15. Points soumis à validation

La validation de l’étape 0 vaut accord sur les choix suivants :

1. rôles globaux au module, sans restriction par site dans le MVP ;
2. visibilité des quantités pour tous les utilisateurs autorisés ;
3. aucune conversion automatique d’unité ;
4. unité figée après le premier mouvement ;
5. stock négatif interdit ;
6. transfert du contenant entier seulement ;
7. corrections réservées aux gestionnaires ;
8. aucune suppression normale de l’historique ou des documents ;
9. FDS « à vérifier » après 36 mois par défaut ;
10. alerte de péremption à 90 jours par défaut ;
11. InnoDB pour les tables du module ;
12. schéma et migrations décrits dans `SCHEMA_SQL.md`.
