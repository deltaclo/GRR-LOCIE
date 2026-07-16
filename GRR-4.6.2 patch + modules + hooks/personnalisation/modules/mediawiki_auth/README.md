# Module GRR `mediawiki_auth`

## Objectif

Ce module valide une session GRR puis émet une preuve d’accès signée destinée
à l’extension MediaWiki `GrrAccessGate`.

La preuve :

- ne contient aucune identité ;
- est signée avec HMAC-SHA-256 ;
- est liée au navigateur par une empreinte du `User-Agent` ;
- expire par défaut après 120 secondes ;
- utilise un cookie `Secure`, `HttpOnly` et `SameSite=Strict` ;
- est limitée au chemin MediaWiki configuré.

Valeurs recommandées :

| Environnement | Chemin | Cookie | Audience |
|---|---|---|---|
| Test | `/test/mediawiki/` | `GRRMediaWikiAccessTest` | `mediawiki-test` |
| Production | `/mediawiki/` | `GRRMediaWikiAccess` | `mediawiki-production` |

Les secrets de test et de production doivent être différents.

Test autonome des valeurs d'environnement :

```sh
php84 personnalisation/modules/mediawiki_auth/tests/run.php
```

Le lecteur demeure anonyme dans MediaWiki. Les comptes locaux MediaWiki restent
nécessaires pour l’édition et l’administration.

## Isolation de l’environnement de test

GRR de production et GRR de test étant servis par le même hôte, leurs sessions
PHP doivent avoir des noms distincts. Si les deux instances utilisent le
cookie `GRR` sur un chemin commun, la copie de test doit utiliser par exemple
`$gSessionName = "GRRTEST"`.

## Fichiers

- `authorize.php` : validation initiale et émission de la preuve ;
- `refresh.php` : renouvellement silencieux avec réponse HTTP 204 ;
- `controleur.php` : suppression de la preuve lors du logout GRR ;
- `admin.php` : configuration réservée aux administrateurs GRR ;
- `lib/AccessCookie.php` : création et suppression du cookie ;
- `lib/AccessToken.php` : émission et vérification HMAC ;
- `lib/Config.php` : lecture et validation des réglages ;
- `lib/UrlPolicy.php` : validation stricte de l’URL de retour ;
- `lib/bootstrap.php` : chargement autonome de GRR.

## Déconnexion et renouvellement

Le contrôleur de déconnexion GRR appelle le hook `hookBeforeLogout` avant
`grr_closeSession()`. Le module supprime alors immédiatement le cookie
MediaWiki.

Tant qu’une page MediaWiki reste ouverte, l’extension appelle périodiquement
`refresh.php`. Avec une session GRR valide, la preuve est renouvelée. Sans
session valide, le cookie est supprimé et le point d’entrée répond HTTP 401.

## Retour arrière

Désactiver le module depuis l’administration GRR ou décocher
`Passerelle activée`. Le seul changement du cœur GRR est l’appel du hook
`hookBeforeLogout` dans le contrôleur de déconnexion.
