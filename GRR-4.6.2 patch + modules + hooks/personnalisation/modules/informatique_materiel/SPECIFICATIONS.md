# Specifications fonctionnelles du module Informatique materiel

Version de travail du 25 juin 2026.

Statut : etapes 0, 1, 2, 3 et 4 validees le 25 juin 2026. L'import CSV est
implemente en version 0.5.0 et attend la recette NAS.

## 1. Objectif

Le module `informatique_materiel` doit fournir dans GRR :

- un inventaire fiable du materiel informatique ;
- une gestion des personnes concernees par les prets ;
- un suivi des prets, retours et retards ;
- une disponibilite calculee ;
- des alertes operationnelles ;
- un import controle depuis l'Excel historique ;
- des droits adaptes aux profils d'utilisation ;
- un stockage securise des documents associes au materiel.

Le module est un outil de gestion quotidienne du parc informatique local. Il ne
remplace pas les decisions d'achat, les procedures de support, ni un outil de
gestion de parc specialise.

## 2. Definitions

### Personne

Membre ou utilisateur du laboratoire issu du classeur historique. Une personne
peut ne pas avoir de compte GRR. Le lien avec un login GRR pourra etre ajoute
plus tard, mais ne doit pas etre obligatoire pour importer l'existant.

### Materiel

Objet informatique inventorie : ordinateur, ecran, station d'accueil,
accessoire, disque, tablette, routeur, NAS ou autre materiel reference. Un
materiel peut etre marque comme generique lorsqu'il represente un stock
d'accessoires pretables simultanement, par exemple souris, clavier ou cle USB.

### Categorie

Type de materiel associe a un prefixe d'identifiant. Exemple : `OP` pour
ordinateur portable, `Ec` pour ecran, `A` pour accessoires.

### Pret

Affectation temporaire ou durable d'un materiel a une personne, avec date de
debut, localisation, date de fin prevue et date de fin effective.

### Restitution

Cloture d'un pret par saisie d'une date de fin effective. Le materiel redevient
disponible si aucun autre pret ouvert ne le concerne.

### Disponibilite

Etat calcule : un materiel actif est disponible lorsqu'il n'a aucun pret ouvert.

## 3. Donnees source

Le classeur Excel source contient :

| Feuille | Donnees importables | Volume constate |
|---|---|---:|
| `personnels` | personnes, cadre d'usage, date de depart | 79 |
| `materiels` | inventaire, identifiants, caracteristiques | 478 |
| `prets` | historique et prets ouverts | 483 |
| `_categories` | categories, prefixes, listes de reference | 17 designations |

Les colonnes calculees Excel doivent etre converties en regles applicatives
serveur, pas importees comme verite definitive.

## 4. Decisions proposees pour le MVP

| Sujet | Decision proposee |
|---|---|
| Module | Module autonome `informatique_materiel` |
| Relation avec `gestion_materiel` | Reference technique, pas de refactor pendant le MVP |
| Personnes | Table dediee, login GRR facultatif |
| Identifiants Excel | Conserves dans un champ historique |
| Nouveaux identifiants | Generes par prefixe et sequence |
| Disponibilite | Calculee depuis les prets ouverts |
| Double pret ouvert | Interdit pour un meme materiel, sauf materiel generique en pret multiple |
| Historique des prets | Conserve, non supprime dans les ecrans normaux |
| Date de retour | Cloture le pret, ne supprime pas l'enregistrement |
| Materiel obsolete | Archive plutot que supprime |
| Numero de serie | Non unique par defaut |
| Code-barres USMB | Unique si renseigne |
| MAC | A surveiller comme doublon, pas bloquant au premier import |
| Documents | Stockage protege comme `gestion_materiel` |
| Roles | Globaux au module pour le MVP |
| Import | Previsualisation obligatoire avant ecriture |
| Moteur SQL | InnoDB propose pour les operations liees pret/materiel |
| Suppression physique | Absente des ecrans normaux du MVP |

## 5. Roles et autorisations

Un administrateur general GRR possede automatiquement tous les droits.

| Fonction | Lecteur | Operateur | Gestionnaire | Admin GRR |
|---|:---:|:---:|:---:|:---:|
| Acceder au module | Oui | Oui | Oui | Oui |
| Voir personnes, materiels et prets | Oui | Oui | Oui | Oui |
| Exporter les listes autorisees | Oui | Oui | Oui | Oui |
| Creer ou modifier une personne | Non | Non | Oui | Oui |
| Creer ou modifier un materiel | Non | Non | Oui | Oui |
| Creer un pret | Non | Oui | Oui | Oui |
| Enregistrer une restitution | Non | Oui | Oui | Oui |
| Corriger un pret clos | Non | Non | Oui | Oui |
| Importer depuis CSV | Non | Non | Oui | Oui |
| Deposer ou retirer un document | Non | Non | Oui | Oui |
| Archiver une personne ou un materiel | Non | Non | Oui | Oui |
| Gerer les roles et la configuration | Non | Non | Non | Oui |
| Consulter le diagnostic technique | Non | Non | Non | Oui |

Un utilisateur sans role et qui n'est pas administrateur general ne voit pas le
module. Les droits sont toujours controles cote serveur.

## 6. Referentiels

### 6.1 Categories

Champs :

- prefixe, obligatoire ;
- designation, obligatoire ;
- description facultative ;
- actif ou archive ;
- auteur et dates techniques.

Regles :

- une designation active ne devrait pas etre dupliquee ;
- plusieurs designations peuvent partager un prefixe, comme dans le classeur ;
- l'archivage est prefere a la suppression ;
- le prefixe sert a generer les nouveaux identifiants.

### 6.2 Personnes

Champs :

- identifiant historique Excel ;
- prenom ;
- nom ;
- cadre d'usage ;
- date de depart ;
- login GRR facultatif ;
- statut actif ou archive ;
- notes ;
- auteur et dates techniques.

Regles :

- prenom et nom sont obligatoires ;
- l'identifiant historique est conserve pendant l'import ;
- une personne ayant des prets historiques ne doit pas etre supprimee ;
- la date de depart genere une alerte si du materiel reste prete.

## 7. Materiels

Champs :

- identifiant historique Excel ;
- identifiant courant ;
- categorie ;
- designation ;
- precision ;
- MAC ;
- marque ;
- numero de serie ;
- code-barres USMB ;
- OS ;
- annee ;
- commentaire ;
- localisation de stockage ;
- statut ;
- pret multiple ;
- notes ;
- auteur et dates techniques.

Statuts proposes :

- `actif` ;
- `stocke` ;
- `en_pret` ;
- `pret_multiple` ;
- `maintenance` ;
- `a_reformer` ;
- `archive`.

Regles :

- l'identifiant courant est obligatoire et unique ;
- a la creation manuelle, l'identifiant courant peut etre genere depuis le
  prefixe de la categorie ;
- le code-barres USMB est unique lorsqu'il est renseigne ;
- la disponibilite est calculee, meme si un statut d'affichage existe ;
- un materiel archive ne peut pas recevoir de nouveau pret ;
- un materiel avec un pret ouvert ne peut pas etre archive sans retour ou
  decision explicite d'un gestionnaire, a partir de l'etape Prets ;
- un materiel marque en pret multiple peut recevoir plusieurs prets ouverts ;
- le statut `pret_multiple` est applique automatiquement lorsqu'un materiel
  generique a plusieurs prets ouverts ;
- les doublons de numero de serie et de MAC sont signales au diagnostic.

## 8. Prets et restitutions

Champs :

- personne ;
- materiel ;
- localisation d'utilisation ;
- date de debut ;
- date de fin prevue ;
- date de fin effective ;
- commentaire ;
- statut ;
- auteur et dates techniques.

Statuts proposes :

- `ouvert` ;
- `clos` ;
- `annule`.

Regles :

- un materiel non generique ne peut avoir qu'un pret ouvert ;
- un materiel generique en pret multiple peut avoir plusieurs prets ouverts ;
- la date de debut est obligatoire ;
- la date de fin effective cloture le pret ;
- la date de fin effective ne peut pas preceder la date de debut ;
- la date de fin prevue alimente les alertes ;
- le retour ne supprime jamais le pret ;
- l'annulation conserve la ligne et remet le materiel en stock si le pret etait
  ouvert ;
- le transfert vers une autre personne cloture le pret courant et cree un
  nouveau pret ouvert afin de conserver l'historique ;
- si la personne destinataire a une date de depart, la date de fin prevue du
  nouveau pret est fixee a cette date ;
- le transfert de tous les prets ouverts d'une personne est reserve aux
  gestionnaires ;
- un administrateur peut aligner la date de fin prevue de tous les prets
  ouverts d'une personne sur la date de depart definie dans sa fiche, sans
  cloturer les prets ;
- une correction de pret clos doit etre reservee aux gestionnaires et
  journalisee ;
- les lignes importees depuis Excel doivent rester tracables.

## 9. Import initial

L'import doit etre realise a partir de CSV prepares depuis le classeur.

Principes :

- previsualiser avant toute ecriture ;
- separer personnes, categories, materiels et prets ;
- conserver le numero de ligne source ;
- stocker le hash du fichier source pour eviter une relance en doublon ;
- detecter les doublons d'identifiant ;
- signaler les references manquantes ;
- enregistrer chaque ligne importee dans un journal ;
- permettre une relance sans dupliquer les donnees deja acceptees.

Anomalies bloquantes :

- pret vers un materiel inconnu ;
- pret vers une personne inconnue ;
- identifiant materiel duplique ;
- code-barres USMB duplique lorsqu'il est renseigne ;
- deux prets ouverts pour le meme materiel non generique ;
- date de retour avant la date de debut.

Anomalies non bloquantes a signaler :

- numero de serie duplique ;
- MAC dupliquee ;
- materiel sans marque ;
- materiel sans localisation ;
- personne avec date de depart passee.

## 10. Documents

Types proposes :

- facture ;
- bon de livraison ;
- notice ;
- garantie ;
- intervention ;
- photo ;
- autre.

Regles :

- un document est rattache a un materiel ;
- stockage sous nom physique aleatoire ;
- acces par `download.php` uniquement ;
- controle session et droits avant telechargement ;
- liste blanche d'extensions ;
- taille maximale configurable ;
- aucun acces HTTP direct au repertoire de stockage ;
- suppression ou archivage reserve aux gestionnaires.

## 11. Alertes

Types d'alertes du MVP :

- pret en retard ;
- personne partie avec pret ouvert ;
- materiel actif sans identifiant courant ;
- materiel actif sans categorie ;
- code-barres duplique ;
- double pret ouvert non generique detecte par diagnostic.

L'integration dans `hookDemandesStatus` concatene son rendu avec les autres
modules et n'affiche un lien que si des alertes sont actives.

## 12. Securite

- controle de session sur toutes les pages autonomes ;
- controle d'autorisation cote serveur pour chaque action ;
- requetes preparees pour toutes les donnees utilisateur ;
- echappement HTML systematique ;
- jeton CSRF propre au module ;
- validation stricte des identifiants et dates ;
- stockage documentaire protege ;
- noms physiques aleatoires ;
- messages utilisateur distincts des details techniques journalises.

## 13. Contraintes techniques

- GRR 4.6.2 avec les hooks presents dans le projet ;
- PHP 8.4 ;
- MariaDB 10 ;
- Apache 2.4 sur Synology DSM 7 ;
- pas de dependance Composer supplementaire ;
- pas de modification du coeur GRR prevue pour le MVP ;
- pages integrees dans "Gerer mon compte" ;
- page d'administration autonome conservee ;
- requetes compatibles avec le prefixe de tables GRR.

## 14. Criteres d'acceptation du MVP

Le MVP sera accepte lorsque les scenarios suivants seront valides sur le NAS :

1. un administrateur installe et active le module sans erreur ;
2. un utilisateur sans role ne peut pas acceder au module ;
3. chaque role ne voit et n'execute que les actions autorisees ;
4. un gestionnaire cree une categorie ;
5. un gestionnaire cree une personne ;
6. un gestionnaire cree un materiel ;
7. l'identifiant materiel est genere correctement ;
8. un operateur cree un pret sur un materiel disponible ;
9. un second pret ouvert sur le meme materiel non generique est refuse ;
10. une restitution cloture le pret ;
11. le materiel redevient disponible ;
12. un pret en retard est signale ;
13. une personne partie avec pret ouvert est signalee ;
14. l'import CSV previsualise les donnees avant ecriture ;
15. une relance d'import ne cree pas de doublons ;
16. les documents sont telecharges uniquement par un utilisateur autorise ;
17. l'acces direct au stockage documentaire echoue ;
18. les exports respectent les droits ;
19. les diagnostics ne signalent pas d'incoherence bloquante ;
20. un materiel generique accepte plusieurs prets ouverts et passe au statut
    `pret_multiple` ;
21. un pret ouvert peut etre transfere vers une autre personne depuis un
    materiel ou une fiche personne ;
22. tous les prets ouverts d'une personne peuvent etre transferes par un
    gestionnaire ;
23. les autres modules GRR continuent a fonctionner.

## 15. Points soumis a validation

La validation de l'etape 0 vaut accord sur les choix suivants :

1. creer un module autonome `informatique_materiel` ;
2. ne pas modifier `gestion_materiel` pendant le MVP ;
3. conserver les identifiants Excel historiques ;
4. generer les nouveaux identifiants par prefixe et sequence ;
5. calculer la disponibilite depuis les prets ouverts ;
6. interdire deux prets ouverts pour un meme materiel non generique ;
7. stocker les personnes dans une table dediee, login GRR facultatif ;
8. stocker les roles par login GRR ;
9. utiliser InnoDB pour les tables du module ;
10. archiver plutot que supprimer dans les ecrans normaux ;
11. importer uniquement apres previsualisation ;
12. documenter les migrations avant toute creation de table.
