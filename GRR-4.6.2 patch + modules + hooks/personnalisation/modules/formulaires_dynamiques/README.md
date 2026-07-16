# Formulaires dynamiques

Module local GRR pour creer des formulaires dynamiques, collecter les reponses
et consulter/exporter les resultats.

## Acces

- Gestion du module : `compte/compte.php?pc=formulaires_dynamiques`
- Configuration administrateur : bouton `Configuration du module` depuis la
  gestion, ou `personnalisation/modules/formulaires_dynamiques/admin.php`
- Formulaire integre GRR : `app.php?p=formulairesdynamiques&view=formulaire&token=...`
- Resultats integres GRR : `app.php?p=formulairesdynamiques&view=resultats&token=...`
- Formulaire autonome : `personnalisation/modules/formulaires_dynamiques/public.php?view=formulaire&token=...`
- Resultats autonomes : `personnalisation/modules/formulaires_dynamiques/public.php?view=resultats&token=...`

## Fonctionnalites

- Creation et modification de formulaires.
- Statuts `brouillon`, `publie`, `archive`.
- Champs dynamiques : texte, zone de texte, email, nombre, date, liste, radio,
  cases a cocher et separateur.
- Liens par jetons pour formulaires et pages de resultats.
- Desactivation des jetons existants et regeneration de nouveaux liens.
- Gestionnaires globaux configures dans l'administration du module.
- Gestionnaires par formulaire, limites aux formulaires affectes.
- Destinataires de notification par formulaire.
- Notification mail a la creation d'une reponse si les mails GRR sont actifs.
- Consultation des reponses avec recherche, filtre source, filtre date et
  pagination.
- Consultation detaillee d'une reponse.
- Exports CSV, XLSX et PDF pour une reponse ou toutes les reponses filtrees.
- Historique recent des actions du formulaire.
- Integration avec `boutons_perso`.

## Droits

- Administrateur GRR : configuration du module et acces a tous les formulaires.
- Gestionnaire global : creation de formulaires et gestion complete du module
  fonctionnel.
- Gestionnaire par formulaire : gestion du formulaire affecte, champs,
  notifications, jetons, resultats et exports.
- Repondant : acces uniquement via un lien formulaire actif.
- Lecteur de resultats : acces uniquement via un lien resultats actif.

## Exports

Les exports utilisent le lien de resultats actif.

- Depuis la liste des resultats : export de toutes les reponses correspondant
  aux filtres courants.
- Depuis le detail d'une reponse : export de cette reponse uniquement.
- Le CSV est genere en UTF-8 avec BOM et separateur `;`.
- Le XLSX necessite l'extension PHP `ZipArchive`.
- Le PDF est genere sans dependance externe, avec une mise en page simple.

## Tables SQL

Les tables sont creees automatiquement par `FormulairesDynamiquesRepository::ensureTables()`.

- `*_formulaire_dyn_formulaire`
- `*_formulaire_dyn_champ`
- `*_formulaire_dyn_reponse`
- `*_formulaire_dyn_valeur`
- `*_formulaire_dyn_gestionnaire`
- `*_formulaire_dyn_notification`
- `*_formulaire_dyn_token`
- `*_formulaire_dyn_historique`

## Points d'attention

- Les jetons complets ne sont affiches qu'au moment de leur creation.
- Une page de resultats par jeton donne acces aux reponses et aux exports.
- Les pages autonomes peuvent etre desactivees dans la configuration du module.
- Les notifications dependent de la configuration mail GRR existante.
