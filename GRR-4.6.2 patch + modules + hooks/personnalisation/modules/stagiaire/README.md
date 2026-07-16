# Stagiaire

Module externe GRR pour ajouter des informations obligatoires de stagiaire aux reservations.

## Version 1.2.3

Cette version applique la strategie d'affichage responsive du module `boutons_perso`.

Changements :

- affichage fluide en pleine largeur dans l'administration autonome et integree ;
- adaptation des tableaux et formulaires aux petits ecrans ;
- bouton du menu gauche `Gerer mon compte` force en pleine largeur disponible.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR.

## Version 1.2.2

Cette version ajuste l affichage de la consultation des reservations stagiaires.

Changements :

- suppression du bouton `Ouvrir la page autonome` sur `compte.php?pc=stagiaire` ;
- conservation du bouton `Administration du module` sur la consultation ;
- conservation de la page autonome `personnalisation/modules/stagiaire/admin.php`.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR.

## Version 1.2.1

Cette version corrige le point d entree de la consultation des reservations stagiaires.

Changements :

- la page `Gerer mon compte > Stagiaire` affiche directement les reservations stagiaires ;
- l URL principale devient `compte.php?pc=stagiaire` ;
- l administration integree reste accessible via `compte.php?pc=stagiaire&admin=1` ;
- les filtres et le bouton `Reinitialiser` restent sur `compte.php?pc=stagiaire` ;
- l ancienne URL `compte.php?pc=stagiaire&view=reservations` affiche encore la consultation car le parametre `view` est simplement ignore.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR ;
- l export CSV reste fourni par `personnalisation/modules/stagiaire/export.php`.

## Version 1.2.0

Cette version ajoute une page de consultation des reservations stagiaires dans `Gerer mon compte`.

Fonctionnalites ajoutees :

- ajout d un bouton `Reservations stagiaires` dans la page `Gerer mon compte > Stagiaire` ;
- ajout d une page `compte.php?pc=stagiaire&view=reservations` reservee aux administrateurs generaux ;
- liste des reservations GRR possedant des donnees stagiaire ;
- filtres par date de debut, ressource, compte GRR stagiaire et e-mail ;
- choix du nombre de reservations affichees ;
- lien direct vers le detail GRR de chaque reservation ;
- export CSV des resultats filtres ;
- conservation de l administration integree ajoutee en V1.1.1.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR ;
- les reservations supprimees logiquement dans GRR ne sont pas affichees dans la liste.

## Version 1.1.1

Correctif d integration de l administration du module dans `Gerer mon compte`.

Changements :

- le bouton `Administration du module` ouvre maintenant `compte.php?pc=stagiaire&admin=1` ;
- la page `admin.php` peut maintenant etre rendue comme fragment integre dans l interface GRR ;
- la page autonome `personnalisation/modules/stagiaire/admin.php` reste accessible directement ;
- le formulaire d administration conserve ses enregistrements en mode integre ;
- les styles de la page admin sont limites au bloc du module pour eviter d impacter l interface GRR.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR ;
- cette version garde le point d acces ajoute en V1.1.0.

## Version 1.1.0

Cette version ajoute un point d acces au module dans `Gerer mon compte` pour les administrateurs generaux.

Fonctionnalites ajoutees :

- ajout d une entree `Stagiaire` dans le menu `Gerer mon compte` pour les administrateurs generaux ;
- ajout d une page `compte.php?pc=stagiaire` reservee aux administrateurs ;
- ajout d un bouton `Administration du module` vers `personnalisation/modules/stagiaire/admin.php` ;
- conservation de la page autonome `personnalisation/modules/stagiaire/admin.php`.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR ;
- cette version utilise les hooks `hookCompteMenu` et `hookComptePage` deja presents dans GRR.

## Version 1.0.0

Cette version stabilise le module apres validation des fonctionnalites principales.

Fonctionnalites stabilisees :

- configuration des comptes GRR consideres comme stagiaires ;
- affichage des champs obligatoires `Nom`, `Prenom`, `Email` et `Encadrant` dans le formulaire de reservation pour les comptes stagiaires ;
- validation serveur des champs obligatoires et de l adresse e-mail ;
- enregistrement des informations stagiaire avec la reservation ;
- affichage des informations stagiaire dans le detail de reservation ;
- prise en charge des reservations simples et repetees ;
- envoi d un mail initial au stagiaire apres creation ;
- envoi d un mail au stagiaire apres acceptation ou refus par moderation ;
- envoi d un mail au stagiaire lors de la suppression d une reservation ;
- page d administration du module avec diagnostic des hooks, tables, options et configuration mail.

Notes :

- aucun changement de schema SQL par rapport a la V0.10.0 ;
- aucun nouveau patch coeur GRR par rapport a la V0.10.0 ;
- les patchs coeur existants restent requis pour les hooks formulaire, traitement, moderation, detail, series et suppression.

## Version 0.10.0

Cette version ajoute l envoi d un mail au stagiaire lorsqu une reservation stagiaire est supprimee.

Fonctionnalites ajoutees :

- ajout du hook `hookDeleteEntry` dans `reservation/controleurs/supreservation.php` avant la suppression logique de la reservation ;
- transmission au module des identifiants de reservations concernes par la suppression ;
- prise en compte de la suppression d une occurrence, de toute une serie ou de la fin d une serie ;
- envoi d un mail au stagiaire avec la ressource, le debut, la fin, le titre et l utilisateur ayant realise la suppression ;
- respect du reglage global GRR `automatic_mail` ;
- respect de l option module `Envoyer les confirmations e-mail au stagiaire` ;
- ajout d une ligne `Hook suppression` dans le diagnostic de la page d administration.

Changement coeur GRR :

- `reservation/controleurs/supreservation.php` est modifie pour appeler `hookDeleteEntry` ;
- une sauvegarde de l etat precedent est conservee dans `reservation/controleurs/supreservation.php.0.9.org`.

Notes :

- aucun changement de schema SQL ;
- le mail de suppression est envoye avant la suppression logique afin de pouvoir lire les informations de reservation ;
- le patch coeur V0.8.0 reste requis pour la prise en charge complete des reservations repetees a la creation.

## Version 0.9.0

Cette version stabilise le module apres les tests V0.8.0.

Fonctionnalites ajoutees :

- securisation des appels a `Email::Envois()` pour eviter qu un probleme mail bloque une reservation ;
- detection de la limite d envoi mail GRR avant appel a `Email::Envois()` ;
- journalisation serveur en cas d erreur d envoi mail stagiaire ;
- ajout de la version du module dans le diagnostic d administration ;
- ajout de la version PHP detectee dans le diagnostic d administration.

Notes :

- aucun changement de schema SQL ;
- aucun nouveau patch coeur GRR ;
- le patch coeur V0.8.0 reste requis pour la prise en charge des reservations repetees ;
- cette version ne change pas les champs visibles dans le formulaire de reservation.

## Version 0.8.0

Cette version prend en compte les reservations repetees.

Fonctionnalites ajoutees :

- ajout du contexte `entry_ids` dans `reservation/controleurs/editentreetrt.php` ;
- enregistrement des informations stagiaire sur chaque occurrence creee dans une serie ;
- affichage des informations stagiaire dans le detail de chaque occurrence ;
- envoi d un seul mail initial pour une serie, avec indication du nombre d occurrences ;
- marquage de toutes les occurrences de la serie comme notifiees apres envoi du mail initial ;
- envoi d un seul mail de moderation pour une serie, avec indication du nombre d occurrences ;
- marquage de toutes les occurrences de la serie comme notifiees apres envoi du mail de moderation ;
- ajout d une ligne `Contexte series` dans le diagnostic de la page d administration.

Changement coeur GRR :

- `reservation/controleurs/editentreetrt.php` est modifie pour transmettre `entry_ids` au hook `hookEditEntreeTrt` ;
- une sauvegarde de l etat precedent est conservee dans `reservation/controleurs/editentreetrt.php.0.7.org`.

Notes :

- aucun changement de schema SQL ;
- les autres modules continuent de recevoir `entry_id` comme avant ;
- les fichiers joints restent geres comme avant : cette version ne modifie pas l import de fichiers pour les series.

## Version 0.7.0

Cette version ajoute un diagnostic dans la page d administration du module.

Fonctionnalites ajoutees :

- affichage de l etat d activation du module ;
- affichage de l etat des options formulaire, detail et e-mail ;
- verification de la configuration mail globale GRR ;
- verification de la presence des tables `*_stagiaire_user` et `*_stagiaire_reservation` ;
- verification de la presence des hooks coeur necessaires :
  - `hookEditEntreeForm` ;
  - `hookEditEntreeValidate` ;
  - `hookEditEntreeTrt` ;
  - `hookVueReservation` ;
  - `hookModerateEntry`.

Notes :

- aucun changement de schema SQL ;
- aucun changement dans le coeur GRR ;
- le diagnostic ne bloque pas la configuration, il sert uniquement d aide au test.

## Version 0.6.0

Cette version envoie une confirmation au stagiaire lorsqu une reservation soumise a moderation est acceptee ou refusee.

Fonctionnalites ajoutees :

- ecoute du hook `hookModerateEntry` ajoute en version 0.2.0 ;
- envoi d un mail au stagiaire apres acceptation par moderation ;
- envoi d un mail au stagiaire apres refus par moderation ;
- reprise du commentaire de moderation dans le message si disponible ;
- respect du reglage global GRR `automatic_mail` ;
- respect de l option module `Envoyer les confirmations e-mail au stagiaire` ;
- respect de l option d envoi de mail de l action de moderation GRR ;
- journalisation via `Email::Envois()` et la table GRR de suivi des mails ;
- prevention des doublons avec le champ `mail_moderation_sent`.

Notes :

- aucun changement de schema SQL ;
- les donnees stagiaire doivent avoir ete enregistrees sur la reservation pour que le mail de moderation soit envoye.

## Version 0.5.0

Cette version envoie une confirmation initiale au stagiaire apres l enregistrement de la reservation.

Fonctionnalites ajoutees :

- envoi d un e-mail au stagiaire a l adresse saisie dans le formulaire ;
- respect du reglage global GRR `automatic_mail` ;
- respect du mode mail GRR `smtp` ou `mail` ;
- respect de l option module `Envoyer les confirmations e-mail au stagiaire` ;
- journalisation via `Email::Envois()` et la table GRR de suivi des mails ;
- prevention des doublons avec le champ `mail_creation_sent`.

Notes :

- si la reservation est soumise a moderation, le mail indique que la reservation est en attente de moderation ;
- le mail apres acceptation ou refus par un moderateur est ajoute a partir de la version 0.6.0 ;
- aucun changement de schema SQL.

## Version 0.4.0

Cette version enregistre les informations stagiaire avec la reservation et les affiche dans le detail de reservation.

Fonctionnalites ajoutees :

- enregistrement de `Nom`, `Prenom`, `Email` et `Encadrant` dans `*_stagiaire_reservation` apres validation de la reservation ;
- mise a jour des informations stagiaire lors de la modification d une reservation ;
- pre-remplissage du bloc `Stagiaire` lors de la modification d une reservation existante ;
- affichage des informations stagiaire dans le detail de reservation si l option module `Afficher dans le detail de reservation` est activee ;
- aucun changement de schema SQL.

Limite connue :

- les donnees stagiaire sont enregistrees sur l identifiant de reservation fourni par le hook `hookEditEntreeTrt`. Les reservations repetees ne sont pas encore generalisees a toute une serie.

## Version 0.3.1

Correctif de compatibilite avec les autres modules utilisant le formulaire de reservation.

Notes :

- aucun changement fonctionnel direct dans le module `stagiaire` ;
- le bloc `Stagiaire` peut etre masque si un autre module remplace `hookEditEntreeForm` au lieu de concatener son contenu ;
- le module `suivi_demandes` doit etre au minimum en version `4.4.2` si les deux modules sont actifs ensemble ;
- aucun changement de schema SQL.

## Version 0.3.0

Cette version affiche et valide les champs stagiaire dans le formulaire de reservation.

Fonctionnalites ajoutees :

- affichage du bloc `Stagiaire` dans le formulaire de reservation pour les comptes declares stagiaires ;
- champs obligatoires `Nom`, `Prenom`, `Email`, `Encadrant` ;
- conservation des valeurs saisies lors d un retour au formulaire apres erreur ;
- validation serveur via `hookEditEntreeValidate` ;
- refus de la reservation si un champ obligatoire est vide ;
- refus de la reservation si l adresse e-mail est invalide ;
- aucun changement de schema SQL.

Cette version ne stocke pas encore les donnees stagiaire avec la reservation.

## Version 0.2.0

Cette version ajoute les hooks coeur minimaux necessaires aux prochaines integrations du module.

Changements :

- ajout du contexte generique `$GLOBALS['grr_editentree_context']` dans `reservation/controleurs/editentree.php` ;
- conservation du contexte historique `$GLOBALS['suivi_demandes_editentree_context']` pour ne pas casser le module `suivi_demandes` ;
- ajout des champs `stagiaire_nom`, `stagiaire_prenom`, `stagiaire_email` et `stagiaire_encadrant` dans les variables filtrees de `reservation/controleurs/editentreetrt.php` ;
- ajout du hook `hookEditEntreeValidate` avant l ecriture de la reservation ;
- ajout du contexte generique `$GLOBALS['grr_editentreetrt_context']` apres enregistrement d une reservation simple ;
- conservation du contexte historique `$GLOBALS['suivi_demandes_editentreetrt_context']` pour compatibilite ;
- ajout du hook `hookModerateEntry` dans `include/mrbs_sql.inc.php` apres decision de moderation et avant suppression logique d une reservation refusee ;
- sauvegarde locale de `include/mrbs_sql.inc.php` dans `include/mrbs_sql.inc.php.org`.

Cette version ne rend pas encore les champs stagiaire visibles dans le formulaire. Elle prepare uniquement les points d integration.

## Version 0.1.0

Cette premiere version installe le socle du module et l administration des comptes stagiaires.

Fonctionnalites ajoutees :

- declaration du module externe `stagiaire` ;
- creation de la table `*_stagiaire_user` pour les comptes GRR consideres comme stagiaires ;
- creation de la table `*_stagiaire_reservation` pour les futures donnees liees aux reservations ;
- page autonome `personnalisation/modules/stagiaire/admin.php` reservee aux administrateurs generaux ;
- reglages d activation du module, du formulaire de reservation, du detail de reservation et des e-mails ;
- selection des comptes stagiaires parmi les utilisateurs GRR actifs.

Cette version ne modifie pas encore le formulaire de reservation et ne modifie pas le coeur GRR.

## Tables

### `*_stagiaire_user`

- `login` : identifiant GRR du compte considere comme stagiaire ;
- `created_by` : administrateur ayant enregistre la selection ;
- `created_at` : date d enregistrement.

### `*_stagiaire_reservation`

- `entry_id` : reservation GRR associee ;
- `nom` ;
- `prenom` ;
- `email` ;
- `encadrant` ;
- `created_by` ;
- `created_at` ;
- `updated_at` ;
- `mail_creation_sent` ;
- `mail_moderation_sent`.

La table est creee en V0.1.0 pour stabiliser le schema et elle est utilisee a partir de la version 0.4.0.

## Protocole de Test V0.1.0

1. Importer le module dans `personnalisation/modules/stagiaire`.
2. Installer ou mettre a jour le module depuis l administration GRR.
3. Verifier dans phpMyAdmin que `*_modulesext` contient `stagiaire` actif.
4. Verifier que les tables `*_stagiaire_user` et `*_stagiaire_reservation` existent.
5. Ouvrir `personnalisation/modules/stagiaire/admin.php` en administrateur general.
6. Modifier le nom affiche, enregistrer, puis verifier que la valeur reste affichee.
7. Cocher et decocher les options d activation, enregistrer, puis verifier leur persistance.
8. Selectionner un ou plusieurs comptes stagiaires, enregistrer, puis verifier que les cases restent cochees.
9. Verifier dans phpMyAdmin que `*_stagiaire_user` contient les logins selectionnes.
10. Ouvrir la page avec un compte non administrateur et verifier `Acces refuse`.

## Protocole de Test V0.2.0

1. Copier les fichiers coeur modifies sur l installation de test :
   - `reservation/controleurs/editentree.php`
   - `reservation/controleurs/editentreetrt.php`
   - `include/mrbs_sql.inc.php`
2. Verifier que les sauvegardes `.org` existent pour les fichiers modifies.
3. Importer le module `stagiaire` version `0.2.0`.
4. Installer ou mettre a jour le module depuis l administration GRR.
5. Creer une reservation classique avec un compte non stagiaire.
6. Verifier que la reservation fonctionne comme avant.
7. Creer une reservation sur une ressource soumise a moderation.
8. Verifier que la reservation apparait toujours dans la moderation GRR.
9. Valider puis refuser une reservation de test et verifier qu aucun message d erreur nouveau n apparait.
10. Verifier que le module `suivi_demandes`, si actif, continue a afficher ses champs dans le formulaire de reservation.

## Protocole de Test V0.3.0

1. Importer le module `stagiaire` version `0.3.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Dans `personnalisation/modules/stagiaire/admin.php`, selectionner un compte de test comme stagiaire.
4. Ouvrir le formulaire de reservation avec ce compte.
5. Verifier que le bloc `Stagiaire` apparait avec les champs `Nom`, `Prenom`, `Email`, `Encadrant`.
6. Soumettre le formulaire avec un champ vide et verifier que la reservation est refusee.
7. Soumettre le formulaire avec un e-mail invalide et verifier que la reservation est refusee.
8. Verifier que les valeurs saisies sont conservees lors du retour au formulaire.
9. Renseigner les quatre champs correctement et verifier que la reservation passe.
10. Ouvrir le formulaire avec un compte non stagiaire et verifier que le bloc `Stagiaire` n apparait pas.
11. Desactiver `Afficher les champs dans le formulaire de reservation` dans l administration du module et verifier que le bloc n apparait plus.

## Protocole de Test V0.4.0

1. Importer le module `stagiaire` version `0.4.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Verifier que le module est active et que les options `Afficher les champs dans le formulaire de reservation` et `Afficher dans le detail de reservation` sont cochees.
4. Avec un compte declare stagiaire, creer une reservation en renseignant `Nom`, `Prenom`, `Email` et `Encadrant`.
5. Verifier dans phpMyAdmin qu une ligne est creee dans `*_stagiaire_reservation` avec son `entry_id`.
6. Ouvrir le detail de la reservation et verifier que le bloc `Stagiaire` affiche les quatre informations.
7. Modifier la reservation, changer une information stagiaire, enregistrer, puis verifier que la ligne SQL et le detail de reservation sont mis a jour.
8. Desactiver `Afficher dans le detail de reservation`, ouvrir le detail de la reservation et verifier que le bloc n apparait plus.
9. Creer une reservation avec un compte non stagiaire et verifier qu aucune ligne nouvelle n est creee dans `*_stagiaire_reservation`.
10. Si le module `suivi_demandes` est actif, ouvrir le detail d une reservation associee a une demande et verifier que les deux blocs restent visibles.

## Protocole de Test V0.5.0

1. Importer le module `stagiaire` version `0.5.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Verifier que les mails automatiques GRR sont actifs et que la methode mail est `smtp` ou `mail`.
4. Dans l administration du module, verifier que `Envoyer les confirmations e-mail au stagiaire` est coche.
5. Avec un compte declare stagiaire, creer une reservation avec une adresse e-mail de test accessible.
6. Verifier que l e-mail de confirmation est recu par le stagiaire.
7. Verifier dans phpMyAdmin que `mail_creation_sent` vaut `1` dans `*_stagiaire_reservation`.
8. Verifier dans le suivi/log des mails GRR que le mail utilise le template `stagiaire_confirmation_creation`.
9. Modifier la meme reservation et verifier qu un second mail initial n est pas renvoye.
10. Desactiver l option mail du module, creer une autre reservation, puis verifier qu aucun mail stagiaire n est envoye.

## Protocole de Test V0.6.0

1. Importer le module `stagiaire` version `0.6.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Verifier que les mails automatiques GRR sont actifs et que la methode mail est `smtp` ou `mail`.
4. Dans l administration du module, verifier que `Envoyer les confirmations e-mail au stagiaire` est coche.
5. Creer une reservation avec un compte stagiaire sur une ressource soumise a moderation.
6. Verifier que le mail initial indique une reservation en attente de moderation.
7. Depuis un compte moderateur, accepter la reservation en conservant l envoi de mail actif.
8. Verifier que le stagiaire recoit un mail indiquant que la reservation est acceptee.
9. Verifier dans phpMyAdmin que `mail_moderation_sent` vaut `1` dans `*_stagiaire_reservation`.
10. Creer une seconde reservation soumise a moderation, puis la refuser avec un commentaire.
11. Verifier que le stagiaire recoit un mail indiquant le refus et le commentaire.
12. Verifier dans le suivi/log des mails GRR les templates `stagiaire_confirmation_moderation_accepted` et `stagiaire_confirmation_moderation_refused`.
13. Refaire un test avec l option d envoi de mail de moderation desactivee et verifier qu aucun mail stagiaire de moderation n est envoye.

## Protocole de Test V0.7.0

1. Importer le module `stagiaire` version `0.7.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Ouvrir `personnalisation/modules/stagiaire/admin.php` avec un compte administrateur general.
4. Verifier que le bloc `Diagnostic` apparait entre la configuration generale et les comptes stagiaires.
5. Verifier que les lignes `Table stagiaire_user` et `Table stagiaire_reservation` sont en etat `OK`.
6. Verifier que les hooks formulaire, validation/traitement, detail et moderation sont en etat `OK`.
7. Desactiver puis reactiver une option du module et verifier que le diagnostic change sans empecher l enregistrement.
8. Si les mails GRR sont desactives, verifier que la ligne `Mails automatiques GRR` indique `A verifier`.

## Protocole de Test V0.8.0

1. Copier le fichier coeur modifie `reservation/controleurs/editentreetrt.php` sur l installation de test.
2. Verifier que la sauvegarde `reservation/controleurs/editentreetrt.php.0.7.org` existe dans l environnement de travail.
3. Importer le module `stagiaire` version `0.8.0`.
4. Mettre a jour le module depuis l administration GRR.
5. Ouvrir `personnalisation/modules/stagiaire/admin.php` et verifier que la ligne `Contexte series` est en etat `OK`.
6. Avec un compte declare stagiaire, creer une reservation repetee avec plusieurs occurrences.
7. Dans phpMyAdmin, verifier que chaque occurrence creee possede une ligne dans `*_stagiaire_reservation`.
8. Ouvrir le detail de plusieurs occurrences et verifier que le bloc `Stagiaire` apparait a chaque fois.
9. Verifier qu un seul mail initial est envoye pour la serie et qu il indique le nombre d occurrences.
10. Si la ressource est soumise a moderation, accepter ou refuser la serie et verifier qu un seul mail de moderation est envoye.
11. Verifier dans phpMyAdmin que les champs `mail_creation_sent` et `mail_moderation_sent` sont marques sur les occurrences concernees.

## Protocole de Test V0.9.0

1. Importer le module `stagiaire` version `0.9.0`.
2. Conserver le fichier coeur V0.8.0 `reservation/controleurs/editentreetrt.php` deja valide.
3. Mettre a jour le module depuis l administration GRR.
4. Ouvrir `personnalisation/modules/stagiaire/admin.php`.
5. Verifier que les lignes `Version module` et `Version PHP` apparaissent dans le diagnostic.
6. Verifier que `Version module` indique `0.9.0`.
7. Creer une reservation simple stagiaire et verifier qu elle fonctionne comme en V0.8.0.
8. Creer une reservation repetee stagiaire et verifier que les lignes SQL sont toujours creees pour chaque occurrence.
9. Si les mails GRR sont actifs, verifier que les mails stagiaire continuent a partir.
10. Si un probleme mail survient, verifier que la reservation reste creee et que l erreur est visible dans les logs serveur.

## Protocole de Test V0.10.0

1. Copier le fichier coeur modifie `reservation/controleurs/supreservation.php` sur l installation de test.
2. Verifier que la sauvegarde `reservation/controleurs/supreservation.php.0.9.org` existe dans l environnement de travail.
3. Importer le module `stagiaire` version `0.10.0`.
4. Mettre a jour le module depuis l administration GRR.
5. Ouvrir `personnalisation/modules/stagiaire/admin.php`.
6. Verifier que `Version module` indique `0.10.0`.
7. Verifier que la ligne `Hook suppression` est en etat `OK`.
8. Verifier que les mails automatiques GRR sont actifs et que l option module `Envoyer les confirmations e-mail au stagiaire` est cochee.
9. Avec un compte declare stagiaire, creer une reservation simple avec une adresse e-mail de test accessible.
10. Supprimer la reservation et verifier que le stagiaire recoit un mail indiquant la suppression.
11. Creer une reservation repetee stagiaire, supprimer une seule occurrence et verifier qu un mail de suppression est recu.
12. Creer une autre reservation repetee stagiaire, supprimer toute la serie ou la fin de serie et verifier qu un seul mail est recu avec le nombre de reservations concernees.
13. Desactiver les mails automatiques GRR ou l option mail du module, supprimer une autre reservation stagiaire et verifier qu aucun mail stagiaire de suppression n est envoye.

## Protocole de Test V1.0.0

1. Importer le module `stagiaire` version `1.0.0`.
2. Copier les fichiers coeur requis si ce n est pas deja fait :
   - `reservation/controleurs/editentree.php`
   - `reservation/controleurs/editentreetrt.php`
   - `reservation/controleurs/vuereservation.php`
   - `reservation/controleurs/supreservation.php`
   - `include/mrbs_sql.inc.php`
3. Mettre a jour le module depuis l administration GRR.
4. Ouvrir `personnalisation/modules/stagiaire/admin.php`.
5. Verifier que `Version module` indique `1.0.0`.
6. Verifier que les lignes de diagnostic des tables et hooks sont en etat `OK`.
7. Declarer un compte test comme stagiaire.
8. Creer une reservation simple, verifier les champs obligatoires, l enregistrement SQL et le detail de reservation.
9. Creer une reservation repetee et verifier qu une ligne SQL existe pour chaque occurrence.
10. Si les mails GRR sont actifs, verifier le mail de creation.
11. Sur une ressource soumise a moderation, accepter puis refuser deux reservations de test et verifier les mails de moderation.
12. Supprimer une reservation simple et verifier le mail de suppression.
13. Supprimer une occurrence et une serie repetee, puis verifier qu un seul mail de suppression est envoye pour chaque action.
14. Desactiver l option mail du module et verifier qu aucun mail stagiaire n est envoye lors d une creation, moderation ou suppression.

## Protocole de Test V1.1.0

1. Importer le module `stagiaire` version `1.1.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Se connecter avec un administrateur general.
4. Ouvrir `Gerer mon compte`.
5. Verifier que le bouton `Stagiaire` apparait dans le menu lateral.
6. Cliquer sur `Stagiaire`.
7. Verifier que la page `compte.php?pc=stagiaire` s affiche dans l interface GRR.
8. Cliquer sur `Administration du module`.
9. Verifier que `personnalisation/modules/stagiaire/admin.php` s ouvre et reste accessible.
10. Se connecter avec un compte non administrateur et verifier que le bouton `Stagiaire` n apparait pas.
11. Ouvrir directement `compte.php?pc=stagiaire` avec un compte non administrateur et verifier `Acces refuse`.

## Protocole de Test V1.1.1

1. Importer le module `stagiaire` version `1.1.1`.
2. Mettre a jour le module depuis l administration GRR.
3. Se connecter avec un administrateur general.
4. Ouvrir `Gerer mon compte`.
5. Cliquer sur `Stagiaire`.
6. Cliquer sur `Administration du module`.
7. Verifier que l administration s affiche dans l interface `Gerer mon compte`, sans ouvrir la page autonome.
8. Modifier une option simple, enregistrer, puis verifier que la page reste dans `compte.php?pc=stagiaire&admin=1`.
9. Verifier que la configuration est bien conservee.
10. Cliquer sur `Ouvrir la page autonome` et verifier que `personnalisation/modules/stagiaire/admin.php` fonctionne toujours.
11. Se connecter avec un compte non administrateur et verifier que le bouton `Stagiaire` n apparait pas.

## Protocole de Test V1.2.0

1. Importer le module `stagiaire` version `1.2.0`.
2. Mettre a jour le module depuis l administration GRR.
3. Se connecter avec un administrateur general.
4. Ouvrir `Gerer mon compte > Stagiaire`.
5. Cliquer sur `Reservations stagiaires`.
6. Verifier que la page `compte.php?pc=stagiaire&view=reservations` s affiche.
7. Verifier que les reservations stagiaires existantes apparaissent dans le tableau.
8. Cliquer sur le titre d une reservation et verifier l ouverture du detail GRR.
9. Tester le filtre par date.
10. Tester le filtre par ressource.
11. Tester le filtre par compte GRR stagiaire.
12. Tester le filtre par e-mail.
13. Changer le nombre de reservations affichees et verifier que la liste s adapte.
14. Cliquer sur `Exporter CSV` et verifier que le fichier contient les reservations filtrees.
15. Se connecter avec un compte non administrateur et verifier que le bouton `Stagiaire` n apparait pas.

## Protocole de Test V1.2.1

1. Importer le module `stagiaire` version `1.2.1`.
2. Mettre a jour le module depuis l administration GRR si GRR le propose.
3. Se connecter avec un administrateur general.
4. Ouvrir `Gerer mon compte > Stagiaire`.
5. Verifier que les reservations stagiaires s affichent directement sur `compte.php?pc=stagiaire`.
6. Verifier qu il n est plus necessaire de cliquer sur `Reservations stagiaires`.
7. Tester un filtre puis verifier que l URL reste sur `compte.php?pc=stagiaire`.
8. Cliquer sur `Reinitialiser` et verifier que l URL est `compte.php?pc=stagiaire`.
9. Cliquer sur `Exporter CSV` et verifier que le fichier contient les reservations filtrees.
10. Cliquer sur `Administration du module` et verifier que l administration integree fonctionne sur `compte.php?pc=stagiaire&admin=1`.
11. Ouvrir directement `personnalisation/modules/stagiaire/admin.php` et verifier que la page autonome reste accessible.
12. Se connecter avec un compte non administrateur et verifier que le bouton `Stagiaire` n apparait pas.

## Protocole de Test V1.2.2

1. Importer le module `stagiaire` version `1.2.2`.
2. Mettre a jour le module depuis l administration GRR si GRR le propose.
3. Se connecter avec un administrateur general.
4. Ouvrir `Gerer mon compte > Stagiaire`.
5. Verifier que les reservations stagiaires s affichent directement sur `compte.php?pc=stagiaire`.
6. Verifier que le bouton `Administration du module` est present.
7. Verifier que le bouton `Ouvrir la page autonome` n apparait plus sur cette page.
8. Cliquer sur `Administration du module` et verifier que l administration integree fonctionne.
9. Ouvrir directement `personnalisation/modules/stagiaire/admin.php` et verifier que la page autonome reste accessible.

## Limites V1.2.2

- Les mails de serie pointent vers la premiere occurrence concernee.
- Le mail de suppression est envoye avant la suppression logique. Si la suppression echoue ensuite cote SQL, le mail peut deja avoir ete envoye.
- La consultation V1.2.2 affiche uniquement les reservations non supprimees logiquement dans GRR.
