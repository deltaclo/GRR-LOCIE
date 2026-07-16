# Installation MVP - Informatique materiel

Ce document decrit l'installation de recette du module `informatique_materiel`
sur GRR 4.6.2 avec Synology DSM 7, Apache 2.4, PHP 8.4 et MariaDB 10.

## Prerequis

- Avoir une sauvegarde recente de la base GRR.
- Avoir une sauvegarde recente du dossier GRR.
- Avoir un compte administrateur GRR.
- Connaitre le prefixe des tables GRR.
- Verifier que PHP autorise les uploads si les documents doivent etre testes.

## Fichiers a deployer

Copier le dossier complet :

```text
personnalisation/modules/informatique_materiel/
```

Le dossier doit contenir notamment :

- `infos.php`
- `installation.php`
- `controleur.php`
- `admin.php`
- `download.php`
- `export.php`
- `lib/`
- `storage/imports/.htaccess`
- `storage/documents/.htaccess`

## Installation ou mise a jour

1. Copier le dossier du module dans `personnalisation/modules/`.
2. Ouvrir l'administration GRR des modules externes.
3. Installer ou reactiver `Informatique materiel`.
4. Ouvrir l'administration du module.
5. Verifier la version applicative `1.0.0`.
6. Verifier la version BDD attendue `6`.
7. Verifier que toutes les tables sont indiquees `OK`.
8. Verifier que le stockage documentaire est indique `OK`.
9. Attribuer au moins un role `gestionnaire` a un compte de recette.

## Tables attendues

Avec le prefixe reel GRR, les tables suivantes doivent exister :

- `*_informatique_materiel_role`
- `*_informatique_materiel_journal`
- `*_informatique_materiel_personne`
- `*_informatique_materiel_categorie`
- `*_informatique_materiel_sequence`
- `*_informatique_materiel_item`
- `*_informatique_materiel_pret`
- `*_informatique_materiel_document`
- `*_informatique_materiel_import_log`

## Verifications PHP

Depuis la racine GRR :

```sh
find personnalisation/modules/informatique_materiel -type f -name '*.php' -exec php -l {} \;
```

Tous les fichiers doivent retourner `No syntax errors detected`.

## Verifications fonctionnelles rapides

1. Ouvrir `Gerer mon compte`.
2. Verifier le bouton `Informatique materiel`.
3. Ouvrir le tableau de bord du module.
4. Ouvrir les pages `Personnes`, `Categories`, `Materiels`, `Prets`,
   `Alertes` et `Import CSV`.
5. Ouvrir une fiche materiel.
6. Verifier que le panneau `Documents` s'affiche.
7. Tester un export CSV.
8. Verifier que les modules `gestion_materiel`, `stock_chimique`,
   `suivi_demandes` et `boutons_perso` restent accessibles.

## Points de surveillance

- Les dossiers `storage/imports/` et `storage/documents/` doivent etre
  proteges contre l'acces HTTP direct.
- Le compte Apache doit pouvoir ecrire dans `storage/imports/` et
  `storage/documents/`.
- Les uploads PHP doivent autoriser la taille configuree dans le module.
- Les journaux Apache/PHP ne doivent pas contenir d'erreur 500 apres ouverture
  des pages principales.
