# Roadmap - Boutons des modules selon les droits

## Objectif

Permettre au module `boutons_perso` d'afficher et d'organiser, avec les boutons
personnalises existants, les boutons d'acces aux modules suivants :

- Gestion materiel ;
- Stock chimique ;
- Suivi des demandes.

Un bouton de module doit etre visible uniquement lorsque :

- le module concerne est installe et actif ;
- son acces depuis `Gerer mon compte` est actif, lorsqu'il dispose de ce reglage ;
- l'utilisateur connecte possede effectivement le droit d'acceder au module ;
- le bouton de module est active dans l'administration de `boutons_perso`.

Le masquage du bouton ne remplace jamais les controles d'autorisation realises
par les pages des modules.

## Principes techniques

- Conserver les regles de droits dans chaque module metier.
- Ne pas recopier ces regles dans `boutons_perso`.
- Utiliser des identifiants stables pour les boutons fournis par les modules.
- Conserver les boutons personnalises et leur ordre actuel pendant la migration.
- Realiser des changements progressifs, testables et sans modification du coeur
  GRR supplementaire.

## Etape 1 - Centraliser les droits de Gestion materiel

Etat : terminee et validee sur le NAS le 22/06/2026.

Creer `gestion_materiel/lib/Rights.php` afin d'exposer les controles deja
utilises par le module :

- acces general au module ;
- gestion complete du module ;
- administration generale ;
- consultation d'un materiel ;
- consultation d'un groupe.

Le renderer et le telechargement securise utiliseront cette classe sans changer
les regles existantes.

## Etape 2 - Definir les boutons fournis par les modules

Etat : terminee et validee sur le NAS le 22/06/2026.

Chaque module fournira une definition de navigation contenant au minimum :

- un identifiant stable ;
- son libelle configure ;
- son URL ;
- son etat d'activation ;
- son controle d'acces pour l'utilisateur courant.

Le contrat retenu utilise les champs :

- `id` ;
- `module` ;
- `label` ;
- `url` ;
- `enabled` ;
- `can_access`.

Identifiants retenus :

- `module:gestion_materiel` ;
- `module:stock_chimique` ;
- `module:suivi_demandes`.

## Etape 3 - Etendre le stockage de Boutons perso

Etat : terminee et validee sur le NAS le 22/06/2026.

Faire evoluer la table `*_boutons_perso_button` pour distinguer :

- les boutons personnalises ;
- les boutons fournis par un module.

Les boutons existants seront conserves comme boutons personnalises. Les boutons
de module utiliseront le meme ordre d'affichage que les boutons personnalises.

La migration BDD 4 ajoute :

- `source_type` avec `custom` par defaut ;
- `source_key`, nullable pour les boutons personnalises ;
- un index unique sur `source_type` et `source_key` ;
- trois lignes systeme actives, encore masquees par les methodes historiques.

## Etape 4 - Administration et organisation unifiees

Etat : terminee et validee sur le NAS le 22/06/2026.

Afficher une liste unique dans l'administration de `boutons_perso`.

Pour un bouton de module, permettre de configurer :

- l'activation globale ;
- l'ordre ;
- le style et les couleurs ;
- le mode d'ouverture.

Le libelle et l'URL seront fournis par le module. Un bouton de module ne pourra
pas etre supprime comme un bouton personnalise.

La version 1.2.0 ajoute :

- un registre chargeant les trois fournisseurs de navigation ;
- une liste administrative unique ;
- un formulaire protege pour les options des boutons modules ;
- des statuts de disponibilite et un diagnostic SQL etendu ;
- aucune modification du rendu calendrier avant l'etape 5.

## Etape 5 - Filtrer les boutons lors de l'affichage

Etat : terminee et validee sur le NAS le 22/06/2026.

Lors du rendu au-dessus du calendrier :

1. charger tous les boutons actifs dans l'ordre configure ;
2. conserver les boutons personnalises actifs ;
3. resoudre chaque bouton de module ;
4. verifier l'activation du module ;
5. verifier les droits de l'utilisateur courant ;
6. masquer le bloc complet si aucun bouton n'est visible.

La version 1.3.0 applique cette chaine de filtrage dans le renderer tout en
conservant les controles d'autorisation serveur de chaque module.

## Etape 6 - Recette et documentation

Etat : protocole final redige le 22/06/2026, en attente de recette sur le NAS.

Executer une recette sur le NAS avec les profils suivants :

- utilisateur sans aucun droit ;
- utilisateur ayant acces a un seul module ;
- utilisateur ayant acces a deux modules ;
- utilisateur ayant acces aux trois modules ;
- administrateur general ;
- gestionnaire de Gestion materiel ;
- utilisateur affecte a un materiel actif ;
- lecteur, operateur et gestionnaire de Stock chimique ;
- utilisateur active puis desactive dans Suivi des demandes.

Verifier egalement :

- la desactivation d'un bouton de module ;
- la desactivation du module externe ;
- l'ordre melangeant boutons de module et boutons personnalises ;
- le refus d'un acces direct non autorise ;
- l'affichage sur les vues jour, semaine et mois.

Le protocole complet, les resultats attendus et le proces-verbal de validation
sont regroupes dans `RECETTE_NAS.md`.

## Etape 7 - Harmonisation interface admin

Etat : a tester sur le NAS.

Uniformiser l'administration `boutons_perso` avec les modules recents :

- boutons d'action en haut de page ;
- formulaires de configuration, ajout, modification, configuration module et
  suppression en popups ;
- reouverture automatique de la popup bouton en edition ou apres erreur ;
- aucun changement SQL, version applicative `1.3.1`, BDD `4`.

## Regle de validation

Apres chaque etape :

1. produire une synthese des changements ;
2. fournir le protocole de test NAS Synology DSM 7 ;
3. attendre la validation avant de commencer l'etape suivante.
