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
6. Ajouter le gestionnaire global avec la double liste :
   - selectionner un utilisateur dans `Utilisateurs disponibles` ;
   - cliquer sur `Ajouter >` ;
   - verifier qu'il passe dans `Gestionnaires globaux`.
7. Enregistrer.
8. Verifier le diagnostic SQL.

| Test | Etat | Observation |
|---|---|---|
| Configuration ouverte depuis le bouton | | |
| Selection gestionnaire global double liste | | |
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
   - choix unique avec trois options, affiche en cases a cocher ;
   - cases a cocher avec trois options ;
   - piece jointe facultative ;
   - image avec une URL ou un chemin relatif valide et une taille d'affichage ;
   - separateur sans libelle avec `2 colonnes` ou `3 colonnes` pour les
     champs suivants ;
   - champ `Vide`, sans libelle, avec un nombre de colonnes different pour les
     champs suivants.
5. Renseigner au moins deux pages/sections dans les champs.
6. Configurer un affichage conditionnel sur un champ pilote.
7. Changer le type dans l'editeur de champ et verifier que seuls les reglages
   utiles au type selectionne restent affiches.
8. Modifier un champ existant.
9. Desactiver un champ de test.
10. Reordonner les champs par glisser-deposer puis enregistrer l'ordre.
11. Ouvrir l'onglet `Apercu` et verifier le rendu sans jeton.
12. Verifier que les champs qui suivent le separateur s'affichent sur plusieurs
    colonnes, que les separateurs restent pleine largeur et qu'un separateur
    sans libelle n'affiche pas de titre vide.
13. Verifier que le champ `Vide` n'affiche ni libelle ni controle de saisie,
    occupe une cellule vide dans la grille et applique le nombre de colonnes
    configure aux champs suivants.
14. Passer le formulaire en statut `publie`.

| Test | Etat | Observation |
|---|---|---|
| Creation formulaire | | |
| Creation champs | | |
| Modification champ | | |
| Desactivation champ | | |
| Piece jointe | | |
| Image dans formulaire | | |
| Taille affichage image | | |
| Editeur dynamique par type de champ | | |
| Pages / sections | | |
| Affichage conditionnel | | |
| Drag and drop champs | | |
| Apercu avant publication | | |
| Affichage multi-colonnes | | |
| Champ vide de mise en page | | |
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
3. Generer un lien formulaire avec expiration proche et limite a `1` reponse.
4. Copier les liens avec les boutons `Copier` :
   - formulaire integre ;
   - formulaire autonome ;
   - resultats integres ;
   - resultats autonomes.
5. Ouvrir le QR code autonome.
6. Recharger la page et verifier que les liens des nouveaux jetons restent affiches.
7. Verifier que le tableau des jetons affiche actifs, contraintes et usage.
8. Soumettre une reponse avec le jeton limite puis verifier qu'une deuxieme
   soumission est refusee.
9. Attendre ou modifier l'expiration puis verifier que le lien expire est refuse.
10. Desactiver un jeton de test.
11. Verifier que le lien correspondant devient invalide.
12. Supprimer un jeton de test.
13. Regenerer un nouveau jeton.

| Test | Etat | Observation |
|---|---|---|
| Generation lien formulaire | | |
| Generation lien resultats | | |
| Copie lien / token | | |
| QR code autonome | | |
| Expiration jeton | | |
| Limite reponses jeton | | |
| Desactivation jeton | | |
| Lien desactive refuse | | |
| Suppression jeton | | |
| Liens existants reaffiches | | |
| Regeneration jeton | | |

## 9. Soumission des reponses

1. Ouvrir le formulaire via `app.php?p=formulairesdynamiques`.
2. Soumettre une reponse complete.
3. Verifier la confirmation et la reference.
4. Ouvrir le formulaire autonome dans une session non connectee.
5. Soumettre une deuxieme reponse.
6. Tester les validations obligatoires.
7. Ajouter une piece jointe autorisee puis verifier son lien dans les resultats.
8. Tester un fichier avec extension refusee.
9. Configurer une notification conditionnelle sur une liste, un choix unique ou une
   case a cocher.
10. Configurer un modele d'objet et de message de notification.
11. Soumettre une reponse qui respecte la condition.
12. Soumettre une reponse qui ne respecte pas la condition.
13. Si les notifications sont activees, verifier la reception mail uniquement
    pour la reponse conforme et l'historique.

| Test | Etat | Observation |
|---|---|---|
| Soumission integree GRR | | |
| Soumission autonome | | |
| Validation champ obligatoire | | |
| Piece jointe autorisee | | |
| Piece jointe refusee | | |
| Notification conditionnelle envoyee | | |
| Notification conditionnelle ignoree | | |
| Modele notification | | |

## 10. Resultats

1. Ouvrir la page de resultats integree.
2. Verifier que la liste affiche les deux reponses.
3. Verifier que le bouton `Ouvrir le formulaire` est visible si un lien
   formulaire actif existe et qu'il ouvre le formulaire via `app.php`.
4. Ouvrir la page de resultats autonome et verifier que le bouton ouvre le
   formulaire autonome.
5. Tester la recherche texte.
6. Tester le filtre source `Integre GRR`.
7. Tester le filtre source `Autonome`.
8. Tester les filtres date.
9. Tester la pagination avec `25`, `50`, `100`, `200`.
10. Ouvrir le detail d'une reponse et verifier aussi le bouton
    `Ouvrir le formulaire`.
11. Revenir a la liste.
12. Configurer un modele de resultats global et verifier l'affichage.
13. Configurer un modele individuel et verifier le detail d'une reponse.
14. Choisir seulement certaines colonnes dans `Mise en page` et verifier la
    liste.
15. Modifier une reponse avec un gestionnaire connecte.
16. Ouvrir l'onglet `Statistiques`.

| Test | Etat | Observation |
|---|---|---|
| Liste resultats integree | | |
| Liste resultats autonome | | |
| Bouton ouvrir formulaire depuis resultats integres | | |
| Bouton ouvrir formulaire depuis resultats autonomes | | |
| Recherche | | |
| Filtre source | | |
| Filtre date | | |
| Pagination | | |
| Detail reponse | | |
| Modele resultats global | | |
| Modele resultats individuel | | |
| Colonnes resultats personnalisees | | |
| Modification reponse | | |
| Statistiques | | |

## 10 bis. Outils formulaire

1. Ouvrir l'onglet `Outils`.
2. Dupliquer le formulaire.
3. Verifier que le formulaire copie est en brouillon, sans reponse ni jeton.
4. Exporter le formulaire JSON.
5. Reimporter le JSON depuis la liste des formulaires.
6. Verifier que champs, pages, conditions, notifications et gestionnaires sont
   repris.

| Test | Etat | Observation |
|---|---|---|
| Duplication formulaire | | |
| Export JSON formulaire | | |
| Import JSON formulaire | | |
| Copie sans reponses ni jetons | | |

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
   - suppression jeton.
   - ordre des champs.
   - duplication formulaire.
   - import JSON.
   - modification reponse.
4. Tester les filtres de l'historique.

| Test | Etat | Observation |
|---|---|---|
| Historique visible | | |
| Evenements coherents | | |
| Filtres historique | | |

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
- les jetons ont toujours un hash, et les nouveaux jetons ont aussi une
  valeur publique pour pouvoir reafficher les liens ;
- les exports et notifications sont traces dans l'historique ;
- les nouveaux jetons contiennent cette valeur publique dans
  `*_formulaire_dyn_token.token_public` ;
- les conditions de notification sont stockees dans
  `condition_champ_id`, `condition_operateur`, `condition_valeur`.
- les jetons peuvent avoir `expires_at` et `max_responses` ;
- les reponses stockent `token_id`, `updated_at` et `updated_by` ;
- les separateurs peuvent stocker leur nombre de colonnes dans `options` ;
- les champs peuvent stocker `page_titre`, `visibility_champ_id`,
  `visibility_operateur`, `visibility_valeur`.

| Test | Etat | Observation |
|---|---|---|
| Compteurs SQL coherents | | |
| Jetons hashes et valeur publique | | |
| Colonnes nouvelles presentes | | |
| Historique SQL coherent | | |

## 14. Suppression formulaire

Effectuer ce test sur un formulaire de test dedie.

1. Creer ou dupliquer un formulaire, puis enregistrer au moins une reponse.
2. Avec un gestionnaire global qui n'a pas cree le formulaire et qui n'est pas
   affecte au formulaire, verifier que l'action `Supprimer` n'est pas
   disponible.
3. Affecter un gestionnaire au formulaire.
4. Avec ce gestionnaire affecte, verifier que l'action `Supprimer` est
   disponible meme s'il n'est pas createur.
5. Cliquer sur `Supprimer` dans le tableau `Formulaires` et confirmer.
6. Verifier que le formulaire disparait de la liste.
7. Verifier que ses reponses ne sont plus consultables dans les resultats.
8. Verifier en SQL que les lignes liees au formulaire ont disparu des tables
   formulaire, champ, reponse, valeur, gestionnaire, notification, token et
   historique.
9. Avec un administrateur GRR, verifier que l'action `Supprimer` est disponible
   sur un autre formulaire de test.

| Test | Etat | Observation |
|---|---|---|
| Bouton absent pour gestionnaire global non createur | | |
| Suppression par gestionnaire affecte | | |
| Suppression par administrateur | | |
| Reponses et valeurs supprimees | | |

## 15. Non regression

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

## 16. Criteres de validation

Le module est validable si :

- aucune erreur PHP n'apparait ;
- les tables SQL sont creees ;
- un formulaire publie accepte des reponses en mode integre et autonome ;
- les resultats sont consultables en mode integre et autonome ;
- les exports CSV, XLSX et PDF fonctionnent ;
- les gestionnaires par formulaire sont limites aux formulaires affectes ;
- les jetons existants affichent leurs liens quand la valeur publique existe ;
- les jetons peuvent etre desactives, supprimes et regeneres ;
- les images, les pieces jointes et le glisser-deposer des champs
  fonctionnent ;
- l'affichage multi-colonnes des champs fonctionne en apercu, integre et
  autonome ;
- l'apercu, la duplication, l'import/export JSON et les statistiques
  fonctionnent ;
- la suppression definitive est autorisee pour l'administrateur, le
  gestionnaire affecte au formulaire et le gestionnaire global createur, puis
  supprime aussi les reponses ;
- les notifications conditionnelles respectent la valeur du champ cible ;
- les modeles de notification sont appliques ;
- les mises en page de resultats globale et individuelle sont appliquees ;
- les colonnes de resultats sont personnalisables ;
- une reponse peut etre modifiee par un gestionnaire autorise ;
- l'historique contient les actions principales.
