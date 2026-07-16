# Suivi des demandes

Module externe GRR pour ajouter une logique de demandes de suivi autour des ressources.

## Version 4.5.14

Cette version elargit l ajout de ressources pour les gestionnaires deja concernes par une demande.

Changements :

- un gestionnaire qui gere au moins une ressource deja associee a une demande peut ajouter une autre ressource active du module ;
- la liste d ajout propose les ressources actives du module non encore associees a la demande ;
- le controle POST utilise le meme perimetre que la liste affichee ;
- le retrait reste limite aux ressources gerees par le gestionnaire, sauf administrateur ;
- aucun changement de schema SQL.

Recette minimale :

1. Creer ou ouvrir une demande avec une ressource geree par un gestionnaire.
2. Se connecter avec ce gestionnaire.
3. Verifier que le bouton `Ajouter une ressource` est visible dans la fiche.
4. Ajouter une ressource active du module que ce gestionnaire ne gere pas.
5. Verifier que la ressource est bien associee a la demande.
6. Verifier que ce gestionnaire ne peut pas retirer cette ressource non geree.
7. Verifier qu un utilisateur non gestionnaire d une ressource associee ne peut pas ajouter de ressource.

## Version 4.5.13

Cette version corrige la visibilite des demandes creees pour un autre utilisateur.

Changements :

- resolution du demandeur selectionne vers le login canonique stocke en base GRR avant creation ;
- comparaison des logins de createur sans sensibilite a la casse dans les listes, compteurs et droits de consultation ;
- conservation du comportement existant : le demandeur choisi reste le createur de la demande ;
- aucun changement de schema SQL.

Recette minimale :

1. Creer une demande pour un autre utilisateur avec un gestionnaire ou administrateur.
2. Se connecter avec l utilisateur choisi.
3. Verifier que la demande apparait dans `Mes demandes`.
4. Cliquer sur le compteur `Creees par moi` et verifier que la demande apparait aussi dans ce perimetre.
5. Ouvrir la fiche et verifier que l acces est autorise.

## Version 4.5.12

Cette version permet aux gestionnaires de ressources et aux administrateurs de creer une demande pour un autre utilisateur GRR.

Changements :

- ajout d un champ `Demandeur` dans le formulaire de creation depuis `Mes demandes` ;
- ajout du meme champ lors de la creation d une nouvelle demande depuis une reservation ;
- champ visible uniquement pour les administrateurs ou les gestionnaires de ressources ;
- le demandeur selectionne devient le createur de la demande ;
- l acteur connecte reste conserve dans l historique de creation ;
- un gestionnaire non administrateur ne peut creer pour autrui que sur les ressources qu il gere ;
- la liste des demandeurs est limitee aux utilisateurs actifs dont le module est active ;
- aucun changement de schema SQL.

Recette minimale :

1. Ouvrir `Mes demandes` avec un utilisateur simple et verifier que le champ `Demandeur` n apparait pas.
2. Ouvrir `Mes demandes` avec un gestionnaire, creer une demande pour un autre utilisateur sur une ressource qu il gere.
3. Verifier que la fiche affiche l utilisateur choisi comme createur.
4. Verifier que l historique indique une creation realisee par le gestionnaire pour cet utilisateur.
5. Creer une nouvelle demande depuis une reservation avec un gestionnaire ou un administrateur et selectionner un autre demandeur.
6. Se connecter avec l utilisateur choisi et verifier que la demande apparait dans ses demandes visibles.
7. Tester un POST forge ou une selection sur une ressource non geree avec un gestionnaire non administrateur : la creation pour autrui doit etre refusee.

## Version 4.5.11

Cette version ajoute l export PDF des statistiques et simplifie la bascule d affichage de la liste.

Changements :

- ajout d un bouton `Rapport PDF` sur la page statistiques administrateur ;
- generation du PDF avec TCPDF deja fourni par GRR ;
- le rapport PDF reprend les filtres statistiques courants ;
- si une periode est definie dans les filtres, le PDF est limite a cette periode ;
- les demandes supprimees ne sont pas prises en compte, car les statistiques sont calculees uniquement depuis les demandes encore presentes en base ;
- remplacement des deux boutons `Lignes` et `Cartes enrichies` par un seul bouton icone ;
- l icone affiche le mode cible : lignes ou cartes ;
- conservation des filtres lors du changement de mode d affichage ;
- aucun changement de schema SQL.

Recette minimale :

1. Ouvrir la liste des demandes et verifier qu un seul bouton icone de bascule est affiche en haut a droite.
2. Cliquer sur le bouton et verifier que l affichage passe de cartes a lignes, puis de lignes a cartes.
3. Appliquer des filtres puis verifier que la bascule conserve les filtres.
4. Ouvrir la page `Statistiques` avec un administrateur general.
5. Definir une periode puis cliquer sur `Rapport PDF`.
6. Verifier que le PDF s ouvre et reprend la periode et les filtres affiches.
7. Supprimer une demande de test puis verifier qu elle n apparait plus dans les compteurs statistiques ni dans le PDF.

## Version 4.5.10

Cette version ajoute les modes d affichage de liste et une page de statistiques administrateur, tout en finalisant la lisibilite de la fiche detail.

Changements :

- placement des ressources associees et des suiveurs sur deux colonnes responsive dans la fiche detail ;
- bloc des ressources associees visible en lecture, avec actions de gestion uniquement pour les profils autorises ;
- historique rendu plus compact ;
- ajout d un bouton de bascule en haut a droite de la liste entre `Lignes` et `Cartes enrichies` ;
- conservation des filtres lors du changement de mode d affichage ;
- ajout d un bouton `Statistiques` visible uniquement par les administrateurs generaux ;
- ajout d une page statistiques administrateur avec filtres par periode de creation, statut, priorite, categorie, ressource et createur ;
- ajout d indicateurs sur statuts, priorites, categories, createurs, ressources, commentaires, pieces jointes, reservations, suiveurs et reouvertures ;
- calcul du temps de prise en charge depuis la creation jusqu au premier passage en cours ;
- si une demande est cloturee sans passage en cours, le temps de prise en charge est calcule depuis la creation jusqu a la cloture ;
- calcul du temps de cloture depuis la creation jusqu a la cloture ;
- aucun changement de schema SQL.

Recette minimale :

1. Ouvrir une fiche detail et verifier que les ressources associees et les suiveurs sont sur deux colonnes sur grand ecran.
2. Verifier que les ressources restent visibles pour un demandeur sans droit de gestion.
3. Verifier avec un profil autorise que les actions d ajout/retrait de ressources restent disponibles.
4. Verifier que l historique est plus compact et reste chronologique.
5. Ouvrir la liste des demandes et basculer entre `Lignes` et `Cartes enrichies`.
6. Appliquer des filtres puis verifier que la bascule conserve les filtres.
7. Verifier qu un non administrateur ne voit pas le bouton `Statistiques`.
8. Ouvrir `Statistiques` avec un administrateur general et tester les filtres de periode, statut, priorite, ressource et createur.
9. Verifier une demande cloturee sans passage en cours : son temps de prise en charge doit correspondre au delai creation -> cloture.

## Version 4.5.9

Cette version complete la refonte de lisibilite de la fiche detail, en harmonisant les blocs secondaires.

Changements :

- affichage des reservations associees sous forme de blocs lisibles ;
- affichage des suiveurs sous forme de blocs, avec conservation des actions de gestion existantes ;
- affichage de l historique sous forme de blocs chronologiques ;
- regroupement responsive des reservations et suiveurs sur deux colonnes ;
- conservation des droits, formulaires et modales existants ;
- aucun changement de schema SQL.

Recette minimale :

1. Ouvrir une demande ayant au moins une reservation associee, un suiveur et de l historique.
2. Verifier que les reservations associees apparaissent sous forme de blocs avec ressource, debut et fin.
3. Verifier que les suiveurs apparaissent sous forme de blocs.
4. Verifier avec un profil autorise que le retrait d un suiveur reste disponible.
5. Verifier que l historique est affiche sous forme de blocs chronologiques.
6. Reduire la largeur d affichage ou tester sur mobile et verifier que les blocs passent en une colonne.
7. Verifier qu aucune action existante de la fiche detail n a disparu.

## Version 4.5.8

Cette version ameliore la lisibilite des demandes pour les demandeurs, sans changer les droits ni le schema SQL.

Changements :

- affichage de la liste des demandes sous forme de cartes responsive ;
- fiche detail reorganisee en deux colonnes avec le resume en haut a gauche et la description en haut a droite ;
- commentaires et pieces jointes affiches en deux colonnes sous le resume et la description ;
- commentaires et pieces jointes presentes sous forme de blocs plus lisibles ;
- repli automatique en une colonne sur les petits ecrans ;
- conservation des actions et droits existants ;
- aucun changement de schema SQL.

Recette minimale :

1. Ouvrir la liste des demandes avec un compte demandeur.
2. Verifier que chaque demande apparait sous forme de carte avec statut, priorite, ressources, createur et date de mise a jour.
3. Ouvrir une fiche detail de demande.
4. Verifier que le bloc resume est en haut a gauche et que la description est en haut a droite sur grand ecran.
5. Verifier que les commentaires sont a gauche et les pieces jointes a droite sous ces deux premiers blocs.
6. Reduire la largeur d affichage ou tester sur mobile et verifier que les blocs passent en une colonne.
7. Verifier avec un administrateur que les boutons d action restent visibles selon les droits.

## Version 4.5.7

Cette version ajoute le renvoi manuel d une notification aux gestionnaires des ressources d une demande.

Changements :

- ajout d un bouton `Renvoyer aux gestionnaires` sur la fiche detail d une demande ;
- bouton visible uniquement par les administrateurs generaux ;
- envoi cible uniquement vers les gestionnaires des ressources associees a la demande ;
- le createur et les suiveurs ne sont pas destinataires de ce renvoi, sauf s ils sont aussi gestionnaires d une ressource concernee ;
- affichage d un retour si la configuration mail est inactive ou si aucun gestionnaire avec adresse e-mail active n est trouve ;
- ajout d une entree d historique `notification_gestionnaires` quand l envoi est effectue ;
- aucun changement de schema SQL.

Recette minimale :

1. Verifier que les mails automatiques GRR et les notifications du module sont actifs.
2. Ouvrir une demande associee a une ressource ayant au moins un gestionnaire avec adresse e-mail active.
3. Verifier avec un utilisateur non administrateur que le bouton `Renvoyer aux gestionnaires` n apparait pas.
4. Verifier avec un administrateur general que le bouton apparait.
5. Confirmer le renvoi et verifier que les gestionnaires recoivent le mail.
6. Verifier que le createur ou les suiveurs non gestionnaires ne recoivent pas ce renvoi cible.
7. Verifier l entree `notification_gestionnaires` dans l historique de la demande.
8. Tester une demande sans gestionnaire destinataire et verifier le message d absence de destinataire.

## Version 4.5.6

Cette version corrige la liste `Associer une demande existante` dans le formulaire de reservation.

Changements :

- les demandes non cloturees attachables ne sont plus limitees aux demandes creees par l utilisateur courant ;
- la liste reprend les demandes visibles par l utilisateur : createur, suiveur, gestionnaire d une ressource associee ou administrateur general ;
- une demande cloturee puis reouverte en `ouverte` ou `en_cours` redevient attachable si elle est visible par l utilisateur ;
- le controle serveur applique la meme regle que la liste affichee ;
- aucun changement de schema SQL.

Recette minimale :

1. Creer une demande avec un utilisateur A et l associer a au moins une ressource.
2. Cloturer cette demande.
3. Reouvrir cette demande en `ouverte`, puis ouvrir le formulaire de creation d une reservation avec un compte autorise a voir la demande.
4. Verifier que la demande apparait dans `Associer une demande existante`.
5. Reouvrir ou passer cette demande en `en_cours`, puis verifier qu elle apparait toujours.
6. Tester avec un administrateur general qui n est pas le createur.
7. Tester avec un utilisateur sans droit sur la demande et verifier qu elle n apparait pas.

## Version 4.5.5

Cette version ajoute la suppression definitive d une demande par un administrateur general.

Changements :

- ajout d un bouton `Supprimer la demande` sur la fiche detail, visible uniquement par les administrateurs generaux ;
- confirmation avant suppression definitive ;
- suppression des donnees associees au module : pieces jointes, commentaires, historique, suiveurs, ressources et liens de reservations ;
- suppression des fichiers stockes dans `storage/attachments` quand ils sont encore presents ;
- les reservations GRR associees ne sont pas supprimees, seuls les liens du module le sont ;
- aucun changement de schema SQL.

Recette minimale :

1. Creer une demande de test avec commentaire, suiveur, ressource, reservation associee et piece jointe.
2. Verifier avec un utilisateur non administrateur que le bouton `Supprimer la demande` n apparait pas.
3. Verifier avec un administrateur general que le bouton apparait sur la fiche detail.
4. Ouvrir la popup puis fermer sans confirmer et verifier que la demande existe toujours.
5. Confirmer la suppression et verifier le retour a la liste avec le message `Demande supprimee.`
6. Dans phpMyAdmin, verifier que l identifiant de demande n apparait plus dans les tables `*_suivi_demande*`.
7. Verifier que le fichier physique de la piece jointe a disparu de `personnalisation/modules/suivi_demandes/storage/attachments`.
8. Verifier que la reservation GRR associee existe toujours.

## Version 4.5.4

Cette version applique la strategie d'affichage responsive du module `boutons_perso`.

Changements :

- affichage fluide en pleine largeur dans l'administration et dans `Gerer mon compte` ;
- adaptation des tableaux, actions et popups aux petits ecrans ;
- bouton du menu gauche `Gerer mon compte` force en pleine largeur disponible.

Notes :

- aucun changement de schema SQL.

## Version 4.5.3

Cette version rend le commentaire obligatoire lors de la cloture d une demande.

Changements :

- ajout d un champ `Commentaire de cloture` dans la popup `Cloturer la demande` ;
- validation serveur du commentaire avant cloture ;
- enregistrement du commentaire dans les commentaires publics de la demande avant passage au statut `cloturee` ;
- conservation du commentaire saisi et reouverture automatique de la popup si la validation echoue ;
- aucun changement de schema SQL.

## Version 4.5.2

Cette version harmonise l'ergonomie du module avec les autres modules locaux.

Changements :

- administration du module affichee dans une popup de configuration ;
- formulaire de filtres de la liste des demandes deplace dans une popup ;
- actions de fiche demande deplacees dans des popups : changement de statut, pieces jointes, ressources, suiveurs et commentaires ;
- reouverture automatique de la popup concernee en cas d'erreur de validation sur une fiche ;
- conservation des actions serveur, noms de champs et redirections existantes ;
- aucun changement de schema SQL.

## Version 4.5.1

Cette version prepare l'integration du module dans `boutons_perso`.

Changements :

- ajout de `lib/Navigation.php` ;
- exposition d'une definition standardisee du bouton du module ;
- prise en compte de l'activation globale, de l'acces dans `Gerer mon compte` et de l'activation par utilisateur ;
- aucun changement d'affichage avant l'integration dans `boutons_perso`.

## Version 4.5.0

Cette version ajoute l activation ou la desactivation du module par compte utilisateur.

Changements :

- ajout d un panneau `Comptes` dans l administration du module ;
- selection des comptes sous forme de deux colonnes `Module active` et `Module desactive` ;
- boutons `Desactiver >` et `< Activer`, comme pour la selection des ressources ;
- ajout de la table `*_suivi_demande_user_config` ;
- un compte desactive ne voit plus le module dans `Gerer mon compte`, les compteurs de demandes en haut, ni les integrations dans le formulaire de reservation ;
- les comptes desactives ne sont plus proposes comme nouveaux suiveurs ;
- comportement par defaut conserve : un compte sans configuration explicite reste active.

## Version 4.4.4

Cette version ajoute la configuration des couleurs de fond des liens de notification affiches en haut des pages GRR.

Changements :

- ajout de deux reglages dans l administration du module pour la couleur de fond des liens `demandes ouvertes` et `demandes en cours` ;
- application de ces couleurs aux liens affiches par `hookDemandesStatus`, sous les notifications de reservations en attente de moderation ;
- couleurs par defaut alignees avec les badges de statut existants : `ouverte` en `#5bc0de` et `en cours` en `#f0ad4e` ;
- stockage dans `*_setting` avec les cles courtes `suivi_demandes_nopen_col` et `suivi_demandes_nprog_col` ;
- aucun changement de schema SQL metier.

## Version 4.4.3

Correctif de compatibilite entre modules sur les hooks `Gerer mon compte`.

Changements :

- `hookCompteMenu` ajoute maintenant son contenu au hook existant au lieu de le remplacer ;
- `hookComptePage` ajoute maintenant son contenu au hook existant au lieu de le remplacer ;
- correction necessaire pour permettre au module `stagiaire` d afficher son bouton `Stagiaire` dans `Gerer mon compte` quand `suivi_demandes` est actif ;
- aucun changement de schema SQL.

## Version 4.4.2

Correctif de compatibilite entre modules sur les hooks reservation.

Changements :

- `hookEditEntreeForm` ajoute maintenant son contenu au hook existant au lieu de le remplacer ;
- `hookVueReservation` ajoute maintenant son contenu au hook existant au lieu de le remplacer ;
- correction necessaire pour permettre au module `stagiaire` d afficher son bloc dans le formulaire de reservation quand `suivi_demandes` est actif ;
- aucun changement de schema SQL.

## Version 4.4.1

Correctif d affichage des pieces jointes liees a un commentaire.

Changements :

- remplacement de la colonne `Associee a` par `Commentaire` dans la liste des pieces jointes ;
- affichage du texte du commentaire dans la liste des pieces jointes ;
- affichage du texte du commentaire dans la liste deroulante d association lors de l ajout d une piece jointe ;
- conservation de l identifiant, de la date, de l auteur et de l indicateur `interne` dans le libelle pour distinguer les commentaires similaires ;
- aucun changement de schema SQL.

## Version 4.4.0

Cette version permet d associer une piece jointe a un commentaire.

Fonctionnalites ajoutees :

- ajout de `commentaire_id` dans `*_suivi_demande_fichier` ;
- association optionnelle d une piece jointe a un commentaire visible ;
- affichage de l association `Demande` ou `Commentaire #...` dans la liste des pieces jointes ;
- filtrage des pieces jointes liees a un commentaire interne pour les utilisateurs non gestionnaires ;
- blocage du telechargement direct d une piece jointe liee a un commentaire interne pour les utilisateurs non autorises ;
- masquage des actions d historique de pieces jointes internes pour les utilisateurs non gestionnaires ;
- aucune notification generale pour les pieces jointes liees a un commentaire interne.

## Version 4.3.0

Cette version ajoute les notifications e-mail liees aux pieces jointes.

Fonctionnalites ajoutees :

- nouveau type de notification `Ajout ou retrait de piece jointe` dans l administration du module ;
- notification lors de l ajout d une piece jointe ;
- notification lors du retrait d une piece jointe ;
- les fichiers ne sont pas joints aux e-mails : le mail indique uniquement l action et le nom du fichier ;
- aucun changement de schema SQL.

## Version 4.2.2

Correctif de stockage des reglages de pieces jointes.

Changements :

- remplacement des noms de reglages trop longs pour la table GRR `setting` ;
- utilisation des cles courtes `suivi_demandes_attach_on`, `suivi_demandes_attach_mb` et `suivi_demandes_attach_ext` ;
- verification apres sauvegarde que l option d activation des pieces jointes est bien relue avec la valeur demandee ;
- ajout d un garde-fou pour eviter de futures cles de configuration depassant 32 caracteres ;
- aucun changement de schema SQL.

## Version 4.2.1

Correctif de sauvegarde de la configuration des pieces jointes.

Changements :

- fiabilisation de l enregistrement des reglages du module dans la table `setting` ;
- transmission explicite de la valeur decochee pour l option `Autoriser l ajout de pieces jointes` ;
- conservation des valeurs saisies dans le formulaire admin en cas d erreur de validation ;
- aucun changement de schema SQL.

## Version 4.2.0

Cette version ajoute la configuration des pieces jointes dans l administration du module.

Fonctionnalites ajoutees :

- activation ou desactivation de l ajout de pieces jointes ;
- taille maximale configurable par fichier, de 1 a 50 Mo ;
- liste des extensions autorisees configurable ;
- refus des extensions executables ou web dangereuses meme si elles sont saisies dans l administration ;
- conservation de l acces aux pieces jointes existantes quand l ajout est desactive ;
- aucun changement de schema SQL.

## Version 4.1.1

Correctif de confidentialite des commentaires internes.

Changements :

- masquage des actions d historique internes pour les utilisateurs non gestionnaires ;
- l historique `commentaire_interne` reste visible uniquement par les administrateurs et gestionnaires des ressources associees ;
- aucun changement de schema SQL.

## Version 4.1.0

Cette version ajoute les commentaires internes reserves aux gestionnaires et administrateurs.

Fonctionnalites ajoutees :

- ajout d un champ `interne` sur les commentaires ;
- ajout d une option `Commentaire interne` dans le formulaire de commentaire pour les administrateurs et gestionnaires des ressources associees ;
- masquage des commentaires internes pour les createurs et suiveurs qui ne sont pas gestionnaires ;
- absence de notification e-mail pour les commentaires internes afin de ne pas diffuser leur contenu hors du perimetre gestionnaire ;
- aucun changement du comportement des commentaires publics.

## Version 4.0.1

Correctif ergonomique de la fiche detail d une demande.

Changements :

- les boutons d action sont affiches sur la meme ligne que le titre de la demande ;
- le titre separe `Actions` est supprime ;
- aucun changement de schema SQL.

## Version 4.0.0

Cette version ajoute un MVP de pieces jointes associees aux demandes.

Fonctionnalites ajoutees :

- ajout d une table `*_suivi_demande_fichier` ;
- ajout d une section `Pieces jointes` sur la fiche detail d une demande ;
- upload de fichiers par les utilisateurs pouvant commenter la demande ;
- telechargement controle par les droits de lecture de la demande ;
- suppression par l auteur du depot tant que la demande n est pas cloturee, ou par un administrateur general ;
- stockage des fichiers avec un nom aleatoire non devinable ;
- protection minimale du dossier de stockage par `.htaccess` et `index.php`.

Limites volontaires :

- les pieces jointes sont associees a la demande, pas encore a un commentaire precis ;
- les extensions autorisees sont fixes dans le code ;
- taille maximale fixe : 5 Mo par fichier ;
- aucune notification e-mail specifique n est envoyee lors d un ajout ou retrait de piece jointe.

## Version 3.4.1

Correctif ergonomique de la recherche des suiveurs.

Changements :

- la recherche affiche maintenant une liste de resultats separee ;
- chaque resultat propose un bouton `Selectionner` pour ajouter directement le suiveur ;
- la liste deroulante d ajout reste disponible et n est plus filtree par la recherche ;
- aucun changement de schema SQL.

## Version 3.4.0

Cette version ameliore la selection des suiveurs sur la fiche detail d une demande.

Changements :

- ajout d un champ de recherche dans la section `Ajouter un suiveur` ;
- recherche sur le login, le nom et le prenom des utilisateurs actifs ;
- exclusion conservee du createur et des suiveurs deja associes ;
- conservation de la limite de resultats pour eviter une liste trop lourde ;
- aucun changement de schema SQL.

## Version 3.3.0

Cette version ameliore la fiabilite des filtres sur les demandes anciennes.

Changements :

- suppression de la limite de 300 demandes candidates quand un filtre est actif ;
- application des filtres sur l ensemble des demandes visibles avant limitation d affichage ;
- conservation de la limite `Afficher` pour la liste et l export CSV ;
- aucun changement de schema SQL.

## Version 3.2.0

Cette version ajoute un tableau de bord leger dans la page `Suivi des demandes`.

Fonctionnalites ajoutees :

- bloc `Synthese` au-dessus de la liste des demandes visibles ;
- compteurs des demandes ouvertes, en cours et cloturees visibles par l utilisateur ;
- compteur des demandes visibles en priorite haute ;
- compteurs des demandes creees par l utilisateur et suivies par l utilisateur ;
- liens directs depuis chaque compteur vers la liste filtree correspondante ;
- ajout du filtre `Perimetre` pour afficher toutes les demandes visibles, les demandes creees par moi ou les demandes suivies par moi ;
- application du filtre de perimetre a l export CSV.

Cette version ne modifie pas la base de donnees.

## Version 3.1.11

Cette version consolide la documentation et le filtrage des champs ajoutes au formulaire de reservation.

Changements :

- ajout de `suivi_demandes_category` dans la liste des variables filtrees par le traitement de reservation GRR ;
- ajout de la documentation du patch coeur V3.1.10 ;
- ajout des tests manuels de l indicateur des demandes ouvertes et en cours ;
- ajout des entrees `3.1.11` et `3.1.10` dans le changelog.

## Version 3.1.10

Cette version ajoute un indicateur dans l entete GRR pour les demandes actives visibles par l utilisateur.

Changements :

- affichage du nombre de demandes `ouvertes` visibles par l utilisateur ;
- affichage du nombre de demandes `en cours` visibles par l utilisateur ;
- lien direct vers `Suivi des demandes` avec le filtre de statut correspondant ;
- respect des droits existants : createur, suiveur, gestionnaire de ressource associee, administrateur ;
- ajout du hook coeur minimal `hookDemandesStatus` dans `include/functions.inc.php` ;
- affichage du hook dans `reservation/templates/layout.twig` et `compte/templates/layout.twig`.

## Version 3.1.9

Cette version ajoute la reouverture d une demande cloturee par un administrateur general.

Changements :

- affichage d une action `Reouvrir la demande` uniquement pour les administrateurs generaux ;
- choix du statut de reouverture : `ouverte` ou `en_cours` ;
- remise a zero de la date de cloture lors de la reouverture ;
- ajout d une entree d historique ;
- envoi de la notification standard de changement de statut si elle est active.

## Version 3.1.8

Cette version integre l administration du module dans l interface `Gerer mon compte`.

Changements :

- le bouton `Administration du module` dans `Suivi des demandes` ouvre maintenant `compte.php?pc=suivi_demandes&admin=1` ;
- l administration est affichee dans la meme interface GRR que la page utilisateur du module ;
- la page autonome `personnalisation/modules/suivi_demandes/admin.php` reste accessible ;
- `admin.php` peut maintenant etre utilise soit comme page autonome, soit comme fragment integre.

## Version 3.1.7

Cette version ajoute une gestion centralisee de l activation par ressource depuis l administration du module.

Changements :

- ajout d un panneau `Ressources` dans `admin.php` ;
- affichage des ressources en deux colonnes : module active et module desactive ;
- ajout de boutons pour passer une ou plusieurs ressources d une colonne a l autre ;
- enregistrement en masse dans `*_suivi_demande_room_config` ;
- conservation du reglage individuel disponible dans la configuration de chaque ressource GRR.

## Version 3.1.6

Cette version ajoute une activation du module par ressource.

Changements :

- ajout d une option `Activer le module pour cette ressource` dans la configuration de chaque ressource GRR ;
- ajout du hook coeur minimal `hookEditRoomSave` dans `admin/controleurs/admin_edit_room.php` pour enregistrer l option avec les boutons standards GRR ;
- creation idempotente de la table `*_suivi_demande_room_config` ;
- exclusion des ressources desactivees lors de la creation d une demande, de l integration reservation et de l ajout de ressources a une demande ;
- conservation de l historique existant : les demandes deja associees a une ressource desactivee restent consultables selon les droits habituels.

## Version 3.1.5

Cette version ajoute une option d activation des categories dans l administration du module.

Changements :

- ajout de l option `Activer les categories` dans `admin.php` ;
- masquage du champ categorie dans la popup de creation et dans le formulaire de reservation quand l option est desactivee ;
- masquage de la colonne, du filtre, de la fiche detail, des liens reservation, du CSV et des notifications liees aux categories ;
- conservation des anciennes categories en base pour permettre une reactivation ulterieure.

## Version 3.1.4

Correctif d ouverture de la popup `Nouvelle demande`.

Correction :

- remplacement de l ouverture Bootstrap par une popup autonome pilotee par le module ;
- suppression de la dependance a `data-bs-toggle` et a `bootstrap.Modal` ;
- fermeture par bouton, clic sur le fond ou touche Echap ;
- conservation de la reouverture automatique de la popup en cas d erreur de validation.

## Version 3.1.3

Correctif d ouverture de la popup `Nouvelle demande`.

Correction :

- adaptation de la popup a Bootstrap 5 utilise par GRR 4.6.0 ;
- remplacement des attributs `data-toggle` / `data-dismiss` par `data-bs-toggle` / `data-bs-dismiss` ;
- ouverture de secours par `bootstrap.Modal.getOrCreateInstance()` ;
- conservation d un fallback jQuery si une ancienne version Bootstrap est presente.

## Version 3.1.2

Correctif ergonomique des formulaires.

Changements :

- remplacement du formulaire visible `Nouvelle demande` par une popup Bootstrap dans `Gerer mon compte` ;
- ouverture automatique de la popup si la creation contient une erreur de validation ;
- affichage dynamique de la section `Suivi des demandes` dans le formulaire de reservation ;
- affichage du choix de demande existante uniquement si `Associer une demande existante` est selectionne ;
- affichage des champs de nouvelle demande uniquement si `Creer une nouvelle demande` est selectionne ;
- ajout d un lien `Administration du module` dans `Suivi des demandes` pour les administrateurs generaux.

## Version 3.1.1

Correctif ergonomique des filtres.

Changements :

- deplacement des filtres sous le formulaire de creation, directement sous le titre `Demandes visibles` ;
- suppression du titre intermediaire `Filtres` ;
- placement du bouton `Exporter CSV` sur la meme ligne que les filtres, apres `Reinitialiser` ;
- ajout d un choix du nombre de demandes a afficher : 10, 25, 50 ou 100 ;
- application de ce nombre a la liste et a l export CSV.

## Version 3.1.0

Cette version ajoute des filtres simples sur les demandes visibles.

Fonctionnalites ajoutees :

- filtre par statut ;
- filtre par priorite ;
- filtre par categorie, y compris les demandes sans categorie ;
- champ de recherche sur numero, titre, createur, statut, priorite et categorie ;
- export CSV respectant les filtres actifs ;
- conservation des droits de visibilite existants avant application des filtres.

Cette version ne modifie pas la base de donnees.

## Version 3.0.0

Cette version ajoute un export CSV simple des demandes visibles.

Fonctionnalites ajoutees :

- bouton `Exporter CSV` dans `Gerer mon compte > Suivi des demandes` ;
- export des demandes visibles selon les droits existants du module ;
- colonnes exportees : id, titre, statut, priorite, categorie, ressources, createur, dates de creation, mise a jour et cloture ;
- export limite aux demandes retournees par la liste visible du module ;
- protection minimale contre l injection de formule dans les tableurs.

Cette version ne modifie pas la base de donnees.

## Version 2.5.1

Correctif de persistance des categories.

Correction :

- remplacement de la detection SQL `SHOW COLUMNS` par une requete `INFORMATION_SCHEMA` compatible avec `grr_sql_query1()` ;
- conservation de la colonne `categorie` deja creee lors d une tentative precedente ;
- enregistrement effectif de la categorie selectionnee lors de la creation d une demande.

## Version 2.5.0

Cette version ajoute les categories configurables.

Fonctionnalites ajoutees :

- configuration d une liste de categories depuis l administration du module ;
- selection optionnelle d une categorie lors de la creation d une demande ;
- selection optionnelle d une categorie lors de la creation d une demande depuis une reservation ;
- affichage de la categorie dans la liste des demandes, la fiche detail, les liens de reservation et les notifications ;
- migration automatique de la colonne `categorie` sur les installations existantes.

Les categories restent volontairement simples : pas de droits ni de champs personnalises par categorie dans cette version.

## Version 2.4.0

Cette version ajoute le parametrage des libelles de statuts.

Fonctionnalites ajoutees :

- modification du libelle affiche des statuts `ouverte`, `en_cours`, `cloturee` ;
- application des libelles dans les listes, fiches detail, reservations associees et notifications ;
- conservation des codes internes pour ne pas modifier le cycle de vie des demandes.

Les transitions restent fixes : une demande est creee ouverte, peut passer en cours, puis etre cloturee.

## Version 2.3.0

Cette version ajoute le parametrage des priorites.

Fonctionnalites ajoutees :

- modification du libelle affiche des priorites `basse`, `normale`, `haute` ;
- activation/desactivation de chaque priorite dans les formulaires de creation ;
- conservation de l affichage des anciennes demandes meme si leur priorite est desactivee ;
- protection contre la desactivation de toutes les priorites.

Les codes internes restent fixes pour conserver la compatibilite avec les demandes existantes et les historiques.

## Version 2.2.0

Cette version ajoute le parametrage des droits de creation et de cloture.

Fonctionnalites ajoutees :

- choix du droit de creation des demandes ;
- choix du droit de cloture des demandes ;
- application du droit de creation dans `Gerer mon compte` ;
- application du droit de creation dans le formulaire de reservation ;
- application du droit de cloture dans la fiche detail d une demande.

Modes de creation disponibles :

- tout utilisateur connecte ;
- utilisateur ayant acces a au moins une ressource ;
- gestionnaire de ressource ou administrateur ;
- administrateur uniquement.

Modes de cloture disponibles :

- createur, gestionnaire ou administrateur ;
- gestionnaire ou administrateur ;
- administrateur uniquement.

Le comportement par defaut reste identique aux versions precedentes.

## Version 2.1.0

Cette version ajoute le parametrage fin des notifications e-mail.

Fonctionnalites ajoutees :

- activation/desactivation des notifications a la creation d une demande ;
- activation/desactivation des notifications de nouveaux commentaires ;
- activation/desactivation des notifications de changement de statut ;
- activation/desactivation des notifications d ajout ou retrait de suiveur ;
- activation/desactivation des notifications d ajout ou retrait de ressource.

Le reglage global `Activer les notifications e-mail du module` reste prioritaire : s il est desactive, aucune notification du module n est envoyee.

## Version 2.0.2

Correctif d enregistrement des options d administration.

Correction :

- remplacement des cles de configuration trop longues par des cles compatibles avec `grr_setting.NAME varchar(32)` ;
- les options `Afficher dans le formulaire de reservation`, `Afficher les demandes dans le detail d une reservation` et `Activer les notifications e-mail du module` sont maintenant stockees avec des noms courts ;
- conservation d une lecture de secours des anciens noms de cles.

## Version 2.0.1

Correctif d acces a la page d administration autonome.

Correction :

- exposition des variables globales GRR necessaires au chargement de la session depuis `lib/bootstrap.php` ;
- correction du cas ou `admin.php` renvoyait vers l identification alors que l utilisateur etait deja administrateur.

## Version 2.0.0

Cette version ajoute une premiere page d administration du module.

Page d administration :

`personnalisation/modules/suivi_demandes/admin.php`

Fonctionnalites ajoutees :

- nom affiche configurable ;
- activation/desactivation metier du module ;
- activation/desactivation de l entree dans `Gerer mon compte` ;
- activation/desactivation de l integration au formulaire de reservation ;
- activation/desactivation des liens dans le detail d une reservation ;
- activation/desactivation des notifications e-mail du module.

Cette version ne modifie pas les tables SQL metier. Les statuts, priorites, droits fins et modeles d e-mails restent fixes.

## Version 1.3.0

Cette version ajoute les notifications mail simples.

Fonctionnalites ajoutees :

- notification a la creation d une demande ;
- notification lors d un nouveau commentaire ;
- notification lors du passage en cours ;
- notification lors de la cloture ;
- notification lors de l ajout ou du retrait d un suiveur ;
- notification lors de l ajout ou du retrait d une ressource ;
- destinataires dedupliques : createur, suiveurs et gestionnaires GRR des ressources associees ;
- respect des reglages GRR `automatic_mail` et `grr_mail_method`.

Les notifications utilisent `Email::Envois()` de GRR et sont visibles dans le suivi des mails GRR quand la journalisation est active.

## Version 1.2.0

Cette version complete les droits gestionnaires sur les ressources associees.

Fonctionnalites ajoutees :

- bloc de gestion des ressources dans la fiche detail d une demande ;
- ajout d une ressource par un administrateur ou par un gestionnaire GRR de ressource associee ;
- ajout limite aux ressources que l utilisateur gere ;
- retrait d une ressource par un administrateur ou par un gestionnaire de cette ressource ;
- protection contre le retrait de la derniere ressource associee ;
- protection contre le retrait d une ressource liee a une reservation associee.

## Version 1.1.0

Cette version ajoute l integration minimale au formulaire de reservation GRR.

Fonctionnalites ajoutees :

- bloc `Suivi des demandes` dans le formulaire de reservation ;
- choix de ne pas associer de demande ;
- creation d une nouvelle demande depuis une reservation unique ;
- association d une demande existante creee par l utilisateur ;
- association automatique de la ressource reservee ;
- lien depuis la fiche reservation vers les demandes associees ;
- lien depuis la fiche demande vers les reservations associees.

Limite volontaire : les reservations periodiques ne sont pas associees en V1.1.

## Version 1.0.0

Cette version correspond a la V1 stable sans integration au formulaire de reservation.

Fonctionnalites disponibles :

- installation depuis les modules externes GRR ;
- creation idempotente des tables SQL ;
- entree `Mes demandes` dans `Gerer mon compte` ;
- creation d une demande sans reservation ;
- association d une demande a une ou plusieurs ressources ;
- statuts fixes : ouverte, en cours, cloturee ;
- priorites fixes : basse, normale, haute ;
- liste des demandes visibles par l utilisateur ;
- fiche detail d une demande ;
- commentaires ;
- gestion des suiveurs ;
- historique des actions ;
- passage d une demande ouverte en cours par administrateur general ou gestionnaire GRR d une ressource associee ;
- cloture par createur, administrateur general ou gestionnaire GRR d une ressource associee.

## Droits V2.0

Un utilisateur voit :

- les demandes qu il a creees ;
- les demandes dont il est suiveur ;
- les demandes associees a une ressource dont il est gestionnaire GRR.

Un suiveur peut commenter une demande non cloturee.

Un createur peut cloturer sa demande.

Un gestionnaire GRR d une ressource associee peut voir, commenter, gerer les suiveurs, passer en cours, cloturer la demande, ajouter une ressource qu il gere et retirer une ressource qu il gere si elle n est pas la derniere et si elle n est pas liee a une reservation associee.

Un administrateur general voit toutes les demandes et dispose du controle complet.

## Installation

1. Copier le dossier `suivi_demandes` dans `personnalisation/modules/`.
2. Aller dans l administration GRR, section modules externes.
3. Installer ou activer le module `Suivi des demandes`.
4. Verifier que les tables SQL sont creees.
5. Ouvrir `personnalisation/modules/suivi_demandes/admin.php` avec un administrateur general GRR.
6. Verifier la configuration du module.
7. Aller dans `Gerer mon compte > Suivi des demandes`.

## Mise a jour

1. Remplacer le dossier `personnalisation/modules/suivi_demandes` par la nouvelle version.
2. Repasser par l administration des modules externes si GRR demande une reactivation.
3. Les tables sont creees avec `CREATE TABLE IF NOT EXISTS`, la mise a jour est donc idempotente.
4. Pour l integration reservation, verifier que les hooks coeur V1.1 sont presents dans GRR.
5. Pour les notifications, verifier que les mails automatiques GRR sont actifs et que la methode mail n est pas bloquee.
6. Ouvrir la page d administration du module et enregistrer une fois la configuration si les nouveaux reglages n apparaissent pas.
7. En V2.5, la colonne `categorie` est ajoutee automatiquement a `*_suivi_demande` si elle n existe pas encore.
8. En V3.1.6, verifier que le hook coeur `hookEditRoomSave` est present dans `admin/controleurs/admin_edit_room.php` pour enregistrer l option par ressource avec les boutons GRR.
9. En V3.1.10, verifier que le hook coeur `hookDemandesStatus` est present dans `include/functions.inc.php` et affiche dans les templates `reservation/templates/layout.twig` et `compte/templates/layout.twig`.

## Patch coeur V3.1.6

Pour enregistrer l option par ressource avec les boutons standards de GRR, un hook minimal est necessaire dans `admin/controleurs/admin_edit_room.php`.

La sauvegarde locale du fichier original est `admin/controleurs/admin_edit_room.php.org`.

Le hook de sauvegarde doit etre appele apres la creation ou mise a jour de la ressource, une fois `$room` connu :

```php
$GLOBALS['suivi_demandes_edit_room_id'] = (int) $room;
Hook::Appel("hookEditRoomSave");
```

Le hook d affichage existant `hookEditRoom1` doit recevoir l identifiant de la ressource via :

```php
$GLOBALS['suivi_demandes_edit_room_id'] = isset($row["id"]) && $row["id"] !== '' ? (int) $row["id"] : 0;
```

## Patch coeur V3.1.10

Pour afficher le nombre de demandes ouvertes et en cours dans l entete GRR, un hook minimal est necessaire dans `include/functions.inc.php`.

La sauvegarde locale du fichier original est `include/functions.inc.php.org`.

Le hook doit etre appele dans `print_header_twig()`, apres le calcul de `$d['mess_resa']` :

```php
$d['hookDemandesStatus'] = '';
if (class_exists('Hook'))
{
    $resulHook = Hook::Appel("hookDemandesStatus");
    if (isset($resulHook['hookDemandesStatus']))
        $d['hookDemandesStatus'] = $resulHook['hookDemandesStatus'];
}
```

Le contenu du hook doit ensuite etre affiche dans les entetes :

```twig
{{ d.hookDemandesStatus|default('')|raw }}
```

Fichiers concernes :

- `include/functions.inc.php`
- `reservation/templates/layout.twig`
- `compte/templates/layout.twig`

## Tables creees

- `*_suivi_demande`
- `*_suivi_demande_ressource`
- `*_suivi_demande_suiveur`
- `*_suivi_demande_commentaire`
- `*_suivi_demande_historique`
- `*_suivi_demande_reservation`
- `*_suivi_demande_room_config`
- `*_suivi_demande_user_config`
- `*_suivi_demande_fichier`

Le prefixe depend de la constante GRR `TABLE_PREFIX`.

Depuis la V2.5, `*_suivi_demande` contient aussi la colonne `categorie varchar(60)`.

Depuis la V3.1.6, `*_suivi_demande_room_config` permet de desactiver le module pour une ressource sans supprimer les demandes existantes.

Depuis la V4.5.0, `*_suivi_demande_user_config` permet de desactiver le module pour un compte utilisateur actif sans supprimer les demandes existantes.

Depuis la V4.0.0, `*_suivi_demande_fichier` conserve les metadonnees des pieces jointes. Les fichiers sont stockes dans `personnalisation/modules/suivi_demandes/storage/attachments/`.
Depuis la V4.4.0, la colonne `commentaire_id` permet d associer une piece jointe a un commentaire. La valeur `0` signifie que la piece jointe reste associee directement a la demande.

## Validation manuelle conseillee

1. Creer une demande avec titre, priorite, description et ressource.
2. Ouvrir la fiche detail.
3. Ajouter un commentaire.
4. Ajouter un suiveur, puis verifier que ce suiveur voit la demande.
5. Passer la demande en cours avec un administrateur general ou un gestionnaire GRR de la ressource.
6. Cloturer la demande avec le createur, un administrateur general ou un gestionnaire GRR de la ressource.
7. Verifier les entrees dans l historique.
8. Creer une reservation unique et choisir `Creer une nouvelle demande`.
9. Ouvrir la fiche reservation et verifier le lien vers la demande.
10. Ouvrir la fiche demande et verifier le lien vers la reservation.
11. Creer une autre reservation unique et choisir `Associer une demande existante`.
12. Avec un gestionnaire GRR, ouvrir une demande liee a une ressource qu il gere.
13. Ajouter une autre ressource geree par ce gestionnaire.
14. Retirer cette ressource ajoutee et verifier l historique.
15. Verifier qu une ressource liee a une reservation associee ne peut pas etre retiree.
16. Verifier qu une creation, un commentaire, un changement de statut, un ajout de suiveur et un ajout de ressource generent un mail.
17. Verifier les entrees correspondantes dans `Administration > Suivi des mails`.
18. Ouvrir `personnalisation/modules/suivi_demandes/admin.php` avec un administrateur general.
19. Modifier le nom affiche et verifier le libelle dans `Gerer mon compte`.
20. Desactiver l entree `Gerer mon compte` et verifier que le menu disparait.
21. Desactiver l integration reservation et verifier que le bloc n apparait plus dans le formulaire.
22. Desactiver les notifications et verifier qu une action ne genere plus d e-mail module.
23. Reactiver les notifications globales, puis desactiver uniquement `Nouveau commentaire`.
24. Ajouter un commentaire et verifier qu aucun mail de commentaire ne part.
25. Reactiver `Nouveau commentaire`, puis desactiver uniquement `Changement de statut`.
26. Passer une demande en cours ou cloturer une demande et verifier qu aucun mail de statut ne part.
27. Regler `Creation de demandes` sur `Administrateur uniquement`.
28. Verifier avec un utilisateur non administrateur que le formulaire de creation n apparait plus dans `Gerer mon compte`.
29. Verifier avec un utilisateur non administrateur que `Creer une nouvelle demande` n apparait plus dans le formulaire de reservation.
30. Regler `Cloture de demandes` sur `Administrateur uniquement`.
31. Verifier avec le createur non administrateur que le bouton `Cloturer la demande` n apparait plus.
32. Renommer la priorite `haute`, enregistrer, puis verifier le nouveau libelle dans le formulaire de creation.
33. Desactiver la priorite `basse`, enregistrer, puis verifier qu elle n apparait plus dans les nouvelles demandes.
34. Verifier qu une ancienne demande en priorite `basse` reste lisible avec son libelle.
35. Tenter de desactiver toutes les priorites et verifier qu un message d erreur bloque l enregistrement.
36. Renommer le statut `ouverte`, enregistrer, puis verifier le nouveau libelle dans la liste des demandes.
37. Renommer le statut `cloturee`, cloturer une demande de test et verifier le libelle dans la fiche detail.
38. Ajouter deux categories dans l administration, une par ligne.
39. Creer une demande depuis `Gerer mon compte` avec une categorie et verifier la liste puis la fiche detail.
40. Creer une demande depuis une reservation avec une categorie et verifier le lien dans la fiche reservation.
41. Creer une demande sans categorie et verifier qu elle affiche `Sans categorie`.
42. Desactiver `Activer les categories` dans l administration du module.
43. Verifier que le champ categorie, la colonne categorie, le filtre categorie, la fiche detail, le CSV et les notifications ne l affichent plus.
44. Reactiver `Activer les categories` et verifier que les categories existantes reapparaissent.
45. Cliquer sur `Exporter CSV` et verifier que le fichier telecharge contient les demandes visibles.
46. Verifier que le CSV contient les categories et les dates de creation/mise a jour quand les categories sont actives.
47. Tester l export avec un utilisateur non administrateur et verifier qu il ne contient pas les demandes non visibles pour lui.
48. Filtrer par statut puis verifier que la liste ne contient que ce statut.
49. Filtrer par categorie puis verifier que la liste et le CSV exporte respectent ce filtre.
50. Utiliser le champ de recherche avec un mot du titre ou le numero d une demande.
51. Cliquer sur `Reinitialiser` et verifier que la liste complete revient.
52. Changer `Afficher` a 10 puis verifier que la liste et le CSV exporte ne depassent pas 10 lignes de donnees.
53. Cliquer sur `Nouvelle demande` et verifier que le formulaire s ouvre en popup.
54. Soumettre une demande invalide et verifier que la popup se rouvre avec les erreurs.
55. Ouvrir un formulaire de reservation et verifier que les champs de demande existante/nouvelle demande s affichent selon le choix radio.
56. Avec un administrateur general, verifier que le bouton `Administration du module` ouvre l administration integree dans `Gerer mon compte`.
57. Avec un utilisateur non administrateur, verifier que le bouton d administration n apparait pas.
58. Ouvrir la configuration d une ressource GRR et verifier la presence de `Activer le module pour cette ressource`.
59. Desactiver cette option puis enregistrer la ressource avec `Enregistrer`.
60. Verifier que la ressource desactivee ne peut plus etre selectionnee lors de la creation d une demande.
61. Ouvrir le formulaire de reservation de cette ressource et verifier que le bloc `Suivi des demandes` n apparait plus.
62. Reactiver l option pour cette ressource et verifier que la creation et l integration reservation reapparaissent.
63. Ouvrir l administration du module et verifier le panneau `Ressources`.
64. Selectionner une ou plusieurs ressources actives, cliquer sur `Desactiver >`, enregistrer, puis verifier qu elles restent dans la colonne desactivee.
65. Selectionner une ou plusieurs ressources desactivees, cliquer sur `< Activer`, enregistrer, puis verifier qu elles restent dans la colonne active.
66. Verifier que le reglage centralise et le reglage dans la fiche ressource restent coherents.
67. Ouvrir aussi `personnalisation/modules/suivi_demandes/admin.php` directement et verifier que la page autonome fonctionne toujours.
68. Cloturer une demande de test sans commentaire et verifier que la popup reste ouverte avec une erreur.
69. Cloturer la demande avec un commentaire et verifier que ce commentaire apparait dans `Commentaires`.
70. Verifier qu un administrateur general voit l action `Reouvrir la demande`.
71. Reouvrir la demande avec le statut `Ouverte` et verifier que la date de cloture disparait.
72. Cloturer de nouveau la demande avec un autre commentaire.
73. Reouvrir la demande avec le statut `En cours`.
74. Verifier l historique et la notification de changement de statut si les notifications sont actives.
75. Creer ou conserver une demande `Ouverte` visible par l utilisateur de test.
76. Creer ou passer une demande visible au statut `En cours`.
77. Ouvrir une page GRR avec cet utilisateur et verifier l affichage du nombre de demandes ouvertes et en cours dans l entete.
78. Cliquer sur le lien des demandes ouvertes et verifier l ouverture de `Suivi des demandes` avec le filtre `Statut = Ouverte`.
79. Cliquer sur le lien des demandes en cours et verifier l ouverture de `Suivi des demandes` avec le filtre `Statut = En cours`.
80. Tester l affichage avec un createur, un suiveur, un gestionnaire de ressource associee et un administrateur general.
81. Tester un utilisateur sans demande active visible et verifier que l indicateur ne s affiche pas.
82. Ouvrir `Gerer mon compte > Suivi des demandes` et verifier la presence du bloc `Synthese`.
83. Verifier les compteurs `Ouverte`, `En cours`, `Cloturee`, `Priorite Haute`, `Creees par moi` et `Suivies par moi`.
84. Cliquer sur chaque compteur et verifier que le filtre correspondant est applique dans la liste.
85. Utiliser le filtre `Perimetre` sur `Creees par moi`, puis verifier que seules les demandes creees par l utilisateur restent visibles.
86. Utiliser le filtre `Perimetre` sur `Suivies par moi`, puis verifier que seules les demandes suivies par l utilisateur restent visibles.
87. Exporter le CSV avec un filtre `Perimetre` actif et verifier que le CSV respecte ce filtre.
88. Tester les compteurs avec un createur, un suiveur, un gestionnaire de ressource associee et un administrateur general.
89. Filtrer par statut, priorite, categorie, recherche et perimetre, puis verifier que les resultats restent coherents.
90. Si la base contient plus de 300 demandes, verifier qu une demande ancienne peut etre retrouvee par filtre ou recherche.
91. Exporter le CSV avec un filtre actif et verifier que l export correspond a la liste filtree.
92. Ouvrir une demande avec un compte autorise a gerer les suiveurs.
93. Dans `Ajouter un suiveur`, rechercher un utilisateur par login.
94. Rechercher un utilisateur par nom ou prenom.
95. Ajouter un utilisateur depuis une liste filtree et verifier qu il devient suiveur.
96. Verifier qu un utilisateur deja suiveur ne reapparait plus dans les resultats ajoutables.
97. Cliquer sur `Reinitialiser` dans la recherche de suiveur et verifier le retour a la liste non filtree.
98. Verifier que la recherche affiche une liste de resultats avec un bouton `Selectionner`.
99. Verifier que la liste deroulante reste affichee et non filtree apres une recherche.
100. Verifier qu un utilisateur sans droit de gestion des suiveurs ne voit pas le formulaire d ajout.
101. Ouvrir une demande non cloturee avec un utilisateur pouvant commenter.
102. Ajouter une piece jointe autorisee, par exemple `.pdf`, `.txt` ou `.jpg`.
103. Verifier que la piece jointe apparait dans la section `Pieces jointes`.
104. Telecharger la piece jointe depuis le lien affiche et verifier que le fichier s ouvre correctement.
105. Tester un fichier avec extension non autorisee, par exemple `.php`, et verifier le refus.
106. Tester un fichier trop volumineux et verifier le refus.
107. Verifier qu un autre utilisateur ayant le droit de voir la demande peut telecharger la piece jointe.
108. Verifier qu un utilisateur sans droit de voir la demande ne peut pas telecharger le fichier via `download.php`.
109. Supprimer une piece jointe avec son auteur ou un administrateur general.
110. Cloturer une demande avec un commentaire de cloture et verifier que l ajout de piece jointe n est plus propose.
111. Reactiver les options utiles apres le test.
112. Ouvrir une demande non cloturee avec un administrateur general.
113. Ajouter un commentaire public et verifier qu il reste visible par le createur et les suiveurs.
114. Ajouter un commentaire interne avec la case `Commentaire interne`.
115. Verifier que ce commentaire interne affiche le libelle `Interne` pour l administrateur.
116. Ouvrir la meme demande avec un gestionnaire de ressource associee et verifier que le commentaire interne est visible.
117. Ouvrir la meme demande avec le createur non gestionnaire et verifier que le commentaire interne n apparait pas.
118. Ouvrir la meme demande avec un suiveur non gestionnaire et verifier que le commentaire interne n apparait pas.
119. Verifier qu aucun e-mail de commentaire n est envoye pour le commentaire interne.
120. Verifier avec un createur ou suiveur non gestionnaire que l historique ne montre pas l action `commentaire_interne`.
121. Verifier avec un administrateur ou gestionnaire concerne que l historique montre bien l action `commentaire_interne`.
122. Ouvrir l administration du module et verifier le panneau `Pieces jointes`.
123. Desactiver `Autoriser l ajout de pieces jointes`, enregistrer, puis ouvrir une demande avec pieces jointes existantes.
124. Verifier que les pieces jointes existantes restent visibles et telechargeables, mais que le formulaire d ajout n apparait plus.
125. Reactiver l ajout de pieces jointes et regler la taille maximale a `1` Mo.
126. Tester un fichier superieur a 1 Mo et verifier le refus.
127. Ajouter une extension de test autorisee, par exemple `log`, enregistrer, puis verifier qu un fichier `.log` peut etre ajoute.
128. Supprimer `log`, enregistrer, puis verifier qu un fichier `.log` est refuse.
129. Tenter d ajouter une extension dangereuse, par exemple `php`, et verifier que l administration refuse l enregistrement.
130. Reactiver les options utiles apres le test.
131. Decocher `Autoriser l ajout de pieces jointes`, enregistrer et verifier que la case reste decochee apres rechargement.
132. Recocher `Autoriser l ajout de pieces jointes`, enregistrer et verifier que la case reste cochee apres rechargement.
133. Dans phpMyAdmin, verifier que `*_setting.NAME = suivi_demandes_attach_on` vaut `0` quand l option est decochee.
134. Recocher l option et verifier que `suivi_demandes_attach_on` vaut `1`.
135. Ouvrir l administration du module et verifier le nouveau type de notification `Ajout ou retrait de piece jointe`.
136. Activer les notifications globales et le type `Ajout ou retrait de piece jointe`.
137. Ajouter une piece jointe et verifier qu un e-mail de notification est envoye aux destinataires habituels.
138. Supprimer une piece jointe et verifier qu un e-mail de notification est envoye.
139. Desactiver uniquement le type `Ajout ou retrait de piece jointe`.
140. Ajouter puis supprimer une piece jointe et verifier qu aucun e-mail de piece jointe n est envoye.
141. Ajouter une piece jointe en laissant `Demande sans commentaire` et verifier qu elle reste associee a la demande.
142. Ajouter un commentaire public, puis ajouter une piece jointe en selectionnant ce commentaire.
143. Verifier que la liste des pieces jointes affiche l association au commentaire.
144. Ajouter un commentaire interne avec un administrateur ou gestionnaire.
145. Ajouter une piece jointe en selectionnant ce commentaire interne.
146. Verifier avec un administrateur ou gestionnaire que la piece jointe interne est visible et telechargeable.
147. Verifier avec le createur non gestionnaire que la piece jointe interne n apparait pas.
148. Verifier avec un suiveur non gestionnaire que la piece jointe interne n apparait pas.
149. Tester le lien direct `download.php?id=...` de la piece jointe interne avec un utilisateur non autorise et verifier `Acces refuse`.
150. Verifier que l historique ne montre pas `piece_jointe_interne_ajout` ou `piece_jointe_interne_retrait` aux utilisateurs non gestionnaires.
151. Ouvrir l administration du module et verifier le panneau `Notifications`.
152. Verifier que les couleurs des liens de notification en haut valent par defaut `#5bc0de` pour `Ouverte` et `#f0ad4e` pour `En cours`.
153. Modifier les deux couleurs, enregistrer, puis verifier que les valeurs restent affichees apres rechargement.
154. Ouvrir une page GRR avec des demandes ouvertes et en cours visibles, puis verifier que les liens affiches sous les notifications de moderation utilisent les couleurs de fond configurees.
155. Dans phpMyAdmin, verifier si besoin les entrees `suivi_demandes_nopen_col` et `suivi_demandes_nprog_col` dans `*_setting`.
156. Ouvrir l administration du module et verifier le panneau `Comptes`.
157. Selectionner un compte actif dans la colonne `Module active`, cliquer sur `Desactiver >`, enregistrer, puis verifier qu il reste dans la colonne `Module desactive`.
158. Se connecter avec ce compte et verifier que le bouton `Suivi des demandes` n apparait plus dans `Gerer mon compte`.
159. Verifier que les liens de notification en haut et le bloc `Suivi des demandes` du formulaire de reservation n apparaissent plus pour ce compte.
160. Avec un administrateur, ouvrir une demande et verifier que le compte desactive n est plus propose dans l ajout de suiveur.
161. Replacer le compte dans la colonne `Module active`, enregistrer, puis verifier que le module redevient accessible pour ce compte.
162. Dans phpMyAdmin, verifier si besoin que `*_suivi_demande_user_config` contient le login avec `enabled = 0` quand le compte est desactive.

## Limites V4.5.0

- Les reservations periodiques ne sont pas associees en V1.1.
- Pas encore de modeles d e-mails configurables.
- Pas encore de statuts personnalisables avec nouveaux codes ou transitions.
- Pas encore de droits ni de champs personnalises par categorie.
- Pas encore de droits fins par groupe GRR ou par domaine.
- La recherche de suiveurs est une recherche serveur avec rechargement de page, pas encore une recherche AJAX.
- Les commentaires internes ne declenchent volontairement pas de notification e-mail.
- La liste et l export CSV restent limites au nombre choisi dans le filtre `Afficher`.
- Sans filtre actif, la recherche des demandes visibles reste limitee aux demandes candidates les plus recentes pour preserver les performances.
- Les compteurs de synthese sont calcules a l affichage de la page ; une optimisation SQL pourra etre utile si le volume devient important.

## Changelog

### 4.5.11

- Ajout du rapport PDF des statistiques administrateur.
- Transmission des filtres statistiques courants au rapport PDF.
- Exclusion naturelle des demandes supprimees des statistiques et du PDF.
- Remplacement des boutons `Lignes` / `Cartes enrichies` par un bouton icone unique.
- Aucun changement de schema SQL.

### 4.5.10

- Ressources associees et suiveurs regroupes en deux colonnes responsive.
- Historique rendu plus compact.
- Ajout des modes de liste `Lignes` et `Cartes enrichies`.
- Ajout d une page statistiques reservee aux administrateurs generaux.
- Calcul du temps de prise en charge avec fallback creation -> cloture si aucun passage en cours n existe.
- Aucun changement de schema SQL.

### 4.5.9

- Conversion des reservations associees en blocs de lecture.
- Conversion des suiveurs en blocs avec actions conservees.
- Conversion de l historique en blocs chronologiques.
- Aucun changement de schema SQL.

### 4.5.8

- Refonte de la liste des demandes en cartes responsive.
- Reorganisation de la fiche detail avec resume/description puis commentaires/pieces jointes en deux colonnes.
- Amelioration de la lecture des commentaires et pieces jointes sous forme de blocs.
- Aucun changement de schema SQL.

### 4.5.7

- Ajout du bouton administrateur `Renvoyer aux gestionnaires`.
- Envoi cible aux gestionnaires des ressources associees a la demande.
- Ajout de l historique `notification_gestionnaires` lors d un renvoi effectue.
- Aucun changement de schema SQL.

### 4.5.6

- Correction de la liste `Associer une demande existante` dans le formulaire de reservation.
- Prise en compte des demandes non cloturees visibles par createur, suiveur, gestionnaire de ressource ou administrateur.
- Alignement du controle serveur d association sur cette meme regle.
- Aucun changement de schema SQL.

### 4.5.5

- Ajout de la suppression definitive d une demande par administrateur general.
- Suppression des lignes associees au module et des fichiers de pieces jointes.
- Conservation des reservations GRR, seuls les liens de suivi sont retires.
- Aucun changement de schema SQL.

### 4.5.0

- Ajout de la configuration d activation du module par compte utilisateur.
- Ajout de la table `*_suivi_demande_user_config`.
- Ajout d une double liste `Comptes` dans l administration du module.
- Application du reglage aux menus, compteurs, integrations reservation et ajout de suiveurs.

### 4.4.4

- Ajout de la configuration des couleurs de fond des liens de notification en haut.
- Valeurs par defaut alignees avec les statuts `ouverte` et `en_cours`.
- Application des couleurs au rendu `hookDemandesStatus`.
- Correction de la concatenation de `hookDemandesStatus` afin de ne pas masquer les alertes des autres modules.
- Aucun changement de schema SQL metier.

### 4.4.3

- Correction de `hookCompteMenu` pour concatener les contenus de plusieurs modules.
- Correction de `hookComptePage` pour concatener les contenus de plusieurs modules.
- Aucun changement de schema SQL.

### 4.4.2

- Correction de `hookEditEntreeForm` pour concatener les contenus de plusieurs modules.
- Correction de `hookVueReservation` pour concatener les contenus de plusieurs modules.
- Aucun changement de schema SQL.

### 4.4.1

- Affichage du texte du commentaire dans la colonne des pieces jointes.
- Affichage du texte du commentaire dans la liste deroulante d association.
- Conservation des metadonnees utiles dans le libelle pour distinguer les commentaires.
- Aucun changement de schema SQL.

### 4.4.0

- Ajout de l association optionnelle des pieces jointes a un commentaire.
- Ajout de la colonne `commentaire_id` sur `*_suivi_demande_fichier`.
- Filtrage des pieces jointes liees a un commentaire interne pour les utilisateurs non autorises.
- Blocage du telechargement direct des pieces jointes internes pour les utilisateurs non autorises.
- Masquage des actions d historique de pieces jointes internes.

### 4.3.0

- Ajout d un type de notification pour les pieces jointes.
- Notification lors de l ajout d une piece jointe.
- Notification lors du retrait d une piece jointe.
- Aucun fichier n est joint aux e-mails.
- Aucun changement de schema SQL.

### 4.2.2

- Correction de la cause racine de sauvegarde des reglages de pieces jointes : les anciens noms depassaient la limite de 32 caracteres de `*_setting.NAME`.
- Passage aux cles courtes `suivi_demandes_attach_on`, `suivi_demandes_attach_mb` et `suivi_demandes_attach_ext`.
- Ajout d une verification apres sauvegarde de l option d activation des pieces jointes.
- Aucun changement de schema SQL.

### 4.2.1

- Correction de la sauvegarde de l option `Autoriser l ajout de pieces jointes`.
- Fiabilisation de l ecriture des reglages du module dans `*_setting`.
- Conservation des valeurs saisies dans l administration en cas d erreur de validation.
- Aucun changement de schema SQL.

### 4.2.0

- Ajout de la configuration admin des pieces jointes.
- Ajout d une option d activation de l ajout de pieces jointes.
- Ajout d une taille maximale configurable par fichier.
- Ajout d une liste configurable d extensions autorisees avec refus des extensions dangereuses.
- Aucun changement de schema SQL.

### 4.1.1

- Masquage des actions d historique internes pour les utilisateurs non gestionnaires.
- Aucun changement de schema SQL.

### 4.1.0

- Ajout des commentaires internes reserves aux administrateurs et gestionnaires des ressources associees.
- Ajout automatique de la colonne `interne` sur `*_suivi_demande_commentaire`.
- Masquage des commentaires internes pour les utilisateurs non gestionnaires.
- Pas de notification e-mail pour les commentaires internes.

### 4.0.1

- Deplacement des boutons d action dans l en-tete de la fiche demande.
- Suppression du titre `Actions`.
- Aucun changement de schema SQL.

### 4.0.0

- Ajout des pieces jointes associees aux demandes.
- Ajout de la table `*_suivi_demande_fichier`.
- Ajout de `download.php` pour telecharger les fichiers apres verification des droits.
- Ajout du stockage local `storage/attachments`.

### 3.4.1

- Separation des resultats de recherche suiveur et de la liste deroulante d ajout.
- Ajout d un bouton `Selectionner` sur chaque resultat de recherche.

### 3.4.0

- Ajout d une recherche serveur pour les utilisateurs ajoutables comme suiveurs.
- Recherche sur login, nom et prenom, avec conservation des exclusions existantes.

### 3.3.0

- Suppression de la limite de 300 demandes candidates quand un filtre est actif.
- Conservation de la limite `Afficher` apres application des filtres.

### 3.2.0

- Ajout d un bloc `Synthese` dans `Gerer mon compte > Suivi des demandes`.
- Ajout de compteurs cliquables : ouvertes, en cours, cloturees, priorite haute, creees par moi, suivies par moi.
- Ajout du filtre `Perimetre` et propagation de ce filtre a l export CSV.

### 3.1.11

- Consolidation documentaire apres l ajout de l indicateur d entete.
- Ajout de `suivi_demandes_category` dans les variables filtrees du traitement de reservation GRR.

### 3.1.10

- Ajout d un indicateur dans l entete GRR pour les demandes ouvertes et en cours visibles par l utilisateur.
- Ajout des liens directs vers `Suivi des demandes` avec filtre de statut.

### 3.1.9

- Ajout de la reouverture des demandes cloturees par un administrateur general.
- Choix du statut cible de reouverture entre `ouverte` et `en_cours`.

### 3.1.8

- Ouverture de l administration du module dans l interface `Gerer mon compte`.
- Conservation de la page autonome `personnalisation/modules/suivi_demandes/admin.php`.

### 3.1.7

- Ajout d une configuration centralisee des ressources actives/desactivees dans l administration du module.
- Ajout d une double liste avec boutons de transfert entre colonnes.

### 3.1.6

- Ajout d une activation/desactivation du module par ressource.
- Ajout d une table de configuration par ressource.
- Ajout du hook coeur minimal `hookEditRoomSave` pour enregistrer l option depuis la configuration des ressources.

### 3.1.5

- Ajout de l activation/desactivation des categories depuis l administration.
- Masquage fonctionnel des categories dans les formulaires, listes, details, reservation, CSV et notifications quand l option est desactivee.

### 3.1.4

- Remplacement de l ouverture Bootstrap JS par une popup autonome pour `Nouvelle demande`.

### 3.1.3

- Correction de l ouverture de la popup `Nouvelle demande` avec Bootstrap 5.

### 3.1.2

- Passage de la creation de demande en popup Bootstrap.
- Ajout d un affichage dynamique dans le formulaire de reservation.
- Ajout d un lien d administration dans la page utilisateur pour les administrateurs generaux.

### 3.1.1

- Repositionnement des filtres sous `Demandes visibles`.
- Ajout du filtre de nombre de demandes affichees.
- Deplacement du bouton `Exporter CSV` sur la ligne des filtres.

### 3.1.0

- Ajout de filtres simples sur la liste des demandes visibles.
- Application des filtres a l export CSV.
- Aucun changement de schema SQL.

### 3.0.0

- Ajout de `export.php` pour telecharger les demandes visibles au format CSV.
- Ajout d un bouton `Exporter CSV` dans la page utilisateur du module.
- Export en lecture seule, sans changement de schema SQL.

### 2.5.1

- Correctif de detection de la colonne `categorie`.
- Correction de l enregistrement de la categorie selectionnee sur une nouvelle demande.

### 2.5.0

- Ajout des categories configurables.
- Ajout de la colonne `categorie` avec migration idempotente.
- Affichage de la categorie dans les vues utilisateur, reservation et notifications.

### 2.4.0

- Ajout de la configuration des libelles de statuts.
- Conservation des codes internes et des transitions existantes.

### 2.3.0

- Ajout de la configuration des libelles de priorites.
- Ajout de l activation/desactivation des priorites disponibles a la creation.
- Conservation de la compatibilite avec les demandes existantes.

### 2.2.0

- Ajout des reglages de droits de creation.
- Ajout des reglages de droits de cloture.
- Application des droits au compte utilisateur, au formulaire de reservation et aux actions de cloture.

### 2.1.0

- Ajout des reglages fins de notifications par type d evenement.
- Conservation du comportement par defaut : toutes les notifications restent actives a l installation.

### 2.0.2

- Correctif des cles de configuration depassant la limite `NAME varchar(32)` de la table `*_setting`.
- Stockage court : `suivi_demandes_resa_form`, `suivi_demandes_resa_detail`, `suivi_demandes_notif`.

### 2.0.1

- Correctif de reprise de session GRR dans `lib/bootstrap.php` pour la page `admin.php`.

### 2.0.0

- Ajout de `admin.php`, page d administration autonome reservee aux administrateurs generaux.
- Ajout des reglages d activation du module, du compte utilisateur, de l integration reservation, des liens reservation et des notifications.
- Ajout du nom affiche configurable.
- Ajout d un bootstrap module pour les pages autonomes.

### 1.3.0

- Notifications mail simples via `Email::Envois()`.
- Notifications creation, commentaire, statut, suiveurs et ressources.
- Destinataires dedupliques entre createur, suiveurs et gestionnaires GRR.

### 1.2.0

- Gestion des ressources associees depuis la fiche demande.
- Ajout/retrait de ressources par administrateur ou gestionnaire GRR autorise.
- Garde-fous sur derniere ressource et ressources liees a une reservation.

### 1.1.0

- Integration minimale au formulaire de reservation GRR.
- Association demandes-reservations via `*_suivi_demande_reservation`.
- Liens croises entre fiche reservation et fiche demande.

### 1.0.0

- Gel de la V1 stable sans reservation.
- Documentation d installation et de validation.

### 0.6.0

- Passage d une demande ouverte au statut en cours.
- Historique `passage_en_cours`.

### 0.5.0

- Gestion des suiveurs.
- Visibilite et droit de commentaire pour les suiveurs.

### 0.4.0

- Commentaires sur la fiche detail.

### 0.3.0

- Fiche detail.
- Cloture et historique de cloture.

### 0.2.0

- Creation d une demande sans reservation.
- Association aux ressources.
- Liste des demandes visibles.

### 0.1.0

- Installation du module.
- Creation des tables.
- Hooks `Gerer mon compte`.
