# Recette NAS — étape 3

## Préconditions

- environnement GRR de test : `/test/grr/` ;
- base de test : `grr2` ;
- environnement MediaWiki de test : `/test/mediawiki/` ;
- sauvegarde préalable de `grr2` ;
- module copié dans
  `personnalisation/modules/mediawiki_auth/`.

### Contrôle bloquant — isolation des sessions GRR

GRR de production et GRR de test sont sur le même hôte. Avant d'activer le
module :

1. relever dans les outils du navigateur le nom et le `Path` du cookie de
   `/grr/` ;
2. relever le nom et le `Path` du cookie de `/test/grr/` ;
3. vérifier que les deux applications ne partagent pas le même cookie.

Si les deux cookies s'appellent `GRR` avec un chemin commun, modifier uniquement
la copie de test dans `include/config.inc.php` :

```php
$gSessionName = "GRRTEST";
```

Se reconnecter ensuite aux deux applications et vérifier que les cookies
`GRR` et `GRRTEST` coexistent. Ne pas poursuivre la recette tant que les
sessions de production et de test ne sont pas isolées.

## Installation

Depuis la racine de la copie GRR, vérifier d'abord la syntaxe PHP avec
l'exécutable PHP 8.4 disponible sur le NAS, par exemple :

```sh
find personnalisation/modules/mediawiki_auth -name '*.php' -exec php84 -l {} \;
```

Adapter uniquement le nom de l'exécutable si le NAS utilise `php` au lieu de
`php84`. Chaque fichier doit indiquer qu'aucune erreur de syntaxe n'a été
détectée.

1. Ouvrir l'administration GRR de test.
2. Aller dans la gestion des modules externes.
3. Activer `mediawiki_auth`.
4. Vérifier l'absence d'erreur PHP ou SQL.
5. Ouvrir :
   `/test/grr/personnalisation/modules/mediawiki_auth/admin.php`.
6. Vérifier les valeurs :
   - chemin autorisé : `/test/mediawiki/` ;
   - chemin du cookie : `/test/mediawiki/` ;
   - cookie : `GRRMediaWikiAccessTest` ;
   - audience : `mediawiki-test` ;
   - durée : `120`.

## Test A — utilisateur déjà connecté à GRR

1. Se connecter à GRR de test.
2. Ouvrir le lien de diagnostic depuis `admin.php`.
3. Vérifier le retour vers `/test/mediawiki/`.
4. Dans les outils du navigateur, vérifier la présence du cookie
   `GRRMediaWikiAccessTest`.
5. Vérifier ses attributs :
   - `Path=/test/mediawiki/` ;
   - `Secure` ;
   - `HttpOnly` ;
   - `SameSite=Strict`.

## Test B — utilisateur non connecté

1. Ouvrir une nouvelle fenêtre privée.
2. Aller directement sur :
   `/test/grr/personnalisation/modules/mediawiki_auth/authorize.php?return=%2Ftest%2Fmediawiki%2F`
3. Vérifier la redirection vers la connexion GRR de test.
4. Se connecter.
5. Vérifier le retour automatique vers `/test/mediawiki/`.
6. Vérifier la création du cookie signé.

## Test C — conservation de la page demandée

1. Choisir une page existante de MediaWiki.
2. Encoder son chemin dans le paramètre `return`.
3. Refaire le test depuis une fenêtre privée.
4. Vérifier qu'après connexion GRR le navigateur revient exactement sur cette
   page, avec sa chaîne de requête éventuelle.

## Test D — refus des redirections non autorisées

Ces URL doivent retourner HTTP 400 et ne jamais rediriger :

- `?return=https://example.org/`
- `?return=/mediawiki/`
- `?return=/test/grr/`
- `?return=//example.org/`
- `?return=/test/mediawiki/../grr/`

## Test E — désactivation

1. Décocher `Passerelle activée` dans `admin.php`.
2. Appeler `authorize.php`.
3. Vérifier une réponse HTTP 503.
4. Réactiver la passerelle pour la suite.

## Test F — expiration

1. Obtenir un cookie valide.
2. Attendre un peu plus de 120 secondes.
3. Vérifier dans les outils du navigateur que le cookie a expiré.
4. Noter que MediaWiki n'exploite pas encore ce cookie : ce contrôle sera
   ajouté à l'étape 4.

## Résultat attendu

Le module valide la session GRR et émet une preuve signée, mais l'accès direct à
MediaWiki reste encore possible tant que l'extension `GrrAccessGate` n'est pas
installée.
