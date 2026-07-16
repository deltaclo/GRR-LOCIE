# Recette NAS — étape 5

Cette recette concerne uniquement les copies :

- GRR : `/test/grr/`, base `grr2` ;
- MediaWiki : `/test/mediawiki/`, base `mediawiki2`.

## 1. Précaution avant déploiement

Conserver une copie des fichiers actuellement fonctionnels :

- `test/grr/reservation/controleurs/deconnexion.php` ;
- `test/grr/personnalisation/modules/mediawiki_auth/` ;
- `test/mediawiki/LocalSettings.php` ;
- `test/mediawiki/.htaccess` ;
- `test/mediawiki/images/.htaccess` ;
- `test/mediawiki/extensions/GrrAccessGate/`.

Le `LocalSettings.php` du workspace contient volontairement un secret vide.
La valeur réelle doit être placée uniquement dans :

```text
test/mediawiki/LocalSettings.grr-secret.php
```

Utiliser `LocalSettings.grr-secret.php.example` comme modèle. Le fichier réel
est refusé par Apache et ne doit pas être recopié dans le workspace.

Pendant le déploiement, placer temporairement :

```php
$wgGrrAccessGateEnabled = false;
```

## 2. Fichiers à déployer

Dans GRR :

- le module `personnalisation/modules/mediawiki_auth/` version 0.2.0 ;
- `reservation/controleurs/deconnexion.php`.

Dans MediaWiki :

- `extensions/GrrAccessGate/` ;
- `grr_rest.php` ;
- `grr_img_auth.php` ;
- `.htaccess` ;
- `images/.htaccess`.

Fusionner dans `LocalSettings.php` :

```php
$wgGrrAccessGateRefreshUrl =
	'/test/grr/personnalisation/modules/mediawiki_auth/refresh.php';
$wgGrrAccessGateRefreshInterval = 60;

$wgRestPath = "$wgScriptPath/grr_rest.php";
$wgUploadPath = "$wgScriptPath/grr_img_auth.php";
$wgImgAuthPath = "$wgScriptPath/grr_img_auth.php";
```

Le secret ayant été précédemment stocké dans le fichier principal, le
régénérer depuis l’administration du module GRR. Créer ensuite directement sur
le NAS :

```php
<?php
$wgGrrAccessGateSecret = 'NOUVEAU_SECRET_GENERE_DANS_GRR';
```

Enfin, réactiver dans `LocalSettings.php` :

```php
$wgGrrAccessGateEnabled = true;
```

## 3. Vérification de syntaxe

Adapter `php84` si le NAS utilise un autre nom :

```sh
find personnalisation/modules/mediawiki_auth -name '*.php' -exec php84 -l {} \;
php84 -l reservation/controleurs/deconnexion.php

php84 -l extensions/GrrAccessGate/includes/AccessGate.php
php84 -l extensions/GrrAccessGate/includes/EntryPointGate.php
php84 -l extensions/GrrAccessGate/includes/Hooks.php
php84 -l grr_rest.php
php84 -l grr_img_auth.php
php84 maintenance/run.php version
php84 extensions/GrrAccessGate/tests/run.php
```

## 4. Déconnexion GRR

1. Ouvrir MediaWiki après authentification GRR.
2. Vérifier la présence du cookie `GRRMediaWikiAccessTest`.
3. Se déconnecter explicitement de `/test/grr/`.
4. Vérifier que le cookie `GRRMediaWikiAccessTest` a disparu.
5. Recharger une page MediaWiki.
6. Vérifier la redirection immédiate vers la connexion GRR.

Une page déjà affichée ne peut pas être retirée de l’écran, mais toute nouvelle
requête MediaWiki doit être refusée.

## 5. Renouvellement silencieux

Avec une session GRR valide, ouvrir :

```text
/test/grr/personnalisation/modules/mediawiki_auth/refresh.php
```

La réponse attendue est HTTP 204 et le cookie reçoit une nouvelle expiration.

Après déconnexion GRR, la même URL doit répondre HTTP 401 et supprimer le
cookie.

Laisser ensuite une édition wikicode ouverte plus de 120 secondes, enregistrer
une petite modification et vérifier son apparition dans l’historique.

## 6. REST et VisualEditor

Avec une preuve valide :

```text
/test/mediawiki/grr_rest.php/v1/search/page?q=Accueil&limit=1
```

La route doit répondre normalement.

Sans le cookie de preuve :

- `grr_rest.php` doit répondre HTTP 403 avec du JSON ;
- `rest.php` doit répondre HTTP 403 par Apache ;
- l’ouverture normale d’une page doit rediriger vers GRR.

Tester ensuite VisualEditor :

1. ouvrir une page ;
2. démarrer VisualEditor ;
3. enregistrer une petite modification ;
4. vérifier l’historique ;
5. recommencer après avoir laissé l’éditeur ouvert plus de 120 secondes.

## 7. Fichiers et miniatures

Choisir une image existante et relever son URL.

Purger d’abord la page de test avec `?action=purge`, car le cache du parseur
peut encore contenir l’ancienne URL sous `images/`.

Après rechargement, l’URL générée doit commencer par :

```text
/test/mediawiki/grr_img_auth.php/
```

Contrôles attendus :

- URL `grr_img_auth.php/...` avec preuve valide : HTTP 200 ;
- même URL sans preuve : HTTP 403 ;
- ancienne URL directe sous `/test/mediawiki/images/` : HTTP 403 ;
- ancienne URL `/test/mediawiki/img_auth.php/...` : HTTP 403.
- `/test/mediawiki/thumb.php` et `thumb_handler.php` : HTTP 403.
- les réponses de `grr_img_auth.php` doivent contenir
  `Cache-Control: private, no-store` et `Vary: Cookie`.

Si une URL directe sous `images/` retourne encore HTTP 200, interrompre la
recette : Apache n’applique pas le `.htaccess`. Il faudra reporter les règles
dans la configuration du VirtualHost DSM avant validation.

## 8. Journaux

Contrôler les journaux Apache, PHP et MediaWiki. Le journal
`GrrAccessGate` peut contenir un motif court comme `missing-cookie` ou
`expired`, mais ne doit contenir ni secret ni valeur de cookie.

## Retour arrière

1. Passer `$wgGrrAccessGateEnabled = false`.
2. Restaurer :

```php
$wgRestPath = "$wgScriptPath/rest.php";
$wgUploadPath = "$wgScriptPath/images";
$wgImgAuthPath = false;
```

3. Restaurer les deux `.htaccess` sauvegardés.
4. Restaurer l’ancien contrôleur de déconnexion GRR si nécessaire.
