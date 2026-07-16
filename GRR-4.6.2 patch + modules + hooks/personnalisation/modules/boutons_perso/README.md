# Boutons perso

Module local GRR permettant d'afficher des boutons personnalises au-dessus du calendrier lateral et de piloter les boutons modules de `Gerer mon compte`.

La recette complete des boutons modules et des droits est disponible dans
`RECETTE_NAS.md`.

## Version 0.1.0

- Declaration du module externe.
- Ajout du hook `hookBoutonsPersoCalendrier` cote GRR.
- Affichage d'un bouton statique de test au-dessus du mini-calendrier gauche.
- Aucune table SQL metier pour cette premiere etape.

## Version 0.2.0

- Ajout de la table `*_boutons_perso_button`.
- Ajout d'une administration simple reservee aux administrateurs generaux.
- Acces integre via `compte.php?pc=boutons_perso`.
- La page autonome reste accessible via `personnalisation/modules/boutons_perso/admin.php`.
- Creation, modification, suppression et activation/desactivation de boutons.
- Affichage des boutons actifs au-dessus du calendrier lateral.
- Modes d'ouverture disponibles en V0.2 : fenetre courante, nouvel onglet ou nouvelle fenetre simple.
- Validation minimale des URLs : relatives a GRR ou `http://` / `https://`.

## Version 1.0.0

- Configuration generale du module :
  - activation/desactivation ;
  - nom affiche ;
  - affichage ou non du titre dans le bloc calendrier ;
  - couleurs du bloc calendrier.
- Configuration avancee des boutons :
  - libelle ;
  - URL relative, absolue sur le meme site, ou externe `http://` / `https://` ;
  - ordre d'affichage ;
  - activation/desactivation ;
  - infobulle ;
  - message de confirmation facultatif ;
  - ouverture dans la fenetre courante, un nouvel onglet ou une nouvelle fenetre ;
  - largeur/hauteur de la nouvelle fenetre ;
  - nom technique de la nouvelle fenetre ;
  - style Bootstrap ou couleurs personnalisees.
- Migration automatique de la table V0.2 vers le schema V1.0.0.
- Diagnostic minimal de la table SQL du module.
- Page integree dans GRR et page autonome conservees.
- README de test NAS.

## Version 1.1.0

- Migration BDD 4 de la table `*_boutons_perso_button`.
- Ajout de `source_type` pour distinguer les boutons personnalises des boutons de module.
- Ajout de `source_key` pour identifier durablement un bouton fourni par un module.
- Ajout d'un index unique evitant la creation en double d'un bouton de module.
- Conservation automatique des boutons existants avec le type `custom`.
- Creation rejouable des lignes systeme pour Gestion materiel, Stock chimique et Suivi des demandes.
- Les methodes historiques et l'administration actuelle continuent a ne manipuler que les boutons personnalises.
- Aucun bouton de module n'est encore affiche dans le calendrier avant les etapes suivantes.

## Version 1.2.0

- Ajout de `lib/ModuleRegistry.php` pour charger les definitions fournies par les trois modules.
- Liste administrative unique melangeant boutons personnalises et boutons de module.
- Affichage du type, de l'etat de la source, de l'ordre, du style et de l'activation.
- Configuration des boutons modules : activation, ordre, style, couleurs, infobulle, confirmation et mode d'ouverture.
- Libelle et URL des boutons modules fournis en lecture seule par leur module.
- Suppression des boutons modules interdite dans l'interface et cote serveur.
- Diagnostic etendu aux colonnes `source_type`, `source_key` et a l'index `source_button`.
- Aucun changement du calendrier avant l'etape 5.

## Version 1.3.0

- Affichage des boutons modules dans le calendrier lateral.
- Conservation de l'ordre commun avec les boutons personnalises.
- Filtrage de chaque bouton module selon :
  - l'activation du bouton dans `boutons_perso` ;
  - la disponibilite de son fournisseur ;
  - l'activation du module externe GRR ;
  - l'activation fonctionnelle du module ;
  - le droit d'acces de l'utilisateur courant.
- Libelle et URL toujours recuperes dynamiquement depuis le module concerne.
- Masquage complet du bloc si aucun bouton n'est visible.
- Conservation des controles d'autorisation serveur propres a chaque module.

## Version 1.3.1

- Uniformisation de l'administration avec les modules recents.
- Passage des formulaires `Configuration`, `Ajouter / Modifier / Configurer`
  et `Supprimer` en popups.
- Reouverture automatique du formulaire bouton en cas d'edition ou d'erreur de
  validation.
- Aucun changement de schema SQL : la version BDD reste `4`.

## Version 1.4.0

- Ajout d'une option globale pour gerer les boutons modules du menu gauche de
  `Gerer mon compte` depuis `boutons_perso`.
- Les liens natifs `Mon compte`, `Mes connexions` et `Mes reservations` restent
  codes en dur dans le template GRR.
- Seuls les boutons ajoutes par les modules via `hookCompteMenu` sont geres par
  ce mode centralise.
- Ajout de `account_menu_active` et `account_position_order` dans
  `*_boutons_perso_button`.
- Ajout de fournisseurs de navigation pour `stagiaire` et `boutons_perso`.
- Le mode centralise est desactive par defaut pour conserver le comportement
  historique apres installation ou mise a jour.

## Version 1.4.1

- Affichage de l'administration `boutons_perso` en largeur fluide dans
  `Gerer mon compte`.
- Adaptation responsive de la liste des boutons : tableau large sur desktop,
  lignes empilees sur mobile.
- Popups d'administration plus larges sur desktop et ajustees aux petits
  ecrans.
- Boutons du calendrier lateral et du menu compte forces sur la largeur
  disponible, avec retour a la ligne des libelles longs.
- Aucun changement de schema SQL : la version BDD reste `5`.

## Version 1.4.2

- Ajout de nouveaux styles de boutons : gris fonce, noir, gris clair, violet,
  bordeaux, bleu nuit, turquoise et olive.
- Ajout d'un rendu de secours pour garantir ces couleurs dans l'administration,
  le calendrier lateral et le menu gauche `Gerer mon compte`.
- Aucun changement de schema SQL : la version BDD reste `5`.

## Test attendu

1. Activer le module dans l'administration des modules externes.
2. Lancer l'installation/mise a jour du module pour creer la table SQL.
3. Ouvrir `compte.php?pc=boutons_perso` avec un administrateur general.
4. Creer un bouton actif avec l'URL relative `app.php?p=jour`.
5. Ouvrir une vue planning jour, semaine ou mois.
6. Verifier que le bouton apparait au-dessus du mini-calendrier gauche.
7. Tester les trois modes d'ouverture : fenetre courante, nouvel onglet, nouvelle fenetre.
8. Desactiver le bouton et verifier qu'il ne s'affiche plus.
9. Modifier les couleurs du bloc et verifier le rendu au-dessus du calendrier.
10. Tester un style personnalise de bouton.
11. Tester un message de confirmation.
12. Verifier que la page autonome `personnalisation/modules/boutons_perso/admin.php` fonctionne toujours.

## Test de l'administration en popups

1. Ouvrir `compte.php?pc=boutons_perso` avec un administrateur general.
2. Cliquer sur `Configuration`, modifier une couleur du bloc, enregistrer et
   verifier que le message de succes apparait.
3. Cliquer sur `Ajouter un bouton`, creer un bouton de test puis verifier son
   apparition dans la liste.
4. Cliquer sur `Modifier` sur un bouton personnalise et verifier que le
   formulaire s'ouvre en popup avec les valeurs existantes.
5. Cliquer sur `Configurer` sur un bouton de module et verifier que le libelle
   et l'URL sont en lecture seule.
6. Cliquer sur `Supprimer` sur un bouton personnalise de test et verifier que
   la confirmation est affichee en popup.
7. Verifier qu'aucun formulaire de configuration ou d'edition n'est affiche en
   pleine page hors popup.
8. Tester la fermeture par la croix, le fond de popup et la touche `Escape`.

## Test du menu `Gerer mon compte`

1. Ouvrir `compte.php` avec un administrateur general.
2. Verifier que le menu gauche historique s'affiche quand l'option
   `Gerer les boutons modules de Gerer mon compte` est desactivee.
3. Ouvrir `compte.php?pc=boutons_perso`, puis `Configuration`.
4. Activer `Gerer les boutons modules de Gerer mon compte` et enregistrer.
5. Verifier que `Mon compte`, `Mes connexions` et `Mes reservations` restent
   visibles.
6. Configurer un bouton de module, cocher ou decocher
   `Afficher dans Gerer mon compte`, puis enregistrer.
7. Verifier que le bouton apparait ou disparait du menu gauche sans impacter le
   bloc des boutons au-dessus du calendrier.
8. Modifier `Ordre menu compte` sur deux boutons modules et verifier l'ordre
   dans `Gerer mon compte`.
9. Desactiver l'option globale et verifier que le rendu historique par
   `hookCompteMenu` est de nouveau utilise.

## Test responsive

1. Ouvrir `compte.php?pc=boutons_perso` sur un ecran desktop.
2. Verifier que l'administration occupe toute la largeur disponible de la zone
   de contenu.
3. Reduire progressivement la largeur du navigateur.
4. Verifier que la liste des boutons reste lisible et passe en lignes empilees
   sur mobile.
5. Ouvrir les popups `Configuration`, `Ajouter un bouton` et `Configurer` sur
   desktop puis mobile.
6. Verifier que les boutons du calendrier lateral et du menu compte utilisent
   toute la largeur disponible et que les libelles longs reviennent a la ligne.

## Test de migration BDD 4 / 5

1. Sauvegarder la table `*_boutons_perso_button`.
2. Lancer la mise a jour du module depuis l'administration des modules externes.
3. Verifier dans phpMyAdmin la presence de `source_type`, `source_key`,
   `account_menu_active` et `account_position_order`.
4. Verifier la presence de l'index unique `source_button`.
5. Verifier que tous les anciens boutons ont `source_type = custom` et `source_key = NULL`.
6. Verifier la presence d'une seule ligne pour chacune des cles :
   - `module:gestion_materiel` ;
   - `module:stock_chimique` ;
   - `module:suivi_demandes` ;
   - `module:informatique_materiel` ;
   - `module:informatique_materiel_user` ;
   - `module:stagiaire` ;
   - `module:boutons_perso`.
7. Relancer la mise a jour et verifier que les lignes systeme ne sont pas dupliquees.
8. Verifier que `module:informatique_materiel_user` a `account_menu_active = 0`.
9. Verifier que le calendrier affiche exactement les memes boutons qu'avant la migration.

Requete de controle, en remplacant le prefixe si necessaire :

```sql
SELECT id, source_type, source_key, label, position_order, active,
       account_menu_active, account_position_order
FROM grr_boutons_perso_button
ORDER BY position_order, id;
```

## Test de l'administration unifiee

1. Ouvrir `compte.php?pc=boutons_perso` avec un administrateur general.
2. Verifier que la liste contient les boutons personnalises et les boutons modules connus.
3. Verifier les badges `Personnalise` et `Module`.
4. Verifier que chaque bouton module indique son etat : disponible, module desactive ou fournisseur indisponible.
5. Cliquer sur `Configurer` pour chaque bouton module.
6. Verifier que le libelle et l'URL sont en lecture seule.
7. Modifier l'ordre, le style, l'infobulle et le mode d'ouverture, puis enregistrer.
8. Desactiver un bouton module et verifier que la valeur `active` passe a `0` dans phpMyAdmin.
9. Reactiver le bouton et verifier que la valeur repasse a `1`.
10. Verifier qu'aucune action `Supprimer` n'est proposee pour un bouton module.
11. Verifier que les boutons personnalises restent modifiables et supprimables.
12. Avec la version 1.3.0, verifier que le calendrier affiche les boutons modules autorises.

## Test du filtrage des boutons modules

1. Configurer des ordres differents pour les boutons modules et au moins deux boutons personnalises.
2. Verifier que l'ordre du calendrier correspond exactement a l'ordre administratif.
3. Avec un utilisateur sans droit sur les modules, verifier qu'aucun bouton module n'apparait.
4. Avec un utilisateur affecte a un materiel actif, verifier que `Gestion materiel` apparait.
5. Avec un gestionnaire Gestion materiel, verifier que `Gestion materiel` apparait.
6. Avec un lecteur, operateur ou gestionnaire Stock chimique, verifier que `Stock chimique` apparait.
7. Avec un utilisateur active dans Suivi des demandes, verifier que `Suivi des demandes` apparait.
8. Desactiver ce compte dans Suivi des demandes et verifier que son bouton disparait.
9. Desactiver individuellement un bouton dans `boutons_perso` et verifier sa disparition.
10. Desactiver fonctionnellement un module et verifier la disparition de son bouton.
11. Desactiver le module externe GRR et verifier la disparition de son bouton.
12. Modifier le nom affiche d'un module et verifier que le bouton reprend ce nom.
13. Tester les modes fenetre courante, nouvel onglet et nouvelle fenetre.
14. Tester un message de confirmation et un style personnalise sur un bouton module.
15. Sans bouton personnalise actif et sans droit sur aucun module, verifier que le bloc complet disparait.
16. Tenter l'acces direct a un module non autorise et verifier que son controle serveur refuse toujours l'acces.

## Installation / mise a jour

1. Copier le dossier `boutons_perso` dans `personnalisation/modules/`.
2. Depuis l'administration GRR des modules externes, lancer l'installation ou la mise a jour du module.
3. Ouvrir `compte.php?pc=boutons_perso` avec un administrateur general pour configurer les boutons.

## Validation finale

Executer `RECETTE_NAS.md` sur le serveur Synology avant la mise en production.
Le document couvre :

- l'administration unifiee ;
- la migration SQL ;
- les profils et combinaisons de droits ;
- les activations des boutons et modules ;
- l'ordre et les modes d'ouverture ;
- les acces directs et les journaux PHP/Apache.

## Notes de securite

- Les URLs `javascript:` et `data:` sont refusees.
- Les schemes autres que `http://` et `https://` sont refuses.
- Les URLs protocol-relative commencant par `//` sont refusees.
- Les chemins contenant `..` sont refuses.
- Les pages d'administration sont reservees aux administrateurs generaux GRR.
