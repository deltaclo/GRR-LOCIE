# Recette MVP - Informatique materiel

Cette checklist regroupe la recette complete du MVP. Elle doit etre executee
sur une copie de recette avant validation finale.

## 1. Installation

1. Installer ou mettre a jour le module.
2. Verifier version applicative `1.0.0` et version BDD `6`.
3. Verifier les diagnostics de tables.
4. Verifier les dossiers de stockage.
5. Attribuer les roles de recette : lecteur, operateur, gestionnaire.

## 2. Referentiels

1. Verifier les categories historiques.
2. Creer une categorie de test.
3. Modifier cette categorie.
4. Archiver cette categorie.
5. Creer une personne de test.
6. Modifier cette personne.
7. Archiver cette personne.
8. Tester les exports `Personnes` et `Categories`.

## 3. Inventaire

1. Creer un materiel avec identifiant automatique.
2. Creer un materiel avec identifiant saisi.
3. Modifier les champs principaux : marque, serie, code-barres, MAC, OS,
   annee, localisation.
4. Tester les filtres par recherche, categorie et statut.
5. Ouvrir une fiche materiel.
6. Tester l'export `Materiels`.
7. Archiver un materiel de test.

## 4. Prets et restitutions

1. Creer un pret ouvert.
2. Verifier le statut materiel `en_pret`.
3. Tenter un deuxieme pret ouvert sur le meme materiel.
4. Verifier que l'action est refusee.
5. Restituer le pret.
6. Verifier le statut materiel `stocke`.
7. Annuler un pret de test.
8. Ouvrir l'historique depuis la fiche materiel.
9. Ouvrir l'historique depuis la fiche pret.
10. Avec un administrateur, ouvrir une fiche personne ayant une date de depart
    et des prets ouverts, puis aligner les fins de prets sur la date de depart.
11. Verifier que les prets restent ouverts et que leur date de fin prevue est
    mise a jour.
12. Tester l'export `Prets`.

## 5. Import CSV

1. Importer les categories.
2. Importer les personnes.
3. Importer les materiels.
4. Importer les prets.
5. Verifier la previsualisation avant execution.
6. Relancer un meme fichier et verifier l'anti-doublon.
7. Importer une ligne volontairement invalide et verifier l'erreur journalisee.
8. Verifier dans phpMyAdmin les lignes du journal d'import.

## 6. Alertes

1. Creer un pret en retard.
2. Verifier l'alerte `Pret en retard`.
3. Creer une personne avec date de depart passee et pret ouvert.
4. Verifier l'alerte `Personne partie`.
5. Simuler un materiel sans identifiant sur une copie de test.
6. Simuler un code-barres duplique sur une copie de test.
7. Corriger les situations.
8. Verifier la disparition des alertes.
9. Verifier le lien `hookDemandesStatus`.

## 7. Documents

1. Deposer un PDF sur une fiche materiel active.
2. Telecharger le document.
3. Tenter une extension interdite.
4. Tenter un fichier trop volumineux.
5. Tenter un acces direct a `storage/documents/`.
6. Archiver le document.
7. Verifier que le document archive n'est plus telechargeable.

## 8. Droits

1. Lecteur : consulter les listes, fiches, exports et documents.
2. Lecteur : verifier l'absence de formulaires d'ecriture.
3. Operateur : creer et restituer des prets.
4. Operateur : verifier l'absence d'administration, import et documents.
5. Gestionnaire : verifier l'acces complet au module fonctionnel.
6. Administrateur GRR : verifier l'acces a l'administration du module.

## 9. Non-regression

1. Ouvrir `gestion_materiel`.
2. Ouvrir `stock_chimique`.
3. Ouvrir `suivi_demandes`.
4. Ouvrir `boutons_perso`.
5. Verifier que les liens `hookDemandesStatus` se cumulent correctement.
6. Verifier les journaux Apache, PHP et MariaDB apres la recette.

## Validation finale

Le MVP est validable si :

- aucune erreur PHP fatale n'est presente dans les journaux ;
- toutes les tables attendues sont presentes ;
- les imports sont rejouables ;
- les prets ne creent pas de double pret ouvert sur un materiel non generique ;
- un materiel generique en pret multiple accepte plusieurs prets ouverts ;
- un pret ouvert peut etre transfere vers une autre personne sans perte
  d'historique ;
- le transfert vers une personne avec date de depart initialise la date de fin
  prevue du nouveau pret a cette date ;
- un administrateur peut aligner les fins prevues des prets ouverts d'une
  personne sur sa date de depart depuis la fiche personne ;
- tous les prets ouverts d'une personne peuvent etre transferes par un
  gestionnaire ;
- les documents ne sont pas accessibles directement ;
- les droits par role sont respectes ;
- les autres modules locaux restent operationnels.
