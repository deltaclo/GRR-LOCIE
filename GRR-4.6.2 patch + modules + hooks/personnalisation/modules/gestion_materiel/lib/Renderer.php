<?php

class GestionMaterielRenderer
{
    public static function accountMenu()
    {
        $login = self::currentLogin();
        if (!GestionMaterielConfig::isEnabled() || !self::canAccessModule($login)) {
            return '';
        }

        return '<br><br><a href="compte.php?pc=gestion_materiel" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 gm-account-btn">'
            .self::html(GestionMaterielConfig::displayName())
            .'</a>';
    }

    public static function statusSummaryLinks()
    {
        $login = self::currentLogin();
        if (!GestionMaterielConfig::isEnabled() || !self::canAccessModule($login)) {
            return '';
        }

        $days = GestionMaterielConfig::upcomingDays();
        $canViewAll = self::canManageModule($login);
        $counts = GestionMaterielRepository::deadlineAlertCountsForUser($login, $canViewAll, $days);
        $links = array();

        if ((int) $counts['total_overdue'] > 0) {
            $count = (int) $counts['total_overdue'];
            $links[] = '<a href="'.self::html(self::accountUrl(array('pc' => GestionMaterielConfig::MODULE)).'#gm-alerts').'" style="'.self::statusLinkStyle('overdue').'">'
                .self::html($count.' echeance'.($count > 1 ? 's' : '').' materiel en retard').'</a>';
        }

        if ((int) $counts['total_upcoming'] > 0) {
            $count = (int) $counts['total_upcoming'];
            $links[] = '<a href="'.self::html(self::accountUrl(array('pc' => GestionMaterielConfig::MODULE)).'#gm-alerts').'" style="'.self::statusLinkStyle('upcoming').'">'
                .self::html($count.' echeance'.($count > 1 ? 's' : '').' materiel a venir').'</a>';
        }

        if (count($links) === 0) {
            return '';
        }

        return '<p class="gestion-materiel-status" style="text-align:center;">'.implode('<br>', $links).'</p>';
    }

    public static function accountPage()
    {
        $pc = isset($_GET['pc']) ? (string) $_GET['pc'] : '';
        if ($pc !== GestionMaterielConfig::MODULE) {
            return '';
        }

        if (!GestionMaterielConfig::isEnabled()) {
            return '<div class="alert alert-warning">Module desactive.</div>';
        }

        GestionMaterielRepository::ensureTables();
        $login = self::currentLogin();
        if (!self::canAccessModule($login)) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        if (isset($_GET['admin']) && $_GET['admin'] === '1') {
            if (!self::isAdmin($login)) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            return self::renderEmbeddedAdminPage();
        }

        $messages = array();
        $errors = array();
        $formValues = GestionMaterielRepository::emptyItemValues();
        $editValues = null;
        $actionValues = GestionMaterielRepository::emptyActionValues();
        $documentValues = array('type_document' => 'autre', 'description' => '');
        $groupValues = GestionMaterielRepository::emptyGroupValues();
        $openCreateModal = false;
        $openEditModal = false;
        $openActionModal = false;
        $openDocumentModal = false;
        $openAssignedUsersModal = false;
        $openGroupModal = false;
        $openGroupItemsModal = false;
        $openNotificationsModal = false;
        $view = isset($_GET['view']) ? (string) $_GET['view'] : '';
        $itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = isset($_POST['gm_action']) ? (string) $_POST['gm_action'] : '';
            if ($action === 'create_item') {
                if (!self::canCreateItem()) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $formValues = GestionMaterielRepository::normalizeItemValues($_POST);
                    $errors = GestionMaterielRepository::validateItemValues($formValues);
                    $openCreateModal = count($errors) > 0;
                    if (count($errors) === 0) {
                        if (GestionMaterielRepository::createItem($formValues, $login)) {
                            $messages[] = 'Materiel ajoute.';
                            $formValues = GestionMaterielRepository::emptyItemValues();
                        } else {
                            $errors[] = 'Erreur lors de l enregistrement du materiel.';
                            $openCreateModal = true;
                        }
                    }
                }
            }

            if ($action === 'create_group') {
                $view = 'groups';
                if (!self::canManageModule()) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $groupValues = GestionMaterielRepository::normalizeGroupValues($_POST);
                    $errors = GestionMaterielRepository::validateGroupValues($groupValues);
                    $openGroupModal = count($errors) > 0;
                    if (count($errors) === 0) {
                        if (GestionMaterielRepository::createGroup($groupValues, $login) > 0) {
                            $messages[] = 'Groupe ajoute.';
                            $groupValues = GestionMaterielRepository::emptyGroupValues();
                        } else {
                            $errors[] = 'Erreur lors de l enregistrement du groupe.';
                            $openGroupModal = true;
                        }
                    }
                }
            }

            if ($action === 'save_group_items') {
                $itemId = isset($_POST['group_id']) ? (int) $_POST['group_id'] : 0;
                $view = 'group';
                if (!self::canManageModule()) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $groupItemIds = isset($_POST['group_item_id']) ? $_POST['group_item_id'] : array();
                    if (GestionMaterielRepository::setGroupItems($itemId, $groupItemIds)) {
                        $messages[] = 'Materiels du groupe enregistres.';
                    } else {
                        $errors[] = 'Erreur lors de l enregistrement des materiels du groupe.';
                        $openGroupItemsModal = true;
                    }
                }
            }

            if ($action === 'create_action') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $view = 'item';

                if (!self::canEditItem($itemId)) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $actionValues = GestionMaterielRepository::normalizeActionValues($_POST);
                    $errors = GestionMaterielRepository::validateActionValues($itemId, $actionValues);
                    $openActionModal = count($errors) > 0;
                    if (count($errors) === 0) {
                        if (GestionMaterielRepository::createAction($itemId, $actionValues, $login)) {
                            $messages[] = 'Action ajoutee.';
                            $actionValues = GestionMaterielRepository::emptyActionValues();
                        } else {
                            $errors[] = 'Erreur lors de l enregistrement de l action.';
                            $openActionModal = true;
                        }
                    }
                }
            }

            if ($action === 'upload_document') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $view = 'item';
                $documentValues = array(
                    'type_document' => isset($_POST['type_document']) ? trim((string) $_POST['type_document']) : 'autre',
                    'description' => isset($_POST['description']) ? trim((string) $_POST['description']) : '',
                );

                if (!self::canEditItem($itemId)) {
                    $errors[] = 'Acces refuse.';
                } elseif (!GestionMaterielConfig::documentsEnabled()) {
                    $errors[] = 'L ajout de documents est desactive.';
                } else {
                    $uploadErrors = self::uploadDocument($itemId, $documentValues, $login);
                    foreach ($uploadErrors as $uploadError) {
                        $errors[] = $uploadError;
                    }
                    if (count($uploadErrors) === 0) {
                        $messages[] = 'Document ajoute.';
                        $documentValues = array('type_document' => 'autre', 'description' => '');
                    } else {
                        $openDocumentModal = true;
                    }
                }
            }

            if ($action === 'delete_document') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $documentId = isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0;
                $view = 'item';
                $document = GestionMaterielRepository::document($documentId);

                if (!self::canEditItem($itemId)) {
                    $errors[] = 'Acces refuse.';
                } elseif (!$document || (int) $document['item_id'] !== $itemId) {
                    $errors[] = 'Document introuvable.';
                } elseif (!isset($_POST['delete_confirmed']) || (string) $_POST['delete_confirmed'] !== '1') {
                    $errors[] = 'La suppression doit etre confirmee.';
                } elseif (GestionMaterielRepository::deleteDocument($documentId)) {
                    $messages[] = 'Document supprime.';
                } else {
                    $errors[] = 'Erreur lors de la suppression du document.';
                }
            }

            if ($action === 'save_assigned_users') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $view = 'item';

                if (!self::canManageModule() || !self::canEditItem($itemId)) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $assignedLogins = isset($_POST['assigned_login']) ? $_POST['assigned_login'] : array();
                    $notifyMaintenance = isset($_POST['notify_maintenance']) ? $_POST['notify_maintenance'] : array();
                    $notifyEtalonnage = isset($_POST['notify_etalonnage']) ? $_POST['notify_etalonnage'] : array();
                    $login = function_exists('getUserName') ? (string) getUserName() : '';

                    if (GestionMaterielRepository::setAssignedUsers($itemId, $assignedLogins, $notifyMaintenance, $notifyEtalonnage, $login)) {
                        $messages[] = 'Utilisateurs assignes enregistres.';
                    } else {
                        $errors[] = 'Erreur lors de l enregistrement des utilisateurs assignes.';
                        $openAssignedUsersModal = true;
                    }
                }
            }

            if ($action === 'send_notifications') {
                $view = 'notifications';

                if (!self::canManageModule()) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $days = isset($_POST['notification_days']) ? (int) $_POST['notification_days'] : GestionMaterielConfig::upcomingDays();
                    $result = GestionMaterielNotification::sendDueNotifications($days);
                    $messages[] = 'Notifications envoyees : '.(int) $result['sent'].'. Deja envoyees ignorees : '.(int) $result['skipped'].'.';
                    foreach ($result['errors'] as $error) {
                        $errors[] = $error;
                    }
                }
            }

            if ($action === 'update_item') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $view = 'item';
                $openEditModal = true;

                if (!self::canEditItem($itemId)) {
                    $errors[] = 'Acces refuse.';
                } else {
                    $editValues = GestionMaterielRepository::normalizeItemValues($_POST);
                    $errors = GestionMaterielRepository::validateItemValues($editValues);
                    if (count($errors) === 0) {
                        if (GestionMaterielRepository::updateItem($itemId, $editValues, $login)) {
                            $messages[] = 'Materiel modifie.';
                            $editValues = null;
                            $openEditModal = false;
                            $view = 'item';
                        } else {
                            $errors[] = 'Erreur lors de la modification du materiel.';
                        }
                    }
                }
            }

            if ($action === 'delete_item') {
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $returnView = isset($_POST['return_view']) ? (string) $_POST['return_view'] : '';
                $view = $returnView === 'archives' ? 'archives' : '';

                if (!self::canManageModule()) {
                    $errors[] = 'Acces refuse.';
                } elseif (!isset($_POST['delete_confirmed']) || (string) $_POST['delete_confirmed'] !== '1') {
                    $errors[] = 'La suppression doit etre confirmee.';
                } elseif (GestionMaterielRepository::deleteItem($itemId)) {
                    $messages[] = 'Materiel et donnees associees supprimes.';
                    $itemId = 0;
                } else {
                    $errors[] = 'Erreur lors de la suppression du materiel.';
                }
            }

            if ($action === 'delete_action') {
                $actionId = isset($_POST['action_id']) ? (int) $_POST['action_id'] : 0;
                $returnView = isset($_POST['return_view']) ? (string) $_POST['return_view'] : 'actions';
                $itemId = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
                $view = $returnView === 'item' ? 'item' : 'actions';

                if (!self::isAdmin()) {
                    $errors[] = 'Acces refuse.';
                } elseif (!isset($_POST['delete_confirmed']) || (string) $_POST['delete_confirmed'] !== '1') {
                    $errors[] = 'La suppression doit etre confirmee.';
                } elseif (GestionMaterielRepository::deleteAction($actionId)) {
                    $messages[] = 'Action supprimee.';
                } else {
                    $errors[] = 'Erreur lors de la suppression de l action.';
                }
            }
        }

        if ($view === 'actions') {
            return self::actionsPage($messages, $errors);
        }

        if ($view === 'notifications') {
            return self::notificationsPage($messages, $errors, $openNotificationsModal);
        }

        if ($view === 'archives') {
            return self::archivesPage($messages, $errors);
        }

        if ($view === 'groups') {
            return self::groupsPage($messages, $errors, $groupValues, $openGroupModal);
        }

        if ($view === 'group') {
            $openGroupItemsModal = $openGroupItemsModal
                || (isset($_GET['items']) && (string) $_GET['items'] === 'edit');

            return self::groupPage($itemId, $messages, $errors, $openGroupItemsModal);
        }

        if ($view === 'item') {
            $openActionModal = $openActionModal
                || (isset($_GET['action']) && (string) $_GET['action'] === 'add');
            $openDocumentModal = $openDocumentModal
                || (isset($_GET['document']) && (string) $_GET['document'] === 'add');
            $openAssignedUsersModal = $openAssignedUsersModal
                || (isset($_GET['users']) && (string) $_GET['users'] === 'edit');

            return self::itemPage(
                $itemId,
                $messages,
                $errors,
                $actionValues,
                $documentValues,
                $openActionModal,
                $openDocumentModal,
                $openAssignedUsersModal,
                $editValues,
                $openEditModal
            );
        }

        if ($view === 'edit') {
            return self::editItemPage($itemId, $messages, $errors, $editValues);
        }

        return self::dashboard($messages, $errors, $formValues, $openCreateModal);
    }

    private static function renderEmbeddedAdminPage()
    {
        ob_start();
        $gestion_materiel_admin_embedded = true;
        include __DIR__.'/../admin.php';
        $html = ob_get_clean();

        return '<section id="gestion-materiel">'.self::moduleAssets().$html.'</section>';
    }

    private static function dashboard($messages, $errors, $formValues, $openCreateModal)
    {
        $title = self::html(GestionMaterielConfig::displayName());
        $login = self::currentLogin();
        $canViewAll = self::canManageModule($login);
        $items = GestionMaterielRepository::itemsForUser($login, $canViewAll, 100);
        $days = GestionMaterielConfig::upcomingDays();
        $deadlineCounts = GestionMaterielRepository::deadlineAlertCountsForUser($login, $canViewAll, $days);
        $deadlineAlerts = GestionMaterielRepository::deadlineAlertsForUser($login, $canViewAll, $days, 50);

        return '<div id="gestion-materiel">'
            .self::moduleAssets()
            .'<div class="gm-page-head">'
                .'<div>'
                    .'<h1>'.$title.'</h1>'
                .'</div>'
                .'<div class="gm-page-meta">'.self::html(date('d/m/Y')).'</div>'
            .'</div>'
            .self::alerts($messages, 'success')
            .self::alerts($errors, 'danger')
            .self::summaryCards($login, $canViewAll, $deadlineCounts)
            .self::moduleToolbar()
            .self::deadlineAlertsPanel($deadlineAlerts, $days)
            .self::itemsPanel($items)
            .self::createItemModal($formValues)
            .self::createItemModalScript($openCreateModal)
        .'</div>';
    }

    private static function summaryCards($login, $canViewAll, $deadlineCounts)
    {
        $config = GestionMaterielConfig::dashboardTileConfig();
        $definitions = GestionMaterielConfig::dashboardTileDefinitions();
        $values = array(
            'items' => GestionMaterielRepository::countItemsForUser($login, $canViewAll),
            'maintenance_overdue' => $deadlineCounts['maintenance_overdue'],
            'etalonnage_overdue' => $deadlineCounts['etalonnage_overdue'],
            'assigned_users' => GestionMaterielRepository::countAssignedUsersForUser($login, $canViewAll),
            'maintenance_upcoming' => $deadlineCounts['maintenance_upcoming'],
            'etalonnage_upcoming' => $deadlineCounts['etalonnage_upcoming'],
            'deadlines_total' => $deadlineCounts['total'],
            'actions' => GestionMaterielRepository::countActionsForUser($login, $canViewAll),
        );
        $urls = array(
            'items' => '',
            'maintenance_overdue' => self::moduleUrl().'#gm-alerts',
            'etalonnage_overdue' => self::moduleUrl().'#gm-alerts',
            'assigned_users' => '',
            'maintenance_upcoming' => self::moduleUrl().'#gm-alerts',
            'etalonnage_upcoming' => self::moduleUrl().'#gm-alerts',
            'deadlines_total' => self::moduleUrl().'#gm-alerts',
            'actions' => self::moduleUrl(array('view' => 'actions')),
        );
        $columnClass = self::summaryColumnClass();
        $cards = '';

        foreach ($config['order'] as $key) {
            if (
                !isset($definitions[$key])
                || empty($config['enabled'][$key])
                || !array_key_exists($key, $values)
            ) {
                continue;
            }

            $cards .= self::summaryCard(
                $definitions[$key]['label'],
                $values[$key],
                $config['colors'][$key],
                isset($urls[$key]) ? $urls[$key] : '',
                $columnClass
            );
        }

        if ($cards === '') {
            return '';
        }

        return '<div class="row gm-summary-row gm-summary-columns-'.(int) $config['columns'].' gm-summary-size-'.self::html($config['size']).'">'.$cards.'</div>';
    }

    private static function summaryColumnClass()
    {
        return 'gm-summary-card col-md-6 col-sm-6 col-xs-12';
    }

    private static function moduleToolbar()
    {
        $html = '<div class="gm-toolbar">';

        if (self::canCreateItem()) {
            $html .= '<button type="button" id="gestion-materiel-create-button" class="btn btn-primary" onclick="return gestionMaterielOpenCreateModal();">Ajouter un materiel</button>';
        }

        $html .= '<a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'actions')).'">Voir les actions</a>';
        $html .= '<a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'groups')).'">Groupes</a>';

        if (self::canManageModule()) {
            $html .= '<a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'notifications')).'">Notifications</a>';
            $html .= '<a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'archives')).'">Archives ('.(int) GestionMaterielRepository::countArchivedItems().')</a>';
        }

        if (self::isAdmin()) {
            $html .= '<a class="btn btn-default" href="'.self::moduleUrl(array('admin' => '1')).'">Administration du module</a>';
        }

        return $html.'</div>';
    }

    private static function itemsPanel($items, $title = 'Liste du materiel')
    {
        $tableId = self::nextTableId('gm-items');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">'.self::html($title).'</h3></div>'
            .'<div class="card-body">';

        if (count($items) === 0) {
            $html .= '<div class="gm-empty">Aucun materiel enregistre.</div>';
        } else {
            $html .= self::tableTools($tableId, count($items), 'Rechercher un materiel')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .'<th>Reference</th>'
                .'<th>Nom</th>'
                .'<th>Statut</th>'
                .'<th>Categorie</th>'
                .'<th>Groupe</th>'
                .'<th>Localisation</th>'
                .'<th>Maintenance</th>'
                .'<th>Etalonnage</th>'
                .'<th>Actions</th>'
                .'</tr></thead><tbody>';

            foreach ($items as $item) {
                $id = isset($item['id']) ? (int) $item['id'] : 0;
                $rowClassName = self::itemDeadlineRowClass($item);
                $rowClass = $rowClassName !== '' ? ' class="'.$rowClassName.'"' : '';
                $itemUrl = self::moduleUrl(array('view' => 'item', 'id' => $id));
                $html .= '<tr'.$rowClass.'>'
                    .'<td><a href="'.$itemUrl.'">'.self::html($item['reference']).'</a></td>'
                    .'<td class="gm-cell-title"><a href="'.$itemUrl.'">'.self::html($item['nom']).'</a></td>'
                    .'<td>'.self::statusBadge($item['statut']).'</td>'
                    .'<td>'.self::html($item['categorie']).'</td>'
                    .'<td>'.self::groupLinkFromItem($item).'</td>'
                    .'<td>'.self::html($item['localisation']).'</td>'
                    .'<td data-gm-sort-value="'.(int) $item['maintenance_prochaine'].'">'.self::deadlineBadge($item['maintenance_prochaine']).'</td>'
                    .'<td data-gm-sort-value="'.(int) $item['etalonnage_prochain'].'">'.self::deadlineBadge($item['etalonnage_prochain'], isset($item['statut']) && $item['statut'] === 'sans_projet').'</td>'
                    .'<td>'.self::itemActions($id, '').'</td>'
                    .'</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private static function itemPage($id, $messages, $errors, $actionValues, $documentValues, $openActionModal, $openDocumentModal, $openAssignedUsersModal, $editValues = null, $openEditModal = false)
    {
        $item = GestionMaterielRepository::item($id);
        $html = self::pageHeader($messages, $errors);

        if (!$item || !self::canViewItem($id)) {
            return $html
                .'<div class="alert alert-warning">Materiel introuvable.</div>'
                .'<p><a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a></p>'
            .'</div>';
        }

        $isArchived = self::isArchivedItem($item);
        $returnUrl = $isArchived
            ? self::moduleUrl(array('view' => 'archives'))
            : self::moduleUrl();
        $itemButtons = '';
        if (self::canEditItem((int) $item['id'])) {
            $itemButtons .= '<button type="button" class="btn btn-success" onclick="return gestionMaterielOpenActionModal();">Ajouter une action</button> ';
            if (GestionMaterielConfig::documentsEnabled()) {
                $itemButtons .= '<button type="button" class="btn btn-info" onclick="return gestionMaterielOpenDocumentModal();">Ajouter un document</button> ';
            }
            $itemButtons .= '<button type="button" class="btn btn-primary" onclick="return gestionMaterielOpenEditModal();">Modifier</button>';
        }
        if (self::canManageModule() && !$isArchived) {
            $itemButtons .= ' <button type="button" class="btn btn-default" onclick="return gestionMaterielOpenAssignedUsersModal();">Gerer les utilisateurs assignes</button>';
        }
        if (self::canManageModule()) {
            $itemButtons .= ' '.self::deleteItemForm((int) $item['id'], $isArchived ? 'archives' : '', 'btn btn-danger');
        }

        $html .= '<div class="gm-toolbar">'
            .'<a class="btn btn-default" href="'.$returnUrl.'">'.($isArchived ? 'Retour aux archives' : 'Retour a la liste').'</a>'
            .$itemButtons
        .'</div>';

        $html .= '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">'.self::html($item['nom']).'</h3></div>'
            .'<div class="card-body">'
                .'<div class="table-responsive">'
                    .'<table class="table table-bordered table-striped"><tbody>'
                        .self::detailRow('Reference', $item['reference'])
                        .self::detailHtmlRow('Statut', self::statusBadge($item['statut']))
                        .self::detailRow('Categorie', $item['categorie'])
                        .self::detailRow('Fabricant', $item['fabricant'])
                        .self::detailRow('Modele', $item['modele'])
                        .self::detailRow('Numero de serie', $item['numero_serie'])
                        .self::detailRow('Numero inventaire', $item['numero_inventaire'])
                        .self::detailRow('Localisation', $item['localisation'])
                        .self::detailHtmlRow('Groupe', self::groupLinkFromItem($item))
                        .self::detailRow('Date acquisition', self::formatDate($item['date_acquisition']))
                        .self::detailRow('Fin garantie', self::formatDate($item['date_fin_garantie']))
                        .self::detailRow('Intervalle maintenance', self::formatInterval($item['maintenance_interval_jours']))
                        .self::detailHtmlRow('Prochaine maintenance', self::deadlineBadge($item['maintenance_prochaine']))
                        .self::detailRow('Intervalle etalonnage', self::formatInterval($item['etalonnage_interval_jours']))
                        .self::detailHtmlRow('Prochain etalonnage', self::deadlineBadge($item['etalonnage_prochain'], isset($item['statut']) && $item['statut'] === 'sans_projet'))
                        .self::detailRow('Cree par', $item['created_by'])
                        .self::detailRow('Creation', self::formatDateTime($item['created_at']))
                        .self::detailRow('Derniere modification', self::formatDateTime($item['updated_at']))
                    .'</tbody></table>'
                .'</div>'
                .'<h4>Description</h4>'
                .'<div>'.self::htmlMultiline($item['description']).'</div>'
            .'</div>'
        .'</div>';

        $html .= self::documentsPanel((int) $item['id'], !$isArchived);
        $html .= self::assignedUsersPanel((int) $item['id']);
        $html .= self::actionsPanel(GestionMaterielRepository::actionsForItem((int) $item['id']), false, !$isArchived);
        if (!$isArchived) {
            $html .= self::editItemModal((int) $item['id'], $editValues === null ? self::itemToFormValues($item) : $editValues);
            $html .= self::editItemModalScript((int) $item['id'], $openEditModal);
            $html .= self::assignedUsersModal((int) $item['id']);
            $html .= self::assignedUsersModalScript((int) $item['id'], $openAssignedUsersModal);
            $html .= self::createActionModal((int) $item['id'], $actionValues);
            $html .= self::createActionModalScript((int) $item['id'], $openActionModal);
            $html .= self::documentModal((int) $item['id'], $documentValues);
            $html .= self::documentModalScript((int) $item['id'], $openDocumentModal);
        }

        return $html.'</div>';
    }

    private static function deadlineAlertsPanel($alerts, $days)
    {
        $tableId = self::nextTableId('gm-alerts-table');
        $html = '<div class="card card-primary card-outline" id="gm-alerts">'
            .'<div class="card-header"><h3 class="card-title">Alertes maintenance et etalonnage - prochains '.(int) $days.' jours</h3></div>'
            .'<div class="card-body">';

        if (count($alerts) === 0) {
            return $html.'<div class="alert alert-success">Aucune echeance de maintenance ou d etalonnage a signaler sur cette periode.</div></div></div>';
        }

        $html .= self::tableTools($tableId, count($alerts), 'Rechercher une alerte')
            .'<div class="table-responsive">'
            .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
            .'<thead><tr>'
            .'<th>Etat</th>'
            .'<th>Echeance</th>'
            .'<th>Type</th>'
            .'<th>Materiel</th>'
            .'<th>Statut</th>'
            .'<th>Localisation</th>'
            .'</tr></thead><tbody>';

        foreach ($alerts as $alert) {
            $itemLabel = trim((string) $alert['reference']) !== ''
                ? $alert['reference'].' - '.$alert['item_nom']
                : $alert['item_nom'];
            $rowClass = self::alertRowClass($alert);

            $html .= '<tr'.($rowClass !== '' ? ' class="'.$rowClass.'"' : '').'>'
                .'<td>'.self::deadlineStatusBadge($alert).'</td>'
                .'<td data-gm-sort-value="'.(int) $alert['echeance'].'">'.self::html(self::formatDate($alert['echeance'])).'</td>'
                .'<td>'.self::html(self::notificationTypeLabel($alert['type_echeance'])).'</td>'
                .'<td><a href="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $alert['item_id'])).'">'.self::html($itemLabel).'</a></td>'
                .'<td>'.self::statusBadge($alert['statut']).'</td>'
                .'<td>'.self::html($alert['localisation']).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div></div>';
    }

    private static function notificationsPage($messages, $errors, $openNotificationsModal = false)
    {
        if (!self::canManageModule()) {
            return self::pageHeader($messages, $errors)
                .'<div class="alert alert-warning">Acces refuse.</div>'
                .'<p><a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a></p>'
            .'</div>';
        }

        $days = isset($_GET['days']) ? (int) $_GET['days'] : GestionMaterielConfig::upcomingDays();
        if (isset($_POST['notification_days'])) {
            $days = (int) $_POST['notification_days'];
        }
        if ($days <= 0 || $days > 365) {
            $days = GestionMaterielConfig::upcomingDays();
        }

        $status = GestionMaterielNotification::mailStatus();
        $notifications = GestionMaterielRepository::upcomingNotifications($days, true);
        $logs = GestionMaterielRepository::recentNotificationLogs(100);

        $html = self::pageHeader($messages, $errors)
            .'<div class="gm-toolbar">'
                .'<a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a>'
                .'<button type="button" class="btn btn-primary" onclick="return gestionMaterielOpenNotificationsModal();">Envoyer les notifications</button>'
            .'</div>';

        $html .= '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Notifications</h3></div>'
            .'<div class="card-body">'
                .'<p>Etat mail GRR : '.self::html($status['enabled'] ? 'actif' : 'inactif')
                    .' - methode : '.self::html($status['method'])
                    .' - automatic_mail : '.self::html($status['automatic_mail']).'</p>';

        $html .= '</div></div>';
        $html .= self::notificationsPanel($notifications);
        $html .= self::notificationLogsPanel($logs);
        $html .= self::notificationsSendModal($days);
        $html .= self::notificationsSendModalScript($openNotificationsModal);

        return $html.'</div>';
    }

    private static function notificationsSendModal($days)
    {
        if (!self::canManageModule()) {
            return '';
        }

        return '<div id="gestion-materiel-notifications-modal" role="dialog" aria-labelledby="gestion-materiel-notifications-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-notifications-title">Envoyer les notifications</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseNotificationsModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl(array('view' => 'notifications')).'">'
                    .'<input type="hidden" name="gm_action" value="send_notifications">'
                    .'<div class="row">'
                        .self::input('notification_days', 'Echeances sur les prochains jours', $days, 3, true, 'number')
                    .'</div>'
                    .'<button class="btn btn-primary" type="submit">Envoyer les notifications non envoyees</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseNotificationsModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function notificationsSendModalScript($open)
    {
        if (!self::canManageModule()) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenNotificationsModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-notifications-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseNotificationsModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-notifications-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-notifications-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseNotificationsModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseNotificationsModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenNotificationsModal();' : '')
        .'</script>';
    }

    private static function actionsPage($messages, $errors)
    {
        $login = self::currentLogin();
        $canViewAll = self::canManageModule($login);
        $html = self::pageHeader($messages, $errors)
            .'<div class="gm-toolbar">'
                .'<a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a>'
            .'</div>';

        $html .= self::actionsPanel(GestionMaterielRepository::recentActionsForUser($login, $canViewAll, 200), true, true);

        return $html.'</div>';
    }

    private static function archivesPage($messages, $errors)
    {
        if (!self::canManageModule()) {
            return self::pageHeader($messages, array_merge($errors, array('Acces refuse.')))
                .'<p><a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a></p>'
            .'</div>';
        }

        $html = self::pageHeader($messages, $errors)
            .'<div class="gm-toolbar">'
                .'<a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a>'
            .'</div>';

        $html .= self::archivedItemsPanel(GestionMaterielRepository::archivedItems(1000));

        return $html.'</div>';
    }

    private static function archivedItemsPanel($items)
    {
        $tableId = self::nextTableId('gm-archives');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Materiels archives</h3></div>'
            .'<div class="card-body">';

        if (count($items) === 0) {
            return $html.'<div class="gm-empty">Aucun materiel archive.</div></div></div>';
        }

        $html .= self::tableTools($tableId, count($items), 'Rechercher dans les archives')
            .'<div class="table-responsive">'
            .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
            .'<thead><tr>'
            .'<th>Reference</th>'
            .'<th>Nom</th>'
            .'<th>Categorie</th>'
            .'<th>Groupe</th>'
            .'<th>Localisation</th>'
            .'<th>Derniere modification</th>'
            .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($items as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;
            $itemUrl = self::moduleUrl(array('view' => 'item', 'id' => $id));
            $html .= '<tr>'
                .'<td><a href="'.$itemUrl.'">'.self::html($item['reference']).'</a></td>'
                .'<td class="gm-cell-title"><a href="'.$itemUrl.'">'.self::html($item['nom']).'</a></td>'
                .'<td>'.self::html($item['categorie']).'</td>'
                .'<td>'.self::html(isset($item['groupe_nom']) ? $item['groupe_nom'] : '').'</td>'
                .'<td>'.self::html($item['localisation']).'</td>'
                .'<td data-gm-sort-value="'.(int) $item['updated_at'].'">'.self::html(self::formatDateTime($item['updated_at'])).'</td>'
                .'<td>'.self::deleteItemForm($id, 'archives', 'btn btn-danger btn-xs').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div></div>';
    }

    private static function groupsPage($messages, $errors, $groupValues, $openGroupModal)
    {
        $login = self::currentLogin();
        $canViewAll = self::canManageModule($login);
        $groups = GestionMaterielRepository::groupsForUser($login, $canViewAll, 200);

        $html = self::pageHeader($messages, $errors)
            .'<div class="gm-toolbar">'
                .'<a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a>';

        if (self::canManageModule()) {
            $html .= '<button type="button" class="btn btn-primary" onclick="return gestionMaterielOpenGroupModal();">Ajouter un groupe</button>';
        }

        $html .= '</div>'
            .self::groupsPanel($groups)
            .self::createGroupModal($groupValues)
            .self::createGroupModalScript($openGroupModal);

        return $html.'</div>';
    }

    private static function groupPage($groupId, $messages, $errors, $openGroupItemsModal)
    {
        $groupId = (int) $groupId;
        $group = GestionMaterielRepository::group($groupId);
        $html = self::pageHeader($messages, $errors);

        if (!$group || !self::canViewGroup($groupId)) {
            return $html
                .'<div class="alert alert-warning">Groupe introuvable.</div>'
                .'<p><a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'groups')).'">Retour aux groupes</a></p>'
            .'</div>';
        }

        $login = self::currentLogin();
        $canViewAll = self::canManageModule($login);
        $items = GestionMaterielRepository::itemsForGroup($groupId, $login, $canViewAll, 500);
        $days = GestionMaterielConfig::upcomingDays();
        $alerts = GestionMaterielRepository::groupAlerts($groupId, $login, $canViewAll, $days);

        $html .= '<div class="gm-toolbar">'
            .'<a class="btn btn-default" href="'.self::moduleUrl(array('view' => 'groups')).'">Retour aux groupes</a> '
            .'<a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a>';

        if (self::canManageModule()) {
            $html .= '<button type="button" class="btn btn-primary" onclick="return gestionMaterielOpenGroupItemsModal();">Modifier les materiels du groupe</button>';
        }

        $html .= '</div>';

        $html .= '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">'.self::html($group['nom']).'</h3></div>'
            .'<div class="card-body">'
                .'<div>'.self::htmlMultiline($group['description']).'</div>'
            .'</div>'
        .'</div>';

        $html .= self::groupAlertsPanel($alerts, $days);
        $html .= self::itemsPanel($items, 'Materiels du groupe');

        if (self::canManageModule()) {
            $html .= self::groupItemsModal($groupId);
            $html .= self::groupItemsModalScript($groupId, $openGroupItemsModal);
        }

        return $html.'</div>';
    }

    private static function groupsPanel($groups)
    {
        $login = self::currentLogin();
        $canViewAll = self::canManageModule($login);
        $days = GestionMaterielConfig::upcomingDays();
        $tableId = self::nextTableId('gm-groups');

        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Groupes de materiel</h3></div>'
            .'<div class="card-body">';

        if (count($groups) === 0) {
            return $html.'<div class="gm-empty">Aucun groupe disponible.</div></div></div>';
        }

        $html .= self::tableTools($tableId, count($groups), 'Rechercher un groupe')
            .'<div class="table-responsive">'
            .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
            .'<thead><tr>'
            .'<th>Nom</th>'
            .'<th>Description</th>'
            .'<th>Materiels</th>'
            .'<th>Alertes groupe</th>'
            .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($groups as $group) {
            $groupId = isset($group['id']) ? (int) $group['id'] : 0;
            $groupAlerts = GestionMaterielRepository::groupAlerts($groupId, $login, $canViewAll, $days);
            $html .= '<tr>'
                .'<td class="gm-cell-title">'.self::html($group['nom']).'</td>'
                .'<td>'.self::htmlMultiline($group['description']).'</td>'
                .'<td>'.(int) $group['item_count'].'</td>'
                .'<td>'.self::groupAlertCountBadge($groupAlerts).'</td>'
                .'<td><div class="gm-actions"><a class="btn btn-default btn-xs" href="'.self::moduleUrl(array('view' => 'group', 'id' => $groupId)).'">Voir</a></div></td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div></div>';
    }

    private static function groupAlertsPanel($alerts, $days)
    {
        $tableId = self::nextTableId('gm-group-alerts');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Alertes du groupe - prochains '.(int) $days.' jours</h3></div>'
            .'<div class="card-body">';

        if (count($alerts) === 0) {
            return $html.'<div class="alert alert-success">Aucune alerte pour ce groupe.</div></div></div>';
        }

        $html .= self::tableTools($tableId, count($alerts), 'Rechercher une alerte')
            .'<div class="table-responsive">'
            .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
            .'<thead><tr>'
            .'<th>Etat</th>'
            .'<th>Type</th>'
            .'<th>Materiel</th>'
            .'<th>Detail</th>'
            .'<th>Echeance</th>'
            .'<th>Statut</th>'
            .'</tr></thead><tbody>';

        foreach ($alerts as $alert) {
            $itemLabel = trim((string) $alert['reference']) !== ''
                ? $alert['reference'].' - '.$alert['item_nom']
                : $alert['item_nom'];
            $rowClass = self::alertRowClass($alert);
            $echeance = isset($alert['echeance']) && (int) $alert['echeance'] > 0 ? self::formatDate($alert['echeance']) : '-';

            $html .= '<tr'.($rowClass !== '' ? ' class="'.$rowClass.'"' : '').'>'
                .'<td>'.self::groupAlertBadge($alert).'</td>'
                .'<td>'.self::html(self::groupAlertTypeLabel($alert['alert_type'])).'</td>'
                .'<td><a href="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $alert['item_id'])).'">'.self::html($itemLabel).'</a></td>'
                .'<td>'.self::html($alert['detail']).'</td>'
                .'<td data-gm-sort-value="'.(isset($alert['echeance']) ? (int) $alert['echeance'] : 0).'">'.self::html($echeance).'</td>'
                .'<td>'.self::statusBadge($alert['statut']).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div></div>';
    }

    private static function groupItemsModal($groupId)
    {
        $items = GestionMaterielRepository::activeItemsForGroupSelection(1000);
        if (count($items) === 0) {
            return '';
        }
        $tableId = self::nextTableId('gm-group-items');

        $html = '<div id="gestion-materiel-group-items-modal" role="dialog" aria-labelledby="gestion-materiel-group-items-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-group-items-title">Materiels du groupe</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseGroupItemsModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl(array('view' => 'group', 'id' => (int) $groupId)).'">'
                    .'<input type="hidden" name="gm_action" value="save_group_items">'
                    .'<input type="hidden" name="group_id" value="'.(int) $groupId.'">'
                    .self::tableTools($tableId, count($items), 'Rechercher un materiel', false)
                    .'<div class="table-responsive">'
                    .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                    .'<thead><tr>'
                    .'<th>Affecter</th>'
                    .'<th>Materiel</th>'
                    .'<th>Statut</th>'
                    .'<th>Groupe actuel</th>'
                    .'</tr></thead><tbody>';

        foreach ($items as $item) {
            $id = isset($item['id']) ? (int) $item['id'] : 0;
            $currentGroupId = isset($item['groupe_id']) ? (int) $item['groupe_id'] : 0;
            $checked = ($currentGroupId === (int) $groupId);
            $locked = ($currentGroupId > 0 && $currentGroupId !== (int) $groupId);
            $itemLabel = trim((string) $item['reference']) !== ''
                ? $item['reference'].' - '.$item['nom']
                : $item['nom'];
            $currentGroup = trim((string) $item['groupe_nom']) !== '' ? $item['groupe_nom'] : 'Sans groupe';

            $html .= '<tr>'
                .'<td><input type="checkbox" name="group_item_id[]" value="'.$id.'"'.self::checked($checked).($locked ? ' disabled' : '').'></td>'
                .'<td class="gm-cell-title"><a href="'.self::moduleUrl(array('view' => 'item', 'id' => $id)).'">'.self::html($itemLabel).'</a></td>'
                .'<td>'.self::statusBadge($item['statut']).'</td>'
                .'<td>'.self::html($currentGroup).($locked ? ' <span class="label label-default">verrouille</span>' : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>'
            .'<button class="btn btn-primary" type="submit">Enregistrer les materiels du groupe</button> '
            .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseGroupItemsModal();">Annuler</button>'
            .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function groupItemsModalScript($groupId, $open)
    {
        if (!self::canManageModule()) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenGroupItemsModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-group-items-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseGroupItemsModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-group-items-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-group-items-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseGroupItemsModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseGroupItemsModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenGroupItemsModal();' : '')
        .'</script>';
    }

    private static function createGroupModal($values)
    {
        if (!self::canManageModule()) {
            return '';
        }

        $values = array_merge(GestionMaterielRepository::emptyGroupValues(), is_array($values) ? $values : array());

        return '<div id="gestion-materiel-group-modal" role="dialog" aria-labelledby="gestion-materiel-group-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-group-title">Ajouter un groupe</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseGroupModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl(array('view' => 'groups')).'">'
                    .'<input type="hidden" name="gm_action" value="create_group">'
                    .'<div class="row">'
                        .self::input('nom', 'Nom', $values['nom'], 6, true)
                    .'</div>'
                    .'<div class="form-group">'
                        .'<label for="gm_group_description">Description</label>'
                        .'<textarea class="form-control" id="gm_group_description" name="description" rows="4">'.self::html($values['description']).'</textarea>'
                    .'</div>'
                    .'<button class="btn btn-primary" type="submit">Ajouter</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseGroupModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function createGroupModalScript($open)
    {
        if (!self::canManageModule()) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenGroupModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-group-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseGroupModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-group-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-group-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseGroupModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseGroupModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenGroupModal();' : '')
        .'</script>';
    }

    private static function editItemPage($id, $messages, $errors, $postedValues)
    {
        $html = self::pageHeader($messages, $errors);

        if (!self::canEditItem($id)) {
            return $html
                .'<div class="alert alert-warning">Acces refuse.</div>'
                .'<p><a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a></p>'
            .'</div>';
        }

        $item = GestionMaterielRepository::item($id);
        if (!$item) {
            return $html
                .'<div class="alert alert-warning">Materiel introuvable.</div>'
                .'<p><a class="btn btn-default" href="'.self::moduleUrl().'">Retour a la liste</a></p>'
            .'</div>';
        }

        $values = $postedValues === null ? self::itemToFormValues($item) : $postedValues;

        return self::itemPage(
            (int) $item['id'],
            $messages,
            $errors,
            GestionMaterielRepository::emptyActionValues(),
            array('type_document' => 'autre', 'description' => ''),
            false,
            false,
            false,
            $values,
            true
        );
    }

    private static function createItemPanel($values)
    {
        if (!self::canCreateItem()) {
            return '';
        }

        return self::itemFormPanel(
            'Ajouter un materiel',
            'create_item',
            'Ajouter',
            $values,
            0,
            self::moduleUrl().'#gm-form-add',
            'gm-form-add'
        );
    }

    private static function editItemModal($itemId, $values)
    {
        if (!self::canEditItem($itemId)) {
            return '';
        }

        $values = array_merge(GestionMaterielRepository::emptyItemValues(), is_array($values) ? $values : array());

        return '<div id="gestion-materiel-edit-modal" role="dialog" aria-labelledby="gestion-materiel-edit-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content" id="gm-form-edit">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-edit-title">Modifier le materiel</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseEditModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $itemId)).'#gm-form-edit" data-gm-item-form="1">'
                    .'<input type="hidden" name="gm_action" value="update_item">'
                    .'<input type="hidden" name="item_id" value="'.(int) $itemId.'">'
                    .self::itemFormFields($values, $itemId)
                    .'<button class="btn btn-primary" type="submit">Enregistrer</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseEditModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function editItemModalScript($itemId, $open)
    {
        if (!self::canEditItem($itemId)) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenEditModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-edit-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'if(window.gestionMaterielInitUi){window.gestionMaterielInitUi();}'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseEditModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-edit-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-edit-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseEditModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseEditModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenEditModal();' : '')
        .'</script>';
    }

    private static function createItemModal($values)
    {
        if (!self::canCreateItem()) {
            return '';
        }

        $values = array_merge(GestionMaterielRepository::emptyItemValues(), is_array($values) ? $values : array());

        return '<div id="gestion-materiel-create-modal" role="dialog" aria-labelledby="gestion-materiel-create-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-create-title">Ajouter un materiel</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseCreateModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl().'" data-gm-item-form="1">'
                    .'<input type="hidden" name="gm_action" value="create_item">'
                    .self::itemFormFields($values)
                    .'<button class="btn btn-primary" type="submit">Ajouter</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseCreateModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function createItemModalScript($open)
    {
        if (!self::canCreateItem()) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenCreateModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-create-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseCreateModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-create-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-create-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseCreateModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseCreateModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenCreateModal();' : '')
        .'</script>';
    }

    private static function documentsPanel($itemId, $allowDelete)
    {
        $documents = GestionMaterielRepository::documentsForItem($itemId);
        $types = GestionMaterielRepository::documentTypes();
        $canDelete = $allowDelete && self::canEditItem($itemId);
        $tableId = self::nextTableId('gm-documents');
        $html = '<div class="card card-primary card-outline" id="gm-documents">'
            .'<div class="card-header"><h3 class="card-title">Documents</h3></div>'
            .'<div class="card-body">';

        if (count($documents) === 0) {
            $html .= '<div class="gm-empty">Aucun document enregistre.</div>';
        } else {
            $html .= self::tableTools($tableId, count($documents), 'Rechercher un document')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .'<th>Fichier</th>'
                .'<th>Type</th>'
                .'<th>Description</th>'
                .'<th>Taille</th>'
                .'<th>Ajoute par</th>'
                .'<th>Date</th>'
                .($canDelete ? '<th>Actions</th>' : '')
                .'</tr></thead><tbody>';

            foreach ($documents as $document) {
                $type = isset($document['type_document']) ? (string) $document['type_document'] : 'autre';
                $typeLabel = isset($types[$type]) ? $types[$type] : $type;
                $downloadUrl = '../personnalisation/modules/gestion_materiel/download.php?id='.(int) $document['id'];
                $html .= '<tr>'
                    .'<td class="gm-cell-title"><a href="'.self::html($downloadUrl).'">'.self::html($document['original_name']).'</a></td>'
                    .'<td>'.self::html($typeLabel).'</td>'
                    .'<td>'.self::htmlMultiline($document['description']).'</td>'
                    .'<td data-gm-sort-value="'.(int) $document['taille'].'">'.self::html(self::formatBytes($document['taille'])).'</td>'
                    .'<td>'.self::html($document['uploaded_by']).'</td>'
                    .'<td data-gm-sort-value="'.(int) $document['created_at'].'">'.self::html(self::formatDateTime($document['created_at'])).'</td>';
                if ($canDelete) {
                    $html .= '<td>'.self::deleteDocumentForm(
                        (int) $document['id'],
                        (int) $itemId,
                        isset($document['original_name']) ? $document['original_name'] : ''
                    ).'</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        if (!GestionMaterielConfig::documentsEnabled() && self::canEditItem($itemId)) {
            $html .= '<p class="help-block" style="margin-top:10px;">L ajout de documents est desactive dans la configuration du module.</p>';
        }

        return $html.'</div></div>';
    }

    private static function documentModal($itemId, $values)
    {
        if (!GestionMaterielConfig::documentsEnabled() || !self::canEditItem($itemId)) {
            return '';
        }

        $values = array_merge(
            array('type_document' => 'autre', 'description' => ''),
            is_array($values) ? $values : array()
        );
        $types = GestionMaterielRepository::documentTypes();
        $accept = array();
        foreach (GestionMaterielConfig::documentExtensions() as $extension) {
            $accept[] = '.'.$extension;
        }

        $html = '<div id="gestion-materiel-document-modal" role="dialog" aria-labelledby="gestion-materiel-document-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content" id="gm-document-add">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-document-title">Ajouter un document</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseDocumentModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" enctype="multipart/form-data" action="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $itemId)).'#gm-documents">'
                    .'<input type="hidden" name="gm_action" value="upload_document">'
                    .'<input type="hidden" name="item_id" value="'.(int) $itemId.'">'
                    .'<input type="hidden" name="MAX_FILE_SIZE" value="'.(int) GestionMaterielConfig::documentMaxBytes().'">'
                    .'<div class="row">'
                        .'<div class="col-md-6"><div class="form-group">'
                            .'<label for="gm_type_document">Type de document</label>'
                            .'<select class="form-control" id="gm_type_document" name="type_document">';
        foreach ($types as $type => $label) {
            $html .= '<option value="'.self::html($type).'"'.((string) $values['type_document'] === (string) $type ? ' selected' : '').'>'.self::html($label).'</option>';
        }
        $html .= '</select></div></div>'
                        .'<div class="col-md-6"><div class="form-group">'
                            .'<label for="gm_document_file">Fichier</label>'
                            .'<input class="form-control" id="gm_document_file" type="file" name="document_file" accept="'.self::html(implode(',', $accept)).'" required>'
                        .'</div></div>'
                    .'</div>'
                    .'<div class="form-group">'
                        .'<label for="gm_document_description">Description</label>'
                        .'<textarea class="form-control" id="gm_document_description" name="description" rows="3">'.self::html($values['description']).'</textarea>'
                    .'</div>'
                    .'<p class="help-block">Extensions autorisees : '.self::html(implode(', ', GestionMaterielConfig::documentExtensions()))
                        .'. Taille maximale : '.self::html(self::formatBytes(GestionMaterielConfig::documentMaxBytes())).'.</p>'
                    .'<button class="btn btn-primary" type="submit">Ajouter le document</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseDocumentModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';

        return $html;
    }

    private static function documentModalScript($itemId, $open)
    {
        if (!GestionMaterielConfig::documentsEnabled() || !self::canEditItem($itemId)) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenDocumentModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-document-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseDocumentModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-document-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-document-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseDocumentModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseDocumentModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenDocumentModal();' : '')
        .'</script>';
    }

    private static function assignedUsersPanel($itemId)
    {
        $assignedUsers = GestionMaterielRepository::assignedUsers($itemId);
        $tableId = self::nextTableId('gm-assigned');

        $html = '<div class="card card-primary card-outline" id="gm-assigned-users">'
            .'<div class="card-header"><h3 class="card-title">Utilisateurs assignes</h3></div>'
            .'<div class="card-body">';

        if (count($assignedUsers) === 0) {
            $html .= '<div class="gm-empty">Aucun utilisateur assigne.</div>';
        } else {
            $html .= self::tableTools($tableId, count($assignedUsers), 'Rechercher un utilisateur')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .'<th>Utilisateur</th>'
                .'<th>Maintenance</th>'
                .'<th>Etalonnage</th>'
                .'</tr></thead><tbody>';

            foreach ($assignedUsers as $user) {
                $html .= '<tr>'
                    .'<td class="gm-cell-title">'.self::html($user['label']).'</td>'
                    .'<td>'.self::yesNoBadge($user['notify_maintenance']).'</td>'
                    .'<td>'.self::yesNoBadge($user['notify_etalonnage']).'</td>'
                    .'</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        return $html.'</div></div>';
    }

    private static function assignedUsersModal($itemId)
    {
        if (!self::canManageModule() || !self::canEditItem($itemId)) {
            return '';
        }

        $assignedUsers = GestionMaterielRepository::assignedUsers($itemId);

        return '<div id="gestion-materiel-users-modal" role="dialog" aria-labelledby="gestion-materiel-users-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-users-title">Gerer les utilisateurs assignes</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseAssignedUsersModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .self::assignedUsersForm($itemId, $assignedUsers)
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function assignedUsersModalScript($itemId, $open)
    {
        if (!self::canManageModule() || !self::canEditItem($itemId)) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenAssignedUsersModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-users-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'if(window.gestionMaterielInitUi){window.gestionMaterielInitUi();}'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseAssignedUsersModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-users-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-users-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseAssignedUsersModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseAssignedUsersModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenAssignedUsersModal();' : '')
        .'</script>';
    }

    private static function assignedUsersForm($itemId, $assignedUsers)
    {
        $tableId = self::nextTableId('gm-users-form');
        $assignedMap = array();
        foreach ($assignedUsers as $user) {
            $login = isset($user['login']) ? (string) $user['login'] : '';
            if ($login !== '') {
                $assignedMap[$login] = $user;
            }
        }

        $users = GestionMaterielRepository::activeUsers();
        if (count($users) === 0) {
            return '<div class="gm-empty">Aucun compte utilisateur actif disponible.</div>';
        }

        $html = '<form method="post" action="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $itemId)).'#gm-assigned-users">'
                .'<input type="hidden" name="gm_action" value="save_assigned_users">'
                .'<input type="hidden" name="item_id" value="'.(int) $itemId.'">'
                .self::tableTools($tableId, count($users), 'Rechercher un compte', false)
                .'<div class="table-responsive">'
                    .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                    .'<thead><tr>'
                    .'<th>Assigner</th>'
                    .'<th>Utilisateur</th>'
                    .'<th>Notifier maintenance</th>'
                    .'<th>Notifier etalonnage</th>'
                    .'</tr></thead><tbody>';

        foreach ($users as $user) {
            $login = isset($user['login']) ? (string) $user['login'] : '';
            if ($login === '') {
                continue;
            }

            $isAssigned = isset($assignedMap[$login]);
            $notifyMaintenance = !$isAssigned || (isset($assignedMap[$login]['notify_maintenance']) && (int) $assignedMap[$login]['notify_maintenance'] === 1);
            $notifyEtalonnage = !$isAssigned || (isset($assignedMap[$login]['notify_etalonnage']) && (int) $assignedMap[$login]['notify_etalonnage'] === 1);

            $html .= '<tr>'
                .'<td><input type="checkbox" name="assigned_login[]" value="'.self::html($login).'"'.self::checked($isAssigned).'></td>'
                .'<td>'.self::html($user['label']).'</td>'
                .'<td><input type="checkbox" name="notify_maintenance[]" value="'.self::html($login).'"'.self::checked($notifyMaintenance).'></td>'
                .'<td><input type="checkbox" name="notify_etalonnage[]" value="'.self::html($login).'"'.self::checked($notifyEtalonnage).'></td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>'
            .'<button class="btn btn-primary" type="submit">Enregistrer les utilisateurs assignes</button> '
            .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseAssignedUsersModal();">Annuler</button>'
        .'</form>';
    }

    private static function createActionModal($itemId, $values)
    {
        if (!self::canEditItem($itemId)) {
            return '';
        }

        $values = array_merge(GestionMaterielRepository::emptyActionValues(), is_array($values) ? $values : array());

        return '<div id="gestion-materiel-action-modal" role="dialog" aria-labelledby="gestion-materiel-action-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content" id="gm-action-add">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="gestion-materiel-action-title">Ajouter une action</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return gestionMaterielCloseActionModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .'<form method="post" action="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $itemId)).'#gm-action-add">'
                    .'<input type="hidden" name="gm_action" value="create_action">'
                    .'<input type="hidden" name="item_id" value="'.(int) $itemId.'">'
                    .'<div class="row">'
                        .self::select('type_action', 'Type', $values['type_action'], self::actionTypesForItem($itemId), 3)
                        .self::input('date_action', 'Date action', $values['date_action'], 3, true, 'date')
                        .self::input('cout', 'Cout', $values['cout'], 3, false, 'number', ' step="0.01"')
                    .'</div>'
                    .'<div class="row">'
                        .self::input('prochaine_maintenance', 'Prochaine maintenance', $values['prochaine_maintenance'], 3, false, 'date')
                        .self::input('prochain_etalonnage', 'Prochain etalonnage', $values['prochain_etalonnage'], 3, false, 'date')
                    .'</div>'
                    .'<div class="form-group">'
                        .'<label for="gm_commentaire">Commentaire</label>'
                        .'<textarea class="form-control" id="gm_commentaire" name="commentaire" rows="3">'.self::html($values['commentaire']).'</textarea>'
                    .'</div>'
                    .'<button class="btn btn-primary" type="submit">Ajouter l action</button> '
                    .'<button class="btn btn-default" type="button" onclick="return gestionMaterielCloseActionModal();">Annuler</button>'
                .'</form>'
            .'</div>'
            .'</div>'
            .'</div>'
        .'</div>';
    }

    private static function createActionModalScript($itemId, $open)
    {
        if (!self::canEditItem($itemId)) {
            return '';
        }

        return '<script>'
            .'function gestionMaterielOpenActionModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-action-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function gestionMaterielCloseActionModal(){'
                .'var modalElement=document.getElementById("gestion-materiel-action-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("gestion-materiel-action-modal");'
                .'if(modalElement&&event.target===modalElement){gestionMaterielCloseActionModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){gestionMaterielCloseActionModal();}'
            .'});'
            .($open ? 'gestionMaterielOpenActionModal();' : '')
        .'</script>';
    }

    private static function actionsPanel($actions, $showItem, $allowDeleteActions)
    {
        $canDeleteActions = $allowDeleteActions && self::isAdmin();
        $tableId = self::nextTableId('gm-actions');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">'.($showItem ? 'Actions recentes' : 'Actions du materiel').'</h3></div>'
            .'<div class="card-body">';

        if (count($actions) === 0) {
            $html .= '<div class="gm-empty">Aucune action enregistree.</div>';
        } else {
            $html .= self::tableTools($tableId, count($actions), 'Rechercher une action')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .($showItem ? '<th>Materiel</th>' : '')
                .'<th>Date</th>'
                .'<th>Type</th>'
                .'<th>Commentaire</th>'
                .'<th>Cout</th>'
                .'<th>Maintenance</th>'
                .'<th>Etalonnage</th>'
                .'<th>Auteur</th>'
                .($canDeleteActions ? '<th>Actions</th>' : '')
                .'</tr></thead><tbody>';

            foreach ($actions as $action) {
                $html .= '<tr>';
                if ($showItem) {
                    $itemLabel = trim((string) $action['item_reference']) !== ''
                        ? $action['item_reference'].' - '.$action['item_nom']
                        : $action['item_nom'];
                    $html .= '<td class="gm-cell-title"><a href="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $action['item_id'])).'">'.self::html($itemLabel).'</a></td>';
                }
                $html .= '<td data-gm-sort-value="'.(int) $action['date_action'].'">'.self::html(self::formatDate($action['date_action'])).'</td>'
                    .'<td>'.self::html(self::actionLabel($action['type_action'])).'</td>'
                    .'<td>'.self::htmlMultiline($action['commentaire']).'</td>'
                    .'<td>'.self::html(self::formatCost($action['cout'])).'</td>'
                    .'<td data-gm-sort-value="'.(int) $action['prochaine_maintenance'].'">'.self::html(self::formatDate($action['prochaine_maintenance'])).'</td>'
                    .'<td data-gm-sort-value="'.(int) $action['prochain_etalonnage'].'">'.self::html(self::formatDate($action['prochain_etalonnage'])).'</td>'
                    .'<td>'.self::html($action['created_by']).'</td>';
                if ($canDeleteActions) {
                    $html .= '<td>'.self::deleteActionForm(
                        isset($action['id']) ? (int) $action['id'] : 0,
                        isset($action['item_id']) ? (int) $action['item_id'] : 0,
                        $showItem ? 'actions' : 'item'
                    ).'</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        return $html.'</div></div>';
    }

    private static function notificationsPanel($notifications)
    {
        $tableId = self::nextTableId('gm-notifications');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Echeances a notifier</h3></div>'
            .'<div class="card-body">';

        if (count($notifications) === 0) {
            $html .= '<div class="gm-empty">Aucune echeance a notifier.</div>';
        } else {
            $html .= self::tableTools($tableId, count($notifications), 'Rechercher une notification')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .'<th>Echeance</th>'
                .'<th>Type</th>'
                .'<th>Materiel</th>'
                .'<th>Utilisateur</th>'
                .'<th>Email</th>'
                .'<th>Deja envoye</th>'
                .'</tr></thead><tbody>';

            foreach ($notifications as $notification) {
                $itemLabel = trim((string) $notification['reference']) !== ''
                    ? $notification['reference'].' - '.$notification['item_nom']
                    : $notification['item_nom'];

                $html .= '<tr>'
                    .'<td data-gm-sort-value="'.(int) $notification['echeance'].'">'.self::html(self::formatDate($notification['echeance'])).'</td>'
                    .'<td>'.self::html(self::notificationTypeLabel($notification['type_notification'])).'</td>'
                    .'<td class="gm-cell-title"><a href="'.self::moduleUrl(array('view' => 'item', 'id' => (int) $notification['item_id'])).'">'.self::html($itemLabel).'</a></td>'
                    .'<td>'.self::html($notification['user_label']).'</td>'
                    .'<td>'.self::html($notification['email']).'</td>'
                    .'<td>'.self::yesNoBadge($notification['already_sent']).'</td>'
                    .'</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        return $html.'</div></div>';
    }

    private static function notificationLogsPanel($logs)
    {
        $tableId = self::nextTableId('gm-notification-logs');
        $html = '<div class="card card-primary card-outline">'
            .'<div class="card-header"><h3 class="card-title">Journal recent des notifications</h3></div>'
            .'<div class="card-body">';

        if (count($logs) === 0) {
            $html .= '<div class="gm-empty">Aucune notification journalisee.</div>';
        } else {
            $html .= self::tableTools($tableId, count($logs), 'Rechercher dans le journal')
                .'<div class="table-responsive">'
                .'<table id="'.$tableId.'" class="table table-bordered table-striped">'
                .'<thead><tr>'
                .'<th>Date envoi</th>'
                .'<th>Type</th>'
                .'<th>Materiel</th>'
                .'<th>Utilisateur</th>'
                .'<th>Statut</th>'
                .'<th>Message</th>'
                .'</tr></thead><tbody>';

            foreach ($logs as $log) {
                $itemLabel = trim((string) $log['item_reference']) !== ''
                    ? $log['item_reference'].' - '.$log['item_nom']
                    : $log['item_nom'];
                $login = isset($log['login']) ? (string) $log['login'] : '';

                $html .= '<tr>'
                    .'<td data-gm-sort-value="'.(int) $log['sent_at'].'">'.self::html(self::formatDateTime($log['sent_at'])).'</td>'
                    .'<td>'.self::html(self::notificationTypeLabel($log['type_notification'])).'</td>'
                    .'<td class="gm-cell-title">'.self::html($itemLabel).'</td>'
                    .'<td>'.self::html($login).'</td>'
                    .'<td>'.self::html($log['status']).'</td>'
                    .'<td>'.self::htmlMultiline($log['message']).'</td>'
                    .'</tr>';
            }

            $html .= '</tbody></table></div>';
        }

        return $html.'</div></div>';
    }

    private static function itemFormPanel($title, $action, $buttonLabel, $values, $itemId, $target, $panelId)
    {
        $values = array_merge(GestionMaterielRepository::emptyItemValues(), is_array($values) ? $values : array());

        return '<div class="card card-primary card-outline" id="'.self::html($panelId).'">'
            .'<div class="card-header"><h3 class="card-title">'.self::html($title).'</h3></div>'
            .'<div class="card-body">'
                .'<form method="post" action="'.$target.'" data-gm-item-form="1">'
                    .'<input type="hidden" name="gm_action" value="'.self::html($action).'">'
                    .($itemId > 0 ? '<input type="hidden" name="item_id" value="'.(int) $itemId.'">' : '')
                    .self::itemFormFields($values, $itemId)
                    .'<button class="btn btn-primary" type="submit">'.self::html($buttonLabel).'</button>'
                .'</form>'
            .'</div>'
        .'</div>';
    }

    private static function itemFormFields($values, $itemId = 0)
    {
        return self::itemFormSection(
                'Identification',
                '<div class="row">'
                    .self::input('reference', 'Reference', $values['reference'], 3)
                    .self::input('nom', 'Nom', $values['nom'], 6, true)
                    .self::select('statut', 'Statut', $values['statut'], GestionMaterielRepository::itemStatuses(), 3)
                .'</div>'
                .'<div class="row">'
                    .self::input('categorie', 'Categorie', $values['categorie'], 3)
                    .self::input('fabricant', 'Fabricant', $values['fabricant'], 3)
                    .self::input('modele', 'Modele', $values['modele'], 3)
                    .self::input('localisation', 'Localisation', $values['localisation'], 3)
                .'</div>'
                .'<div class="row">'
                    .self::input('numero_serie', 'Numero de serie', $values['numero_serie'], 6)
                    .self::input('numero_inventaire', 'Numero inventaire', $values['numero_inventaire'], 6)
                .'</div>'
            )
            .self::itemFormSection(
                'Groupe',
                '<div class="row">'
                    .self::select('groupe_id', 'Groupe', $values['groupe_id'], self::groupOptionsForItemForm($itemId), 6)
                    .self::input('nouveau_groupe', 'Nouveau groupe', $values['nouveau_groupe'], 6)
                .'</div>'
            )
            .self::itemFormSection(
                'Dates et echeances',
                '<div class="row">'
                    .self::input('date_acquisition', 'Date acquisition', $values['date_acquisition'], 3, false, 'date')
                    .self::input('date_fin_garantie', 'Fin garantie', $values['date_fin_garantie'], 3, false, 'date')
                .'</div>'
                .'<div class="row">'
                    .self::input('maintenance_interval_jours', 'Intervalle maintenance jours', $values['maintenance_interval_jours'], 3, false, 'number')
                    .self::input('maintenance_prochaine', 'Prochaine maintenance', $values['maintenance_prochaine'], 3, false, 'date')
                    .self::input('etalonnage_interval_jours', 'Intervalle etalonnage jours', $values['etalonnage_interval_jours'], 3, false, 'number')
                    .self::input('etalonnage_prochain', 'Prochain etalonnage', $values['etalonnage_prochain'], 3, false, 'date')
                .'</div>'
            )
            .self::itemFormSection(
                'Description',
                '<div class="form-group">'
                    .'<label for="gm_description">Description</label>'
                    .'<textarea class="form-control" id="gm_description" name="description" rows="3">'.self::html($values['description']).'</textarea>'
                .'</div>'
            )
            .self::itemFormScript();
    }

    private static function itemFormSection($title, $content)
    {
        return '<fieldset class="well well-sm" style="margin-bottom:15px;">'
            .'<legend style="font-size:16px;margin-bottom:10px;">'.self::html($title).'</legend>'
            .$content
        .'</fieldset>';
    }

    private static function groupOptionsForItemForm($itemId)
    {
        $options = GestionMaterielRepository::groupOptions();
        $itemId = (int) $itemId;
        if ($itemId <= 0) {
            return $options;
        }

        $item = GestionMaterielRepository::item($itemId);
        $currentGroupId = isset($item['groupe_id']) ? (int) $item['groupe_id'] : 0;
        if ($currentGroupId <= 0) {
            return $options;
        }

        $currentLabel = isset($options[(string) $currentGroupId])
            ? $options[(string) $currentGroupId]
            : (isset($item['groupe_nom']) && trim((string) $item['groupe_nom']) !== '' ? $item['groupe_nom'] : 'Groupe actuel');

        return array(
            '0' => 'Sans groupe',
            (string) $currentGroupId => $currentLabel,
        );
    }

    private static function itemFormScript()
    {
        return '<script>'
            .'window.gestionMaterielInitItemForms=function(){'
                .'function parseDate(value){'
                    .'if(!value){return null;}'
                    .'var parts=value.split("-");'
                    .'if(parts.length!==3){return null;}'
                    .'var date=new Date(parseInt(parts[0],10),parseInt(parts[1],10)-1,parseInt(parts[2],10));'
                    .'return isNaN(date.getTime())?null:date;'
                .'}'
                .'function formatDate(date){'
                    .'var month=("0"+(date.getMonth()+1)).slice(-2);'
                    .'var day=("0"+date.getDate()).slice(-2);'
                    .'return date.getFullYear()+"-"+month+"-"+day;'
                .'}'
                .'function fillNextDate(form,intervalName,nextName){'
                    .'var intervalInput=form.querySelector("[name=\""+intervalName+"\"]");'
                    .'var nextInput=form.querySelector("[name=\""+nextName+"\"]");'
                    .'var acquisitionInput=form.querySelector("[name=\"date_acquisition\"]");'
                    .'if(!intervalInput||!nextInput||nextInput.value){return;}'
                    .'var days=parseInt(intervalInput.value,10);'
                    .'if(!days||days<=0){return;}'
                    .'var base=parseDate(acquisitionInput?acquisitionInput.value:"")||new Date();'
                    .'base.setHours(0,0,0,0);'
                    .'base.setDate(base.getDate()+days);'
                    .'nextInput.value=formatDate(base);'
                .'}'
                .'function init(form){'
                    .'if(form.getAttribute("data-gm-item-form-ready")==="1"){return;}'
                    .'form.setAttribute("data-gm-item-form-ready","1");'
                    .'var groupSelect=form.querySelector("[name=\"groupe_id\"]");'
                    .'var newGroupInput=form.querySelector("[name=\"nouveau_groupe\"]");'
                    .'function syncGroupInputs(){'
                        .'if(groupSelect&&newGroupInput){groupSelect.disabled=newGroupInput.value.replace(/^\\s+|\\s+$/g,"")!=="";}'
                    .'}'
                    .'if(newGroupInput){newGroupInput.addEventListener("input",syncGroupInputs);syncGroupInputs();}'
                    .'["maintenance_interval_jours","etalonnage_interval_jours","date_acquisition"].forEach(function(name){'
                        .'var input=form.querySelector("[name=\""+name+"\"]");'
                        .'if(input){input.addEventListener("change",function(){'
                            .'fillNextDate(form,"maintenance_interval_jours","maintenance_prochaine");'
                            .'fillNextDate(form,"etalonnage_interval_jours","etalonnage_prochain");'
                        .'});}'
                    .'});'
                    .'fillNextDate(form,"maintenance_interval_jours","maintenance_prochaine");'
                    .'fillNextDate(form,"etalonnage_interval_jours","etalonnage_prochain");'
                .'}'
                .'var forms=document.querySelectorAll("form[data-gm-item-form=\"1\"]");'
                .'for(var i=0;i<forms.length;i++){init(forms[i]);}'
            .'};'
            .'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",window.gestionMaterielInitItemForms);}else{window.gestionMaterielInitItemForms();}'
        .'</script>';
    }

    private static function pageHeader($messages, $errors)
    {
        return '<div id="gestion-materiel">'
            .self::moduleAssets()
            .'<div class="gm-page-head">'
                .'<div><h1>'.self::html(GestionMaterielConfig::displayName()).'</h1></div>'
                .'<div class="gm-page-meta">'.self::html(date('d/m/Y')).'</div>'
            .'</div>'
            .self::alerts($messages, 'success')
            .self::alerts($errors, 'danger');
    }

    private static function moduleAssets()
    {
        return '<style>'
            .'#gestion-materiel{--gm-border:#d8dee6;--gm-soft:#f6f8fb;--gm-text:#263238;--gm-muted:#60717d;--gm-link:#245f9f;--gm-danger:#c0392b;--gm-warning:#b26a00;--gm-success:#237445;--gm-info:#256f8f;color:var(--gm-text);width:100%;max-width:none;box-sizing:border-box;}'
            .'#gestion-materiel *,#menu-compte .gm-account-btn{box-sizing:border-box;}'
            .'#menu-compte .gm-account-btn{display:block;width:100%;max-width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
            .'#gestion-materiel .gm-page-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 16px 0;padding:14px 0;border-bottom:1px solid var(--gm-border);}'
            .'#gestion-materiel .gm-page-head h1{margin:0;font-size:28px;line-height:1.2;font-weight:600;}'
            .'#gestion-materiel .gm-page-meta{color:var(--gm-muted);font-size:13px;white-space:nowrap;}'
            .'#gestion-materiel .gm-toolbar{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 16px 0;align-items:center;}'
            .'#gestion-materiel .gm-toolbar .btn{margin:0;}'
            .'#gestion-materiel .card{border:1px solid var(--gm-border);box-shadow:0 1px 2px rgba(0,0,0,.04);margin-bottom:16px;}'
            .'#gestion-materiel .card-header{background:var(--gm-soft);border-bottom:1px solid var(--gm-border);padding:10px 12px;}'
            .'#gestion-materiel .card-title{font-size:17px;font-weight:600;margin:0;}'
            .'#gestion-materiel .card-body{padding:14px;}'
            .'#gestion-materiel .gm-summary-row{margin-bottom:0;}'
            .'#gestion-materiel .small-box{border-radius:4px;box-shadow:0 1px 2px rgba(0,0,0,.08);overflow:hidden;margin-bottom:10px;}'
            .'#gestion-materiel .small-box .small-box-footer{background:rgba(0,0,0,.1);color:inherit;display:block;text-align:center;text-decoration:none;}'
            .'#gestion-materiel .gm-summary-size-compact .small-box .inner{padding:7px 10px;}'
            .'#gestion-materiel .gm-summary-size-compact .small-box .inner h3{font-size:22px;font-weight:600;line-height:1.1;margin:0 0 3px 0;}'
            .'#gestion-materiel .gm-summary-size-compact .small-box .inner p{font-size:13px;line-height:1.2;margin:0;}'
            .'#gestion-materiel .gm-summary-size-compact .small-box .small-box-footer{font-size:12px;line-height:1.2;padding:2px 0;}'
            .'#gestion-materiel .gm-summary-size-normal .small-box .inner{padding:10px 12px;}'
            .'#gestion-materiel .gm-summary-size-normal .small-box .inner h3{font-size:28px;font-weight:600;line-height:1.1;margin:0 0 5px 0;}'
            .'#gestion-materiel .gm-summary-size-normal .small-box .inner p{font-size:14px;line-height:1.25;margin:0;}'
            .'#gestion-materiel .gm-summary-size-normal .small-box .small-box-footer{font-size:13px;line-height:1.2;padding:3px 0;}'
            .'#gestion-materiel .gm-summary-size-large .small-box .inner{padding:14px 16px;}'
            .'#gestion-materiel .gm-summary-size-large .small-box .inner h3{font-size:34px;font-weight:600;line-height:1.1;margin:0 0 7px 0;}'
            .'#gestion-materiel .gm-summary-size-large .small-box .inner p{font-size:15px;line-height:1.3;margin:0;}'
            .'#gestion-materiel .gm-summary-size-large .small-box .small-box-footer{font-size:14px;line-height:1.2;padding:5px 0;}'
            .'@media (min-width:992px){'
                .'#gestion-materiel .gm-summary-columns-1>.gm-summary-card{width:100%;max-width:100%;flex:0 0 100%;}'
                .'#gestion-materiel .gm-summary-columns-2>.gm-summary-card{width:50%;max-width:50%;flex:0 0 50%;}'
                .'#gestion-materiel .gm-summary-columns-3>.gm-summary-card{width:33.333333%;max-width:33.333333%;flex:0 0 33.333333%;}'
                .'#gestion-materiel .gm-summary-columns-4>.gm-summary-card{width:25%;max-width:25%;flex:0 0 25%;}'
                .'#gestion-materiel .gm-summary-columns-5>.gm-summary-card{width:20%;max-width:20%;flex:0 0 20%;}'
                .'#gestion-materiel .gm-summary-columns-6>.gm-summary-card{width:16.666667%;max-width:16.666667%;flex:0 0 16.666667%;}'
                .'#gestion-materiel .gm-summary-columns-7>.gm-summary-card{width:14.285714%;max-width:14.285714%;flex:0 0 14.285714%;}'
                .'#gestion-materiel .gm-summary-columns-8>.gm-summary-card{width:12.5%;max-width:12.5%;flex:0 0 12.5%;}'
            .'}'
            .'#gestion-materiel .gm-list-tools{display:flex;justify-content:space-between;gap:10px;align-items:center;margin:0 0 10px 0;flex-wrap:wrap;}'
            .'#gestion-materiel .gm-filter{max-width:320px;min-width:220px;}'
            .'#gestion-materiel .gm-count{font-size:13px;color:var(--gm-muted);}'
            .'#gestion-materiel .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
            .'#gestion-materiel .table{width:100%;margin-bottom:0;}'
            .'#gestion-materiel .table>thead>tr>th{background:#eef3f7;border-bottom:1px solid var(--gm-border);font-weight:600;vertical-align:middle;}'
            .'#gestion-materiel .gm-sortable{cursor:pointer;user-select:none;}'
            .'#gestion-materiel .gm-sortable:after{content:" \2195";color:var(--gm-muted);font-size:11px;}'
            .'#gestion-materiel .gm-sort-asc:after{content:" \2191";color:var(--gm-link);}'
            .'#gestion-materiel .gm-sort-desc:after{content:" \2193";color:var(--gm-link);}'
            .'#gestion-materiel .gm-column-filters th{padding:5px;background:#f8fafc;}'
            .'#gestion-materiel .gm-column-filter{width:100%;min-width:70px;font-size:12px;font-weight:400;}'
            .'#gestion-materiel .table>tbody>tr>td{vertical-align:middle;}'
            .'#gestion-materiel th,#gestion-materiel td{overflow-wrap:anywhere;}'
            .'#gestion-materiel .gm-cell-title{font-weight:600;}'
            .'#gestion-materiel .gm-cell-muted{color:var(--gm-muted);font-size:12px;}'
            .'#gestion-materiel .gm-actions{display:flex;gap:4px;flex-wrap:wrap;}'
            .'#gestion-materiel .gm-delete-form{display:inline-block;margin:0;}'
            .'#gestion-materiel .gm-status{display:inline-block;padding:3px 8px;border-radius:12px;font-size:12px;font-weight:600;line-height:1.2;color:#fff;}'
            .'#gestion-materiel .gm-status-en_service{background:var(--gm-success);}'
            .'#gestion-materiel .gm-status-maintenance{background:var(--gm-warning);}'
            .'#gestion-materiel .gm-status-panne,#gestion-materiel .gm-status-hors_service{background:var(--gm-danger);}'
            .'#gestion-materiel .gm-status-sans_projet{background:#607d8b;}'
            .'#gestion-materiel .gm-status-archive{background:#6c757d;}'
            .'#gestion-materiel .gm-yesno{display:inline-block;padding:2px 7px;border-radius:10px;font-size:12px;font-weight:600;}'
            .'#gestion-materiel .gm-yes{background:#e2f4e8;color:var(--gm-success);}'
            .'#gestion-materiel .gm-no{background:#eef0f2;color:#5f6b73;}'
            .'#gestion-materiel .gm-empty{padding:14px;background:var(--gm-soft);border:1px dashed var(--gm-border);color:var(--gm-muted);}'
            .'#gestion-materiel fieldset.well{background:#fbfcfd;border-color:var(--gm-border);box-shadow:none;}'
            .'#gestion-materiel fieldset legend{color:#2e3d49;border:0;width:auto;padding:0 4px;}'
            .'#gestion-materiel .modal-dialog{width:calc(100% - 20px);max-width:1120px;}'
            .'#gestion-materiel .modal-content{border-radius:4px;box-shadow:0 8px 24px rgba(0,0,0,.25);}'
            .'#gestion-materiel .modal-header{background:var(--gm-soft);border-bottom:1px solid var(--gm-border);}'
            .'#gestion-materiel tr.gm-hidden{display:none;}'
            .'@media (max-width:767px){#gestion-materiel .gm-page-head{align-items:flex-start;flex-direction:column;}#gestion-materiel .gm-toolbar .btn{width:100%;}#gestion-materiel .gm-filter{max-width:none;width:100%;}#gestion-materiel .modal-dialog{width:calc(100% - 20px);margin:10px auto!important;}#gestion-materiel table[data-responsive-table="1"],#gestion-materiel table[data-responsive-table="1"] thead,#gestion-materiel table[data-responsive-table="1"] tbody,#gestion-materiel table[data-responsive-table="1"] tr,#gestion-materiel table[data-responsive-table="1"] th,#gestion-materiel table[data-responsive-table="1"] td{display:block;width:100%;}#gestion-materiel table[data-responsive-table="1"] thead{display:none;}#gestion-materiel table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}#gestion-materiel table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}#gestion-materiel table[data-responsive-table="1"] td:last-child{border-bottom:0;}#gestion-materiel table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}}'
        .'</style>'
        .'<script>'
            .'window.gestionMaterielInitUi=function(){'
                .'function normalize(value){'
                    .'var text=(value||"").toString().toLowerCase();'
                    .'return text.normalize?text.normalize("NFD").replace(/[\u0300-\u036f]/g,""):text;'
                .'}'
                .'function comparable(cell){'
                    .'var technical=cell.getAttribute("data-gm-sort-value");'
                    .'if(technical!==null&&technical!==""){return {type:"number",value:parseFloat(technical)||0};}'
                    .'var text=cell.textContent.trim();'
                    .'var dateMatch=text.match(/(\d{2})\/(\d{2})\/(\d{4})/);'
                    .'if(dateMatch){return {type:"number",value:Date.UTC(parseInt(dateMatch[3],10),parseInt(dateMatch[2],10)-1,parseInt(dateMatch[1],10))};}'
                    .'var number=text.replace(/\s/g,"").replace(",",".");'
                    .'if(/^-?\d+(\.\d+)?$/.test(number)){return {type:"number",value:parseFloat(number)};}'
                    .'return {type:"text",value:normalize(text)};'
                .'}'
                .'function ignoredColumn(label){'
                    .'label=normalize(label).trim();'
                    .'return label==="actions"||label==="affecter"||label==="assigner";'
                .'}'
                .'function csvCell(value){return "\""+(value||"").replace(/\"/g,"\"\"").replace(/\s*\n\s*/g," ").trim()+"\"";}'
                .'function prepareResponsiveTables(){'
                    .'var tables=document.querySelectorAll("#gestion-materiel table");'
                    .'for(var t=0;t<tables.length;t++){'
                        .'var table=tables[t];'
                        .'if(table.getAttribute("data-responsive-table")==="1"){continue;}'
                        .'var headRow=table.tHead&&table.tHead.rows.length?table.tHead.rows[0]:null;'
                        .'if(!headRow||!headRow.cells.length){continue;}'
                        .'table.setAttribute("data-responsive-table","1");'
                        .'for(var b=0;b<table.tBodies.length;b++){'
                            .'var rows=table.tBodies[b].rows;'
                            .'for(var r=0;r<rows.length;r++){'
                                .'for(var c=0;c<rows[r].cells.length;c++){'
                                    .'if(headRow.cells[c]){rows[r].cells[c].setAttribute("data-label",headRow.cells[c].textContent.trim());}'
                                .'}'
                            .'}'
                        .'}'
                    .'}'
                .'}'
                .'prepareResponsiveTables();'
                .'var inputs=document.querySelectorAll("#gestion-materiel [data-gm-filter]");'
                .'for(var i=0;i<inputs.length;i++){'
                    .'(function(input){'
                        .'if(input.getAttribute("data-gm-filter-ready")==="1"){return;}'
                        .'input.setAttribute("data-gm-filter-ready","1");'
                        .'var target=document.getElementById(input.getAttribute("data-gm-filter"));'
                        .'if(!target){return;}'
                        .'var count=document.querySelector("[data-gm-count-for=\""+input.getAttribute("data-gm-filter")+"\"]");'
                        .'var headerRow=target.tHead&&target.tHead.rows.length?target.tHead.rows[0]:null;'
                        .'var columnInputs=[];'
                        .'function refresh(){'
                            .'var query=normalize(input.value);'
                            .'var rows=target.querySelectorAll("tbody tr");'
                            .'var visible=0;'
                            .'for(var r=0;r<rows.length;r++){'
                                .'var match=normalize(rows[r].textContent).indexOf(query)!==-1;'
                                .'if(match){'
                                    .'for(var c=0;c<columnInputs.length;c++){'
                                        .'var columnFilter=columnInputs[c];'
                                        .'if(!columnFilter||!columnFilter.value){continue;}'
                                        .'var cell=rows[r].cells[c];'
                                        .'if(!cell||normalize(cell.textContent).indexOf(normalize(columnFilter.value))===-1){match=false;break;}'
                                    .'}'
                                .'}'
                                .'rows[r].classList.toggle("gm-hidden",!match);'
                                .'if(match){visible++;}'
                            .'}'
                            .'if(count){count.textContent=visible+" / "+rows.length;}'
                        .'}'
                        .'if(headerRow&&target.getAttribute("data-gm-enhanced")!=="1"){'
                            .'target.setAttribute("data-gm-enhanced","1");'
                            .'var filterRow=document.createElement("tr");'
                            .'filterRow.className="gm-column-filters";'
                            .'for(var c=0;c<headerRow.cells.length;c++){'
                                .'(function(column){'
                                    .'var label=headerRow.cells[column].textContent.trim();'
                                    .'var filterCell=document.createElement("th");'
                                    .'if(!ignoredColumn(label)){'
                                        .'var columnInput=document.createElement("input");'
                                        .'columnInput.type="search";'
                                        .'columnInput.className="form-control gm-column-filter";'
                                        .'columnInput.placeholder="Filtrer";'
                                        .'columnInput.setAttribute("aria-label","Filtrer "+label);'
                                        .'columnInput.addEventListener("input",refresh);'
                                        .'filterCell.appendChild(columnInput);'
                                        .'columnInputs[column]=columnInput;'
                                        .'headerRow.cells[column].classList.add("gm-sortable");'
                                        .'headerRow.cells[column].setAttribute("tabindex","0");'
                                        .'function sortColumn(){'
                                            .'var direction=headerRow.cells[column].getAttribute("data-gm-sort-direction")==="asc"?"desc":"asc";'
                                            .'for(var h=0;h<headerRow.cells.length;h++){'
                                                .'headerRow.cells[h].removeAttribute("data-gm-sort-direction");'
                                                .'headerRow.cells[h].classList.remove("gm-sort-asc","gm-sort-desc");'
                                            .'}'
                                            .'headerRow.cells[column].setAttribute("data-gm-sort-direction",direction);'
                                            .'headerRow.cells[column].classList.add(direction==="asc"?"gm-sort-asc":"gm-sort-desc");'
                                            .'var body=target.tBodies[0];'
                                            .'var rows=Array.prototype.slice.call(body.rows);'
                                            .'rows.sort(function(a,b){'
                                                .'var av=comparable(a.cells[column]);'
                                                .'var bv=comparable(b.cells[column]);'
                                                .'var result=av.type==="number"&&bv.type==="number"?av.value-bv.value:av.value.toString().localeCompare(bv.value.toString(),"fr",{numeric:true,sensitivity:"base"});'
                                                .'return direction==="asc"?result:-result;'
                                            .'});'
                                            .'for(var r=0;r<rows.length;r++){body.appendChild(rows[r]);}'
                                            .'refresh();'
                                        .'}'
                                        .'headerRow.cells[column].addEventListener("click",sortColumn);'
                                        .'headerRow.cells[column].addEventListener("keydown",function(event){if(event.key==="Enter"||event.key===" "){event.preventDefault();sortColumn();}});'
                                    .'}'
                                    .'filterRow.appendChild(filterCell);'
                                .'})(c);'
                            .'}'
                            .'target.tHead.appendChild(filterRow);'
                        .'}'
                        .'input.addEventListener("input",refresh);'
                        .'refresh();'
                    .'})(inputs[i]);'
                .'}'
                .'var exports=document.querySelectorAll("#gestion-materiel [data-gm-export]");'
                .'for(var e=0;e<exports.length;e++){'
                    .'(function(button){'
                        .'if(button.getAttribute("data-gm-export-ready")==="1"){return;}'
                        .'button.setAttribute("data-gm-export-ready","1");'
                        .'button.addEventListener("click",function(){'
                            .'var table=document.getElementById(button.getAttribute("data-gm-export"));'
                            .'if(!table||!table.tHead||!table.tBodies.length){return;}'
                            .'var headers=table.tHead.rows[0].cells;'
                            .'var columns=[];'
                            .'var lines=[];'
                            .'for(var c=0;c<headers.length;c++){if(!ignoredColumn(headers[c].textContent)){columns.push(c);}}'
                            .'lines.push(columns.map(function(c){return csvCell(headers[c].textContent);}).join(";"));'
                            .'var rows=table.tBodies[0].rows;'
                            .'for(var r=0;r<rows.length;r++){'
                                .'if(rows[r].classList.contains("gm-hidden")){continue;}'
                                .'lines.push(columns.map(function(c){return csvCell(rows[r].cells[c]?rows[r].cells[c].textContent:"");}).join(";"));'
                            .'}'
                            .'var blob=new Blob(["\uFEFF"+lines.join("\r\n")],{type:"text/csv;charset=utf-8;"});'
                            .'var link=document.createElement("a");'
                            .'link.href=URL.createObjectURL(blob);'
                            .'link.download=table.id+"-"+new Date().toISOString().slice(0,10)+".csv";'
                            .'document.body.appendChild(link);'
                            .'link.click();'
                            .'document.body.removeChild(link);'
                            .'setTimeout(function(){URL.revokeObjectURL(link.href);},0);'
                        .'});'
                    .'})(exports[e]);'
                .'}'
            .'};'
            .'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",window.gestionMaterielInitUi);}else{window.gestionMaterielInitUi();}'
        .'</script>';
    }

    private static function nextTableId($prefix)
    {
        static $index = 0;
        $index++;

        return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $prefix).'-'.$index;
    }

    private static function tableTools($tableId, $count, $placeholder, $export = true)
    {
        return '<div class="gm-list-tools">'
            .'<input class="form-control gm-filter" type="search" data-gm-filter="'.self::html($tableId).'" placeholder="'.self::html($placeholder).'">'
            .($export ? '<button class="btn btn-default" type="button" data-gm-export="'.self::html($tableId).'">Export CSV</button>' : '')
            .'<span class="gm-count" data-gm-count-for="'.self::html($tableId).'">'.(int) $count.' / '.(int) $count.'</span>'
        .'</div>';
    }

    private static function groupLinkFromItem($item)
    {
        $groupId = isset($item['groupe_id']) ? (int) $item['groupe_id'] : 0;
        $groupName = isset($item['groupe_nom']) ? trim((string) $item['groupe_nom']) : '';
        if ($groupId <= 0 || $groupName === '') {
            return 'Sans groupe';
        }

        return '<a href="'.self::moduleUrl(array('view' => 'group', 'id' => $groupId)).'">'.self::html($groupName).'</a>';
    }

    private static function groupAlertCountBadge($alerts)
    {
        $alerts = is_array($alerts) ? $alerts : array();
        $count = count($alerts);
        if ($count <= 0) {
            return '<span class="label label-success">Aucune</span>';
        }

        $hasOverdue = false;
        $hasAttention = false;
        foreach ($alerts as $alert) {
            $status = isset($alert['alert_status']) ? (string) $alert['alert_status'] : '';
            $hasOverdue = $hasOverdue || $status === 'overdue';
            $hasAttention = $hasAttention || $status === 'attention';
        }
        $class = $hasOverdue ? 'label-danger' : ($hasAttention ? 'label-warning' : 'label-info');

        return '<span class="label '.$class.'">'.self::html($count.' alerte'.($count > 1 ? 's' : '')).'</span>';
    }

    private static function groupAlertBadge($alert)
    {
        $type = isset($alert['alert_type']) ? (string) $alert['alert_type'] : '';
        if ($type === 'statut') {
            $status = isset($alert['statut']) ? (string) $alert['statut'] : '';
            if ($status === 'hors_service' || $status === 'panne') {
                return '<span class="label label-danger">'.self::html(self::statusLabel($status)).'</span>';
            }

            return '<span class="label label-warning">'.self::html(self::statusLabel($status)).'</span>';
        }

        return self::deadlineStatusBadge($alert);
    }

    private static function groupAlertTypeLabel($type)
    {
        if ($type === 'maintenance') {
            return 'Maintenance';
        }

        if ($type === 'etalonnage') {
            return 'Etalonnage';
        }

        if ($type === 'statut') {
            return 'Statut';
        }

        return $type;
    }

    private static function uploadDocument($itemId, $values, $login)
    {
        $errors = array();
        $types = GestionMaterielRepository::documentTypes();
        $type = isset($values['type_document']) ? (string) $values['type_document'] : '';
        $description = isset($values['description']) ? trim((string) $values['description']) : '';

        if (!isset($types[$type])) {
            $errors[] = 'Le type de document est invalide.';
        }
        if (strlen($description) > 5000) {
            $errors[] = 'La description ne doit pas depasser 5000 caracteres.';
        }
        if (!isset($_FILES['document_file']) || !is_array($_FILES['document_file'])) {
            $errors[] = 'Selectionnez un fichier.';
            return $errors;
        }

        $file = $_FILES['document_file'];
        $uploadError = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = self::uploadErrorMessage($uploadError);
            return $errors;
        }

        $originalName = self::sanitizeDocumentName(isset($file['name']) ? $file['name'] : '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, GestionMaterielConfig::documentExtensions(), true)) {
            $errors[] = 'Extension de fichier non autorisee.';
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $maxBytes = GestionMaterielConfig::documentMaxBytes();
        if ($size <= 0 || $size > $maxBytes) {
            $errors[] = 'La taille du fichier doit etre comprise entre 1 octet et '.self::formatBytes($maxBytes).'.';
        }
        if (count($errors) > 0) {
            return $errors;
        }

        if (!GestionMaterielRepository::ensureDocumentStorage()) {
            $errors[] = 'Le dossier de stockage des documents n est pas accessible en ecriture.';
            return $errors;
        }

        $storedName = self::generateDocumentStoredName();
        $targetPath = GestionMaterielRepository::documentPath($storedName);
        $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($targetPath === '' || $tmpName === '' || !move_uploaded_file($tmpName, $targetPath)) {
            $errors[] = 'Le fichier n a pas pu etre enregistre.';
            return $errors;
        }

        $actualSize = @filesize($targetPath);
        $actualSize = $actualSize === false ? 0 : (int) $actualSize;
        if ($actualSize <= 0 || $actualSize > $maxBytes) {
            @unlink($targetPath);
            $errors[] = 'La taille du fichier enregistre est invalide.';
            return $errors;
        }

        $mimeType = self::documentMimeType($targetPath, isset($file['type']) ? $file['type'] : '');
        if (!GestionMaterielRepository::addDocument(
            $itemId,
            $type,
            $description,
            $originalName,
            $storedName,
            $mimeType,
            $actualSize,
            $login
        )) {
            @unlink($targetPath);
            $errors[] = 'Le document n a pas pu etre enregistre en base.';
        }

        return $errors;
    }

    private static function itemActions($id, $returnView = '')
    {
        $id = (int) $id;
        if ($id <= 0) {
            return '';
        }

        $html = '<a class="btn btn-default btn-xs" href="'.self::moduleUrl(array('view' => 'item', 'id' => $id)).'">Voir</a>';
        if (self::canEditItem($id)) {
            $html .= ' <a class="btn btn-success btn-xs" href="'.self::moduleUrl(array('view' => 'item', 'id' => $id, 'action' => 'add')).'">Action</a>';
            $html .= ' <a class="btn btn-primary btn-xs" href="'.self::moduleUrl(array('view' => 'edit', 'id' => $id)).'">Modifier</a>';
        }
        if (self::canManageModule()) {
            $html .= ' '.self::deleteItemForm($id, $returnView, 'btn btn-danger btn-xs');
        }

        return '<div class="gm-actions">'.$html.'</div>';
    }

    private static function deleteItemForm($id, $returnView, $buttonClass)
    {
        $id = (int) $id;
        if ($id <= 0 || !self::canManageModule()) {
            return '';
        }

        $message = 'Attention : cette suppression est definitive. Le materiel, ses actions, ses documents, ses affectations utilisateur et ses notifications seront supprimes. Continuer ?';
        $targetParams = array();
        if ((string) $returnView === 'archives') {
            $targetParams['view'] = 'archives';
        }

        return '<form class="gm-delete-form" method="post" action="'.self::moduleUrl($targetParams).'" onsubmit="return window.confirm('.self::html(json_encode($message)).');">'
            .'<input type="hidden" name="gm_action" value="delete_item">'
            .'<input type="hidden" name="item_id" value="'.$id.'">'
            .'<input type="hidden" name="delete_confirmed" value="1">'
            .'<input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<button class="'.self::html($buttonClass).'" type="submit">Supprimer</button>'
        .'</form>';
    }

    private static function deleteDocumentForm($documentId, $itemId, $originalName)
    {
        $documentId = (int) $documentId;
        $itemId = (int) $itemId;
        if ($documentId <= 0 || $itemId <= 0 || !self::canEditItem($itemId)) {
            return '';
        }

        $message = 'Supprimer definitivement le document '.trim((string) $originalName).' ?';

        return '<form class="gm-delete-form" method="post" action="'.self::moduleUrl(array('view' => 'item', 'id' => $itemId)).'#gm-documents" onsubmit="return window.confirm('.self::html(json_encode($message)).');">'
            .'<input type="hidden" name="gm_action" value="delete_document">'
            .'<input type="hidden" name="document_id" value="'.$documentId.'">'
            .'<input type="hidden" name="item_id" value="'.$itemId.'">'
            .'<input type="hidden" name="delete_confirmed" value="1">'
            .'<button class="btn btn-danger btn-xs" type="submit">Supprimer</button>'
        .'</form>';
    }

    private static function deleteActionForm($actionId, $itemId, $returnView)
    {
        $actionId = (int) $actionId;
        $itemId = (int) $itemId;
        if ($actionId <= 0 || !self::isAdmin()) {
            return '';
        }

        $message = 'Attention : cette suppression est definitive. Supprimer cette action ?';
        $targetParams = $returnView === 'item'
            ? array('view' => 'item', 'id' => $itemId)
            : array('view' => 'actions');

        return '<form class="gm-delete-form" method="post" action="'.self::moduleUrl($targetParams).'" onsubmit="return window.confirm('.self::html(json_encode($message)).');">'
            .'<input type="hidden" name="gm_action" value="delete_action">'
            .'<input type="hidden" name="action_id" value="'.$actionId.'">'
            .'<input type="hidden" name="item_id" value="'.$itemId.'">'
            .'<input type="hidden" name="delete_confirmed" value="1">'
            .'<input type="hidden" name="return_view" value="'.self::html($returnView).'">'
            .'<button class="btn btn-danger btn-xs" type="submit">Supprimer</button>'
        .'</form>';
    }

    private static function detailRow($label, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            $value = '-';
        }

        return '<tr><th style="width: 260px;">'.self::html($label).'</th><td>'.self::html($value).'</td></tr>';
    }

    private static function detailHtmlRow($label, $htmlValue)
    {
        $htmlValue = trim((string) $htmlValue);
        if ($htmlValue === '') {
            $htmlValue = '-';
        }

        return '<tr><th style="width: 260px;">'.self::html($label).'</th><td>'.$htmlValue.'</td></tr>';
    }

    private static function itemToFormValues($item)
    {
        $values = GestionMaterielRepository::emptyItemValues();
        foreach ($values as $key => $value) {
            if (isset($item[$key])) {
                $values[$key] = (string) $item[$key];
            }
        }

        foreach (array('date_acquisition', 'date_fin_garantie', 'maintenance_prochaine', 'etalonnage_prochain') as $dateField) {
            $values[$dateField] = self::formatDateInput(isset($item[$dateField]) ? $item[$dateField] : 0);
        }

        foreach (array('maintenance_interval_jours', 'etalonnage_interval_jours') as $intervalField) {
            $interval = isset($item[$intervalField]) ? (int) $item[$intervalField] : 0;
            $values[$intervalField] = $interval > 0 ? (string) $interval : '';
        }

        return $values;
    }

    private static function moduleUrl($params = array())
    {
        $url = 'compte.php?pc=gestion_materiel';
        foreach ($params as $key => $value) {
            $url .= '&amp;'.rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return $url;
    }

    private static function alerts($messages, $type)
    {
        $html = '';
        foreach ($messages as $message) {
            $html .= '<div class="alert alert-'.self::html($type).'">'.self::html($message).'</div>';
        }

        return $html;
    }

    private static function input($name, $label, $value, $columns, $required = false, $type = 'text', $extraAttributes = '')
    {
        $requiredAttr = $required ? ' required' : '';
        $minAttr = $type === 'number' ? ' min="0"' : '';

        return '<div class="col-md-'.(int) $columns.'">'
            .'<div class="form-group">'
                .'<label for="gm_'.$name.'">'.self::html($label).'</label>'
                .'<input class="form-control" id="gm_'.$name.'" type="'.self::html($type).'" name="'.self::html($name).'" value="'.self::html($value).'"'.$requiredAttr.$minAttr.$extraAttributes.'>'
            .'</div>'
        .'</div>';
    }

    private static function select($name, $label, $value, $options, $columns)
    {
        $html = '<div class="col-md-'.(int) $columns.'">'
            .'<div class="form-group">'
            .'<label for="gm_'.$name.'">'.self::html($label).'</label>'
            .'<select class="form-control" id="gm_'.$name.'" name="'.self::html($name).'">';

        foreach ($options as $key => $optionLabel) {
            $selected = ((string) $key === (string) $value) ? ' selected' : '';
            $html .= '<option value="'.self::html($key).'"'.$selected.'>'.self::html($optionLabel).'</option>';
        }

        return $html.'</select></div></div>';
    }

    private static function canManage()
    {
        return self::canManageModule();
    }

    private static function canAccessModule($login = null)
    {
        return GestionMaterielRights::canAccessModule($login);
    }

    private static function canManageModule($login = null)
    {
        return GestionMaterielRights::canManageModule($login);
    }

    private static function canCreateItem()
    {
        return self::canManageModule();
    }

    private static function canViewItem($itemId)
    {
        return GestionMaterielRights::canViewItem($itemId);
    }

    private static function canViewGroup($groupId)
    {
        return GestionMaterielRights::canViewGroup($groupId);
    }

    private static function canEditItem($itemId)
    {
        $item = GestionMaterielRepository::item($itemId);
        return $item && !self::isArchivedItem($item) && self::canViewItem($itemId);
    }

    private static function isArchivedItem($item)
    {
        return is_array($item)
            && (
                (isset($item['statut']) && (string) $item['statut'] === 'archive')
                || (isset($item['actif']) && (int) $item['actif'] !== 1)
            );
    }

    private static function isAdmin($login = null)
    {
        return GestionMaterielRights::isAdmin($login);
    }

    private static function currentLogin()
    {
        return GestionMaterielRights::currentLogin();
    }

    private static function statusLabel($status)
    {
        $statuses = GestionMaterielRepository::itemStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    private static function statusBadge($status)
    {
        $status = (string) $status;
        $class = 'gm-status gm-status-'.preg_replace('/[^a-z0-9_-]/', '', $status);

        return '<span class="'.self::html($class).'">'.self::html(self::statusLabel($status)).'</span>';
    }

    private static function actionLabel($type)
    {
        $types = GestionMaterielRepository::actionTypes();
        return isset($types[$type]) ? $types[$type] : $type;
    }

    private static function actionTypesForItem($itemId)
    {
        $types = GestionMaterielRepository::actionTypes();
        $item = GestionMaterielRepository::item($itemId);
        $status = isset($item['statut']) ? (string) $item['statut'] : '';

        if ($status !== 'en_service') {
            unset($types['fin_projet']);
        }
        if ($status !== 'sans_projet') {
            unset($types['debut_projet']);
        }

        return $types;
    }

    private static function notificationTypeLabel($type)
    {
        if ($type === 'etalonnage') {
            return 'Etalonnage';
        }

        if ($type === 'maintenance') {
            return 'Maintenance';
        }

        return $type;
    }

    private static function yesNo($value)
    {
        return ((int) $value === 1) ? 'Oui' : 'Non';
    }

    private static function yesNoBadge($value)
    {
        $yes = ((int) $value === 1);
        return '<span class="gm-yesno '.($yes ? 'gm-yes' : 'gm-no').'">'.self::html($yes ? 'Oui' : 'Non').'</span>';
    }

    private static function statusLinkStyle($status)
    {
        $color = $status === 'attention'
            ? '#f39c12'
            : GestionMaterielConfig::alertLinkColor($status);

        return 'background-color:'.self::html($color).'; color:#fff; display:inline-block; padding:2px 6px; margin:2px 0; text-decoration:none;';
    }

    private static function checked($checked)
    {
        return $checked ? ' checked' : '';
    }

    private static function sanitizeDocumentName($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[[:cntrl:]]/', '', $name);
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'fichier';
        }

        if (strlen($name) > 255) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $base = substr(pathinfo($name, PATHINFO_FILENAME), 0, 230);
            $name = $extension !== '' ? $base.'.'.substr($extension, 0, 20) : substr($name, 0, 255);
        }

        return $name;
    }

    private static function generateDocumentStoredName()
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(20));
            } catch (Exception $exception) {
                // Fallback below.
            }
        }

        return sha1(uniqid('', true).mt_rand());
    }

    private static function documentMimeType($path, $fallback)
    {
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string) finfo_file($finfo, $path);
                finfo_close($finfo);
            }
        }
        if ($mimeType === '') {
            $mimeType = trim((string) $fallback);
        }
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        return substr(preg_replace('/[\r\n]/', '', $mimeType), 0, 190);
    }

    private static function uploadErrorMessage($error)
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'Le fichier depasse la taille maximale autorisee.';
        }
        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Selectionnez un fichier.';
        }

        return 'Le fichier n a pas pu etre transfere.';
    }

    private static function formatBytes($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes < 1024) {
            return $bytes.' o';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' Ko';
        }

        return round($bytes / 1048576, 1).' Mo';
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('d/m/Y', $timestamp) : '-';
    }

    private static function deadlineBadge($timestamp, $attention = false)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '-';
        }

        $today = self::todayStart();
        if ($timestamp < $today) {
            return '<span class="label '.($attention ? 'label-warning' : 'label-danger').'">'.self::html(self::formatDate($timestamp).' - en retard').'</span>';
        }

        if ($timestamp === $today) {
            return '<span class="label label-warning">'.self::html(self::formatDate($timestamp).' - aujourd hui').'</span>';
        }

        return self::html(self::formatDate($timestamp));
    }

    private static function deadlineStatusBadge($alert)
    {
        $status = isset($alert['alert_status']) ? (string) $alert['alert_status'] : '';
        $delta = isset($alert['days_delta']) ? (int) $alert['days_delta'] : 0;

        if ($status === 'overdue') {
            $days = abs($delta);
            return '<span class="label label-danger">'.self::html('En retard de '.$days.' jour'.($days > 1 ? 's' : '')).'</span>';
        }

        if ($status === 'attention') {
            $days = abs($delta);
            return '<span class="label label-warning">'.self::html('En retard de '.$days.' jour'.($days > 1 ? 's' : '')).'</span>';
        }

        if ($status === 'today') {
            return '<span class="label label-warning">Aujourd hui</span>';
        }

        return '<span class="label label-info">'.self::html('Dans '.$delta.' jour'.($delta > 1 ? 's' : '')).'</span>';
    }

    private static function formatDateInput($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('Y-m-d', $timestamp) : '';
    }

    private static function formatDateTime($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('d/m/Y H:i', $timestamp) : '-';
    }

    private static function formatInterval($days)
    {
        $days = (int) $days;
        return $days > 0 ? $days.' jours' : '-';
    }

    private static function formatCost($cost)
    {
        $cost = (float) $cost;
        return $cost > 0 ? number_format($cost, 2, ',', ' ').' EUR' : '-';
    }

    private static function htmlMultiline($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }

        return nl2br(self::html($value));
    }

    private static function accountUrl($params)
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?'.http_build_query($params, '', '&');
    }

    private static function summaryCard($label, $value, $color, $url, $columnClass)
    {
        $color = GestionMaterielConfig::normalizeColor($color, '#17a2b8');
        $textColor = self::summaryTextColor($color);
        $footer = trim((string) $url) !== ''
            ? '<a class="small-box-footer" href="'.$url.'">Voir</a>'
            : '';

        return '<div class="'.self::html($columnClass).'">'
            .'<div class="small-box" style="background-color:'.self::html($color).';color:'.self::html($textColor).';">'
                .'<div class="inner">'
                    .'<h3>'.self::html($value).'</h3>'
                    .'<p>'.self::html($label).'</p>'
                .'</div>'
                .$footer
            .'</div>'
        .'</div>';
    }

    private static function summaryTextColor($color)
    {
        $color = ltrim((string) $color, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return '#ffffff';
        }

        $red = hexdec(substr($color, 0, 2));
        $green = hexdec(substr($color, 2, 2));
        $blue = hexdec(substr($color, 4, 2));
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness >= 160 ? '#212529' : '#ffffff';
    }

    private static function itemDeadlineRowClass($item)
    {
        if (self::isOverdueDeadline(isset($item['maintenance_prochaine']) ? $item['maintenance_prochaine'] : 0)) {
            return 'danger';
        }

        if (self::isOverdueDeadline(isset($item['etalonnage_prochain']) ? $item['etalonnage_prochain'] : 0)) {
            return isset($item['statut']) && (string) $item['statut'] === 'sans_projet'
                ? 'warning'
                : 'danger';
        }

        return '';
    }

    private static function alertRowClass($alert)
    {
        $status = isset($alert['alert_status']) ? (string) $alert['alert_status'] : '';
        if ($status === 'overdue') {
            return 'danger';
        }
        if ($status === 'attention') {
            return 'warning';
        }

        return '';
    }

    private static function isOverdueDeadline($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 && $timestamp < self::todayStart();
    }

    private static function todayStart()
    {
        $today = strtotime(date('Y-m-d 00:00:00'));
        return $today === false ? time() : (int) $today;
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
