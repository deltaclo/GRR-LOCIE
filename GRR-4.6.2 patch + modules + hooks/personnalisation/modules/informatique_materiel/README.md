# Informatique materiel

Module local pour GRR 4.6.2.

Version applicative : 1.3.9
Version BDD : 9

## Version 1.3.9

- Ajout d'un bouton `Modifier` sur les fiches de pret et dans le tableau des
  prets pour corriger la fiche de pret.
- L'action est reservee aux gestionnaires et administrateurs du module.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.8

- Ajout de la colonne `Commentaire` dans le tableau des prets de la page
  `Prets et restitutions`.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.7

- Remplacement de l'en-tete `#` par `N° du pret` dans le tableau commun des
  prets.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.6

- Ajout des colonnes `N° du pret` et `Commentaire` dans les tableaux
  d'alertes du tableau de bord et de la page `Alertes`.
- Les alertes liees a un pret affichent le numero du pret et le commentaire
  du pret ; les alertes sans pret affichent une valeur vide lisible.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.5

- Ajout d'un bouton administrateur sur la fiche personne pour aligner la date
  de fin prevue des prets ouverts sur la date de depart de la personne.
- L'action demande un commentaire, reste transactionnelle et journalisee, et ne
  cloture pas les prets.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.4

- Lors d'un transfert de pret, si la personne destinataire a une date de
  depart, la date de fin prevue du nouveau pret est definie a cette date.
- Le transfert est refuse si la date de depart de la personne destinataire est
  anterieure a la date de transfert.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.3

- Ajout du bouton `Transferer` directement dans la fiche pret.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.2

- Ajout du transfert d'un pret ouvert vers une autre personne.
- Le transfert cloture le pret courant et cree un nouveau pret ouvert, afin de
  conserver l'historique.
- Ajout des boutons `Transferer` dans le tableau des materiels, dans la fiche
  materiel et dans le tableau materiel de la fiche personne.
- Ajout du bouton `Transferer tout le materiel` sur la fiche personne pour les
  gestionnaires.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.1

- Suppression du bouton `Mon materiel` dans la navigation haute du module.
- L'acces direct a la page `Mon materiel informatique` et le bouton du menu
  `Gerer mon compte` restent inchanges.
- Aucun changement de schema SQL : la version BDD reste `9`.

## Version 1.3.0

- Ajout du mode `Materiel generique / pret multiple` sur les materiels.
- Les materiels marques en pret multiple peuvent avoir plusieurs prets ouverts
  simultanement sans generer de conflit.
- Ajout du statut automatique `pret_multiple` lorsqu'un materiel generique a
  plusieurs prets ouverts en cours.
- Les diagnostics et alertes de prets multiples ne signalent plus les
  materiels generiques.
- Ajout du champ `pret multiple` dans l'import et l'export CSV des materiels.
- Changement de schema SQL : la version BDD passe a `9`.

## Version 1.2.8

- Affichage fluide en pleine largeur dans l'administration et dans `Gerer mon compte`.
- Tableaux, filtres, actions et popups adaptes aux petits ecrans.
- Bouton du menu gauche `Gerer mon compte` force en pleine largeur disponible.
- Aucun changement de schema SQL : la version BDD reste `8`.

## Version 1.2.7

- Harmonisation de l'administration avec les modules `boutons_perso` et
  `gestion_materiel` : une barre d'actions ouvre les popups de configuration,
  roles, test LDAP et remise a zero.
- Reouverture automatique de la popup concernee en cas d'erreur de validation
  dans l'administration.
- Remplacement du formulaire visible `Executer l import` par une popup de
  confirmation apres previsualisation CSV.
- Aucun changement de schema SQL : la version BDD reste `8`.

## Version 1.2.6

- Ajout du bouton `Supprimer` dans les fiches personne et materiel.
- Ajout du lien vers la fiche materiel depuis la designation du tableau des
  materiels.
- Ajout d'un bandeau en haut du module lorsqu'il existe des conflits de prets
  en attente.
- Ajout de la configuration admin des couleurs d'alertes et de l'activation du
  bandeau conflits.
- Amelioration de la page `Conflits` avec des actions groupees par usage :
  consultation, decision et administration.
- Aucun changement de schema SQL : la version BDD reste `8`.

## Version 1.2.5

- Ajout de l'etat `Pas de materiel attribue` pour les personnes sans pret
  informatique ouvert.
- Ajout d'une fiche personne accessible depuis l'identifiant personnel, le nom
  et le login GRR.
- Ajout de liens depuis les categories vers la liste du materiel de la
  categorie.
- Ajout de suppressions controlees pour personnes, categories, materiels,
  prets et conflits.
- Ajout du tri par clic sur les titres de colonnes et d'un filtre dynamique
  sur les tableaux du module.
- Le bouton `Associations GRR / LDAP` est limite a la page `Personnes`.
- Le bouton d'administration du module est disponible dans la navigation du
  module pour les administrateurs.
- Ajout de la prolongation depuis la fiche pret et depuis les personnes dont la
  date de depart est depassee.
- Apres creation depuis LDAP, la fiche d'edition de la personne reste ouverte
  pour completer les informations manquantes.
- Aucun changement de schema SQL : la version BDD reste `8`.

## Version 1.2.4

- Le bouton perso `Mon materiel informatique` est affiche uniquement si le
  compte connecte a au moins un pret informatique ouvert.
- L'acces direct a la page `Mon materiel informatique` reste possible pour un
  compte connecte.
- Aucun changement de schema SQL : la version BDD reste `8`.

## Version 1.2.3

- Ajout du champ `email` dans les personnes.
- Ajout du champ email dans le formulaire `Ajouter une personne`.
- Lors d'une association LDAP, l'email LDAP est ajoute si l'email local est
  absent.
- Ajout d'un formulaire `Ajouter depuis LDAP` pour creer une personne a partir
  d'un compte LDAP.
- Ajout d'une popup de chargement lors de l'ouverture et des actions de la page
  `Associations GRR / LDAP`.
- Changement de schema SQL : la version BDD passe a `8`.

## Version 1.2.2

- Ajout d'un test LDAP dans l'administration du module.
- Ajout d'une page `Associations GRR / LDAP` depuis `Personnes`.
- La page recherche les propositions GRR puis LDAP pour les personnes actives.
- Validation possible par ligne, par selection ou pour toutes les lignes
  affichees.
- Aucun changement de schema SQL : la version BDD reste `7`.

## Version 1.2.1

- Ajout d'une page `Conflits de prets` pour traiter les conflits importes.
- Ajout des decisions `Conserver`, `Integrer si libre` et `Remplacer`.
- Chaque resolution demande une justification et met a jour le statut du
  conflit.
- L'action `Remplacer` exige la confirmation `REMPLACER`, annule le pret
  existant et cree la nouvelle entree.
- Aucun changement de schema SQL : la version BDD reste `7`.

## Version 1.2.0

- Ajout de la table `informatique_materiel_pret_conflit` pour conserver les
  doublons de prets ouverts importes sans modifier les prets existants.
- Lorsqu'un import de prets detecte un materiel deja associe a un pret ouvert,
  la nouvelle ligne est stockee avec le statut `en_attente`.
- Le journal d'import utilise le statut `conflict` pour ces lignes.
- La page Import CSV affiche les conflits de prets en attente.
- Changement de schema SQL : la version BDD passe a `7`.

## Version 1.1.1

- Ajout d'une action `Prolonger` dans le tableau des alertes pour les prets
  en retard et les personnes parties avec pret ouvert.
- La prolongation demande une nouvelle date et un commentaire obligatoire.
- L'action met a jour la date concernee, ajoute le commentaire sur l'objet et
  journalise l'evenement.
- Aucun changement de schema SQL : la version BDD reste `6`.

## Version 1.1.0

- Association des personnes a un login GRR via une liste de propositions GRR
  puis LDAP lorsque `personnalisation/config_ldap.inc.php` existe.
- Renommage de l'intitule personne `Identifiant historique` en
  `Identifiant personnel`.
- Arret de l'initialisation automatique des categories historiques.
- Ajout d'une remise a zero controlee dans l'administration du module.
- Ajout d'une fiche `Mon materiel informatique` par compte GRR.
- Ajout des boutons `Informatique materiel` et `Mon materiel informatique`
  pour `boutons_perso`.
- Passage des principaux formulaires de saisie et de filtre en popup.
- Aucun changement de schema SQL : la version BDD reste `6`.

## Version 1.0.0

- Stabilisation documentaire du MVP.
- Ajout de la procedure d'installation et de mise a jour MVP.
- Ajout de la recette complete MVP.
- Ajout de la procedure de sauvegarde et restauration.
- Ajout du bilan MVP et des evolutions post-MVP.
- Aucun changement de schema SQL : la version BDD reste `6`.

## Version 0.7.0

- Ajout de la table `informatique_materiel_document`.
- Ajout des documents sur la fiche materiel.
- Depot reserve aux gestionnaires et administrateurs.
- Telechargement securise via `download.php`.
- Stockage protege dans `storage/documents/`.
- Controle extension, taille maximale et type MIME interdit.
- Archivage logique des documents.

## Version 0.6.0

- Ajout de la page `Alertes`.
- Alertes calculees pour les prets en retard, personnes parties avec pret
  ouvert, materiels sans identifiant courant, materiels sans categorie,
  codes-barres dupliques et prets ouverts multiples.
- Ajout d'un apercu des alertes sur le tableau de bord.
- Ajout du lien de statut via `hookDemandesStatus` lorsque des alertes sont
  actives.
- Diagnostic administrateur enrichi avec les compteurs d'alertes detailles.

## Version 0.5.0

- Ajout de la table `informatique_materiel_import_log`.
- Ajout de la page `Import CSV` reservee aux gestionnaires et administrateurs.
- Stockage protege des fichiers CSV importes dans `storage/imports/`.
- Previsualisation obligatoire avant ecriture.
- Import des categories, personnes, materiels et prets.
- Journalisation ligne par ligne avec anti-doublon par hash de fichier, type de
  donnees et numero de ligne source.
- Support des dates `AAAA-MM-JJ`, `JJ/MM/AAAA`, `JJ-MM-AAAA` et des numeros de
  serie Excel.

## Version 0.4.0

- Ajout de la table `informatique_materiel_pret`.
- Ajout de la page `Prets` avec creation de pret, restitution et historique.
- Blocage applicatif d'un deuxieme pret ouvert sur le meme materiel.
- Mise a jour du statut materiel en `en_pret` a la creation et `stocke` a la
  restitution ou a l'annulation d'un pret ouvert.
- Historique des prets visible depuis la fiche materiel et la fiche pret.
- Export CSV des prets.
- Diagnostic administrateur des prets ouverts multiples, prets orphelins,
  retards et personnes parties avec pret ouvert.

## Version 0.3.0

- Ajout de la table `informatique_materiel_item`.
- Ajout de la page `Materiels` avec liste filtrable par recherche, categorie
  et statut.
- Creation, modification et archivage des materiels pour les gestionnaires et
  administrateurs.
- Generation automatique d'un identifiant lorsque le champ est laisse vide a la
  creation.
- Ajout d'une fiche materiel consultable.
- Export CSV des materiels.
- Diagnostic administrateur des materiels sans categorie et des doublons a
  verifier.

## Version 0.2.0

- Ajout des tables `informatique_materiel_personne`,
  `informatique_materiel_categorie` et `informatique_materiel_sequence`.
- Initialisation des categories issues du classeur historique lorsque la table
  des categories est vide.
- Ajout des pages `Personnes` et `Categories` dans le module.
- Creation, modification et archivage des personnes et categories pour les
  gestionnaires et administrateurs.
- Consultation des referentiels par tous les roles autorises.
- Exports CSV des personnes et categories.
- Diagnostic enrichi avec les tables et compteurs de referentiels.

## Version 0.1.0

- Declaration du module.
- Integration dans "Gerer mon compte".
- Page d'accueil initiale.
- Page d'administration.
- Activation et nom affiche configurables.
- Roles lecteur, operateur et gestionnaire.
- Tables socle `informatique_materiel_role` et `informatique_materiel_journal`.
- Diagnostic d'installation.
- Definition de navigation pour une future integration dans `boutons_perso`.

## Fonctions prevues

- referentiel des personnes ;
- categories et prefixes de materiel ;
- inventaire du parc informatique ;
- prets et restitutions ;
- alertes de retard et de depart ;
- import controle depuis l'Excel historique ;
- documents par materiel ;
- exports CSV ;
- diagnostics fonctionnels et techniques.

## Installation

1. Sauvegarder la base GRR et les fichiers.
2. Copier le dossier `informatique_materiel` dans `personnalisation/modules/`.
3. Installer ou activer le module depuis l'administration des modules externes.
4. Ouvrir l'administration du module.
5. Verifier que les tables du module sont indiquees en etat `OK`.
6. Attribuer au moins un role de gestionnaire pour la recette.

Le module ne modifie aucun fichier du coeur GRR. Il utilise les hooks
`hookCompteMenu`, `hookComptePage` et `hookDemandesStatus` deja presents dans le
projet.

Pour la mise en service du MVP, suivre aussi :

- `INSTALLATION_MVP.md` pour l'installation et la mise a jour ;
- `RECETTE_MVP.md` pour la recette complete ;
- `SAUVEGARDE_RESTAURATION.md` pour la sauvegarde et la restauration ;
- `BILAN_MVP.md` pour le bilan et les suites post-MVP.

## Roles

- Lecteur : consultation et exports autorises.
- Operateur : consultation, creation de prets et restitutions.
- Gestionnaire : administration fonctionnelle du parc.
- Administrateur general GRR : tous les droits et configuration.

## Validation syntaxique sur le NAS

Adapter le chemin si necessaire :

```sh
find personnalisation/modules/informatique_materiel -type f -name '*.php' \
  -exec php -l {} \;
```

## Protocole de recette etape 1

1. Installer ou reactiver le module depuis l'administration des modules externes.
2. Ouvrir l'administration du module.
3. Verifier la version applicative `0.1.0` et la version BDD `1` pour une
   recette isolee de cette etape.
4. Verifier que les tables `*_informatique_materiel_role` et
   `*_informatique_materiel_journal` existent et utilisent InnoDB.
5. Modifier le nom affiche et enregistrer.
6. Attribuer un role lecteur, operateur ou gestionnaire a un compte de test.
7. Se connecter avec ce compte et verifier l'acces au module depuis
   "Gerer mon compte".
8. Retirer le role et verifier que l'acces est refuse.
9. Verifier que les modules `gestion_materiel`, `stock_chimique`,
   `suivi_demandes` et `boutons_perso` restent accessibles.

## Protocole de recette etape 2

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version BDD `2`.
3. Verifier que les tables suivantes existent et utilisent InnoDB :
   `*_informatique_materiel_personne`,
   `*_informatique_materiel_categorie`,
   `*_informatique_materiel_sequence`.
4. Verifier que les categories historiques sont presentes.
5. Avec un gestionnaire, creer une categorie de test.
6. Modifier cette categorie, puis l'archiver.
7. Creer une personne de test avec prenom, nom, cadre d'usage et date de
   depart.
8. Modifier cette personne, puis l'archiver.
9. Tester les exports CSV `Personnes` et `Categories`.
10. Se connecter avec un lecteur et verifier que les listes sont consultables
    mais que les formulaires d'ecriture ne sont pas disponibles.
11. Verifier dans phpMyAdmin que les lignes archivees restent presentes avec
    `actif = 0`.
12. Verifier que les autres modules restent accessibles.

## Protocole de recette etape 3

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `0.3.0` et la version BDD `3`.
3. Verifier que la table `*_informatique_materiel_item` existe et utilise
   InnoDB.
4. Avec un gestionnaire, creer un ordinateur portable sans renseigner
   l'identifiant, puis verifier l'identifiant genere.
5. Creer un accessoire sans renseigner l'identifiant, puis verifier la sequence
   du prefixe concerne.
6. Modifier les champs principaux d'un materiel : marque, numero de serie,
   code-barres, MAC, OS, annee et localisation.
7. Tester les filtres de la liste par texte, categorie et statut.
8. Ouvrir la fiche d'un materiel depuis son identifiant dans la liste.
9. Tester l'export CSV `Materiels`.
10. Archiver un materiel et verifier dans phpMyAdmin que la ligne reste
    presente avec `actif = 0` et `statut = archive`.
11. Se connecter avec un lecteur et verifier que la liste et la fiche sont
    consultables mais que les formulaires d'ecriture ne sont pas disponibles.
12. Verifier que les diagnostics de doublons restent a `OK` sur les donnees de
    test.
13. Verifier que les autres modules restent accessibles.

## Protocole de recette etape 4

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `0.4.0` et la version BDD `4`.
3. Verifier que la table `*_informatique_materiel_pret` existe et utilise
   InnoDB.
4. Avec un operateur ou un gestionnaire, creer un pret entre une personne
   active et un materiel actif.
5. Verifier que le tableau de bord indique un pret ouvert et que le materiel
   passe au statut `en_pret`.
6. Tenter de creer un second pret ouvert sur le meme materiel et verifier que
   l'action est refusee.
7. Tenter d'archiver le materiel prete et verifier que l'action est refusee.
8. Ouvrir la fiche du materiel et verifier l'historique des prets.
9. Restituer le pret avec une date de retour effective.
10. Verifier que le pret passe au statut `clos` et que le materiel repasse au
   statut `stocke`.
11. Creer un pret avec une date de fin prevue passee et verifier le diagnostic
    `Prets en retard`.
12. Tester l'export CSV `Prets`.
13. Avec un lecteur, verifier que les prets sont consultables mais que les
    actions de creation, restitution et annulation ne sont pas disponibles.
14. Avec un gestionnaire, annuler un pret de test et verifier la journalisation.
15. Verifier dans phpMyAdmin qu'aucun materiel n'a plus d'un pret ouvert.
16. Verifier que les autres modules restent accessibles.

## Protocole de recette etape 5

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `0.5.0` et la version BDD `5`.
3. Verifier que la table `*_informatique_materiel_import_log` existe et utilise
   InnoDB.
4. Preparer quatre petits CSV de test dans l'ordre : categories, personnes,
   materiels, prets.
5. Importer d'abord les categories, puis verifier la previsualisation et
   executer l'import.
6. Importer les personnes, puis les materiels.
7. Importer les prets et verifier que les references personne/materiel sont
   resolues par identifiant historique.
8. Relancer le meme CSV et verifier que les lignes deja journalisees sont
   ignorees.
9. Corriger volontairement une ligne avec une reference inconnue et verifier
   qu'elle apparait en erreur dans le journal.
10. Verifier dans phpMyAdmin les colonnes `package_hash`, `source_table`,
    `source_row` et `status`.
11. Verifier que le dossier `storage/imports/` n'est pas directement accessible
    en HTTP.
12. Avec un lecteur ou un operateur, verifier que la page d'import n'est pas
    disponible.
13. Verifier que les autres modules restent accessibles.

## Protocole de recette etape 6

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `0.6.0` et la version BDD `5`.
3. Ouvrir `Gerer mon compte > Informatique materiel > Alertes`.
4. Creer un pret ouvert avec une date de fin prevue passee et verifier
   l'alerte `Pret en retard`.
5. Creer ou modifier une personne avec une date de depart passee, puis verifier
   l'alerte `Personne partie` sur son pret ouvert.
6. Sur une copie de test, vider temporairement l'identifiant d'un materiel actif
   via phpMyAdmin et verifier l'alerte `Sans identifiant`.
7. Sur une copie de test, affecter temporairement le meme code-barres a deux
   materiels actifs via phpMyAdmin et verifier l'alerte `Code-barres duplique`.
8. Corriger les situations et verifier la disparition des alertes.
9. Verifier que le lien `hookDemandesStatus` apparait seulement quand des
   alertes sont actives.
10. Verifier que les autres modules restent accessibles.

## Protocole de recette etape 7

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `0.7.0` et la version BDD `6`.
3. Verifier que la table `*_informatique_materiel_document` existe et utilise
   InnoDB.
4. Ouvrir une fiche materiel active et deposer un PDF de test.
5. Telecharger le document avec un utilisateur autorise.
6. Tenter de deposer un fichier avec une extension interdite.
7. Tenter de deposer un fichier trop volumineux selon la configuration.
8. Tenter un acces direct a `personnalisation/modules/informatique_materiel/storage/documents/`.
9. Archiver le document avec un gestionnaire et verifier qu'il n'est plus
   telechargeable.
10. Avec un lecteur, verifier que le telechargement est possible mais que le
    depot et l'archivage ne sont pas disponibles.

## Protocole de recette etape 8

1. Relancer l'installation ou la mise a jour du module depuis les modules
   externes GRR.
2. Ouvrir l'administration du module et verifier la version applicative
   `1.0.0` et la version BDD `6`.
3. Executer la validation syntaxique PHP sur le NAS.
4. Suivre la checklist complete de `RECETTE_MVP.md`.
5. Tester la sauvegarde puis la restauration selon
   `SAUVEGARDE_RESTAURATION.md` sur une copie de recette.
6. Verifier les journaux Apache, PHP et MariaDB apres les scenarios complets.
7. Verifier que les modules `gestion_materiel`, `stock_chimique`,
   `suivi_demandes` et `boutons_perso` restent accessibles.

## Protocole de recette version 1.2.6

1. Verifier dans l'administration la version applicative `1.2.6` et la version
   BDD `8`.
2. Ouvrir une fiche personne sans pret ni conflit lie, cliquer sur
   `Supprimer` et verifier que la suppression est confirmee puis executee.
3. Ouvrir une fiche personne avec pret ou conflit lie et verifier que
   `Supprimer` est refuse avec un message explicite.
4. Ouvrir une fiche materiel sans pret, document ni conflit lie, cliquer sur
   `Supprimer` et verifier que la suppression est executee.
5. Ouvrir une fiche materiel avec dependance et verifier que la suppression est
   refusee proprement.
6. Dans `Materiels`, cliquer sur une designation et verifier l'ouverture de la
   fiche materiel.
7. Creer ou conserver au moins un conflit de pret en attente, puis ouvrir le
   module : le bandeau conflits doit apparaitre en haut de page.
8. Dans l'administration, ouvrir `Configuration`, changer les couleurs
   critique, avertissement et conflits, puis enregistrer.
9. Verifier que le diagnostic admin affiche les nouvelles couleurs et que le
   bandeau conflits reprend la couleur configuree.
10. Desactiver le bandeau conflits dans l'administration et verifier qu'il ne
    s'affiche plus, sans supprimer les conflits.
11. Ouvrir `Conflits` et verifier que les actions sont groupees en
    `Consulter`, `Decider` et `Administration`.
12. Tester une decision `Conserver l existant`, `Integrer si libre` et
    `Remplacer l existant` sur des conflits de recette.

## Protocole de recette version 1.2.5

1. Verifier dans l'administration la version applicative `1.2.5` et la version
   BDD `8`.
2. Ouvrir `Personnes` et verifier qu'une personne active sans pret ouvert
   affiche l'etat `Pas de materiel attribue`.
3. Creer un pret ouvert pour cette personne et verifier que l'etat affiche
   `Materiel attribue : 1`.
4. Mettre une date de depart passee sur une personne active et verifier l'etat
   `Parti` ainsi que le bouton `Prolonger`.
5. Cliquer sur l'identifiant personnel, le nom et le login GRR d'une personne :
   chaque lien doit ouvrir la fiche personne.
6. Depuis `Categories`, cliquer sur une designation et verifier que la page
   `Materiels` est filtree sur cette categorie.
7. Verifier que `Associations GRR / LDAP` est visible dans `Personnes` mais
   pas dans la navigation generale du module.
8. Verifier que `Administration` est visible dans la navigation du module avec
   un compte administrateur.
9. Tester le tri en cliquant sur plusieurs titres de colonnes, puis le champ
   `Filtrer ce tableau` sur `Personnes`, `Materiels`, `Prets`, `Categories` et
   `Conflits`.
10. Depuis une fiche pret ouvert, tester `Prolonger` avec une nouvelle date et
    un commentaire, puis verifier l'historique/journal.
11. Tester les suppressions sur des objets de recette sans dependance :
    personne, categorie, materiel, pret et conflit doivent etre supprimables.
12. Tester les suppressions sur des objets avec dependances : le module doit
    refuser proprement et afficher la raison.
13. Depuis `Ajouter depuis LDAP`, creer une personne et verifier que la popup
    d'edition de la personne creee s'ouvre pour completer les champs.
14. Enregistrer un formulaire en popup et verifier que la popup se ferme apres
    validation.

## Protocole de recette version 1.2.4

1. Verifier dans l'administration la version applicative `1.2.4` et la version
   BDD `8`.
2. Ouvrir `boutons_perso` et verifier que le bouton fourni
   `Mon materiel informatique` est actif.
3. Se connecter avec un compte GRR qui n'a aucun pret informatique ouvert :
   le bouton ne doit pas apparaitre sur le calendrier.
4. Associer ce compte a une personne puis creer un pret ouvert sur un materiel :
   le bouton doit apparaitre.
5. Restituer ou annuler le pret ouvert : le bouton doit disparaitre.
6. Verifier que le bouton principal `Informatique materiel` garde son
   comportement base sur les roles.

## Protocole de recette version 1.2.3

1. Sauvegarder la base avant mise a jour.
2. Relancer l'installation ou la mise a jour du module.
3. Verifier dans l'administration la version applicative `1.2.3` et la version
   BDD `8`.
4. Verifier dans phpMyAdmin que la colonne `email` existe dans
   `*_informatique_materiel_personne`.
5. Ouvrir `Personnes`, cliquer sur `Ajouter une personne`, saisir un email
   valide et enregistrer.
6. Exporter les personnes et verifier la colonne `Email`.
7. Importer un petit CSV personnes contenant la colonne `email` et verifier que
   l'email est conserve.
8. Ouvrir `Ajouter depuis LDAP`, rechercher un nom connu, puis creer une
   personne depuis un resultat LDAP.
9. Verifier que la personne creee contient prenom, nom, login et email LDAP.
10. Sur une personne existante sans email, utiliser `Associations GRR / LDAP`,
    associer un login LDAP et verifier que l'email est complete.
11. Ouvrir `Associations GRR / LDAP` depuis la navigation et verifier que la
    popup de chargement apparait avant la page.
12. Tester validation individuelle, par selection et globale.
13. Tester l'absence de `config_ldap.inc.php` sur une copie de recette :
    l'ajout depuis LDAP doit refuser proprement et le reste de la page
    personnes doit rester accessible.

## Protocole de recette version 1.2.2

1. Verifier dans l'administration la version applicative `1.2.2` et la version
   BDD `7`.
2. Ouvrir l'administration du module puis `Tester LDAP`.
3. Tester sans terme de recherche et verifier que la connexion/bind LDAP est
   signalee, ou que l'absence de configuration est affichee proprement.
4. Tester avec un nom ou login connu et verifier la liste des comptes LDAP.
5. Ouvrir `Personnes`, puis `Associations GRR / LDAP`.
6. Verifier que les personnes sans association apparaissent par defaut.
7. Pour une personne avec une seule proposition, verifier que la proposition est
   preselectionnee puis valider la ligne.
8. Pour une personne avec plusieurs propositions, choisir explicitement le
   compte puis valider la ligne.
9. Cocher plusieurs personnes et tester `Valider la selection`.
10. Tester `Toutes les personnes actives`, puis `Valider toutes les lignes` sur
    une copie de recette.
11. Verifier dans la fiche personne que `Login GRR associe` est mis a jour.
12. Verifier le journal fonctionnel avec l'evenement
    `personne_login_associe`.
13. Renommer temporairement `personnalisation/config_ldap.inc.php` sur une
    copie de recette et verifier que la page reste utilisable avec les seuls
    comptes GRR.

## Protocole de recette version 1.2.1

1. Verifier dans l'administration la version applicative `1.2.1` et la version
   BDD `7`.
2. Creer ou importer au moins un conflit de pret en attente.
3. Ouvrir `Conflits` depuis le tableau de bord ou la navigation du module.
4. Tester `Conserver` avec une justification et verifier que le conflit passe
   hors des conflits en attente sans modifier les prets.
5. Tester `Integrer si libre` sur un conflit dont le pret existant a deja ete
   cloture ou annule et verifier qu'un nouveau pret ouvert est cree.
6. Tester `Integrer si libre` quand un pret ouvert existe encore et verifier que
   l'action est refusee.
7. Tester `Remplacer` avec une justification et la confirmation `REMPLACER` :
   le pret existant doit passer en `annule`, le nouveau pret doit etre cree et
   le conflit doit passer en `remplace`.
8. Tester `Remplacer` sans confirmation exacte et verifier que l'action est
   refusee.
9. Verifier le journal fonctionnel : `conflit_pret_ignore`,
   `conflit_pret_importe`, `conflit_pret_remplace` selon les actions testees.
10. Verifier que les conflits resolus restent visibles avec `Voir tout`.

## Protocole de recette version 1.2.0

1. Sauvegarder la base avant mise a jour.
2. Relancer l'installation ou la mise a jour du module.
3. Verifier dans l'administration la version applicative `1.2.0` et la version
   BDD `7`.
4. Verifier dans le diagnostic que la table `Conflits de prets` existe.
5. Importer le fichier CSV des doublons de prets avec le type `Prets`.
6. Verifier que le resultat d'import indique des `conflits` et pas une creation
   de prets ouverts pour ces lignes.
7. Ouvrir la page Import CSV et verifier que les lignes apparaissent dans
   `Conflits de prets en attente`.
8. Verifier dans la table `*_informatique_materiel_pret` que le pret ouvert
   existant n'a pas ete modifie.
9. Verifier dans la table `*_informatique_materiel_pret_conflit` que la nouvelle
   entree contient le `pret_existant_id`, la personne, le materiel, les dates,
   le commentaire et le motif.
10. Rejouer le meme fichier et verifier que les lignes deja journalisees ne
    creent pas de doublons.
11. Tester la remise a zero du module sur une copie de recette et verifier que
    les conflits sont supprimes avec les autres donnees metier.

## Protocole de recette version 1.1.1

1. Relancer l'installation ou la mise a jour du module.
2. Verifier dans l'administration la version applicative `1.1.1` et la version
   BDD `6`.
3. Creer ou conserver un pret ouvert avec `date fin prevue` passee et verifier
   l'alerte `Pret en retard`.
4. Cliquer sur `Prolonger`, saisir une date future et un commentaire, puis
   enregistrer.
5. Verifier que l'alerte disparait, que le pret contient la nouvelle date de
   fin prevue et que le commentaire est ajoute dans l'historique du pret.
6. Verifier dans le journal fonctionnel qu'un evenement
   `alerte_pret_prolongee` est present.
7. Creer ou conserver une personne avec date de depart passee et pret ouvert,
   puis verifier l'alerte `Personne partie`.
8. Cliquer sur `Prolonger`, saisir une nouvelle date de depart future et un
   commentaire, puis enregistrer.
9. Verifier que l'alerte disparait, que la personne contient la nouvelle date
   de depart et que le commentaire est ajoute aux notes.
10. Verifier dans le journal fonctionnel qu'un evenement
    `alerte_depart_prolongee` est present.
11. Tester une date passee ou un commentaire vide et verifier que l'action est
    refusee.
12. Verifier que les autres alertes gardent seulement le bouton `Ouvrir`.

## Protocole de recette version 1.1.0

1. Relancer l'installation ou la mise a jour du module.
2. Verifier dans l'administration la version applicative `1.1.0` et la version
   BDD `6`.
3. Executer le lint PHP sur le NAS.
4. Ouvrir `Personnes`, creer ou modifier une personne, verifier que le
   formulaire s'ouvre en popup.
5. Ouvrir `Materiels` et `Prets`, verifier que les filtres s'ouvrent en
   popup et que la remise a zero des filtres fonctionne.
6. Verifier que le champ `Login GRR associe` propose `Non associe`, puis les
   comptes GRR actifs trouves par nom/prenom.
7. Si `personnalisation/config_ldap.inc.php` existe et que PHP LDAP est actif,
   verifier que des identifiants LDAP sont proposes apres les comptes GRR.
8. Renommer ou supprimer temporairement `config_ldap.inc.php` sur une copie de
   recette et verifier que la page personnes reste accessible sans recherche
   LDAP.
9. Verifier que le libelle `Identifiant personnel` apparait dans la page
   personnes et dans l'export CSV personnes.
10. Sur une base de test vide, verifier que les categories historiques ne sont
   plus creees automatiquement.
11. Dans l'administration, tester la remise a zero sur une copie de recette
    avec la confirmation `REMISE A ZERO`.
12. Verifier que les documents et imports stockes sont supprimes sauf les
    fichiers de protection `.htaccess`.
13. Ouvrir `compte.php?pc=informatique_materiel&view=user` avec un compte GRR
    associe et verifier l'affichage du materiel.
14. Ouvrir `boutons_perso`, verifier les deux boutons fournis par le module et
    leur filtrage selon le role.
15. Verifier que les autres modules restent accessibles.
