# Gestion materiel

Module local GRR pour gerer progressivement le cycle de vie du materiel.

## Version 0.1.0

- Declaration du module.
- Integration dans `Gerer mon compte`.
- Tableau de bord initial.
- Configuration minimale : activation du module et nom affiche.

## Version 0.2.0

- Creation des tables SQL metier du module.
- Ajout d'une couche `GestionMaterielRepository`.
- Diagnostic d'installation dans la page admin du module.
- Compteurs techniques initiaux pour les materiels, assignations, actions et notifications.

## Version 0.3.0

- Tableau de bord branche sur les donnees reelles.
- Liste des materiels actifs.
- Formulaire d'ajout simple pour les administrateurs generaux.
- Enregistrement des champs principaux du materiel.
- Affichage des prochaines maintenances et prochains etalonnages dans la liste.

## Version 0.4.0

- Fiche detaillee d'un materiel depuis la liste.
- Liens `Voir` et `Modifier` dans la liste.
- Formulaire d'edition des champs principaux pour les administrateurs generaux.
- Conservation de la validation de base : nom obligatoire, dates au format attendu.
- Retour a la fiche apres modification.

## Version 0.5.0

- Journal des actions par materiel.
- Formulaire d'ajout d'action depuis la fiche materiel.
- Types d'action fixes : acquisition, maintenance, etalonnage, controle, reparation, autre.
- Page globale des actions recentes.
- Mise a jour des prochaines dates de maintenance et d'etalonnage depuis une action.

## Version 0.6.0

- Affichage des utilisateurs assignes sur la fiche materiel.
- Selection des comptes GRR actifs a assigner a un materiel.
- Options par utilisateur pour les futures notifications de maintenance et d'etalonnage.
- Compteur global des utilisateurs assignes sur le tableau de bord.

## Version 0.7.4

- Calcul separe des echeances de maintenance et d'etalonnage en retard ou a venir.
- Alertes visibles sur le tableau de bord du module.
- Mise en evidence des dates depassees dans la liste et la fiche materiel.
- Alertes haut de page via `hookDemandesStatus` pour les utilisateurs connectes ayant acces au module.
- Lien direct vers le panneau des alertes depuis les notifications haut de page.
- Configuration des couleurs des liens de notification haut de page : en retard et a venir.
- Configuration du nombre de jours a prendre en compte pour les echeances a venir.
- Exclusion des materiels archives et hors service des alertes et des notifications e-mail.
- Lien haut de page compatible avec les pages `compte/` et `reservation/`.
- Correction de la coexistence avec les autres modules utilisant `hookDemandesStatus`.
- Les alertes haut de page suivent l'acces actuel au module et ne sont plus limitees au test administrateur strict.

## Version 0.8.0

- Page `Notifications` dans le module.
- Liste des echeances de maintenance et d'etalonnage a notifier.
- Envoi manuel des notifications non encore envoyees.
- Respect des reglages mail GRR `automatic_mail` et `grr_mail_method`.
- Journalisation des envois dans la table du module et via `Email::Envois()` GRR.
- Anti-doublon par materiel, utilisateur, type de notification et date d'echeance.
- Generation d'un token dans l'administration du module.
- Point d'entree `cron_notifications.php` executable par une tache planifiee NAS Synology.
- Le lien planifie utilise le delai configure dans `Echeances a venir`, avec surcharge possible via `days`.

## Version 0.9.0

- Remplacement du formulaire visible d'ajout de materiel par un bouton `Ajouter un materiel`.
- Affichage du formulaire d'ajout dans une popup integree, sur le modele du module `suivi_demandes`.
- Ajout d'un bouton `Administration du module` dans la page du module pour les administrateurs generaux.
- Integration de l'administration dans `Gerer mon compte` via `compte.php?pc=gestion_materiel&admin=1`.
- Conservation de la page autonome `personnalisation/modules/gestion_materiel/admin.php`.
- Ajout d'une selection de gestionnaires du module dans l'administration.
- Les gestionnaires configures voient tout le module, peuvent ajouter du materiel, modifier les materiels et gerer les utilisateurs assignes.
- Les utilisateurs non administrateurs et non gestionnaires ne voient le module que s'ils sont assignes a au moins un materiel actif.
- Les utilisateurs assignes voient uniquement leurs materiels, leurs alertes et leurs actions associees.
- Les utilisateurs assignes peuvent modifier leurs materiels et ajouter des actions sur ces materiels.
- Les couleurs des liens de notification haut de page restent configurables dans l'administration.

## Version 0.9.1

- Remplacement du formulaire visible d'ajout d'action par une popup integree dans la fiche materiel.
- Ajout d'un bouton `Ajouter une action` dans la fiche materiel.
- Ajout d'un bouton `Action` sur chaque materiel de la liste principale quand l'utilisateur peut modifier ce materiel.
- Ouverture automatique de la popup d'action depuis le bouton de la liste ou apres une erreur de validation.

## Version 0.10.0

- Ajout d'une table de groupes de materiel et d'un rattachement optionnel des materiels a un groupe.
- Ajout d'une page `Groupes` dans le module avec formulaire de creation en popup integree.
- Possibilite de laisser un materiel sans groupe, de choisir un groupe existant ou de creer un groupe a l'ajout/edition d'un materiel.
- Fiche groupe avec liste des materiels rattaches.
- Affectation des materiels a un groupe depuis la fiche groupe pour les administrateurs et gestionnaires du module.
- Alertes de groupe visibles uniquement sur la fiche groupe : maintenance, etalonnage, materiel en panne, en maintenance ou hors service.
- Exclusion des materiels en panne des alertes globales haut de page et des notifications e-mail d'echeance.

## Version 0.10.1

- Blocage de la reaffectation directe d'un materiel deja rattache a un autre groupe.
- Affectation des materiels depuis une popup appelee par un bouton dans la fiche groupe.
- Les materiels deja rattaches a un autre groupe restent visibles mais ne sont pas selectionnables.
- Formulaire materiel reorganise par sections pour ameliorer la lisibilite.
- Calcul automatique des prochaines dates de maintenance et d'etalonnage a l'ajout si un intervalle est renseigne et que la prochaine date est vide.

## Version 0.10.2

- Harmonisation visuelle generale du module.
- Ajout d'une couche CSS limitee au conteneur `gestion-materiel`.
- Barres d'action unifiees sur les pages principales et secondaires.
- Tableaux principaux filtrables cote navigateur.
- Ajout de compteurs de lignes visibles dans les listes.
- Statuts materiel et valeurs Oui/Non affiches sous forme de badges.
- Etats vides rendus de facon plus lisible dans les panneaux.

## Version 0.11.0

- Ajout du type d'action `Panne`, qui place automatiquement le materiel au statut `En panne`.
- L'action `Reparation` remet automatiquement le materiel au statut `En service`.
- Ajout du type d'action `Reparation partielle`, sans changement automatique de statut.
- Ajout d'une page dediee aux materiels archives pour les administrateurs et gestionnaires du module.
- Exclusion des materiels archives des listes, compteurs et groupes actifs.
- Ajout d'un bouton de suppression dans la liste et la fiche materiel.
- Confirmation obligatoire avant la suppression definitive.
- Suppression conjointe du materiel, de ses actions, de ses utilisateurs assignes et de ses journaux de notification.

## Version 0.12.0

- Liens vers la fiche materiel sur les references et noms des listes de materiels actifs.
- Ajout de filtres par colonne et du tri croissant ou decroissant sur les tableaux du module.
- Ajout de l'export CSV UTF-8 des lignes visibles apres filtrage.
- Deplacement du formulaire de gestion des utilisateurs assignes dans une popup.
- Ajout d'un bouton `Gerer les utilisateurs assignes` dans la fiche materiel.
- Ajout de la suppression definitive d'une action, reservee aux administrateurs generaux.
- Confirmation obligatoire avant la suppression d'une action.

## Version 0.12.1

- Liens vers la fiche materiel sur les references et noms de la liste des archives.
- Consultation des fiches archivees par les administrateurs et gestionnaires du module.
- Affichage en lecture seule des fiches archivees, avec conservation de l'historique des actions.
- Blocage des actions, modifications et changements d'utilisateurs sur les materiels archives.
- Conservation de la suppression definitive du materiel archive.
- Reduction de la hauteur des tuiles statistiques du tableau de bord.

## Version 0.12.2

- Prise en compte des gestionnaires sans sensibilite a la casse du login.
- Verification du resultat de chaque enregistrement de la configuration du module.
- Affichage des logins gestionnaires enregistres dans le diagnostic administrateur.

## Version 0.13.0

- Configuration des tuiles statistiques depuis l'administration du module.
- Selection des statistiques affichees et personnalisation de leur ordre.
- Configuration individuelle de la couleur de chaque tuile.
- Choix d'une disposition de une a huit tuiles par ligne sur ordinateur.
- Choix d'une taille globale compacte, normale ou grande.
- Conservation automatique de l'affichage historique en l'absence de configuration.

## Version 0.13.1

- Ajout du statut `Pas de projet en cours`.
- Ajout de l'action `Fin de projet`, disponible pour passer un materiel de `En service` a `Pas de projet en cours`.
- Ajout de l'action `Debut de projet`, disponible pour repasser le materiel a `En service`.
- Controle serveur des transitions de statut autorisees avant l'enregistrement de l'action.
- Affichage en orange des etalonnages depasses lorsque le materiel est sans projet en cours.
- Conservation des notifications e-mail d'etalonnage pour ces materiels.
- Priorite de l'alerte rouge lorsqu'une maintenance est egalement depassee.

## Version 0.14.0

- Ajout de documents sur chaque fiche materiel depuis une popup.
- Types proposes : mode operatoire, certificat d'etalonnage, verification periodique, maintenance, notice fabricant et autre.
- Description facultative, nom d'origine, taille, auteur et date conserves.
- Extensions autorisees et taille maximale configurables dans l'administration du module.
- Taille maximale par defaut de 10 Mo et extensions par defaut : `pdf`, `txt`, `csv`, `jpg`, `jpeg`, `png`, `odt`, `ods`, `doc`, `docx`, `xls`, `xlsx`, `zip`.
- Stockage sous un nom physique aleatoire sans extension dans `storage/documents`.
- Refus de l'acces HTTP direct au repertoire de stockage.
- Telechargement securise par `download.php`, apres controle de la session et des droits sur le materiel.
- Suppression d'un document reservee aux utilisateurs pouvant modifier le materiel.
- Documents visibles et telechargeables sur les fiches archivees, sans ajout ni suppression.
- Suppression des fichiers et lignes documentaires lors de la suppression definitive d'un materiel.
- Migration BDD 4 avec creation de la table `gestion_materiel_document`.

## Version 0.14.1

- Suppression de la notification haut de page pour un etalonnage depasse lorsque le materiel est au statut `Pas de projet en cours`.
- Conservation de l'alerte orange correspondante dans la page Gestion materiel.
- Conservation des autres notifications haut de page en retard ou a venir.

## Version 0.14.2

- Centralisation des controles d'acces dans `lib/Rights.php`.
- Conservation des regles existantes pour les administrateurs, les gestionnaires et les utilisateurs assignes.
- Utilisation du meme controle de consultation pour la page du module et le telechargement securise des documents.
- Preparation de l'integration avec le module `boutons_perso`, sans modification de l'affichage actuel.

## Version 0.14.3

- Ajout de `lib/Navigation.php`.
- Exposition d'une definition standardisee du bouton du module.
- Le libelle, l'activation et le droit d'acces restent determines par Gestion materiel.
- Aucun changement d'affichage avant l'integration dans `boutons_perso`.

## Version 0.14.4

- Harmonisation de l'administration du module avec `boutons_perso`.
- Remplacement des formulaires visibles de configuration et de token planifie par des popups.
- Remplacement de la page d'edition materiel par une popup ouverte depuis la fiche, avec compatibilite des anciens liens `view=edit`.
- Remplacement du formulaire d'envoi manuel des notifications par une popup.
- Conservation du diagnostic administrateur directement visible dans la page.
- Reouverture automatique de la popup en cas d'erreur de validation.

## Version 0.14.5

- Affichage fluide en pleine largeur dans l'administration et dans `Gerer mon compte`.
- Tableaux, barres d'actions et popups adaptes aux petits ecrans.
- Bouton du menu gauche `Gerer mon compte` force en pleine largeur disponible.
- Aucun changement de schema SQL : la version BDD reste `4`.
