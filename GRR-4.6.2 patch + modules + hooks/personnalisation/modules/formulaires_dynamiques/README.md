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
- Champs dynamiques : texte, zone de texte, email, nombre, date, liste, choix
  unique par case a cocher, cases a cocher, piece jointe, signature
  electronique, image et separateur.
- Champ vide de mise en page, sans libelle ni reponse stockee, utilisable pour
  occuper une cellule ou changer le nombre de colonnes.
- Editeur de champs dynamique affichant uniquement les reglages utiles au type
  selectionne.
- Affichage des champs en 1 a 4 colonnes par separateur ou champ vide, avec
  retour en une colonne sur mobile.
- Organisation des champs en pages/sections et affichage conditionnel de
  champs selon une liste, un choix unique ou une case cochee.
- Reorganisation des champs par glisser-deposer dans la gestion.
- Apercu du formulaire avant publication.
- Option par formulaire pour autoriser un utilisateur GRR connecte a modifier
  sa propre reponse depuis le formulaire integre.
- Option par formulaire pour envoyer un mail de confirmation au declarant.
- Duplication d'un formulaire en brouillon.
- Suppression definitive d'un formulaire et de ses reponses par un
  administrateur, par un gestionnaire affecte au formulaire ou par le
  gestionnaire global createur du formulaire.
- Import/export JSON d'un formulaire complet hors reponses et jetons.
- Liens par jetons pour formulaires et pages de resultats, avec expiration,
  limite de reponses, copie rapide et QR code autonome.
- Affichage des liens existants pour les nouveaux jetons, avec desactivation
  ou suppression, et ouverture directe depuis la colonne d'actions.
- Bouton `Ouvrir le formulaire` sur les pages de resultats integrees ou
  autonomes lorsqu'un lien formulaire actif et reaffichable existe.
- Gestionnaires globaux selectionnes depuis les utilisateurs GRR actifs avec
  une double liste disponible/selectionne.
- Gestionnaires par formulaire selectionnes depuis les utilisateurs GRR actifs.
- Destinataires de notification par formulaire, avec conditions sur liste,
  choix unique ou cases a cocher.
- Notification mail a la creation d'une reponse si les mails GRR sont actifs.
- Le lien de modification de la reponse est ajoute aux notifications lorsque
  l'option de modification par declarant est activee.
- Modeles d'objet et de contenu de notification par formulaire.
- Consultation des reponses avec recherche, filtre source, filtre date et
  pagination.
- Consultation detaillee et modification d'une reponse par un gestionnaire.
- Statistiques simples sur les champs a choix.
- Choix des colonnes affichees dans la liste des resultats.
- Mise en page personnalisable des resultats globaux et individuels par
  modeles texte.
- Exports CSV, XLSX et PDF pour une reponse ou toutes les reponses filtrees.
- Historique recent des actions du formulaire.
- Integration avec `boutons_perso`.

## Droits

- Administrateur GRR : configuration du module, acces a tous les formulaires
  et suppression de tous les formulaires.
- Gestionnaire global : creation de formulaires et gestion complete du module
  fonctionnel ; suppression limitee aux formulaires qu'il a crees, sauf s'il
  est aussi affecte comme gestionnaire du formulaire.
- Gestionnaire par formulaire : gestion du formulaire affecte, champs,
  notifications, jetons, resultats, exports et suppression du formulaire.
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
- Les formulaires, listes de resultats et details de reponse peuvent etre
  imprimes depuis un bouton dedie.

## Modeles de resultats

Les modeles sont des textes avec placeholders :

- `{reference}`
- `{date}`
- `{source}`
- `{declarant}`
- `{champ:Libelle exact}`
- `{field:ID}`

Si le modele est vide, l'affichage tableau standard est utilise.

Les memes placeholders peuvent etre utilises dans les modeles de notification.
Les modeles de notification acceptent aussi `{lien_modification}`.

## Pieces jointes

Le type `piece jointe` accepte uniquement des extensions autorisees
(`jpg`, `png`, `webp`, `pdf`, documents bureautiques, `csv`, `txt`, etc.) et
limite chaque fichier a 10 Mo. Les fichiers sont stockes dans
`personnalisation/modules/formulaires_dynamiques/uploads/`.

## Import/export formulaire

L'onglet `Outils` permet :

- de dupliquer un formulaire en brouillon ;
- d'exporter la structure au format JSON ;
- d'importer un JSON pour creer un nouveau formulaire brouillon.

Les reponses et jetons ne sont pas exportes.

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

- Les nouveaux jetons sont stockes avec leur valeur publique pour reafficher
  les liens. Les anciens jetons hashes avant cette evolution restent valides
  mais ne sont pas reaffichables.
- Les QR codes affiches pour les liens autonomes sont generes par un service
  externe.
- Une page de resultats par jeton donne acces aux reponses et aux exports.
- Les pages autonomes peuvent etre desactivees dans la configuration du module.
- La colonne `allow_user_edit` est ajoutee automatiquement aux formulaires
  existants pour l'option de modification par le declarant connecte.
- La colonne `confirmation_email_enabled` est ajoutee automatiquement pour
  l'option de confirmation mail au declarant.
- Les notifications dependent de la configuration mail GRR existante.
