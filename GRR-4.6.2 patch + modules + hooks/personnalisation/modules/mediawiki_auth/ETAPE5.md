# Complément étape 5

La version 0.2.0 ajoute :

- `refresh.php`, qui renouvelle silencieusement la preuve quand la session GRR
  est encore valide ;
- `controleur.php`, qui répond au hook `hookBeforeLogout` et supprime le cookie
  MediaWiki ;
- `lib/AccessCookie.php`, qui centralise la création et la suppression du
  cookie.

Le contrôleur principal de déconnexion GRR appelle désormais :

```php
Hook::Appel('hookBeforeLogout');
```

Cet appel est placé avant `grr_closeSession()`, afin que les modules actifs
puissent nettoyer leurs cookies avant la destruction de la session.

Sans session GRR valide, `refresh.php` supprime la preuve et répond HTTP 401.
Avec une session valide, il renouvelle le cookie et répond HTTP 204.
