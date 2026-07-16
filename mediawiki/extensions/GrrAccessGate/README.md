# GrrAccessGate

Extension MediaWiki 1.43 destinée à la copie de test LOCIE.

Elle valide la preuve HMAC créée par le module GRR `mediawiki_auth`. Une preuve
valide donne accès à MediaWiki sans créer ni connecter un compte MediaWiki :
le lecteur reste donc anonyme. La connexion locale MediaWiki reste disponible
pour les comptes chargés de l’édition et de l’administration.

## Périmètre de cette version

- contrôle des pages servies par `index.php` ;
- contrôle des appels à `api.php` ;
- désactivation du cache HTML fichier lorsque la passerelle est active ;
- conservation de l’URL demandée pendant le passage par GRR ;
- aucun secret, jeton ou User-Agent n’est écrit dans les journaux.

Les accès par `rest.php`, `img_auth.php` et les fichiers servis directement
depuis `images/` seront fermés à l’étape 5.

Le renouvellement de la preuve pendant une édition longue et la validation
complète de VisualEditor font également partie de l’étape 5.

## Complément étape 5

La version 0.2 protège également :

- REST par `grr_rest.php` ;
- les fichiers et miniatures par `grr_img_auth.php` ;
- les éditions longues par un renouvellement silencieux de la preuve ;
- la déconnexion GRR par suppression immédiate du cookie de preuve.

Les anciens points d’entrée et l’accès HTTP direct à `images/` doivent être
refusés par Apache. Voir `RECETTE_ETAPE5_NAS.md`.

## Activation

L’extension est chargée dans `LocalSettings.php`, mais son interrupteur reste
sur `false` tant que le secret créé dans l’administration du module GRR n’a pas
été recopié directement sur le NAS dans
`LocalSettings.grr-secret.php`.

Ne jamais transmettre ce secret dans un ticket, un courriel ou une
conversation. Le secret doit rester identique dans GRR et MediaWiki.

## Test autonome

Sur le NAS, depuis la racine MediaWiki :

```sh
php extensions/GrrAccessGate/tests/run.php
```

Ce test ne charge pas MediaWiki et ne touche pas la base de données.
