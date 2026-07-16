# Recette NAS — étape 4

Environnement visé :

- MediaWiki : `https://intranet-locie.local.univ-savoie.fr/test/mediawiki/`
- GRR : `https://intranet-locie.local.univ-savoie.fr/test/grr/`
- PHP 8.4, Apache 2.4, MariaDB 10

## 1. Déployer sans activer

Copier le dossier `GrrAccessGate` dans :

```text
test/mediawiki/extensions/GrrAccessGate/
```

Déployer ensuite les lignes de configuration préparées dans
`LocalSettings.php`. À ce stade, conserver :

```php
$wgGrrAccessGateEnabled = false;
$wgGrrAccessGateSecret = '';
```

Vérifier que MediaWiki reste accessible comme à la fin de l’étape 3.

## 2. Vérifications techniques

Depuis la racine de la copie MediaWiki :

```sh
php maintenance/run.php version
php extensions/GrrAccessGate/tests/run.php
```

La commande `version` charge `LocalSettings.php` et les manifestes des
extensions. Le test autonome doit afficher :

```text
OK - tests GrrAccessGate réussis.
```

## 3. Copier le secret sans l’exposer

Dans l’administration du module `mediawiki_auth` de la copie GRR, afficher le
secret partagé. Copier `LocalSettings.grr-secret.php.example` sous le nom
`LocalSettings.grr-secret.php`, directement sur le NAS, puis y placer le
secret :

```php
<?php
$wgGrrAccessGateSecret = 'SECRET_COPIE_DIRECTEMENT_DEPUIS_GRR';
```

Activer ensuite dans `LocalSettings.php` :

```php
$wgGrrAccessGateEnabled = true;
```

Vérifier aussi que les valeurs suivantes correspondent exactement au module
GRR :

```php
$wgGrrAccessGateCookieName = 'GRRMediaWikiAccessTest';
$wgGrrAccessGateAudience = 'mediawiki-test';
```

Ne modifier ni la production `/mediawiki/`, ni la base `mediawiki`.

## 4. Scénarios navigateur

Utiliser d’abord une fenêtre privée.

1. Fermer toute session GRR de test.
2. Demander une page profonde, par exemple :
   `/test/mediawiki/index.php?title=Special:RecentChanges`.
3. Vérifier la redirection vers la connexion de `/test/grr/`.
4. Se connecter à GRR.
5. Vérifier le retour exact vers la page demandée.
6. Vérifier que MediaWiki affiche toujours l’utilisateur comme anonyme.
7. Naviguer vers plusieurs pages et vérifier l’absence de boucle.
8. Ouvrir `Special:UserLogin`, se connecter avec `Administrateur`, puis
   vérifier l’accès à `Special:UserRights`.
9. Se déconnecter du compte local MediaWiki : la lecture doit rester possible
   tant que la preuve GRR est valide.
10. Enregistrer une petite modification en wikicode, dans la durée de validité
    de la preuve, puis vérifier qu’elle apparaît dans l’historique.

## 5. Contrôles négatifs

1. Supprimer uniquement le cookie `GRRMediaWikiAccessTest`.
2. Recharger MediaWiki : un passage par GRR doit recréer la preuve.
3. Modifier temporairement un caractère du secret MediaWiki :
   MediaWiki doit répondre HTTP 503, sans boucle de redirection.
4. Restaurer immédiatement le bon secret.
5. Appeler `api.php?action=query&meta=siteinfo&format=json` sans le cookie :
   l’API doit répondre HTTP 403.
6. Refaire l’appel avec la preuve valide : l’API doit répondre normalement.

## 6. Limites à constater, sans les corriger à cette étape

Les URL suivantes ne sont pas encore considérées comme protégées :

- `rest.php` ;
- `img_auth.php` ;
- les fichiers et miniatures servis directement depuis `images/`.

Elles constituent le début de l’étape 5. L’étape 4 ne doit pas être déployée en
production avant leur fermeture.

La preuve GRR est volontairement courte (120 secondes par défaut, 600 secondes
au maximum). Un formulaire d’édition conservé ouvert au-delà de cette durée
peut être interrompu au moment de son enregistrement. Le renouvellement pendant
une édition longue et le fonctionnement complet de VisualEditor seront traités
avec les voies API/REST à l’étape 5.

## Retour arrière

Dans `LocalSettings.php` :

```php
$wgGrrAccessGateEnabled = false;
```

Ce seul changement désactive immédiatement le contrôle. Le chargement de
l’extension peut rester en place pendant le diagnostic.
