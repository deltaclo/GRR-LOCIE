# Recette finale NAS - Boutons perso et droits des modules

## Objet

Valider sur Synology DSM 7 le fonctionnement de `boutons_perso` version 1.4.1
avec :

- Gestion materiel version 0.14.3 ;
- Stock chimique version 0.11.5 ;
- Suivi des demandes version 4.5.1 ;
- PHP 8.4 ;
- Apache 2.4 ;
- MariaDB 10.

Cette recette valide l'affichage et l'organisation des boutons. Elle ne remplace
pas les controles d'autorisation serveur propres a chaque module.

## 1. Informations de recette

| Information | Valeur |
|---|---|
| Date | |
| Testeur | |
| NAS / environnement | |
| URL GRR | |
| Prefixe SQL | |
| Version GRR | 4.6.2 |
| Version Boutons perso | 1.4.1 |
| Version BDD Boutons perso | 5 |

Etats a utiliser :

- `OK` : resultat conforme ;
- `KO` : resultat non conforme ;
- `NA` : test non applicable, avec justification.

## 2. Sauvegarde et prealables

Avant la recette :

1. sauvegarder la base GRR ou au minimum la table
   `*_boutons_perso_button` ;
2. noter l'etat actif ou inactif des quatre modules externes ;
3. noter les roles Stock chimique des comptes de test ;
4. noter les gestionnaires et affectations Gestion materiel ;
5. noter les comptes actives ou desactives dans Suivi des demandes ;
6. verifier qu'aucune erreur PHP n'est deja presente au chargement d'une vue
   planning.

Important : dans Suivi des demandes, un compte sans configuration explicite est
actif par defaut. Le compte de test sans droit doit donc etre explicitement
place dans la colonne `Module desactive`.

## 3. Verification technique

Executer depuis la racine de GRR :

```bash
php84 -l personnalisation/modules/boutons_perso/admin.php
php84 -l personnalisation/modules/boutons_perso/controleur.php
php84 -l personnalisation/modules/boutons_perso/lib/Config.php
php84 -l personnalisation/modules/boutons_perso/lib/ModuleRegistry.php
php84 -l personnalisation/modules/boutons_perso/lib/Navigation.php
php84 -l personnalisation/modules/boutons_perso/lib/Renderer.php
php84 -l personnalisation/modules/boutons_perso/lib/Repository.php
php84 -l personnalisation/modules/stagiaire/lib/Navigation.php
php84 -l compte/compte.php
php84 -l personnalisation/modules/gestion_materiel/lib/Navigation.php
php84 -l personnalisation/modules/gestion_materiel/lib/Rights.php
php84 -l personnalisation/modules/stock_chimique/lib/Navigation.php
php84 -l personnalisation/modules/suivi_demandes/lib/Navigation.php
```

Resultat attendu : `No syntax errors detected` pour chaque fichier.

| Test | Etat | Observation |
|---|---|---|
| Syntaxe PHP | | |

## 4. Verification SQL

Adapter le prefixe de table si necessaire :

```sql
SELECT id, source_type, source_key, label, position_order, active,
       account_menu_active, account_position_order
FROM grr_boutons_perso_button
ORDER BY position_order, label, id;
```

Verifier :

- les boutons personnalises ont `source_type = custom` et
  `source_key IS NULL` ;
- une seule ligne existe pour chaque bouton module ;
- les lignes modules peuvent avoir des ordres differents ;
- la valeur `active` correspond a l'administration.
- les colonnes `account_menu_active` et `account_position_order` sont presentes ;
- `module:informatique_materiel_user` a `account_menu_active = 0` par defaut.

Verifier l'index :

```sql
SHOW INDEX
FROM grr_boutons_perso_button
WHERE Key_name = 'source_button';
```

Resultat attendu : deux lignes pour l'index unique, une pour `source_type` et
une pour `source_key`.

| Test | Etat | Observation |
|---|---|---|
| Colonnes source presentes | | |
| Colonnes menu compte presentes | | |
| Lignes modules sans doublon | | |
| Index unique source_button | | |
| Boutons historiques conserves | | |

## 5. Preparation des comptes

Utiliser de preference des comptes de test dedies.

| Profil | Gestion materiel | Stock chimique | Suivi demandes | Boutons attendus |
|---|---|---|---|---|
| P0 Aucun module | Ni gestionnaire ni affectation active | Aucun role | Explicitement desactive | Aucun bouton module |
| P1 Materiel affecte | Affecte a un materiel actif | Aucun role | Desactive | Gestion materiel |
| P2 Gestionnaire materiel | Gestionnaire | Aucun role | Desactive | Gestion materiel |
| P3 Lecteur chimique | Aucun acces | Lecteur | Desactive | Stock chimique |
| P4 Operateur chimique | Aucun acces | Operateur | Desactive | Stock chimique |
| P5 Gestionnaire chimique | Aucun acces | Gestionnaire | Desactive | Stock chimique |
| P6 Suivi seulement | Aucun acces | Aucun role | Active | Suivi des demandes |
| P7 Deux modules | Affectation active | Lecteur | Desactive | Gestion materiel et Stock chimique |
| P8 Trois modules | Affectation ou gestionnaire | Role Stock chimique | Active | Les trois boutons |
| P9 Administrateur | Administrateur general | Automatique | Automatique | Les trois boutons |

Les boutons personnalises actifs doivent rester visibles pour tous ces profils.

## 6. Recette de l'administration

Ouvrir `compte.php?pc=boutons_perso` avec un administrateur general.

| ID | Action | Resultat attendu | Etat | Observation |
|---|---|---|---|---|
| A01 | Afficher la liste | Boutons personnalises et boutons modules connus presents | | |
| A02 | Examiner les types | Badges `Personnalise` et `Module` corrects | | |
| A03 | Examiner les sources | Etat disponible/inactif coherent avec les modules | | |
| A04 | Configurer un bouton module | Libelle et URL en lecture seule | | |
| A05 | Modifier l'ordre | Nouvelle valeur enregistree | | |
| A06 | Modifier le style | Style et apercu mis a jour | | |
| A07 | Configurer une nouvelle fenetre | Dimensions et nom enregistres | | |
| A08 | Ajouter une confirmation | Message enregistre | | |
| A09 | Desactiver puis reactiver | Valeur `active` mise a jour | | |
| A10 | Examiner les actions | Aucun bouton Supprimer pour un module | | |
| A11 | Modifier un bouton personnalise | Fonctionnement historique conserve | | |
| A12 | Supprimer un bouton personnalise de test | Suppression possible apres confirmation | | |
| A13 | Examiner le diagnostic | Table, colonnes et index tous en etat OK | | |
| A14 | Ouvrir Configuration | Le formulaire s'affiche en popup | | |
| A15 | Ajouter un bouton | Le formulaire s'affiche en popup et se ferme apres succes | | |
| A16 | Modifier un bouton personnalise | La popup s'ouvre avec les valeurs existantes | | |
| A17 | Supprimer un bouton de test | La confirmation est affichee en popup | | |
| A18 | Fermer les popups | Croix, fond et touche Escape ferment la popup | | |
| A19 | Ouvrir Configuration | L'option `Gerer les boutons modules de Gerer mon compte` est disponible | | |
| A20 | Configurer un bouton module | `Afficher dans Gerer mon compte` et `Ordre menu compte` sont disponibles | | |
| A21 | Afficher sur desktop | L'administration occupe toute la largeur disponible de la zone de contenu | | |
| A22 | Reduire la largeur du navigateur | La liste des boutons reste lisible et passe en affichage empile sur mobile | | |
| A23 | Ouvrir les popups sur mobile | Les popups restent dans la largeur de l'ecran | | |

## 6 bis. Recette du menu Gerer mon compte

Ouvrir `compte.php` avec un administrateur general.

| ID | Action | Resultat attendu | Etat | Observation |
|---|---|---|---|---|
| M01 | Option globale desactivee | Le menu historique issu de `hookCompteMenu` reste utilise | | |
| M02 | Activer l'option globale | Les liens natifs `Mon compte`, `Mes connexions`, `Mes reservations` restent visibles | | |
| M03 | Cocher `Afficher dans Gerer mon compte` sur un bouton module autorise | Le bouton apparait dans le menu gauche | | |
| M04 | Decocher `Afficher dans Gerer mon compte` | Le bouton disparait du menu gauche uniquement | | |
| M05 | Modifier `Ordre menu compte` | L'ordre du menu gauche suit cette valeur | | |
| M06 | Tester un utilisateur sans droit module | Le bouton du module non autorise est masque | | |
| M07 | Desactiver l'option globale | Le rendu historique par hooks est restaure | | |

## 7. Recette des droits

Pour chaque profil, ouvrir une vue planning jour et noter les boutons visibles.

| Profil | Resultat attendu | Etat | Observation |
|---|---|---|---|
| P0 Aucun module | Aucun bouton module | | |
| P1 Materiel affecte | Gestion materiel uniquement | | |
| P2 Gestionnaire materiel | Gestion materiel uniquement | | |
| P3 Lecteur chimique | Stock chimique uniquement | | |
| P4 Operateur chimique | Stock chimique uniquement | | |
| P5 Gestionnaire chimique | Stock chimique uniquement | | |
| P6 Suivi seulement | Suivi des demandes uniquement | | |
| P7 Deux modules | Gestion materiel et Stock chimique | | |
| P8 Trois modules | Les trois boutons | | |
| P9 Administrateur | Les trois boutons | | |

Pour P1 :

1. retirer l'affectation au materiel actif ;
2. verifier que le bouton disparait ;
3. affecter uniquement un materiel archive ;
4. verifier que le bouton reste absent ;
5. restaurer l'affectation initiale.

Pour P6 :

1. placer le compte dans `Module desactive` de Suivi des demandes ;
2. verifier que le bouton disparait ;
3. reactiver le compte ;
4. verifier que le bouton reapparait.

## 8. Recette des activations

Executer ces tests avec un compte ayant normalement acces aux trois modules.

| ID | Action | Resultat attendu | Etat | Observation |
|---|---|---|---|---|
| V01 | Desactiver un bouton dans Boutons perso | Bouton masque, autres boutons inchanges | | |
| V02 | Reactiver ce bouton | Bouton de nouveau visible | | |
| V03 | Desactiver fonctionnellement un module | Son bouton disparait | | |
| V04 | Reactiver fonctionnellement le module | Son bouton reapparait | | |
| V05 | Desactiver le module externe GRR | Son bouton disparait | | |
| V06 | Reactiver le module externe GRR | Son bouton reapparait | | |
| V07 | Renommer un module | Le nouveau libelle apparait sur le bouton | | |

Restaurer chaque configuration apres son test.

## 9. Recette de l'ordre et du rendu

Configurer par exemple :

- bouton personnalise A : ordre 10 ;
- Gestion materiel : ordre 20 ;
- bouton personnalise B : ordre 30 ;
- Stock chimique : ordre 40 ;
- Suivi des demandes : ordre 50.

| ID | Verification | Resultat attendu | Etat | Observation |
|---|---|---|---|---|
| R01 | Vue jour | Ordre administratif respecte | | |
| R02 | Vue semaine | Ordre administratif respecte | | |
| R03 | Vue mois | Ordre administratif respecte | | |
| R04 | Style Bootstrap | Style configure applique | | |
| R05 | Couleurs personnalisees | Fond et texte conformes | | |
| R06 | Infobulle | Texte affiche au survol | | |
| R07 | Confirmation | Confirmation demandee avant ouverture | | |
| R08 | Fenetre courante | Module ouvert dans la page courante | | |
| R09 | Nouvel onglet | Nouvel onglet avec protection `noopener` | | |
| R10 | Nouvelle fenetre | Dimensions et nom configures appliques | | |

## 10. Bloc vide

Pour P0 :

1. desactiver temporairement tous les boutons personnalises ;
2. verifier qu'aucun bouton module n'est autorise ;
3. ouvrir les vues jour, semaine et mois.

Resultat attendu : le bloc `boutons-perso-calendrier` est completement absent.

| Test | Etat | Observation |
|---|---|---|
| Bloc vide masque en vue jour | | |
| Bloc vide masque en vue semaine | | |
| Bloc vide masque en vue mois | | |

## 11. Securite des acces directs

Avec P0, tenter directement :

```text
compte/compte.php?pc=gestion_materiel
compte/compte.php?pc=stock_chimique
compte/compte.php?pc=suivi_demandes
```

Resultat attendu : chaque module refuse l'acces selon ses propres regles. Le
simple masquage du bouton ne doit jamais constituer l'unique protection.

| Module | Etat | Observation |
|---|---|---|
| Gestion materiel | | |
| Stock chimique | | |
| Suivi des demandes | | |

## 12. Journaux

Apres la recette :

1. consulter les journaux Apache et PHP du NAS ;
2. rechercher `boutons_perso`, `ModuleRegistry`, `Navigation`, `Warning`,
   `Fatal error` et `Uncaught` ;
3. verifier l'absence de nouvelle erreur liee aux tests.

| Test | Etat | Observation |
|---|---|---|
| Aucune erreur PHP/Apache nouvelle | | |

## 13. Decision

| Critere | Etat |
|---|---|
| Administration unifiee conforme | |
| Droits par utilisateur conformes | |
| Activations conformes | |
| Ordre et rendu conformes | |
| Acces directs proteges | |
| Journaux sans erreur | |

Decision finale :

- [ ] Recette acceptee ;
- [ ] Recette acceptee avec reserves ;
- [ ] Recette refusee.

Reserves ou anomalies :

```text

```

Nom et validation :

```text

```
