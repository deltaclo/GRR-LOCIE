# Module GRR - Feeds ICS par ressource

Ce module ajoute un flux iCalendar par ressource GRR.

## Installation

1. Importer l'archive ZIP depuis l'administration GRR, section modules externes.
2. Activer le module `ics_ressources`.
3. Ouvrir l'administration d'une ressource.
4. Configurer le bloc `Feed ICS` ajoute en bas du formulaire de la ressource.

## Fonctionnement

Chaque ressource dispose d'une URL ICS protegee par token.

Les options de la v1 sont :

- activation globale des feeds ;
- confidentialite : occupe uniquement, titre, ou complet ;
- fenetre en jours passes/futurs ;
- inclusion ou exclusion des reservations moderees ;
- inclusion ou exclusion des reservations optionnelles ;
- activation ou desactivation par ressource ;
- regeneration globale des tokens.

La configuration est integree a la page d'edition d'une ressource GRR afin de reutiliser la session et les droits d'administration deja valides par GRR.

## Compatibilite ICS

Le flux genere :

- des dates UTC ;
- des UID stables par reservation ;
- des lignes pliees sans couper les caracteres UTF-8 ;
- un echappement iCalendar des virgules, points-virgules, antislashs et retours ligne ;
- un statut `TENTATIVE` pour les reservations optionnelles ou moderees incluses dans le flux.

## Securite

Les feeds ne demandent pas de session GRR, car les clients calendrier ne savent generalement pas s'authentifier.
L'acces repose sur un token signe par ressource. Regenerer le secret invalide tous les anciens liens.
