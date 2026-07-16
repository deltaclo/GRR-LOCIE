<?php

class StockChimiqueRenderer
{
    public static function accountMenu()
    {
        if (!StockChimiqueConfig::isEnabled() || !StockChimiqueSecurity::canAccess()) {
            return '';
        }
        return '<br><br><a href="compte.php?pc=stock_chimique" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 sc-account-btn">'
            .self::html(StockChimiqueConfig::displayName()).'</a>';
    }

    public static function statusSummaryLinks()
    {
        if (!StockChimiqueConfig::isEnabled() || !StockChimiqueSecurity::canAccess()) {
            return '';
        }
        $count = array_sum(StockChimiqueRepository::alertCounts());
        if ($count <= 0) {
            return '';
        }
        return '<p style="text-align:center"><a href="'.self::html(self::accountUrl(array('pc' => StockChimiqueConfig::MODULE)).'#sc-alerts').'" '
            .'style="display:inline-block;padding:2px 7px;background:#c9302c;color:#fff;border-radius:3px">'
            .self::html($count.' alerte'.($count > 1 ? 's' : '').' stock chimique').'</a></p>';
    }

    public static function accountPage()
    {
        if (!isset($_GET['pc']) || (string) $_GET['pc'] !== StockChimiqueConfig::MODULE) {
            return '';
        }
        if (!StockChimiqueConfig::isEnabled()) {
            return '<div class="alert alert-warning">Module désactivé.</div>';
        }
        StockChimiqueRepository::ensureTables();
        if (!StockChimiqueSecurity::canAccess()) {
            return '<div class="alert alert-warning">Accès refusé.</div>';
        }
        if (isset($_GET['admin']) && (string) $_GET['admin'] === '1') {
            if (!StockChimiqueSecurity::isAdmin()) {
                return '<div class="alert alert-warning">Accès refusé.</div>';
            }
            ob_start();
            $stock_chimique_admin_embedded = true;
            include __DIR__.'/../admin.php';
            return '<section id="stock-chimique">'.self::assets().ob_get_clean().'</section>';
        }

        $messages = array();
        $errors = array();
        $view = isset($_GET['view']) ? (string) $_GET['view'] : '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!StockChimiqueSecurity::validatePost()) {
                $errors[] = 'Session de formulaire invalide. Rechargez la page.';
            } else {
                self::handlePost($view, $id, $messages, $errors);
            }
        }

        if ($view === 'suppliers') {
            return self::suppliersPage($messages, $errors);
        }
        if ($view === 'locations') {
            return self::locationsPage($messages, $errors);
        }
        if ($view === 'products') {
            return self::productsPage($messages, $errors);
        }
        if ($view === 'product') {
            return self::productPage($id, $messages, $errors);
        }
        if ($view === 'containers') {
            return self::containersPage($messages, $errors);
        }
        if ($view === 'movements') {
            return self::movementsPage($messages, $errors);
        }
        if ($view === 'inventories') {
            return self::inventoriesPage($messages, $errors);
        }
        if ($view === 'inventory') {
            return self::inventoryPage($id, $messages, $errors);
        }
        if ($view === 'notifications') {
            return self::notificationsPage($messages, $errors);
        }
        if ($view === 'journal') {
            return self::journalPage($messages, $errors);
        }
        return self::dashboard($messages, $errors);
    }

    private static function handlePost(&$view, &$id, &$messages, &$errors)
    {
        $action = isset($_POST['sc_action']) ? (string) $_POST['sc_action'] : '';
        $login = StockChimiqueSecurity::currentLogin();

        if ($action === 'save_supplier' && StockChimiqueSecurity::canManage()) {
            $view = 'suppliers';
            $result = StockChimiqueRepository::saveSupplier($_POST, $login);
            self::resultMessage($result, 'Fournisseur enregistré.', $messages, $errors);
        } elseif ($action === 'archive_supplier' && StockChimiqueSecurity::canManage()) {
            $view = 'suppliers';
            self::booleanMessage(StockChimiqueRepository::archiveSupplier((int) $_POST['id'], $login), 'Fournisseur archivé.', 'Archivage impossible : le fournisseur est probablement encore utilisé.', $messages, $errors);
        } elseif ($action === 'save_location' && StockChimiqueSecurity::canManage()) {
            $view = 'locations';
            $result = StockChimiqueRepository::saveLocation($_POST, $login);
            self::resultMessage($result, 'Emplacement enregistré.', $messages, $errors);
        } elseif ($action === 'archive_location' && StockChimiqueSecurity::canManage()) {
            $view = 'locations';
            self::booleanMessage(StockChimiqueRepository::archiveLocation((int) $_POST['id'], $login), 'Emplacement archivé.', 'Archivage impossible : enfants ou contenants actifs présents.', $messages, $errors);
        } elseif ($action === 'save_product' && StockChimiqueSecurity::canManage()) {
            $view = 'products';
            $result = StockChimiqueRepository::saveProduct($_POST, $login);
            self::resultMessage($result, 'Produit enregistré.', $messages, $errors);
        } elseif ($action === 'archive_product' && StockChimiqueSecurity::canManage()) {
            $view = 'products';
            self::booleanMessage(StockChimiqueRepository::archiveProduct((int) $_POST['id'], $login), 'Produit archivé.', 'Archivage impossible tant que des contenants sont en stock.', $messages, $errors);
        } elseif ($action === 'create_container' && StockChimiqueSecurity::canOperate()) {
            $view = isset($_POST['return_view']) && (string) $_POST['return_view'] === 'product'
                ? 'product'
                : 'containers';
            if ($view === 'product') {
                $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            }
            $result = StockChimiqueRepository::createContainer($_POST, $login);
            self::resultMessage($result, 'Contenant réceptionné.', $messages, $errors);
        } elseif ($action === 'create_movement' && StockChimiqueSecurity::canOperate()) {
            $returnView = isset($_POST['return_view']) ? (string) $_POST['return_view'] : 'movements';
            $view = in_array($returnView, array('product', 'containers', 'movements'), true) ? $returnView : 'movements';
            if ($view === 'product') {
                $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            }
            $type = isset($_POST['type_mouvement']) ? (string) $_POST['type_mouvement'] : '';
            if (in_array($type, array('correction_plus', 'correction_moins', 'elimination', 'retour_fournisseur'), true) && !StockChimiqueSecurity::canManage()) {
                $errors[] = 'Ce mouvement est réservé aux gestionnaires.';
            } else {
                $result = StockChimiqueRepository::createMovement($_POST, $login);
                self::resultMessage($result, 'Mouvement enregistré.', $messages, $errors);
            }
        } elseif ($action === 'evacuate_container' && StockChimiqueSecurity::canManage()) {
            $returnView = isset($_POST['return_view']) ? (string) $_POST['return_view'] : 'containers';
            $view = $returnView === 'product' ? 'product' : 'containers';
            if ($view === 'product') {
                $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            }
            $reason = trim((string) (isset($_POST['motif']) ? $_POST['motif'] : ''));
            if ($reason === '') {
                $errors[] = 'Le motif d évacuation est obligatoire.';
            } else {
                $movement = $_POST;
                $movement['type_mouvement'] = 'elimination';
                $movement['motif'] = 'Évacuation vers les déchets chimiques : '.$reason;
                $result = StockChimiqueRepository::createMovement($movement, $login);
                self::resultMessage($result, 'Contenant évacué vers les déchets chimiques.', $messages, $errors);
            }
        } elseif ($action === 'evacuate_product' && StockChimiqueSecurity::canManage()) {
            $view = isset($_POST['return_view']) && (string) $_POST['return_view'] === 'product' ? 'product' : 'products';
            $id = $view === 'product' && isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            $result = StockChimiqueRepository::evacuateProduct($_POST, $login);
            if (!empty($result['ok'])) {
                $messages[] = (int) $result['count'].' contenant(s) évacué(s) vers les déchets chimiques.';
            } else {
                $errors[] = !empty($result['error']) ? $result['error'] : 'Évacuation du produit impossible.';
            }
        } elseif ($action === 'archive_container' && StockChimiqueSecurity::canManage()) {
            $view = 'containers';
            self::booleanMessage(
                StockChimiqueRepository::archiveContainer((int) $_POST['id'], $login),
                'Contenant archivé.',
                'Seul un contenant vide, éliminé ou retourné peut être archivé.',
                $messages,
                $errors
            );
        } elseif ($action === 'upload_document' && StockChimiqueSecurity::canManage()) {
            $view = 'product';
            $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            $uploadErrors = self::uploadDocument($_POST, $login);
            if (count($uploadErrors) === 0) {
                $messages[] = 'Document ajouté.';
            } else {
                $errors = array_merge($errors, $uploadErrors);
            }
        } elseif ($action === 'archive_document' && StockChimiqueSecurity::canManage()) {
            $view = 'product';
            $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            self::booleanMessage(StockChimiqueRepository::archiveDocument((int) $_POST['document_id'], $login), 'Document archivé.', 'Archivage du document impossible.', $messages, $errors);
        } elseif ($action === 'update_document' && StockChimiqueSecurity::isAdmin()) {
            $view = 'product';
            $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            $result = StockChimiqueRepository::updateDocumentInfo($_POST, $login);
            if (!empty($result['product_id'])) {
                $id = (int) $result['product_id'];
            }
            self::resultMessage($result, 'Informations du document mises à jour.', $messages, $errors);
        } elseif ($action === 'validate_fds_alert' && StockChimiqueSecurity::canManage()) {
            $returnView = isset($_POST['return_view']) ? (string) $_POST['return_view'] : '';
            $view = $returnView === 'notifications' ? 'notifications' : ($returnView === 'product' ? 'product' : '');
            if ($view === 'product') {
                $id = isset($_POST['produit_id']) ? (int) $_POST['produit_id'] : 0;
            }
            self::booleanMessage(
                StockChimiqueRepository::validateFdsAlert(
                    isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0,
                    $login
                ),
                'La FDS a été validée. L alerte réapparaîtra après le délai configuré.',
                'Validation impossible : la FDS n est peut-être plus la version courante.',
                $messages,
                $errors
            );
        } elseif ($action === 'create_inventory' && StockChimiqueSecurity::canManage()) {
            $view = 'inventories';
            $inventoryId = StockChimiqueRepository::createInventory(
                isset($_POST['libelle']) ? $_POST['libelle'] : '',
                isset($_POST['emplacement_id']) ? (int) $_POST['emplacement_id'] : 0,
                $login
            );
            if ($inventoryId > 0) {
                $messages[] = 'Inventaire ouvert.';
                $view = 'inventory';
                $id = $inventoryId;
            } else {
                $errors[] = 'Création de l inventaire impossible.';
            }
        } elseif ($action === 'save_inventory' && StockChimiqueSecurity::canManage()) {
            $view = 'inventory';
            $id = (int) $_POST['inventaire_id'];
            self::booleanMessage(
                StockChimiqueRepository::saveInventoryCounts($id, isset($_POST['counted']) ? $_POST['counted'] : array(), isset($_POST['comment']) ? $_POST['comment'] : array(), $login),
                'Comptages enregistrés.',
                'Un comptage est invalide.',
                $messages,
                $errors
            );
        } elseif ($action === 'finalize_inventory' && StockChimiqueSecurity::canManage()) {
            $view = 'inventory';
            $id = (int) $_POST['inventaire_id'];
            $result = StockChimiqueRepository::finalizeInventory($id, $login);
            self::resultMessage($result, 'Inventaire terminé et corrections appliquées.', $messages, $errors);
        } elseif ($action === 'cancel_inventory' && StockChimiqueSecurity::isAdmin()) {
            $view = 'inventory';
            $id = (int) $_POST['inventaire_id'];
            $result = StockChimiqueRepository::cancelInventory($id, $login);
            self::resultMessage($result, 'Inventaire annulé.', $messages, $errors);
        } elseif ($action === 'send_notifications' && StockChimiqueSecurity::canManage()) {
            $view = 'notifications';
            $result = StockChimiqueNotification::sendPending();
            $messages[] = 'Notifications envoyées : '.$result['sent'].' ; ignorées : '.$result['skipped'].'.';
            $errors = array_merge($errors, $result['errors']);
        } elseif ($action !== '') {
            $errors[] = 'Action inconnue ou accès refusé.';
        }
    }

    private static function dashboard($messages, $errors)
    {
        $counts = StockChimiqueRepository::dashboardCounts();
        $alerts = StockChimiqueRepository::alerts(200);
        $html = self::startPage($messages, $errors)
            .'<div class="sc-cards">'
            .self::card('Produits actifs', $counts['products'], '#337ab7')
            .self::card('Contenants en stock', $counts['containers'], '#5cb85c')
            .self::card('Mouvements', $counts['movements'], '#5bc0de')
            .self::card('Inventaires ouverts', $counts['inventories'], '#f0ad4e')
            .self::card('Alertes', $counts['alerts'], '#d9534f')
            .'</div><div class="sc-actions">';
        if (StockChimiqueSecurity::canOperate()) {
            $html .= '<a class="btn btn-success" href="'.self::url(array('view' => 'containers')).'#sc-receive">Réceptionner</a> '
                .'<a class="btn btn-primary" href="'.self::url(array('view' => 'movements')).'#sc-movement">Nouveau mouvement</a> ';
        }
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/stock_chimique/export.php?type=products">Exporter les produits</a></div>';
        $html .= '<div class="panel panel-danger" id="sc-alerts"><div class="panel-heading"><strong>Alertes</strong></div><div class="panel-body">';
        if (!StockChimiqueConfig::alertsEnabled()) {
            $html .= '<p>Les alertes sont désactivées par l administrateur.</p>';
        } elseif (count($alerts) === 0) {
            $html .= '<p>Aucune alerte.</p>';
        } else {
            $showValidation = StockChimiqueSecurity::canManage();
            $html .= '<div class="table-responsive"><table class="table table-striped sc-filterable"><thead><tr><th>Type</th><th>Élément</th><th>Détail</th>'.($showValidation ? '<th>Validation FDS</th>' : '').'</tr></thead><tbody>';
            foreach ($alerts as $alert) {
                $html .= '<tr><td>'.self::badge(self::alertLabel($alert['type']), $alert['type']).'</td>'
                    .'<td><a href="'.self::url(array('view' => 'product', 'id' => $alert['produit_id'])).'">'.self::html($alert['label']).'</a></td>'
                    .'<td>'.self::html($alert['detail']).'</td>';
                if ($showValidation) {
                    $html .= '<td>'.self::fdsValidationForm($alert, '').'</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }
        return $html.'</div></div>'.self::endPage();
    }

    private static function suppliersPage($messages, $errors)
    {
        $edit = isset($_GET['edit']) ? StockChimiqueRepository::supplier((int) $_GET['edit']) : array();
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Fournisseurs</h2><span>';
        if (StockChimiqueSecurity::canManage()) {
            $html .= self::modalButton('sc-supplier-modal', 'Ajouter un fournisseur', 'btn-primary');
        }
        $html .= '</span></div>';
        if (StockChimiqueSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'suppliers')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="save_supplier"><input type="hidden" name="id" value="'.self::value($edit, 'id').'">'
                .self::input('Nom', 'nom', self::value($edit, 'nom'), true)
                .'<div class="row"><div class="col-md-4">'.self::input('Contact', 'contact', self::value($edit, 'contact')).'</div>'
                .'<div class="col-md-4">'.self::input('Téléphone', 'telephone', self::value($edit, 'telephone')).'</div>'
                .'<div class="col-md-4">'.self::input('E-mail', 'email', self::value($edit, 'email'), false, 'email').'</div></div>'
                .self::input('Site web', 'site_web', self::value($edit, 'site_web'))
                .self::textarea('Adresse', 'adresse', self::value($edit, 'adresse'))
                .self::textarea('Notes', 'notes', self::value($edit, 'notes'))
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-supplier-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Enregistrer</button></div></form>';
            $html .= self::modal('sc-supplier-modal', ($edit ? 'Modifier' : 'Ajouter').' un fournisseur', $form, !empty($edit));
        }
        $html .= self::filterInput('fournisseurs').'<div class="table-responsive"><table id="fournisseurs" class="table table-striped table-bordered sc-filterable"><thead><tr><th>Nom</th><th>Contact</th><th>Téléphone</th><th>E-mail</th><th>État</th><th></th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::suppliers(true) as $row) {
            $html .= '<tr><td>'.self::html($row['nom']).'</td><td>'.self::html($row['contact']).'</td><td>'.self::html($row['telephone']).'</td><td>'.self::html($row['email']).'</td><td>'.((int) $row['actif'] === 1 ? 'Actif' : 'Archivé').'</td><td>';
            if (StockChimiqueSecurity::canManage() && (int) $row['actif'] === 1) {
                $html .= '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'suppliers', 'edit' => $row['id'])).'">Modifier</a> '
                    .self::archiveForm('archive_supplier', $row['id'], 'Archiver ce fournisseur ?');
            }
            $html .= '</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function locationsPage($messages, $errors)
    {
        $edit = isset($_GET['edit']) ? StockChimiqueRepository::location((int) $_GET['edit']) : array();
        $locations = StockChimiqueRepository::locations(true);
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Emplacements</h2><span>';
        if (StockChimiqueSecurity::canManage()) {
            $html .= self::modalButton('sc-location-modal', 'Ajouter un emplacement', 'btn-primary');
        }
        $html .= '</span></div>';
        if (StockChimiqueSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'locations')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="save_location"><input type="hidden" name="id" value="'.self::value($edit, 'id').'">'
                .'<div class="row"><div class="col-md-3">'.self::input('Code', 'code', self::value($edit, 'code'), true).'</div><div class="col-md-5">'.self::input('Nom', 'nom', self::value($edit, 'nom'), true).'</div>'
                .'<div class="col-md-4">'.self::select('Type', 'type_emplacement', StockChimiqueRepository::locationTypes(), self::value($edit, 'type_emplacement', 'autre')).'</div></div>'
                .self::select('Parent', 'parent_id', self::locationOptions($locations, (int) self::value($edit, 'id')), self::value($edit, 'parent_id', 0), true)
                .self::input('Responsable', 'responsable', self::value($edit, 'responsable'))
                .self::textarea('Description', 'description', self::value($edit, 'description'))
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-location-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Enregistrer</button></div></form>';
            $html .= self::modal('sc-location-modal', ($edit ? 'Modifier' : 'Ajouter').' un emplacement', $form, !empty($edit));
        }
        $html .= self::filterInput('locations').'<div class="table-responsive"><table id="locations" class="table table-striped table-bordered sc-filterable"><thead><tr><th>Code</th><th>Chemin</th><th>Type</th><th>Responsable</th><th>État</th><th></th></tr></thead><tbody>';
        $locationTypeLabels = StockChimiqueRepository::locationTypes();
        foreach ($locations as $row) {
            $typeLabel = isset($locationTypeLabels[$row['type_emplacement']]) ? $locationTypeLabels[$row['type_emplacement']] : $row['type_emplacement'];
            $html .= '<tr><td>'.self::html($row['code']).'</td><td>'.self::html($row['chemin']).'</td><td>'.self::html($typeLabel).'</td><td>'.self::html($row['responsable']).'</td><td>'.((int) $row['actif'] === 1 ? 'Actif' : 'Archivé').'</td><td>';
            if (StockChimiqueSecurity::canManage() && (int) $row['actif'] === 1) {
                $html .= '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'locations', 'edit' => $row['id'])).'">Modifier</a> '
                    .self::archiveForm('archive_location', $row['id'], 'Archiver cet emplacement ?');
            }
            $html .= '</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function productsPage($messages, $errors)
    {
        $edit = isset($_GET['edit']) ? StockChimiqueRepository::product((int) $_GET['edit']) : array();
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Produits</h2><span>';
        if (StockChimiqueSecurity::canManage()) {
            $html .= self::modalButton('sc-product-modal', 'Ajouter un produit', 'btn-primary').' ';
        }
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/stock_chimique/export.php?type=products">CSV</a></span></div>';
        if (StockChimiqueSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'products')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="save_product"><input type="hidden" name="id" value="'.self::value($edit, 'id').'">'
                .'<div class="row"><div class="col-md-3">'.self::input('Référence interne', 'reference_interne', self::value($edit, 'reference_interne')).'</div>'
                .'<div class="col-md-5">'.self::input('Nom commercial', 'nom_commercial', self::value($edit, 'nom_commercial'), true).'</div>'
                .'<div class="col-md-4">'.self::select('Unité de stock', 'unite_stock', StockChimiqueRepository::productUnits(), self::value($edit, 'unite_stock'), false, true).'</div></div>'
                .'<div class="row"><div class="col-md-4">'.self::select('Fournisseur principal', 'fournisseur_id', self::supplierOptions(), self::value($edit, 'fournisseur_id', 0), true).'</div>'
                .'<div class="col-md-4">'.self::input('Référence fournisseur', 'reference_fournisseur', self::value($edit, 'reference_fournisseur')).'</div>'
                .'<div class="col-md-4">'.self::input('Fabricant', 'fabricant', self::value($edit, 'fabricant')).'</div></div>'
                .'<div class="row"><div class="col-md-3">'.self::input('CAS', 'numero_cas', self::value($edit, 'numero_cas')).'</div>'
                .'<div class="col-md-3">'.self::input('CE', 'numero_ce', self::value($edit, 'numero_ce')).'</div>'
                .'<div class="col-md-3">'.self::input('UFI', 'ufi', self::value($edit, 'ufi')).'</div>'
                .'<div class="col-md-3">'.self::input('Catégorie', 'categorie', self::value($edit, 'categorie')).'</div></div>'
                .'<div class="row"><div class="col-md-4">'.self::select('État physique', 'etat_physique', array('non_renseigne' => 'Non renseigné', 'solide' => 'Solide', 'liquide' => 'Liquide', 'gaz' => 'Gaz', 'autre' => 'Autre'), self::value($edit, 'etat_physique', 'non_renseigne')).'</div>'
                .'<div class="col-md-4">'.self::select('CMR', 'statut_cmr', array('non_renseigne' => 'Non renseigné', 'non' => 'Non', 'oui' => 'Oui'), self::value($edit, 'statut_cmr', 'non_renseigne')).'</div>'
                .'<div class="col-md-4">'.self::input('Seuil minimal', 'seuil_minimal', self::value($edit, 'seuil_minimal', '0'), false, 'number', '0.0001').'</div></div>'
                .self::input('Pictogrammes CLP (codes séparés par des virgules)', 'pictogrammes_clp', self::value($edit, 'pictogrammes_clp'))
                .self::textarea('Mentions H', 'mentions_h', self::value($edit, 'mentions_h'))
                .self::textarea('Conseils P', 'conseils_p', self::value($edit, 'conseils_p'))
                .self::textarea('Conditions de stockage', 'conditions_stockage', self::value($edit, 'conditions_stockage'))
                .self::textarea('Description', 'description', self::value($edit, 'description'))
                .self::textarea('Notes', 'notes', self::value($edit, 'notes'))
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-product-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Enregistrer</button></div></form>';
            $html .= self::modal('sc-product-modal', ($edit ? 'Modifier' : 'Ajouter').' un produit', $form, !empty($edit));
            $html .= self::productEvacuationModal('products');
        }
        $html .= self::filterInput('products').'<div class="table-responsive"><table id="products" class="table table-striped table-bordered sc-filterable"><thead><tr><th>Référence</th><th>Produit</th><th>CAS</th><th>Catégorie</th><th>Stock</th><th>Seuil</th><th>FDS</th><th>État</th><th></th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::products(true, 2000) as $row) {
            $html .= '<tr><td>'.self::html($row['reference_interne']).'</td><td><a href="'.self::url(array('view' => 'product', 'id' => $row['id'])).'">'.self::html($row['nom_commercial']).'</a></td><td>'.self::html($row['numero_cas']).'</td><td>'.self::html($row['categorie']).'</td><td>'.self::quantity($row['stock_total']).' '.self::html($row['unite_stock']).'</td><td>'.self::quantity($row['seuil_minimal']).'</td><td>'.self::html($row['fds_revision'] ?: 'Absente').'</td><td>'.((int) $row['actif'] === 1 ? 'Actif' : 'Archivé').'</td><td>';
            if (StockChimiqueSecurity::canManage() && (int) $row['actif'] === 1) {
                $html .= '<div class="sc-row-actions"><a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'products', 'edit' => $row['id'])).'">Modifier</a>';
                if ((int) $row['contenants_actifs'] > 0) {
                    $html .= self::modalButton(
                        'sc-product-evacuation-modal',
                        'Évacuer',
                        'btn-danger btn-xs',
                        array('produit_id' => (int) $row['id'], 'product_label' => (string) $row['nom_commercial'])
                    );
                }
                $html .= self::archiveForm('archive_product', $row['id'], 'Archiver ce produit ?').'</div>';
            }
            $html .= '</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function productPage($id, $messages, $errors)
    {
        $product = StockChimiqueRepository::product($id);
        if (!$product) {
            return self::startPage($messages, array_merge($errors, array('Produit introuvable.'))).self::endPage();
        }
        $html = self::startPage($messages, $errors)
            .'<div class="sc-title-row"><h2>'.self::html($product['nom_commercial']).'</h2><span>';
        if (StockChimiqueSecurity::canManage() && (int) $product['actif'] === 1) {
            $html .= '<a class="btn btn-warning" href="'.self::url(array('view' => 'products', 'edit' => $id)).'">Modifier</a> ';
        }
        if (StockChimiqueSecurity::canOperate() && (int) $product['actif'] === 1) {
            $html .= self::modalButton('sc-product-container-modal', 'Ajouter un contenant', 'btn-success').' ';
        }
        if (StockChimiqueSecurity::canManage() && StockChimiqueConfig::documentsEnabled() && (int) $product['actif'] === 1) {
            $html .= self::modalButton('sc-document-modal', 'Ajouter un document', 'btn-primary').' ';
        }
        if (StockChimiqueSecurity::canManage() && (int) $product['contenants_actifs'] > 0) {
            $html .= self::modalButton('sc-product-evacuation-modal', 'Évacuer le produit', 'btn-danger').' ';
        }
        $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'products')).'">Retour</a></span></div>'
            .'<div class="panel panel-info"><div class="panel-body"><div class="row">'
            .'<div class="col-md-4"><strong>Référence :</strong> '.self::html($product['reference_interne']).'<br><strong>CAS :</strong> '.self::html($product['numero_cas']).'<br><strong>Fabricant :</strong> '.self::html($product['fabricant']).'</div>'
            .'<div class="col-md-4"><strong>Stock :</strong> '.self::quantity($product['stock_total']).' '.self::html($product['unite_stock']).'<br><strong>Seuil :</strong> '.self::quantity($product['seuil_minimal']).'<br><strong>CMR :</strong> '.self::html($product['statut_cmr']).'</div>'
            .'<div class="col-md-4"><strong>CLP :</strong> '.self::html($product['pictogrammes_clp']).'<br><strong>Fournisseur :</strong> '.self::html($product['fournisseur_nom']).'</div>'
            .'</div><hr><div class="row"><div class="col-md-6"><strong>Mentions H</strong><div>'.self::multiline($product['mentions_h']).'</div></div>'
            .'<div class="col-md-6"><strong>Conseils P</strong><div>'.self::multiline($product['conseils_p']).'</div></div></div>'
            .'<hr><strong>Conditions de stockage</strong><div>'.self::multiline($product['conditions_stockage']).'</div>'
            .'<hr><strong>Description</strong><div>'.self::multiline($product['description']).'</div>'
            .'</div></div><h3>Contenants</h3>';
        $containers = StockChimiqueRepository::containers($id, true, 5000);
        $html .= '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Code</th><th>Lot</th><th>Quantité</th><th>Emplacement</th><th>Péremption</th><th>Statut</th><th>Actions</th></tr></thead><tbody>';
        foreach ($containers as $container) {
            $html .= '<tr><td>'.self::html($container['code_interne']).'</td><td>'.self::html($container['numero_lot']).'</td><td>'.self::quantity($container['quantite_courante']).' '.self::html($container['unite']).'</td><td>'.self::html($container['emplacement_code'].' - '.$container['emplacement_nom']).'</td><td>'.self::html($container['date_peremption']).'</td><td>'.self::html($container['statut']).'</td><td><div class="sc-row-actions">';
            if ((string) $container['statut'] === 'en_stock' && StockChimiqueSecurity::canOperate()) {
                $html .= self::modalButton(
                    'sc-movement-modal',
                    'Mouvement',
                    'btn-primary btn-xs',
                    array('contenant_id' => (int) $container['id'])
                );
            }
            if ((string) $container['statut'] === 'en_stock' && StockChimiqueSecurity::canManage()) {
                $html .= self::modalButton(
                    'sc-container-evacuation-modal',
                    'Évacuer',
                    'btn-danger btn-xs',
                    array(
                        'contenant_id' => (int) $container['id'],
                        'container_label' => (string) $container['code_interne'].' — '.self::quantity($container['quantite_courante']).' '.$container['unite'],
                    )
                );
            }
            $html .= '</div></td></tr>';
        }
        $html .= '</tbody></table></div><h3>Documents</h3>';
        if (StockChimiqueSecurity::canManage() && StockChimiqueConfig::documentsEnabled() && (int) $product['actif'] === 1) {
            $documentForm = '<form method="post" enctype="multipart/form-data" action="'.self::url(array('view' => 'product', 'id' => $id)).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="upload_document"><input type="hidden" name="produit_id" value="'.(int) $id.'"><input type="hidden" name="MAX_FILE_SIZE" value="'.StockChimiqueConfig::documentMaxBytes().'">'
                .'<div class="row"><div class="col-md-3">'.self::select('Type', 'type_document', StockChimiqueRepository::documentTypes(), 'fds').'</div>'
                .'<div class="col-md-2">'.self::input('Langue', 'langue', 'fr', true).'</div>'
                .'<div class="col-md-3">'.self::input('Date de révision FDS', 'date_revision', '', false, 'date').'</div>'
                .'<div class="col-md-4">'.self::input('Version', 'numero_version', '').'</div></div>'
                .self::input('Émetteur', 'emetteur', '')
                .'<div class="form-group"><label><input type="checkbox" name="est_courant" value="1" checked> Version courante</label></div>'
                .self::textarea('Description', 'description', '')
                .'<div class="form-group"><label>Fichier</label><input class="form-control" type="file" name="document_file" required></div>'
                .'<p class="help-block">FDS : PDF obligatoire. Autres documents : '.self::html(StockChimiqueConfig::documentExtensionsText()).'. Maximum '.StockChimiqueConfig::documentMaxMb().' Mo.</p>'
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-document-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Ajouter le document</button></div></form>';
            $html .= self::modal('sc-document-modal', 'Ajouter un document', $documentForm);
        }
        $fdsAlertsByDocument = array();
        foreach (StockChimiqueRepository::alerts(1000) as $alert) {
            if (
                (string) $alert['type'] === 'fds_a_verifier'
                && (int) $alert['produit_id'] === (int) $id
                && (int) $alert['document_id'] > 0
            ) {
                $fdsAlertsByDocument[(int) $alert['document_id']] = $alert;
            }
        }
        $html .= '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Document</th><th>Type</th><th>Langue</th><th>Révision</th><th>Dernière validation</th><th>Version</th><th>État</th><th>Auteur</th><th></th></tr></thead><tbody>';
        $documentTypeLabels = StockChimiqueRepository::documentTypes();
        foreach (StockChimiqueRepository::documentsForProduct($id, true) as $document) {
            $state = (int) $document['actif'] !== 1 ? 'Archivé' : ((int) $document['est_courant'] === 1 ? 'Courant' : 'Ancienne version');
            $typeLabel = isset($documentTypeLabels[$document['type_document']]) ? $documentTypeLabels[$document['type_document']] : $document['type_document'];
            $validation = (int) $document['fds_validated_at'] > 0
                ? self::dateTime($document['fds_validated_at']).' par '.self::html($document['fds_validated_by'])
                : '—';
            $html .= '<tr><td><a href="../personnalisation/modules/stock_chimique/download.php?id='.(int) $document['id'].'">'.self::html($document['original_name']).'</a></td><td>'.self::html($typeLabel).'</td><td>'.self::html($document['langue']).'</td><td>'.self::html($document['date_revision']).'</td><td>'.$validation.'</td><td>'.self::html($document['numero_version']).'</td><td>'.self::html($state).'</td><td>'.self::html($document['uploaded_by']).'</td><td><div class="sc-row-actions">';
            if (isset($fdsAlertsByDocument[(int) $document['id']])) {
                $html .= self::fdsValidationForm($fdsAlertsByDocument[(int) $document['id']], 'product', $id);
            }
            if (StockChimiqueSecurity::isAdmin()) {
                $html .= self::modalButton(
                    'sc-document-edit-modal',
                    'Modifier',
                    'btn-default btn-xs',
                    array(
                        'document_id' => (int) $document['id'],
                        'original_name' => (string) $document['original_name'],
                        'type_document' => (string) $document['type_document'],
                        'langue' => (string) $document['langue'],
                        'date_revision' => (string) $document['date_revision'],
                        'numero_version' => (string) $document['numero_version'],
                        'emetteur' => (string) $document['emetteur'],
                        'est_courant' => (int) $document['est_courant'] === 1,
                        'description' => (string) $document['description'],
                    )
                );
            }
            if (StockChimiqueSecurity::canManage() && (int) $document['actif'] === 1) {
                $html .= '<form method="post" action="'.self::url(array('view' => 'product', 'id' => $id)).'" onsubmit="return confirm(\'Archiver ce document ?\')">'.self::csrf().'<input type="hidden" name="sc_action" value="archive_document"><input type="hidden" name="produit_id" value="'.(int) $id.'"><input type="hidden" name="document_id" value="'.(int) $document['id'].'"><button class="btn btn-danger btn-xs" type="submit">Archiver</button></form>';
            }
            $html .= '</div></td></tr>';
        }
        $html .= '</tbody></table></div>';
        if (StockChimiqueSecurity::isAdmin()) {
            $html .= self::documentEditModal($id);
        }
        if (StockChimiqueSecurity::canOperate()) {
            $html .= self::productContainerModal($id, (string) $product['nom_commercial'])
                .self::movementModal('product', $id);
        }
        if (StockChimiqueSecurity::canManage()) {
            $html .= self::containerEvacuationModal('product', $id)
                .self::productEvacuationModal('product', $id, (string) $product['nom_commercial']);
        }
        return $html.self::endPage();
    }

    private static function containersPage($messages, $errors)
    {
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Contenants</h2><span>';
        if (StockChimiqueSecurity::canOperate()) {
            $html .= self::modalButton('sc-receive-modal', 'Réceptionner un contenant', 'btn-success').' ';
        }
        if (StockChimiqueSecurity::canOperate()) {
            $html .= self::modalButton('sc-movement-modal', 'Nouveau mouvement', 'btn-primary').' ';
        }
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/stock_chimique/export.php?type=containers">CSV</a> <button class="btn btn-default" type="button" onclick="window.print()">Imprimer</button></span></div>';
        if (StockChimiqueSecurity::canOperate()) {
            $receiveForm = '<form method="post" action="'.self::url(array('view' => 'containers')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="create_container"><input type="hidden" name="request_token" value="'.self::html(StockChimiqueSecurity::requestToken()).'">'
                .'<div class="row"><div class="col-md-4">'.self::select('Produit', 'produit_id', self::productOptions(), '', false, true).'</div><div class="col-md-4">'.self::select('Emplacement', 'emplacement_id', self::locationOptions(StockChimiqueRepository::locations(false)), '', false, true).'</div><div class="col-md-4">'.self::select('Fournisseur réel', 'fournisseur_id', self::supplierOptions(), 0, true).'</div></div>'
                .'<div class="row"><div class="col-md-3">'.self::input('Code interne (auto si vide)', 'code_interne', '').'</div><div class="col-md-3">'.self::input('Lot', 'numero_lot', '').'</div><div class="col-md-3">'.self::input('Conditionnement', 'conditionnement', '').'</div><div class="col-md-3">'.self::input('Quantité', 'quantite', '', true, 'number', '0.0001').'</div></div>'
                .'<div class="row"><div class="col-md-4">'.self::input('Réception', 'date_reception', date('Y-m-d'), false, 'date').'</div><div class="col-md-4">'.self::input('Ouverture', 'date_ouverture', '', false, 'date').'</div><div class="col-md-4">'.self::input('Péremption', 'date_peremption', '', false, 'date').'</div></div>'
                .self::textarea('Notes', 'notes', '')
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-receive-modal\')">Annuler</button> '
                .'<button class="btn btn-success" type="submit">Réceptionner</button></div></form>';
            $html .= self::modal('sc-receive-modal', 'Réceptionner un contenant', $receiveForm)
                .self::movementModal('containers');
            if (StockChimiqueSecurity::canManage()) {
                $html .= self::containerEvacuationModal('containers');
            }
        }
        $html .= self::filterInput('containers').'<div class="table-responsive"><table id="containers" class="table table-striped table-bordered sc-filterable"><thead><tr><th>Code</th><th>Produit</th><th>Lot</th><th>Quantité</th><th>Emplacement</th><th>Péremption</th><th>Statut</th><th></th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::containers(0, true, 5000) as $row) {
            $html .= '<tr><td>'.self::html($row['code_interne']).'</td><td><a href="'.self::url(array('view' => 'product', 'id' => $row['produit_id'])).'">'.self::html($row['nom_commercial']).'</a></td><td>'.self::html($row['numero_lot']).'</td><td>'.self::quantity($row['quantite_courante']).' '.self::html($row['unite']).'</td><td>'.self::html($row['emplacement_code'].' - '.$row['emplacement_nom']).'</td><td>'.self::html($row['date_peremption']).'</td><td>'.self::html($row['statut']).'</td><td>';
            $html .= '<div class="sc-row-actions">';
            if ((string) $row['statut'] === 'en_stock' && StockChimiqueSecurity::canOperate()) {
                $html .= self::modalButton('sc-movement-modal', 'Mouvement', 'btn-primary btn-xs', array('contenant_id' => (int) $row['id']));
            }
            if ((string) $row['statut'] === 'en_stock' && StockChimiqueSecurity::canManage()) {
                $html .= self::modalButton(
                    'sc-container-evacuation-modal',
                    'Évacuer',
                    'btn-danger btn-xs',
                    array(
                        'contenant_id' => (int) $row['id'],
                        'container_label' => (string) $row['code_interne'].' — '.$row['nom_commercial'],
                    )
                );
            }
            if (StockChimiqueSecurity::canManage() && in_array((string) $row['statut'], array('vide', 'elimine', 'retourne'), true)) {
                $html .= self::archiveForm('archive_container', $row['id'], 'Archiver ce contenant ?');
            }
            $html .= '</div></td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function movementsPage($messages, $errors)
    {
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Mouvements</h2><span>';
        if (StockChimiqueSecurity::canOperate()) {
            $html .= self::modalButton('sc-movement-modal', 'Nouveau mouvement', 'btn-primary').' ';
        }
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/stock_chimique/export.php?type=movements">CSV</a></span></div>';
        if (StockChimiqueSecurity::canOperate()) {
            $html .= self::movementModal('movements');
        }
        $html .= self::filterInput('movements').'<div class="table-responsive"><table id="movements" class="table table-striped table-bordered sc-filterable"><thead><tr><th>Date</th><th>Type</th><th>Contenant</th><th>Produit</th><th>Quantité</th><th>Avant</th><th>Après</th><th>Emplacements</th><th>Auteur</th><th>Motif</th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::movements(0, 5000) as $row) {
            $places = $row['source_code'].($row['destination_code'] ? ' → '.$row['destination_code'] : '');
            $futureClass = (int) $row['date_effective'] >= strtotime('tomorrow') ? ' class="sc-future-movement"' : '';
            $html .= '<tr'.$futureClass.'><td>'.self::dateTime($row['date_effective']).'</td><td>'.self::html($row['type_mouvement']).'</td><td>'.self::html($row['code_interne']).'</td><td>'.self::html($row['nom_commercial']).'</td><td>'.self::quantity($row['quantite']).' '.self::html($row['unite']).'</td><td>'.self::quantity($row['quantite_avant']).'</td><td>'.self::quantity($row['quantite_apres']).'</td><td>'.self::html($places).'</td><td>'.self::html($row['created_by']).'</td><td>'.self::html($row['motif']).'</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function inventoriesPage($messages, $errors)
    {
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>Inventaires</h2><span>';
        if (StockChimiqueSecurity::canManage()) {
            $html .= self::modalButton('sc-inventory-modal', 'Ouvrir un inventaire', 'btn-primary');
        }
        $html .= '</span></div>';
        if (StockChimiqueSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'inventories')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="create_inventory">'
                .self::input('Libellé', 'libelle', 'Inventaire '.date('d/m/Y'), true)
                .self::select('Emplacement exact (vide = tous)', 'emplacement_id', self::locationOptions(StockChimiqueRepository::locations(false)), 0, true)
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-inventory-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Ouvrir</button></div></form>';
            $html .= self::modal('sc-inventory-modal', 'Ouvrir un inventaire', $form);
        }
        $html .= '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Inventaire</th><th>Emplacement</th><th>Ouverture</th><th>Progression</th><th>Conflits</th><th>Statut</th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::inventories(500) as $row) {
            $html .= '<tr><td><a href="'.self::url(array('view' => 'inventory', 'id' => $row['id'])).'">'.self::html($row['libelle']).'</a></td><td>'.self::html($row['emplacement_code'] ?: 'Tous').'</td><td>'.self::dateTime($row['opened_at']).'</td><td>'.(int) $row['comptees'].' / '.(int) $row['lignes'].'</td><td>'.(int) $row['conflits'].'</td><td>'.self::html($row['statut']).'</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function inventoryPage($id, $messages, $errors)
    {
        $inventory = StockChimiqueRepository::inventory($id);
        if (!$inventory) {
            return self::startPage($messages, array_merge($errors, array('Inventaire introuvable.'))).self::endPage();
        }
        $editable = (string) $inventory['statut'] === 'ouvert' && StockChimiqueSecurity::canManage();
        $actions = '<a class="btn btn-default" href="'.self::url(array('view' => 'inventories')).'">Retour</a>';
        if ((string) $inventory['statut'] === 'ouvert' && StockChimiqueSecurity::isAdmin()) {
            $actions .= ' <form class="sc-inline" method="post" action="'.self::url(array('view' => 'inventory', 'id' => $id)).'" onsubmit="return confirm(\'Annuler cet inventaire ouvert ? Les comptages saisis seront conservés en consultation, mais aucune correction ne sera appliquée.\')">'
                .self::csrf().'<input type="hidden" name="sc_action" value="cancel_inventory"><input type="hidden" name="inventaire_id" value="'.(int) $id.'">'
                .'<button class="btn btn-danger" type="submit">Annuler l inventaire</button></form>';
        }
        $html = self::startPage($messages, $errors).'<div class="sc-title-row"><h2>'.self::html($inventory['libelle']).'</h2><span>'.$actions.'</span></div>'
            .'<p>Statut : <strong>'.self::html($inventory['statut']).'</strong> — Emplacement : '.self::html($inventory['emplacement_code'] ?: 'Tous').'</p>';
        if ($editable) {
            $html .= '<form method="post" action="'.self::url(array('view' => 'inventory', 'id' => $id)).'">'.self::csrf().'<input type="hidden" name="sc_action" value="save_inventory"><input type="hidden" name="inventaire_id" value="'.(int) $id.'">';
        }
        $html .= '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Produit</th><th>Contenant</th><th>Emplacement</th><th>Attendu</th><th>Compté</th><th>Écart</th><th>État</th><th>Commentaire</th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::inventoryLines($id) as $line) {
            $html .= '<tr><td>'.self::html($line['nom_commercial']).'</td><td>'.self::html($line['code_interne']).'</td><td>'.self::html($line['emplacement_code']).'</td><td>'.self::quantity($line['quantite_attendue']).' '.self::html($line['unite']).'</td><td>';
            if ($editable) {
                $html .= '<input class="form-control" type="number" min="0" step="0.0001" name="counted['.(int) $line['id'].']" value="'.self::html($line['quantite_comptee']).'">';
            } else {
                $html .= self::quantity($line['quantite_comptee']);
            }
            $html .= '</td><td>'.self::quantity($line['ecart']).'</td><td>'.self::html($line['statut']).'</td><td>';
            if ($editable) {
                $html .= '<input class="form-control" name="comment['.(int) $line['id'].']" value="'.self::html($line['commentaire']).'">';
            } else {
                $html .= self::html($line['commentaire']);
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
        if ($editable) {
            $html .= '<button class="btn btn-primary" type="submit">Enregistrer les comptages</button></form>'
                .'<form method="post" action="'.self::url(array('view' => 'inventory', 'id' => $id)).'" style="margin-top:12px" onsubmit="return confirm(\'Appliquer les corrections et terminer cet inventaire ?\')">'.self::csrf().'<input type="hidden" name="sc_action" value="finalize_inventory"><input type="hidden" name="inventaire_id" value="'.(int) $id.'"><button class="btn btn-success" type="submit">Terminer et appliquer les écarts</button></form>';
        }
        return $html.self::endPage();
    }

    private static function notificationsPage($messages, $errors)
    {
        $html = self::startPage($messages, $errors).'<h2>Notifications</h2><p>Les notifications sont adressées aux gestionnaires disposant d’une adresse électronique active.</p>';
        if (!StockChimiqueConfig::notificationsEnabled()) {
            $html .= '<div class="alert alert-warning">Les notifications électroniques sont désactivées par l administrateur.</div>';
        } elseif (!StockChimiqueConfig::alertsEnabled()) {
            $html .= '<div class="alert alert-warning">Les alertes sont désactivées ; aucune notification ne peut être produite.</div>';
        } elseif (StockChimiqueSecurity::canManage()) {
            $sendForm = '<form method="post" action="'.self::url(array('view' => 'notifications')).'">'.self::csrf()
                .'<input type="hidden" name="sc_action" value="send_notifications">'
                .'<p>Confirmez l envoi des alertes non encore notifiées aux gestionnaires.</p>'
                .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-notification-send-modal\')">Annuler</button> '
                .'<button class="btn btn-primary" type="submit">Envoyer les alertes</button></div>'
                .'</form>';
            $html .= '<div class="sc-actions">'.self::modalButton('sc-notification-send-modal', 'Envoyer les alertes', 'btn-primary').'</div>';
            $html .= self::modal('sc-notification-send-modal', 'Envoyer les alertes non encore notifiées', $sendForm);
        }
        $showValidation = StockChimiqueSecurity::canManage();
        $html .= '<h3>Alertes actuelles</h3><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Type</th><th>Élément</th><th>Détail</th>'.($showValidation ? '<th>Validation FDS</th>' : '').'</tr></thead><tbody>';
        foreach (StockChimiqueRepository::alerts(1000) as $alert) {
            $html .= '<tr><td>'.self::html(self::alertLabel($alert['type'])).'</td><td>'.self::html($alert['label']).'</td><td>'.self::html($alert['detail']).'</td>';
            if ($showValidation) {
                $html .= '<td>'.self::fdsValidationForm($alert, 'notifications').'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        if (StockChimiqueSecurity::canManage()) {
            $html .= '<h3>Journal des envois</h3><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Destinataire</th><th>Type</th><th>Objet</th><th>État</th><th>Message</th></tr></thead><tbody>';
            foreach (StockChimiqueRepository::notificationLogs(1000) as $log) {
                $html .= '<tr><td>'.self::dateTime($log['sent_at']).'</td><td>'.self::html($log['login']).'</td><td>'.self::html($log['type_notification']).'</td><td>'.(int) $log['objet_id'].'</td><td>'.self::html($log['status']).'</td><td>'.self::html($log['message']).'</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }
        return $html.self::endPage();
    }

    private static function journalPage($messages, $errors)
    {
        if (!StockChimiqueSecurity::canManage()) {
            return self::startPage($messages, array_merge($errors, array('Accès refusé.'))).self::endPage();
        }
        $html = self::startPage($messages, $errors).'<h2>Journal</h2><div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Date</th><th>Événement</th><th>Objet</th><th>Résumé</th><th>Utilisateur</th></tr></thead><tbody>';
        foreach (StockChimiqueRepository::journal(1000) as $row) {
            $html .= '<tr><td>'.self::dateTime($row['created_at']).'</td><td>'.self::html($row['type_evenement']).'</td><td>'.self::html($row['type_objet'].' #'.$row['objet_id']).'</td><td>'.self::html($row['resume']).'</td><td>'.self::html($row['login']).'</td></tr>';
        }
        return $html.'</tbody></table></div>'.self::endPage();
    }

    private static function uploadDocument($source, $login)
    {
        $errors = array();
        if (!StockChimiqueConfig::documentsEnabled()) {
            return array('Le dépôt de documents est désactivé.');
        }
        if (!isset($_FILES['document_file']) || !is_array($_FILES['document_file'])) {
            return array('Sélectionnez un fichier.');
        }
        $file = $_FILES['document_file'];
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('Erreur de téléversement : code '.(int) $file['error'].'.');
        }
        $originalName = self::sanitizeFileName($file['name']);
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $type = isset($source['type_document']) ? (string) $source['type_document'] : '';
        if ($type === 'fds' && $extension !== 'pdf') {
            $errors[] = 'Une FDS doit être déposée au format PDF.';
        } elseif ($type !== 'fds' && !in_array($extension, StockChimiqueConfig::documentExtensions(), true)) {
            $errors[] = 'Extension non autorisée.';
        }
        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0 || $size > StockChimiqueConfig::documentMaxBytes()) {
            $errors[] = 'Taille de fichier invalide.';
        }
        if ($type === 'fds' && (!isset($source['date_revision']) || trim((string) $source['date_revision']) === '')) {
            $errors[] = 'La date de révision est obligatoire pour une FDS.';
        }
        if (count($errors) > 0) {
            return $errors;
        }
        if (!StockChimiqueRepository::ensureDocumentStorage()) {
            return array('Répertoire documentaire non accessible en écriture.');
        }
        $storedName = StockChimiqueSecurity::requestToken();
        $path = StockChimiqueRepository::documentPath($storedName);
        if ($path === '' || !move_uploaded_file((string) $file['tmp_name'], $path)) {
            return array('Le fichier n a pas pu être enregistré.');
        }
        $actualSize = (int) @filesize($path);
        if ($actualSize <= 0 || $actualSize > StockChimiqueConfig::documentMaxBytes()) {
            @unlink($path);
            return array('Taille du fichier enregistré invalide.');
        }
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        }
        if ($type === 'fds' && $mime !== 'application/pdf') {
            @unlink($path);
            return array('Le contenu du fichier ne semble pas être un PDF.');
        }
        $id = StockChimiqueRepository::addDocument(array(
            'produit_id' => isset($source['produit_id']) ? (int) $source['produit_id'] : 0,
            'type_document' => $type,
            'langue' => isset($source['langue']) ? $source['langue'] : 'fr',
            'emetteur' => isset($source['emetteur']) ? $source['emetteur'] : '',
            'date_revision' => isset($source['date_revision']) ? $source['date_revision'] : '',
            'numero_version' => isset($source['numero_version']) ? $source['numero_version'] : '',
            'est_courant' => isset($source['est_courant']) ? 1 : 0,
            'description' => isset($source['description']) ? $source['description'] : '',
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'taille' => $actualSize,
            'sha256' => hash_file('sha256', $path),
        ), $login);
        if ($id <= 0) {
            @unlink($path);
            return array('Le document n a pas pu être enregistré en base.');
        }
        return array();
    }

    private static function startPage($messages, $errors)
    {
        $html = '<section id="stock-chimique">'.self::assets()
            .'<div class="sc-title-row"><h1>'.self::html(StockChimiqueConfig::displayName()).'</h1><span class="label label-info">'.self::html(StockChimiqueSecurity::role()).'</span></div>'
            .self::toolbar();
        foreach ($messages as $message) {
            $html .= '<div class="alert alert-success">'.self::html($message).'</div>';
        }
        foreach ($errors as $error) {
            $html .= '<div class="alert alert-danger">'.self::html($error).'</div>';
        }
        return $html;
    }

    private static function endPage()
    {
        return '</section>';
    }

    private static function toolbar()
    {
        $links = array(
            array('', 'Tableau de bord'),
            array('products', 'Produits'),
            array('containers', 'Contenants'),
            array('movements', 'Mouvements'),
            array('locations', 'Emplacements'),
            array('suppliers', 'Fournisseurs'),
            array('inventories', 'Inventaires'),
            array('notifications', 'Notifications'),
        );
        if (StockChimiqueSecurity::canManage()) {
            $links[] = array('journal', 'Journal');
        }
        $html = '<nav class="sc-toolbar">';
        foreach ($links as $link) {
            $params = $link[0] === '' ? array() : array('view' => $link[0]);
            $html .= '<a class="btn btn-default btn-sm" href="'.self::url($params).'">'.self::html($link[1]).'</a> ';
        }
        if (StockChimiqueSecurity::isAdmin()) {
            $html .= '<a class="btn btn-warning btn-sm" href="'.self::url(array('admin' => 1)).'">Administration</a>';
        }
        return $html.'</nav>';
    }

    private static function modalButton($modalId, $label, $class = 'btn-primary', $values = array())
    {
        $encoded = json_encode((array) $values, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        return '<button class="btn '.self::html($class).'" type="button" onclick="stockChimiqueOpenModal('
            .self::html(json_encode($modalId)).','.self::html($encoded).');return false;">'.self::html($label).'</button>';
    }

    private static function modal($modalId, $title, $body, $autoOpen = false)
    {
        $modalJson = json_encode((string) $modalId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        return '<div id="'.self::html($modalId).'" class="sc-modal" role="dialog" aria-modal="true" aria-labelledby="'.self::html($modalId).'-title">'
            .'<div class="modal-dialog modal-lg" role="document"><div class="modal-content">'
            .'<div class="modal-header"><button type="button" class="close" aria-label="Fermer" onclick="stockChimiqueCloseModal('.self::html($modalJson).')"><span aria-hidden="true">&times;</span></button>'
            .'<h4 class="modal-title" id="'.self::html($modalId).'-title">'.self::html($title).'</h4></div>'
            .'<div class="modal-body">'.$body.'</div></div></div></div>'
            .($autoOpen ? '<script>setTimeout(function(){stockChimiqueOpenModal('.$modalJson.',{});},0);</script>' : '');
    }

    private static function movementModal($returnView, $productId = 0)
    {
        $types = StockChimiqueRepository::movementTypes();
        if (!StockChimiqueSecurity::canManage()) {
            foreach (array('correction_plus', 'correction_moins', 'elimination', 'retour_fournisseur') as $restricted) {
                unset($types[$restricted]);
            }
        }
        $body = '<form method="post" action="'.self::url($returnView === 'product' ? array('view' => 'product', 'id' => $productId) : array('view' => $returnView)).'">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="create_movement"><input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'"><input type="hidden" name="request_token" value="'.self::html(StockChimiqueSecurity::requestToken()).'">'
            .self::select('Contenant', 'contenant_id', self::containerOptions(), '', false, true)
            .'<div class="row"><div class="col-md-6">'.self::select('Type', 'type_mouvement', $types, '', false, true).'</div>'
            .'<div class="col-md-6">'.self::input('Quantité (ignorée pour transfert/solde)', 'quantite', '', false, 'number', '0.0001').'</div></div>'
            .'<div class="row"><div class="col-md-6">'.self::select('Destination du transfert', 'emplacement_destination_id', self::locationOptions(StockChimiqueRepository::locations(false)), 0, true).'</div>'
            .'<div class="col-md-6">'.self::input('Date effective', 'date_effective', date('Y-m-d'), false, 'date').'</div></div>'
            .self::textarea('Motif / commentaire', 'motif', '')
            .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-movement-modal\')">Annuler</button> '
            .'<button class="btn btn-primary" type="submit">Enregistrer le mouvement</button></div></form>';
        return self::modal('sc-movement-modal', 'Nouveau mouvement', $body);
    }

    private static function productContainerModal($productId, $productName)
    {
        $body = '<form method="post" action="'.self::url(array('view' => 'product', 'id' => $productId)).'">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="create_container">'
            .'<input type="hidden" name="return_view" value="product">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'">'
            .'<input type="hidden" name="request_token" value="'.self::html(StockChimiqueSecurity::requestToken()).'">'
            .'<div class="form-group"><label>Produit</label><input class="form-control" value="'.self::html($productName).'" readonly></div>'
            .'<div class="row"><div class="col-md-6">'.self::select('Emplacement', 'emplacement_id', self::locationOptions(StockChimiqueRepository::locations(false)), '', false, true).'</div>'
            .'<div class="col-md-6">'.self::select('Fournisseur réel', 'fournisseur_id', self::supplierOptions(), 0, true).'</div></div>'
            .'<div class="row"><div class="col-md-3">'.self::input('Code interne (auto si vide)', 'code_interne', '').'</div>'
            .'<div class="col-md-3">'.self::input('Lot', 'numero_lot', '').'</div>'
            .'<div class="col-md-3">'.self::input('Conditionnement', 'conditionnement', '').'</div>'
            .'<div class="col-md-3">'.self::input('Quantité', 'quantite', '', true, 'number', '0.0001').'</div></div>'
            .'<div class="row"><div class="col-md-4">'.self::input('Réception', 'date_reception', date('Y-m-d'), false, 'date').'</div>'
            .'<div class="col-md-4">'.self::input('Ouverture', 'date_ouverture', '', false, 'date').'</div>'
            .'<div class="col-md-4">'.self::input('Péremption', 'date_peremption', '', false, 'date').'</div></div>'
            .self::textarea('Notes', 'notes', '')
            .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-product-container-modal\')">Annuler</button> '
            .'<button class="btn btn-success" type="submit">Ajouter le contenant</button></div></form>';
        return self::modal('sc-product-container-modal', 'Ajouter un contenant à '.$productName, $body);
    }

    private static function documentEditModal($productId)
    {
        $body = '<form method="post" action="'.self::url(array('view' => 'product', 'id' => $productId)).'">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="update_document">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'">'
            .'<input type="hidden" name="document_id" value="">'
            .self::input('Nom affiché', 'original_name', '', true)
            .'<div class="row"><div class="col-md-3">'.self::select('Type', 'type_document', StockChimiqueRepository::documentTypes(), 'autre', false, true).'</div>'
            .'<div class="col-md-2">'.self::input('Langue', 'langue', 'fr', true).'</div>'
            .'<div class="col-md-3">'.self::input('Date de révision FDS', 'date_revision', '', false, 'date').'</div>'
            .'<div class="col-md-4">'.self::input('Version', 'numero_version', '').'</div></div>'
            .self::input('Émetteur', 'emetteur', '')
            .'<div class="form-group"><label><input type="checkbox" name="est_courant" value="1"> Version courante FDS</label></div>'
            .self::textarea('Description', 'description', '')
            .'<p class="help-block">Le fichier n est pas remplacé. Si le type est FDS, une date de révision est obligatoire et le fichier doit rester un PDF.</p>'
            .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-document-edit-modal\')">Annuler</button> '
            .'<button class="btn btn-primary" type="submit">Enregistrer les informations</button></div></form>';
        return self::modal('sc-document-edit-modal', 'Modifier les informations du document', $body);
    }

    private static function containerEvacuationModal($returnView, $productId = 0)
    {
        $body = '<form method="post" action="'.self::url($returnView === 'product' ? array('view' => 'product', 'id' => $productId) : array('view' => 'containers')).'">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="evacuate_container"><input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'"><input type="hidden" name="contenant_id" value="">'
            .'<input type="hidden" name="request_token" value="'.self::html(StockChimiqueSecurity::requestToken()).'">'
            .self::input('Contenant', 'container_label', '', false)
            .self::input('Date d évacuation', 'date_effective', date('Y-m-d'), true, 'date')
            .self::textarea('Motif / bordereau / filière de déchets', 'motif', '')
            .'<p class="help-block">La quantité entière sera soldée et le contenant passera à l état éliminé.</p>'
            .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-container-evacuation-modal\')">Annuler</button> '
            .'<button class="btn btn-danger" type="submit">Confirmer l évacuation</button></div></form>';
        return self::modal('sc-container-evacuation-modal', 'Évacuer un contenant vers les déchets chimiques', $body);
    }

    private static function productEvacuationModal($returnView, $productId = 0, $productName = '')
    {
        $body = '<form method="post" action="'.self::url($returnView === 'product' ? array('view' => 'product', 'id' => $productId) : array('view' => 'products')).'">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="evacuate_product"><input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'"><input type="hidden" name="request_token" value="'.self::html(StockChimiqueSecurity::requestToken()).'">'
            .self::input('Produit', 'product_label', $productName, false)
            .self::input('Date d évacuation', 'date_effective', date('Y-m-d'), true, 'date')
            .self::textarea('Motif / bordereau / filière de déchets', 'motif', '')
            .'<div class="alert alert-danger">Tous les contenants encore en stock pour ce produit seront soldés et marqués comme éliminés.</div>'
            .'<div class="text-right"><button class="btn btn-default" type="button" onclick="stockChimiqueCloseModal(\'sc-product-evacuation-modal\')">Annuler</button> '
            .'<button class="btn btn-danger" type="submit">Évacuer tous les contenants</button></div></form>';
        return self::modal('sc-product-evacuation-modal', 'Évacuer un produit vers les déchets chimiques', $body);
    }

    private static function assets()
    {
        return '<style>'
            .'#stock-chimique{width:100%;max-width:none;box-sizing:border-box;padding-bottom:30px}#stock-chimique *,#menu-compte .sc-account-btn{box-sizing:border-box;}#menu-compte .sc-account-btn{display:block;width:100%;max-width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}#stock-chimique .sc-toolbar{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0 18px}'
            .'#stock-chimique .sc-title-row{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}'
            .'#stock-chimique .sc-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:15px 0}'
            .'#stock-chimique .sc-card{color:#fff;padding:12px;border-radius:5px;text-align:center}#stock-chimique .sc-card strong{display:block;font-size:25px}'
            .'#stock-chimique .sc-actions{margin:12px 0}#stock-chimique form.sc-inline{display:inline-block;margin:0}'
            .'#stock-chimique .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}#stock-chimique table{width:100%}#stock-chimique td,#stock-chimique th{vertical-align:middle;overflow-wrap:anywhere}#stock-chimique .sc-filter{max-width:420px;margin:8px 0}'
            .'#stock-chimique textarea{resize:vertical}#stock-chimique .sc-modal{display:none;position:fixed;z-index:1050;inset:0;overflow:auto;background:rgba(0,0,0,.45);padding:30px 10px}'
            .'#stock-chimique .sc-modal .modal-dialog{width:100%;max-width:1120px;margin:0 auto}#stock-chimique .sc-modal .modal-content{border-radius:5px;box-shadow:0 8px 24px rgba(0,0,0,.3)}'
            .'#stock-chimique .sc-modal .modal-header{background:#f5f5f5}#stock-chimique .sc-row-actions{display:flex;flex-wrap:wrap;gap:4px}'
            .'#stock-chimique table.table-striped tbody tr.sc-future-movement>td{background:#f8c471!important;color:#5d3a00}'
            .'@media (max-width:767px){#stock-chimique .sc-toolbar .btn,#stock-chimique .sc-actions .btn{width:100%;}#stock-chimique .sc-filter{max-width:none;width:100%;}#stock-chimique .sc-modal{padding:10px;}#stock-chimique .sc-modal .modal-dialog{max-width:none;}#stock-chimique table[data-responsive-table="1"],#stock-chimique table[data-responsive-table="1"] thead,#stock-chimique table[data-responsive-table="1"] tbody,#stock-chimique table[data-responsive-table="1"] tr,#stock-chimique table[data-responsive-table="1"] th,#stock-chimique table[data-responsive-table="1"] td{display:block;width:100%;}#stock-chimique table[data-responsive-table="1"] thead{display:none;}#stock-chimique table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}#stock-chimique table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}#stock-chimique table[data-responsive-table="1"] td:last-child{border-bottom:0;}#stock-chimique table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}}'
            .'body.modal-open{overflow:hidden}</style>'
            .'<script>'
            .'window.stockChimiqueOpenModal=function(id,values){var modal=document.getElementById(id);if(!modal)return false;values=values||{};var form=modal.querySelector("form");if(form){Object.keys(values).forEach(function(name){var field=form.querySelector("[name=\\""+name.replace(/"/g,"\\\\\\"")+"\\"]");if(field){if(field.type==="checkbox"){field.checked=!!values[name];}else{field.value=values[name];}}});}modal.style.display="block";document.body.classList.add("modal-open");var focus=modal.querySelector("input:not([type=hidden]),select,textarea,button");if(focus){setTimeout(function(){focus.focus();},0);}return false;};'
            .'window.stockChimiqueCloseModal=function(id){var modal=document.getElementById(id);if(modal){modal.style.display="none";}if(!document.querySelector("#stock-chimique .sc-modal[style*=\\"display: block\\"]")){document.body.classList.remove("modal-open");}return false;};'
            .'window.stockChimiqueFilter=function(input,id){var q=(input.value||"").toLowerCase(),t=document.getElementById(id);if(!t)return;var r=t.tBodies[0].rows;for(var i=0;i<r.length;i++){r[i].style.display=(r[i].textContent||"").toLowerCase().indexOf(q)>=0?"":"none";}};'
            .'window.stockChimiqueSort=function(table,index,ascending){var body=table.tBodies[0],rows=[].slice.call(body.rows);rows.sort(function(a,b){var x=(a.cells[index].textContent||"").trim(),y=(b.cells[index].textContent||"").trim(),xn=parseFloat(x.replace(/\\s/g,"").replace(",",".")),yn=parseFloat(y.replace(/\\s/g,"").replace(",","."));if(!isNaN(xn)&&!isNaN(yn)){return ascending?xn-yn:yn-xn;}return ascending?x.localeCompare(y):y.localeCompare(x);});for(var i=0;i<rows.length;i++){body.appendChild(rows[i]);}};'
            .'window.stockChimiqueInitSort=function(){var tables=document.querySelectorAll("#stock-chimique table");for(var t=0;t<tables.length;t++){(function(table){var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(heads.length){table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});}if(!table.classList.contains("sc-filterable")){return;}for(var i=0;i<heads.length;i++){(function(index){heads[index].style.cursor="pointer";heads[index].title="Cliquer pour trier";heads[index].addEventListener("click",function(){var asc=this.getAttribute("data-sc-asc")!=="1";this.setAttribute("data-sc-asc",asc?"1":"0");stockChimiqueSort(table,index,asc);});})(i);}})(tables[t]);}};'
            .'window.addEventListener("click",function(event){if(event.target&&event.target.classList&&event.target.classList.contains("sc-modal")){stockChimiqueCloseModal(event.target.id);}});'
            .'document.addEventListener("keydown",function(event){if(event.key==="Escape"){var modals=document.querySelectorAll("#stock-chimique .sc-modal");for(var i=0;i<modals.length;i++){if(modals[i].style.display==="block"){stockChimiqueCloseModal(modals[i].id);}}}});'
            .'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",stockChimiqueInitSort);}else{stockChimiqueInitSort();}'
            .'</script>';
    }

    private static function card($label, $value, $color)
    {
        return '<div class="sc-card" style="background:'.self::html($color).'"><strong>'.(int) $value.'</strong>'.self::html($label).'</div>';
    }

    private static function input($label, $name, $value, $required = false, $type = 'text', $step = '')
    {
        return '<div class="form-group"><label>'.self::html($label).'</label><input class="form-control" type="'.self::html($type).'" name="'.self::html($name).'" value="'.self::html($value).'"'.($required ? ' required' : '').($step !== '' ? ' step="'.self::html($step).'" min="0"' : '').'></div>';
    }

    private static function textarea($label, $name, $value)
    {
        return '<div class="form-group"><label>'.self::html($label).'</label><textarea class="form-control" name="'.self::html($name).'" rows="2">'.self::html($value).'</textarea></div>';
    }

    private static function select($label, $name, $options, $selected, $empty = false, $required = false)
    {
        $html = '<div class="form-group"><label>'.self::html($label).'</label><select class="form-control" name="'.self::html($name).'"'.($required ? ' required' : '').'>';
        if ($empty) {
            $html .= '<option value="0">—</option>';
        }
        foreach ($options as $key => $option) {
            $html .= '<option value="'.self::html($key).'"'.((string) $key === (string) $selected ? ' selected' : '').'>'.self::html($option).'</option>';
        }
        return $html.'</select></div>';
    }

    private static function supplierOptions()
    {
        $options = array();
        foreach (StockChimiqueRepository::suppliers(false) as $supplier) {
            $options[$supplier['id']] = $supplier['nom'];
        }
        return $options;
    }

    private static function locationOptions($locations, $excludeId = 0)
    {
        $options = array();
        foreach ($locations as $location) {
            if ((int) $location['id'] !== (int) $excludeId && (int) $location['actif'] === 1) {
                $options[$location['id']] = $location['code'].' — '.(isset($location['chemin']) ? $location['chemin'] : $location['nom']);
            }
        }
        return $options;
    }

    private static function productOptions()
    {
        $options = array();
        foreach (StockChimiqueRepository::products(false, 2000) as $product) {
            $options[$product['id']] = ($product['reference_interne'] ? $product['reference_interne'].' — ' : '').$product['nom_commercial'].' ['.$product['unite_stock'].']';
        }
        return $options;
    }

    private static function containerOptions()
    {
        $options = array();
        foreach (StockChimiqueRepository::containers(0, false, 5000) as $container) {
            $options[$container['id']] = $container['code_interne'].' — '.$container['nom_commercial'].' — '.self::quantity($container['quantite_courante']).' '.$container['unite'].' — '.$container['emplacement_code'];
        }
        return $options;
    }

    private static function archiveForm($action, $id, $confirmation)
    {
        return '<form class="sc-inline" method="post" action="'.self::url(array('view' => isset($_GET['view']) ? $_GET['view'] : '')).'" onsubmit="return confirm('.self::html(json_encode($confirmation)).')">'.self::csrf().'<input type="hidden" name="sc_action" value="'.self::html($action).'"><input type="hidden" name="id" value="'.(int) $id.'"><button class="btn btn-danger btn-xs" type="submit">Archiver</button></form>';
    }

    private static function filterInput($id)
    {
        return '<input class="form-control sc-filter" placeholder="Filtrer le tableau…" oninput="stockChimiqueFilter(this,\''.self::html($id).'\')">';
    }

    private static function resultMessage($result, $success, &$messages, &$errors)
    {
        if (is_array($result) && !empty($result['ok'])) {
            $messages[] = $success;
        } else {
            $errors[] = is_array($result) && !empty($result['error']) ? $result['error'] : 'Opération impossible.';
        }
    }

    private static function booleanMessage($ok, $success, $error, &$messages, &$errors)
    {
        if ($ok) {
            $messages[] = $success;
        } else {
            $errors[] = $error;
        }
    }

    private static function alertLabel($type)
    {
        $labels = array('stock_faible' => 'Stock faible', 'peremption_proche' => 'Péremption proche', 'perime' => 'Périmé', 'fds_manquante' => 'FDS manquante', 'fds_a_verifier' => 'FDS à vérifier');
        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    private static function fdsValidationForm($alert, $returnView, $productId = 0)
    {
        if (
            !StockChimiqueSecurity::canManage()
            || !is_array($alert)
            || (string) $alert['type'] !== 'fds_a_verifier'
            || (int) $alert['document_id'] <= 0
        ) {
            return '';
        }
        $actionParams = array();
        if ($returnView === 'notifications') {
            $actionParams = array('view' => 'notifications');
        } elseif ($returnView === 'product') {
            $actionParams = array('view' => 'product', 'id' => $productId);
        }
        return '<form method="post" action="'.self::url($actionParams).'" '
            .'onsubmit="return confirm(\'Confirmer que cette FDS a été contrôlée ?\')">'.self::csrf()
            .'<input type="hidden" name="sc_action" value="validate_fds_alert">'
            .'<input type="hidden" name="document_id" value="'.(int) $alert['document_id'].'">'
            .'<input type="hidden" name="produit_id" value="'.(int) $productId.'">'
            .'<input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<button class="btn btn-success btn-xs" type="submit">Valider la FDS</button></form>';
    }

    private static function badge($label, $type)
    {
        $class = in_array($type, array('perime', 'stock_faible', 'fds_manquante'), true) ? 'danger' : 'warning';
        return '<span class="label label-'.$class.'">'.self::html($label).'</span>';
    }

    private static function sanitizeFileName($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
        return substr(trim((string) $name), 0, 255);
    }

    private static function value($array, $key, $default = '')
    {
        return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
    }

    private static function quantity($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        return rtrim(rtrim(number_format((float) $value, 4, ',', ' '), '0'), ',');
    }

    private static function dateTime($timestamp)
    {
        return (int) $timestamp > 0 ? date('d/m/Y H:i', (int) $timestamp) : '';
    }

    private static function csrf()
    {
        return StockChimiqueSecurity::field();
    }

    private static function url($params)
    {
        $query = array_merge(array('pc' => StockChimiqueConfig::MODULE), $params);
        return 'compte.php?'.http_build_query($query, '', '&');
    }

    private static function accountUrl($params)
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?'.http_build_query($params, '', '&');
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function multiline($value)
    {
        $value = trim((string) $value);
        return $value === '' ? '<span class="text-muted">Non renseigné</span>' : nl2br(self::html($value));
    }
}
