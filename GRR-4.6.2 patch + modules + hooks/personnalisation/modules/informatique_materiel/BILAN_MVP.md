# Bilan MVP et evolutions post-MVP

## Perimetre livre

Le MVP couvre :

- roles lecteur, operateur, gestionnaire et administrateur GRR ;
- referentiels personnes et categories ;
- inventaire du materiel informatique ;
- generation d'identifiants materiel ;
- prets, restitutions, annulations et historiques ;
- imports CSV issus du fichier Excel historique ;
- alertes operationnelles ;
- documents par materiel ;
- exports CSV ;
- diagnostics techniques et fonctionnels ;
- integration dans `Gerer mon compte` et `hookDemandesStatus`.

## Points volontairement simples

- Les roles sont stockes localement par login GRR.
- Les documents sont archives logiquement, sans suppression physique automatique.
- La disponibilite est calculee depuis les prets ouverts.
- Les imports passent par CSV, pas par XLSX direct.
- Les relations ne s'appuient pas sur des cles etrangeres SQL.

## Points a surveiller en production

- Volume des imports et temps d'affichage des alertes.
- Droits d'ecriture Apache sur les dossiers `storage/`.
- Taille totale de `storage/documents/`.
- Qualite des identifiants historiques importes depuis Excel.
- Doublons historiques de MAC, numeros de serie et codes-barres.

## Evolutions post-MVP possibles

1. Ajout d'une recherche plein texte plus ergonomique sur les fiches materiel.
2. Ajout d'une impression d'etiquettes code-barres.
3. Ajout d'un scan code-barres mobile.
4. Ajout de tableaux de bord par categorie ou localisation.
5. Ajout d'une purge controlee des documents archives.
6. Ajout d'un export global de recette.
7. Ajout d'une synchronisation optionnelle avec un annuaire ou une source RH.
8. Ajout de statistiques d'usage par annee ou type de materiel.
9. Ajout d'une reservation de materiel si le besoin metier se confirme.
10. Ajout d'un controle avance des types MIME par extension.

## Critere de passage en production

Le module peut etre propose en production lorsque :

- la recette MVP est terminee ;
- la sauvegarde et la restauration ont ete testees ;
- les journaux Apache/PHP/MariaDB ne montrent plus d'erreur ;
- les droits par role sont valides ;
- les donnees importees sont comparees au fichier Excel source ;
- les responsables metier valident la reprise des usages Excel.
