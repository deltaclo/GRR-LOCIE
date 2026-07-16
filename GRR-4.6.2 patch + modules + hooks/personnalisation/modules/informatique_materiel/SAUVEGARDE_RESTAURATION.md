# Sauvegarde et restauration - Informatique materiel

Ce document decrit les elements a sauvegarder pour restaurer le module
`informatique_materiel`.

## Elements a sauvegarder

### Base de donnees

Exporter les tables du module avec le prefixe reel GRR :

- `*_informatique_materiel_role`
- `*_informatique_materiel_journal`
- `*_informatique_materiel_personne`
- `*_informatique_materiel_categorie`
- `*_informatique_materiel_sequence`
- `*_informatique_materiel_item`
- `*_informatique_materiel_pret`
- `*_informatique_materiel_document`
- `*_informatique_materiel_import_log`

Exporter aussi les parametres du module dans la table des settings GRR :

- `imat_enabled`
- `imat_display_name`
- `imat_docs_enabled`
- `imat_docs_mb`
- `imat_docs_ext`
- `imat_alerts_enabled`
- `imat_depart_days`
- `imat_conflict_banner_enabled`
- `imat_alert_danger_color`
- `imat_alert_warning_color`
- `imat_conflict_alert_color`

### Fichiers

Sauvegarder le dossier complet :

```text
personnalisation/modules/informatique_materiel/
```

Les sous-dossiers les plus sensibles sont :

- `storage/imports/`
- `storage/documents/`

## Sauvegarde via phpMyAdmin

1. Ouvrir phpMyAdmin.
2. Selectionner la base GRR.
3. Exporter les tables `*_informatique_materiel_*`.
4. Exporter les lignes `imat_%` de la table des settings.
5. Conserver l'export SQL avec la sauvegarde des fichiers du module.

## Sauvegarde fichier

1. Copier le dossier `personnalisation/modules/informatique_materiel/`.
2. Verifier que les fichiers caches `.htaccess` sont inclus.
3. Verifier que les fichiers de `storage/documents/` sont inclus.
4. Verifier que les fichiers de `storage/imports/` sont inclus si les imports
   doivent rester auditables.

## Restauration

1. Restaurer les fichiers du module.
2. Restaurer les droits d'ecriture du compte Apache sur `storage/imports/` et
   `storage/documents/`.
3. Restaurer les tables SQL du module.
4. Restaurer les settings `imat_%`.
5. Ouvrir l'administration GRR des modules externes.
6. Reactiver le module si necessaire.
7. Ouvrir l'administration du module.
8. Verifier les diagnostics.
9. Ouvrir une fiche materiel avec document.
10. Telecharger un document de test.

## Verification apres restauration

- La version applicative affiche `1.0.0`.
- La version BDD attendue affiche `6`.
- Toutes les tables du module sont `OK`.
- Le stockage documentaire est `OK`.
- Les documents actifs sont telechargeables via `download.php`.
- L'acces direct a `storage/documents/` reste refuse.
- Les imports deja journalises restent visibles.

## Points de prudence

- Ne pas restaurer uniquement la base sans les fichiers de `storage/documents/`.
- Ne pas restaurer uniquement les fichiers sans les tables documentaires.
- Ne pas supprimer les documents archives sans decision explicite.
- Verifier les permissions apres une restauration Synology, car les droits
  peuvent changer selon le mode de copie.
