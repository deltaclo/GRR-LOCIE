# Recette NAS - Formulaires dynamiques

## 1. Informations

| Information | Valeur |
|---|---|
| Date | |
| Testeur | |
| NAS / environnement | Synology DSM 7 |
| Serveur web | Apache 2.4 |
| PHP | 8.4 |
| Base | MariaDB 10 |
| URL GRR | |
| Prefixe SQL | |

Etats a utiliser :

- `OK` : resultat conforme ;
- `KO` : resultat non conforme ;
- `NA` : test non applicable, avec justification.

## 2. Sauvegarde et prealables

1. Sauvegarder la base GRR.
2. Noter les comptes de test :
   - un administrateur GRR ;
   - un gestionnaire global ;
   - un gestionnaire par formulaire ;
   - un utilisateur GRR simple ;
   - un visiteur autonome non connecte.
3. Verifier que le module est actif dans GRR.
4. Verifier la configuration mail GRR si le test notification est realise.
5. Verifier que l'extension PHP `zip` est active si le test XLSX est attendu.

## 3. Verification technique

Depuis la racine de GRR sur le NAS :

```bash
php84 -l personnalisation/modules/formulaires_dynamiques/admin.php
php84 -l personnalisation/modules/formulaires_dynamiques/controleur.php
php84 -l personnalisation/modules/formulaires_dynamiques/installation.php
php84 -l personnalisation/modules/formulaires_dynamiques/public.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/bootstrap.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Config.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Export.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Navigation.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Notification.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Renderer.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Repository.php
php84 -l personnalisation/modules/formulaires_dynamiques/lib/Rights.php
php84 -l reservation/controleurs/formulairesdynamiques.php
```

Resultat attendu : `No syntax errors detected` pour chaque fichier.

| Test | Etat | Observation |
|---|---|---|
| Syntaxe PHP | | |

## 4. Configuration

1. Se connecter avec un administrateur GRR.
2. Ouvrir `compte/compte.php?pc=formulaires_dynamiques`.
3. Cliquer sur `Configuration du module`.
4. Verifier que la page s'ouvre dans l'interface compte.
5. Activer :
   - module ;
   - affichage dans Gerer mon compte ;
   - pages autonomes ;
   - notifications, si testees.
6. Ajouter le login du gestionnaire global.
7. Enregistrer.
8. Verifier le diagnostic SQL.

| Test | Etat | Observation |
|---|---|---|
| Configuration ouverte depuis le bouton | | |
| Enregistrement configuration | | |
| Tables SQL indiquees OK | | |

## 5. Bouton personnel

1. Ouvrir le module `boutons_perso`.
2. Verifier que `Formulaires dynamiques` apparait dans les modules detectes.
3. Activer le bouton si necessaire.
4. Ouvrir `Gerer mon compte`.
5. Verifier que le bouton ouvre `compte/compte.php?pc=formulaires_dynamiques`.

| Test | Etat | Observation |
|---|---|---|
| Module visible dans boutons_perso | | |
| Bouton visible dans Gerer mon compte | | |
| URL compte correcte | | |

## 6. Creation formulaire

1. Se connecter avec le gestionnaire global.
2. Ouvrir `compte/compte.php?pc=formulaires_dynamiques`.
3. Creer un formulaire en statut `brouillon`.
4. Ajouter les champs :
   - texte obligatoire ;
   - textarea ;
   - email ;
   - nombre ;
   - date ;
   - liste select avec trois options ;
   - radio avec trois options ;
   - cases a cocher avec trois options ;
   - separateur.
5. Modifier un champ existant.
6. Desactiver un champ de test.
7. Passer le formulaire en statut `publie`.

| Test | Etat | Observation |
|---|---|---|
| Creation formulaire | | |
| Creation champs | | |
| Modification champ | | |
| Desactivation champ | | |
| Publication formulaire | | |

## 7. Gestionnaires par formulaire

1. Depuis la fiche du formulaire, ajouter un gestionnaire par formulaire.
2. Se reconnecter avec ce gestionnaire.
3. Verifier qu'il voit le module dans `Gerer mon compte`.
4. Verifier qu'il voit uniquement les formulaires affectes.
5. Verifier qu'il peut ajouter un champ, un destinataire, generer un jeton et
   consulter les resultats du formulaire affecte.
6. Verifier qu'il ne peut pas creer un nouveau formulaire.

| Test | Etat | Observation |
|---|---|---|
| Ajout gestionnaire par formulaire | | |
| Acces limite aux formulaires affectes | | |
| Gestion du formulaire affecte | | |
| Creation nouveau formulaire refusee | | |

## 8. Liens et jetons

1. Generer un lien formulaire.
2. Generer un lien resultats.
3. Copier les quatre liens proposes :
   - formulaire integre ;
   - formulaire autonome ;
   - resultats integres ;
   - resultats autonomes.
4. Verifier que le tableau des jetons affiche les jetons actifs.
5. Desactiver un jeton de test.
6. Verifier que le lien correspondant devient invalide.
7. Regenerer un nouveau jeton.

| Test | Etat | Observation |
|---|---|---|
| Generation lien formulaire | | |
| Generation lien resultats | | |
| Desactivation jeton | | |
| Lien desactive refuse | | |
| Regeneration jeton | | |

## 9. Soumission des reponses

1. Ouvrir le formulaire via `app.php?p=formulairesdynamiques`.
2. Soumettre une reponse complete.
3. Verifier la confirmation et la reference.
4. Ouvrir le formulaire autonome dans une session non connectee.
5. Soumettre une deuxieme reponse.
6. Tester les validations obligatoires.
7. Si les notifications sont activees, verifier la reception mail et
   l'historique.

| Test | Etat | Observation |
|---|---|---|
| Soumission integree GRR | | |
| Soumission autonome | | |
| Validation champ obligatoire | | |
| Notification mail | | |

## 10. Resultats

1. Ouvrir la page de resultats integree.
2. Verifier que la liste affiche les deux reponses.
3. Tester la recherche texte.
4. Tester le filtre source `Integre GRR`.
5. Tester le filtre source `Autonome`.
6. Tester les filtres date.
7. Tester la pagination avec `25`, `50`, `100`, `200`.
8. Ouvrir le detail d'une reponse.
9. Revenir a la liste.

| Test | Etat | Observation |
|---|---|---|
| Liste resultats integree | | |
| Liste resultats autonome | | |
| Recherche | | |
| Filtre source | | |
| Filtre date | | |
| Pagination | | |
| Detail reponse | | |

## 11. Exports

Depuis la liste des resultats :

1. Exporter CSV toutes les reponses filtrees.
2. Exporter XLSX toutes les reponses filtrees.
3. Exporter PDF toutes les reponses filtrees.
4. Ouvrir les fichiers et verifier les colonnes et valeurs.

Depuis le detail d'une reponse :

1. Exporter CSV une reponse.
2. Exporter XLSX une reponse.
3. Exporter PDF une reponse.
4. Verifier que le fichier ne contient que la reponse cible.

| Test | Etat | Observation |
|---|---|---|
| CSV global | | |
| XLSX global | | |
| PDF global | | |
| CSV individuel | | |
| XLSX individuel | | |
| PDF individuel | | |

## 12. Historique

1. Revenir dans la fiche de gestion du formulaire.
2. Verifier l'historique recent.
3. Confirmer la presence des evenements :
   - creation formulaire ;
   - creation champ ;
   - creation jeton ;
   - creation reponse ;
   - notification, si testee ;
   - export reponses ;
   - desactivation jeton.

| Test | Etat | Observation |
|---|---|---|
| Historique visible | | |
| Evenements coherents | | |

## 13. SQL de controle

Adapter le prefixe SQL si necessaire :

```sql
SELECT COUNT(*) FROM grr_formulaire_dyn_formulaire;
SELECT COUNT(*) FROM grr_formulaire_dyn_champ;
SELECT COUNT(*) FROM grr_formulaire_dyn_reponse;
SELECT COUNT(*) FROM grr_formulaire_dyn_valeur;
SELECT COUNT(*) FROM grr_formulaire_dyn_gestionnaire;
SELECT COUNT(*) FROM grr_formulaire_dyn_notification;
SELECT COUNT(*) FROM grr_formulaire_dyn_token;
SELECT COUNT(*) FROM grr_formulaire_dyn_historique;
```

Verifier que :

- les reponses ont bien une ligne dans `*_formulaire_dyn_reponse` ;
- les valeurs sont stockees dans `*_formulaire_dyn_valeur` ;
- les jetons ne sont stockes qu'en hash ;
- les exports et notifications sont traces dans l'historique.

| Test | Etat | Observation |
|---|---|---|
| Compteurs SQL coherents | | |
| Jetons hashes | | |
| Historique SQL coherent | | |

## 14. Non regression

1. Ouvrir une page planning GRR classique.
2. Verifier que le calendrier lateral n'apparait pas sur la page
   `app.php?p=formulairesdynamiques`.
3. Verifier que les autres modules de `Gerer mon compte` s'ouvrent encore.
4. Verifier qu'un utilisateur sans droit n'accede pas a la gestion du module.

| Test | Etat | Observation |
|---|---|---|
| Planning GRR classique | | |
| Page app sans calendrier lateral | | |
| Autres modules compte | | |
| Acces refuse sans droit | | |

## 15. Criteres de validation

Le module est validable si :

- aucune erreur PHP n'apparait ;
- les tables SQL sont creees ;
- un formulaire publie accepte des reponses en mode integre et autonome ;
- les resultats sont consultables en mode integre et autonome ;
- les exports CSV, XLSX et PDF fonctionnent ;
- les gestionnaires par formulaire sont limites aux formulaires affectes ;
- les jetons peuvent etre desactives et regeneres ;
- l'historique contient les actions principales.
