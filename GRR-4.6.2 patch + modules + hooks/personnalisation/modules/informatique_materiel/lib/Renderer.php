<?php

class InformatiqueMaterielRenderer
{
    private static $importPreview = null;
    private static $importResult = null;
    private static $ldapPersonSearch = null;
    private static $ldapPersonSearchTerm = '';

    public static function accountMenu()
    {
        if (!InformatiqueMaterielConfig::isEnabled() || !InformatiqueMaterielSecurity::canAccess()) {
            return '';
        }

        return '<br><br><a href="'.self::html(self::url(array())).'" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 imat-account-btn">'
            .self::html(InformatiqueMaterielConfig::displayName()).'</a>';
    }

    public static function statusSummaryLinks()
    {
        if (!InformatiqueMaterielConfig::isEnabled()
            || !InformatiqueMaterielConfig::alertsEnabled()
            || !InformatiqueMaterielSecurity::canAccess()) {
            return '';
        }

        $counts = InformatiqueMaterielRepository::alertCounts();
        $count = (int) $counts['total'];
        if ($count <= 0) {
            return '';
        }

        return '<p class="informatique-materiel-status" style="text-align:center;">'
            .'<a href="'.self::html(self::url(array('view' => 'alerts'))).'" '
            .'style="display:inline-block;padding:2px 7px;background:'.self::html(InformatiqueMaterielConfig::alertDangerColor()).';color:#fff;border-radius:3px">'
            .self::html($count.' alerte'.($count > 1 ? 's' : '').' informatique').'</a></p>';
    }

    public static function accountPage()
    {
        if (!isset($_GET['pc']) || (string) $_GET['pc'] !== InformatiqueMaterielConfig::MODULE) {
            return '';
        }

        if (!InformatiqueMaterielConfig::isEnabled()) {
            return '<div class="alert alert-warning">Module desactive.</div>';
        }

        InformatiqueMaterielRepository::ensureTables();
        $messages = array();
        $errors = array();
        $view = isset($_GET['view']) ? (string) $_GET['view'] : '';
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (isset($_GET['admin']) && (string) $_GET['admin'] === '1') {
            if (!InformatiqueMaterielSecurity::isAdmin()) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            ob_start();
            $informatique_materiel_admin_embedded = true;
            include __DIR__.'/../admin.php';
            return '<section id="informatique-materiel">'.self::assets().ob_get_clean().'</section>';
        }

        if ($view === 'user') {
            if (!InformatiqueMaterielSecurity::canViewUserEquipment()) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            return self::userPage($messages, $errors);
        }

        if (!InformatiqueMaterielSecurity::canAccess()) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!InformatiqueMaterielSecurity::validatePost()) {
                $errors[] = 'Session de formulaire invalide. Rechargez la page.';
            } else {
                self::handlePost($view, $id, $messages, $errors);
            }
        }

        if ($view === 'categories') {
            return self::categoriesPage($id, $messages, $errors);
        }
        if ($view === 'people') {
            return self::peoplePage($id, $messages, $errors);
        }
        if ($view === 'person') {
            return self::personPage($id, $messages, $errors);
        }
        if ($view === 'people_associations') {
            return self::peopleAssociationsPage($messages, $errors);
        }
        if ($view === 'items') {
            return self::itemsPage($id, $messages, $errors);
        }
        if ($view === 'item') {
            return self::itemPage($id, $messages, $errors);
        }
        if ($view === 'loans') {
            return self::loansPage($id, $messages, $errors);
        }
        if ($view === 'loan') {
            return self::loanPage($id, $messages, $errors);
        }
        if ($view === 'alerts') {
            return self::alertsPage($messages, $errors);
        }
        if ($view === 'conflicts') {
            return self::conflictsPage($messages, $errors);
        }
        if ($view === 'import') {
            return self::importPage($messages, $errors);
        }

        return self::dashboard($messages, $errors);
    }

    private static function handlePost(&$view, &$id, &$messages, &$errors)
    {
        $action = isset($_POST['imat_action']) ? (string) $_POST['imat_action'] : '';
        $login = InformatiqueMaterielSecurity::currentLogin();

        if ($action === 'save_category' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'categories';
            $result = InformatiqueMaterielRepository::saveCategory($_POST, $login);
            if (!empty($result['ok'])) {
                $id = 0;
            }
            self::resultMessage($result, 'Categorie enregistree.', $messages, $errors);
        } elseif ($action === 'archive_category' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'categories';
            self::booleanMessage(
                InformatiqueMaterielRepository::archiveCategory(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login),
                'Categorie archivee.',
                'Archivage de la categorie impossible.',
                $messages,
                $errors
            );
        } elseif ($action === 'delete_category' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'categories';
            $result = InformatiqueMaterielRepository::deleteCategory(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login);
            self::resultMessage($result, 'Categorie supprimee.', $messages, $errors);
        } elseif ($action === 'save_person' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            $result = InformatiqueMaterielRepository::savePerson($_POST, $login);
            if (!empty($result['ok'])) {
                $id = 0;
            }
            self::resultMessage($result, 'Personne enregistree.', $messages, $errors);
        } elseif ($action === 'archive_person' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            self::booleanMessage(
                InformatiqueMaterielRepository::archivePerson(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login),
                'Personne archivee.',
                'Archivage de la personne impossible.',
                $messages,
                $errors
            );
        } elseif ($action === 'delete_person' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            $result = InformatiqueMaterielRepository::deletePerson(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login);
            self::resultMessage($result, 'Personne supprimee.', $messages, $errors);
        } elseif ($action === 'extend_person_departure' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            $result = InformatiqueMaterielRepository::extendPersonDepartureFromAlert($_POST, $login);
            self::resultMessage($result, 'Date de depart prolongee.', $messages, $errors);
        } elseif ($action === 'save_person_associations' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people_associations';
            $result = InformatiqueMaterielRepository::savePersonAssociations($_POST, $login);
            if (!empty($result['ok'])) {
                $messages[] = 'Associations enregistrees : '.(int) $result['updated'].' modification(s).';
            } else {
                self::resultMessage($result, '', $messages, $errors);
            }
        } elseif ($action === 'search_ldap_person' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            self::$ldapPersonSearchTerm = trim((string) (isset($_POST['ldap_search']) ? $_POST['ldap_search'] : ''));
            if (self::$ldapPersonSearchTerm === '') {
                $errors[] = 'Saisissez un terme de recherche LDAP.';
                self::$ldapPersonSearch = array();
            } elseif (!class_exists('InformatiqueMaterielLdapDirectory')) {
                $errors[] = 'Recherche LDAP indisponible.';
                self::$ldapPersonSearch = array();
            } else {
                self::$ldapPersonSearch = InformatiqueMaterielLdapDirectory::searchByText(self::$ldapPersonSearchTerm, 20);
                $messages[] = count(self::$ldapPersonSearch).' resultat(s) LDAP.';
            }
        } elseif ($action === 'create_person_from_ldap' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'people';
            $result = InformatiqueMaterielRepository::createPersonFromLdap($_POST, $login);
            if (!empty($result['ok']) && isset($result['id'])) {
                $id = (int) $result['id'];
            }
            self::resultMessage($result, 'Personne creee depuis LDAP.', $messages, $errors);
        } elseif ($action === 'save_item' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'items';
            $result = InformatiqueMaterielRepository::saveItem($_POST, $login);
            if (!empty($result['ok']) && isset($result['id'])) {
                $id = 0;
            }
            self::resultMessage($result, 'Materiel enregistre.', $messages, $errors);
        } elseif ($action === 'archive_item' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'items';
            self::booleanMessage(
                InformatiqueMaterielRepository::archiveItem(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login),
                'Materiel archive.',
                'Archivage du materiel impossible.',
                $messages,
                $errors
            );
        } elseif ($action === 'delete_item' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'items';
            $result = InformatiqueMaterielRepository::deleteItem(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login);
            self::resultMessage($result, 'Materiel supprime.', $messages, $errors);
        } elseif ($action === 'upload_document' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'item';
            $id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
            $result = self::uploadDocument($id, $_POST, $login);
            self::resultMessage($result, 'Document ajoute.', $messages, $errors);
        } elseif ($action === 'archive_document' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'item';
            $id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;
            self::booleanMessage(
                InformatiqueMaterielRepository::archiveDocument(isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0, $login),
                'Document archive.',
                'Archivage du document impossible.',
                $messages,
                $errors
            );
        } elseif ($action === 'create_loan' && InformatiqueMaterielSecurity::canOperate()) {
            $view = 'loans';
            $result = InformatiqueMaterielRepository::createLoan($_POST, $login);
            if (!empty($result['ok']) && isset($result['id'])) {
                $id = (int) $result['id'];
            }
            self::resultMessage($result, 'Pret cree.', $messages, $errors);
        } elseif ($action === 'close_loan' && InformatiqueMaterielSecurity::canOperate()) {
            $view = 'loans';
            $result = InformatiqueMaterielRepository::closeLoan($_POST, $login);
            self::resultMessage($result, 'Pret restitue.', $messages, $errors);
        } elseif ($action === 'transfer_loan' && InformatiqueMaterielSecurity::canOperate()) {
            $result = InformatiqueMaterielRepository::transferLoan($_POST, $login);
            self::resultMessage($result, 'Pret transfere.', $messages, $errors);
        } elseif ($action === 'transfer_person_loans' && InformatiqueMaterielSecurity::canManage()) {
            $result = InformatiqueMaterielRepository::transferPersonOpenLoans($_POST, $login);
            if (!empty($result['ok'])) {
                $messages[] = 'Transfert effectue : '.(int) $result['count'].' pret(s).';
            } else {
                self::resultMessage($result, '', $messages, $errors);
            }
        } elseif ($action === 'align_person_loans_departure' && InformatiqueMaterielSecurity::isAdmin()) {
            $view = 'person';
            $id = isset($_POST['personne_id']) ? (int) $_POST['personne_id'] : 0;
            $result = InformatiqueMaterielRepository::alignPersonOpenLoansToDeparture($_POST, $login);
            if (!empty($result['ok'])) {
                $messages[] = 'Fins de prets mises a jour : '.(int) $result['count'].' pret(s).';
            } else {
                self::resultMessage($result, '', $messages, $errors);
            }
        } elseif ($action === 'cancel_loan' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'loans';
            $result = InformatiqueMaterielRepository::cancelLoan($_POST, $login);
            self::resultMessage($result, 'Pret annule.', $messages, $errors);
        } elseif ($action === 'delete_loan' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'loans';
            $result = InformatiqueMaterielRepository::deleteLoan(isset($_POST['id']) ? (int) $_POST['id'] : 0, $login);
            self::resultMessage($result, 'Pret supprime.', $messages, $errors);
        } elseif ($action === 'extend_loan' && InformatiqueMaterielSecurity::canOperate()) {
            $view = 'loan';
            $id = isset($_POST['pret_id']) ? (int) $_POST['pret_id'] : 0;
            $result = InformatiqueMaterielRepository::extendLoanDueDateFromAlert($_POST, $login);
            self::resultMessage($result, 'Date de retour prolongee.', $messages, $errors);
        } elseif ($action === 'extend_alert') {
            $view = 'alerts';
            $alertType = isset($_POST['alert_type']) ? (string) $_POST['alert_type'] : '';
            if ($alertType === 'pret_en_retard' && InformatiqueMaterielSecurity::canOperate()) {
                $result = InformatiqueMaterielRepository::extendLoanDueDateFromAlert($_POST, $login);
                self::resultMessage($result, 'Date de retour prolongee.', $messages, $errors);
            } elseif ($alertType === 'personne_partie' && InformatiqueMaterielSecurity::canManage()) {
                $result = InformatiqueMaterielRepository::extendPersonDepartureFromAlert($_POST, $login);
                self::resultMessage($result, 'Date de depart prolongee.', $messages, $errors);
            } else {
                $errors[] = 'Vous n avez pas le droit de prolonger cette alerte.';
            }
        } elseif ($action === 'upload_import' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'import';
            if (!self::ensureImportClass($errors)) {
                return;
            }
            $type = isset($_POST['import_type']) ? (string) $_POST['import_type'] : '';
            $file = isset($_FILES['import_file']) ? $_FILES['import_file'] : array();
            $upload = InformatiqueMaterielImport::saveUpload($file, $type, $login);
            if (!empty($upload['ok'])) {
                self::$importPreview = InformatiqueMaterielImport::preview(
                    $upload['type'],
                    $upload['hash'],
                    $upload['stored_name'],
                    $upload['original_name']
                );
                $messages[] = 'Previsualisation generee.';
            } else {
                self::resultMessage($upload, '', $messages, $errors);
            }
        } elseif ($action === 'execute_import' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'import';
            if (!self::ensureImportClass($errors)) {
                return;
            }
            $type = isset($_POST['import_type']) ? (string) $_POST['import_type'] : '';
            $hash = isset($_POST['package_hash']) ? (string) $_POST['package_hash'] : '';
            $storedName = isset($_POST['stored_name']) ? (string) $_POST['stored_name'] : '';
            $originalName = isset($_POST['original_name']) ? (string) $_POST['original_name'] : '';
            self::$importResult = InformatiqueMaterielImport::execute($type, $hash, $storedName, $originalName, $login);
            $messages[] = 'Import execute : '.(int) self::$importResult['created'].' lignes creees, '.(int) self::$importResult['conflicts'].' conflits en attente, '.(int) self::$importResult['skipped'].' ignorees, '.(int) self::$importResult['errors'].' erreurs.';
        } elseif ($action === 'resolve_loan_conflict' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'conflicts';
            $result = InformatiqueMaterielRepository::resolveLoanConflict($_POST, $login);
            self::resultMessage($result, 'Conflit de pret resolu.', $messages, $errors);
        } elseif ($action === 'delete_loan_conflict' && InformatiqueMaterielSecurity::canManage()) {
            $view = 'conflicts';
            $result = InformatiqueMaterielRepository::deleteLoanConflict(isset($_POST['conflict_id']) ? (int) $_POST['conflict_id'] : 0, $login);
            self::resultMessage($result, 'Conflit supprime.', $messages, $errors);
        } elseif ($action !== '') {
            $errors[] = 'Action inconnue ou acces refuse.';
        }
    }

    private static function dashboard($messages, $errors)
    {
        $counts = InformatiqueMaterielRepository::dashboardCounts();
        $alerts = InformatiqueMaterielRepository::alerts(10);
        $role = InformatiqueMaterielSecurity::role();
        $html = self::startPage($messages, $errors);
        $html .= '<div class="imat-header"><h1>'.self::html(InformatiqueMaterielConfig::displayName()).'</h1>';
        $html .= '<p>Referentiels, inventaire, prets et restitutions disponibles.</p>';
        if (InformatiqueMaterielSecurity::isAdmin()) {
            $html .= '<p><a class="btn btn-default" href="'.self::url(array('admin' => '1')).'">Administration du module</a></p>';
        }
        $html .= '</div>';
        $html .= '<div class="imat-cards">';
        $html .= self::card('Personnes', $counts['personnes']);
        $html .= self::card('Categories', isset($counts['categories']) ? $counts['categories'] : 0);
        $html .= self::card('Materiels', $counts['materiels']);
        $html .= self::card('Prets ouverts', $counts['prets_ouverts']);
        if (!empty($counts['conflits_prets'])) {
            $html .= self::card('Conflits prets', $counts['conflits_prets']);
        }
        $html .= self::card('Documents', isset($counts['documents']) ? $counts['documents'] : 0);
        $html .= self::card('Alertes', $counts['alertes']);
        $html .= '</div>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'people')).'">Personnes</a> ';
        $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'categories')).'">Categories</a> ';
        $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'items')).'">Materiels</a> ';
        $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'loans')).'">Prets</a>';
        $html .= ' <a class="btn btn-primary" href="'.self::url(array('view' => 'alerts')).'">Alertes</a>';
        if (InformatiqueMaterielSecurity::canManage()) {
            if (!empty($counts['conflits_prets'])) {
                $html .= ' <a class="btn btn-warning" href="'.self::url(array('view' => 'conflicts')).'">Conflits</a>';
            }
            $html .= ' <a class="btn btn-primary" href="'.self::url(array('view' => 'import')).'">Import CSV</a>';
        }
        $html .= '</div>';
        $html .= self::dashboardAlertsPanel($alerts, (int) $counts['alertes']);
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Etat du socle</strong></div><div class="panel-body">';
        $html .= '<p>Role courant : <strong>'.self::html($role).'</strong></p>';
        $html .= '<p>Roles attribues : '.(int) $counts['roles'].' ; evenements journalises : '.(int) $counts['journal'].'.</p>';
        $html .= '</div></div>';
        $html .= '</div>';

        return $html;
    }

    private static function userPage($messages, $errors)
    {
        $currentLogin = InformatiqueMaterielSecurity::currentLogin();
        $login = isset($_GET['login']) ? trim((string) $_GET['login']) : $currentLogin;
        if (!InformatiqueMaterielSecurity::canManage() || $login === '') {
            $login = $currentLogin;
        }

        $people = InformatiqueMaterielRepository::peopleForLogin($login);
        $openLoans = InformatiqueMaterielRepository::loansForLogin($login, false);
        $allLoans = InformatiqueMaterielRepository::loansForLogin($login, true);

        $html = self::startPage($messages, $errors, true);
        $html .= '<div class="imat-header"><h1>Mon materiel informatique</h1>';
        $html .= '<p>Compte GRR : <strong>'.self::html($login).'</strong></p></div>';

        $html .= '<div class="imat-cards">';
        $html .= self::card('Personnes associees', count($people));
        $html .= self::card('Prets ouverts', count($openLoans));
        $html .= self::card('Historique', count($allLoans));
        $html .= '</div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Personnes associees</strong></div><div class="panel-body">';
        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Identifiant personnel</th><th>Nom</th><th>Cadre</th><th>Date depart</th><th>Etat</th></tr></thead><tbody>';
        foreach ($people as $person) {
            $label = trim((string) $person['prenom'].' '.(string) $person['nom']);
            $identifier = (string) $person['identifiant_legacy'] !== '' ? (string) $person['identifiant_legacy'] : '#'.(int) $person['id'];
            $html .= '<tr><td>'.self::html($identifier).'</td><td>'.self::html($label).'</td><td>'.self::html($person['cadre_usage']).'</td><td>'.self::html($person['date_depart']).'</td><td>'.self::statusLabel((int) $person['actif'] === 1).'</td></tr>';
        }
        if (count($people) === 0) {
            $html .= '<tr><td colspan="5">Aucune personne associee a ce compte GRR.</td></tr>';
        }
        $html .= '</tbody></table></div></div></div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Materiel actuellement associe</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable($openLoans, false);
        $html .= '</div></div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Historique</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable($allLoans, false);
        $html .= '</div></div></div>';

        return $html;
    }

    private static function alertsPage($messages, $errors)
    {
        $counts = InformatiqueMaterielRepository::alertCounts();
        $alerts = InformatiqueMaterielRepository::alerts(300);
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Alertes</h1>';
        $html .= '<div class="imat-actions"><a class="btn btn-default" href="'.self::url(array()).'">Retour au tableau de bord</a></div>';

        if (!InformatiqueMaterielConfig::alertsEnabled()) {
            $html .= '<div class="alert alert-warning">Les alertes sont desactivees par l administrateur.</div></div>';
            return $html;
        }

        $html .= '<div class="imat-cards">';
        $html .= self::card('Prets en retard', $counts['prets_en_retard']);
        $html .= self::card('Personnes parties', $counts['personnes_parties_avec_pret']);
        $html .= self::card('Sans identifiant', $counts['materiels_sans_identifiant']);
        $html .= self::card('Sans categorie', $counts['materiels_sans_categorie']);
        $html .= self::card('Codes-barres doubles', $counts['codes_barres_dupliques']);
        $html .= self::card('Prets multiples non generiques', $counts['prets_ouverts_multiples']);
        $html .= '</div>';

        $panelClass = (int) $counts['total'] > 0 ? 'danger' : 'default';
        $alertColor = InformatiqueMaterielConfig::alertDangerColor();
        $panelStyle = (int) $counts['total'] > 0 ? ' style="border-color:'.self::html($alertColor).'"' : '';
        $headingStyle = (int) $counts['total'] > 0 ? ' style="background-color:'.self::html($alertColor).';border-color:'.self::html($alertColor).';color:#fff"' : '';
        $html .= '<div class="panel panel-'.$panelClass.'" id="imat-alerts"'.$panelStyle.'><div class="panel-heading"'.$headingStyle.'><strong>Alertes operationnelles</strong></div><div class="panel-body">';
        $html .= self::alertRowsTable($alerts, true);
        $html .= '</div></div></div>';

        return $html;
    }

    private static function categoriesPage($id, $messages, $errors)
    {
        $includeArchived = isset($_GET['archived']) && (string) $_GET['archived'] === '1';
        $edit = $id > 0 ? InformatiqueMaterielRepository::category($id) : array();
        $values = $edit ? $edit : InformatiqueMaterielRepository::emptyCategoryValues();
        $categories = InformatiqueMaterielRepository::categories($includeArchived);
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Categories de materiel</h1>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/informatique_materiel/export.php?type=categories">Exporter CSV</a> ';
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= self::modalButton('imat-category-form', $edit ? 'Modifier la categorie' : 'Ajouter une categorie').' ';
        }
        $html .= $includeArchived
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'categories')).'">Masquer les archives</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'categories', 'archived' => '1')).'">Voir les archives</a>';
        $html .= '</div>';

        if (InformatiqueMaterielSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'categories', 'id' => (int) $values['id'])).'">';
            $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="save_category"><input type="hidden" name="id" value="'.(int) $values['id'].'">';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>Prefixe</label><input class="form-control" name="prefixe" maxlength="20" required value="'.self::html($values['prefixe']).'"></div>';
            $form .= '<div class="col-md-9 form-group"><label>Designation</label><input class="form-control" name="designation" maxlength="190" required value="'.self::html($values['designation']).'"></div></div>';
            $form .= '<div class="form-group"><label>Description</label><textarea class="form-control" name="description" rows="2">'.self::html($values['description']).'</textarea></div>';
            $form .= '<button class="btn btn-primary" type="submit">Enregistrer</button> ';
            if ($edit) {
                $form .= '<a class="btn btn-default" href="'.self::url(array('view' => 'categories')).'">Annuler</a>';
            }
            $form .= '</form>';
            $html .= self::modal('imat-category-form', $edit ? 'Modifier une categorie' : 'Ajouter une categorie', $form, (bool) $edit);
        }

        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Prefixe</th><th>Designation</th><th>Sequence</th><th>Etat</th><th></th></tr></thead><tbody>';
        foreach ($categories as $category) {
            $categoryLink = '<a href="'.self::url(array('view' => 'items', 'categorie_id' => (int) $category['id'])).'">'.self::html($category['designation']).'</a>';
            $html .= '<tr><td>'.self::html($category['prefixe']).'</td><td>'.$categoryLink.'</td><td>'.(int) $category['dernier_numero'].'</td><td>'.self::statusLabel((int) $category['actif'] === 1).'</td><td>';
            if (InformatiqueMaterielSecurity::canManage()) {
                $html .= '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'categories', 'id' => (int) $category['id'], 'archived' => $includeArchived ? '1' : '0')).'">Modifier</a> ';
                if ((int) $category['actif'] === 1) {
                    $html .= '<form method="post" action="'.self::url(array('view' => 'categories')).'" style="display:inline" onsubmit="return confirm(\'Archiver cette categorie ?\')">';
                    $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="archive_category"><input type="hidden" name="id" value="'.(int) $category['id'].'">';
                    $html .= '<button class="btn btn-warning btn-xs" type="submit">Archiver</button></form>';
                }
                $html .= ' <form method="post" action="'.self::url(array('view' => 'categories')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement cette categorie ?\')">';
                $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_category"><input type="hidden" name="id" value="'.(int) $category['id'].'">';
                $html .= '<button class="btn btn-danger btn-xs" type="submit">Supprimer</button></form>';
            }
            $html .= '</td></tr>';
        }
        if (count($categories) === 0) {
            $html .= '<tr><td colspan="5">Aucune categorie.</td></tr>';
        }
        $html .= '</tbody></table></div></div>';

        return $html;
    }

    private static function peoplePage($id, $messages, $errors)
    {
        $includeArchived = isset($_GET['archived']) && (string) $_GET['archived'] === '1';
        $edit = $id > 0 ? InformatiqueMaterielRepository::person($id) : array();
        $values = $edit ? $edit : InformatiqueMaterielRepository::emptyPersonValues();
        $people = InformatiqueMaterielRepository::people($includeArchived);
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Personnes</h1>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/informatique_materiel/export.php?type=people">Exporter CSV</a> ';
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= self::modalButton('imat-person-form', $edit ? 'Modifier la personne' : 'Ajouter une personne').' ';
            $html .= self::modalButton('imat-person-ldap', 'Ajouter depuis LDAP', 'btn btn-default').' ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'people_associations')).'" data-imat-loading="1">Associations GRR / LDAP</a> ';
        }
        $html .= $includeArchived
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'people')).'">Masquer les archives</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'people', 'archived' => '1')).'">Voir les archives</a>';
        $html .= '</div>';

        if (InformatiqueMaterielSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'people', 'id' => (int) $values['id'])).'">';
            $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="save_person"><input type="hidden" name="id" value="'.(int) $values['id'].'">';
            $form .= '<div class="row"><div class="col-md-2 form-group"><label>ID Excel</label><input class="form-control" type="number" min="0" name="legacy_id" value="'.(int) $values['legacy_id'].'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Identifiant personnel</label><input class="form-control" name="identifiant_legacy" maxlength="100" value="'.self::html($values['identifiant_legacy']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Prenom</label><input class="form-control" name="prenom" maxlength="100" required value="'.self::html($values['prenom']).'"></div>';
            $form .= '<div class="col-md-4 form-group"><label>Nom</label><input class="form-control" name="nom" maxlength="100" required value="'.self::html($values['nom']).'"></div></div>';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>Cadre usage</label><input class="form-control" name="cadre_usage" maxlength="100" value="'.self::html($values['cadre_usage']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Date depart</label><input class="form-control" type="date" name="date_depart" value="'.self::html($values['date_depart']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Login GRR associe</label><select class="form-control" name="login_grr">'.self::loginOptions(self::loginSuggestionsForPerson($values), $values['login_grr']).'</select></div>';
            $form .= '<div class="col-md-3 form-group"><label>Email</label><input class="form-control" type="email" name="email" maxlength="190" value="'.self::html(isset($values['email']) ? $values['email'] : '').'"></div></div>';
            $form .= '<div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2">'.self::html($values['notes']).'</textarea></div>';
            $form .= '<button class="btn btn-primary" type="submit">Enregistrer</button> ';
            if ($edit) {
                $form .= '<a class="btn btn-default" href="'.self::url(array('view' => 'people')).'">Annuler</a>';
            }
            $form .= '</form>';
            $html .= self::modal('imat-person-form', $edit ? 'Modifier une personne' : 'Ajouter une personne', $form, (bool) $edit);
            $html .= self::ldapPersonModal();
        }

        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Identifiant personnel</th><th>Nom</th><th>Cadre</th><th>Date depart</th><th>Login GRR</th><th>Email</th><th>Etat</th><th></th></tr></thead><tbody>';
        $personModals = '';
        foreach ($people as $person) {
            $label = self::personDisplayName($person);
            $identifier = (string) $person['identifiant_legacy'] !== '' ? (string) $person['identifiant_legacy'] : '#'.(int) $person['id'];
            $html .= '<tr><td>'.self::personLink($person, $identifier).'</td><td>'.self::personLink($person, $label).'</td><td>'.self::html($person['cadre_usage']).'</td><td>'.self::html($person['date_depart']).'</td><td>'.self::personLoginLink($person).'</td><td>'.self::html(isset($person['email']) ? $person['email'] : '').'</td><td>'.self::personStateLabel($person).'</td><td>';
            if (InformatiqueMaterielSecurity::canManage()) {
                $html .= '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'people', 'id' => (int) $person['id'], 'archived' => $includeArchived ? '1' : '0')).'">Modifier</a> ';
                if (self::personDeparted($person)) {
                    $html .= self::modalButton('imat-person-extend-'.(int) $person['id'], 'Prolonger', 'btn btn-primary btn-xs').' ';
                    $personModals .= self::personDepartureModal($person);
                }
                if ((int) $person['actif'] === 1) {
                    $html .= '<form method="post" action="'.self::url(array('view' => 'people')).'" style="display:inline" onsubmit="return confirm(\'Archiver cette personne ?\')">';
                    $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="archive_person"><input type="hidden" name="id" value="'.(int) $person['id'].'">';
                    $html .= '<button class="btn btn-warning btn-xs" type="submit">Archiver</button></form>';
                }
                $html .= ' <form method="post" action="'.self::url(array('view' => 'people')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement cette personne ?\')">';
                $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_person"><input type="hidden" name="id" value="'.(int) $person['id'].'">';
                $html .= '<button class="btn btn-danger btn-xs" type="submit">Supprimer</button></form>';
            }
            $html .= '</td></tr>';
        }
        if (count($people) === 0) {
            $html .= '<tr><td colspan="8">Aucune personne.</td></tr>';
        }
        $html .= '</tbody></table></div>'.$personModals.'</div>';

        return $html;
    }

    private static function personPage($id, $messages, $errors)
    {
        $person = $id > 0 ? InformatiqueMaterielRepository::person($id) : array();
        $html = self::startPage($messages, $errors);
        if (!$person) {
            $html .= '<div class="alert alert-warning">Personne introuvable.</div></div>';
            return $html;
        }

        $personLoans = InformatiqueMaterielRepository::loansForPerson((int) $person['id']);
        $openLoans = InformatiqueMaterielRepository::openLoansForPerson((int) $person['id']);
        $transferPeople = InformatiqueMaterielSecurity::canOperate() ? InformatiqueMaterielRepository::people(false) : array();
        $html .= '<h1>Fiche personne</h1>';
        $html .= '<div class="imat-actions"><a class="btn btn-default" href="'.self::url(array('view' => 'people')).'">Retour personnes</a> ';
        if (InformatiqueMaterielSecurity::canManage() && count($openLoans) > 0) {
            $html .= self::modalButton('imat-transfer-person-loans-'.(int) $person['id'], 'Transferer tout le materiel', 'btn btn-primary').' ';
        }
        if (InformatiqueMaterielSecurity::isAdmin() && self::personHasDepartureDate($person) && count($openLoans) > 0) {
            $html .= self::modalButton('imat-person-loans-departure-'.(int) $person['id'], 'Mise à jour fin des prêts', 'btn btn-primary').' ';
        }
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'people', 'id' => (int) $person['id'])).'">Modifier</a> ';
            if (self::personDeparted($person)) {
                $html .= self::modalButton('imat-person-extend-'.(int) $person['id'], 'Prolonger', 'btn btn-primary');
            }
            $html .= ' <form method="post" action="'.self::url(array('view' => 'people')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement cette personne ?\')">';
            $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_person"><input type="hidden" name="id" value="'.(int) $person['id'].'">';
            $html .= '<button class="btn btn-danger" type="submit">Supprimer</button></form>';
        }
        $html .= '</div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>'.self::html(self::personDisplayName($person)).'</strong></div><div class="panel-body">';
        $html .= '<table class="table table-bordered"><tbody>';
        $html .= self::detailRow('Identifiant personnel', $person['identifiant_legacy']);
        $html .= self::detailRow('Prenom', $person['prenom']);
        $html .= self::detailRow('Nom', $person['nom']);
        $html .= self::detailRow('Cadre usage', $person['cadre_usage']);
        $html .= self::detailRow('Date depart', $person['date_depart']);
        $html .= self::detailRow('Login GRR associe', $person['login_grr']);
        $html .= self::detailRow('Email', isset($person['email']) ? $person['email'] : '');
        $html .= self::detailRow('Etat', strip_tags(self::personStateLabel($person)));
        $html .= self::detailRow('Notes', $person['notes']);
        $html .= '</tbody></table></div></div>';
        if (InformatiqueMaterielSecurity::canManage() && self::personDeparted($person)) {
            $html .= self::personDepartureModal($person);
        }
        if (InformatiqueMaterielSecurity::canManage() && count($openLoans) > 0) {
            $html .= self::transferPersonLoansModal($person, $openLoans, $transferPeople);
        }
        if (InformatiqueMaterielSecurity::isAdmin() && self::personHasDepartureDate($person) && count($openLoans) > 0) {
            $html .= self::personLoansDepartureModal($person, $openLoans);
        }
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Materiel et historique</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable($personLoans, false, true, array('view' => 'person', 'id' => (int) $person['id']));
        $html .= '</div></div></div>';

        return $html;
    }

    private static function ldapPersonModal()
    {
        $html = '<p>'.self::html(InformatiqueMaterielLdapDirectory::status()).'</p>';
        $html .= '<form method="post" action="'.self::url(array('view' => 'people')).'" data-imat-loading-form="1">';
        $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="search_ldap_person">';
        $html .= '<div class="form-group"><label>Recherche LDAP</label><input class="form-control" name="ldap_search" maxlength="100" value="'.self::html(self::$ldapPersonSearchTerm).'" placeholder="Nom, prenom, email ou login" required></div>';
        $html .= '<button class="btn btn-primary" type="submit">Rechercher</button>';
        $html .= '</form>';

        if (is_array(self::$ldapPersonSearch)) {
            $html .= '<hr><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Login</th><th>Nom</th><th>Prenom</th><th>Email</th><th></th></tr></thead><tbody>';
            foreach (self::$ldapPersonSearch as $entry) {
                $entryLogin = isset($entry['login']) ? (string) $entry['login'] : '';
                $html .= '<tr>';
                $html .= '<td>'.self::html($entryLogin).'</td>';
                $html .= '<td>'.self::html(isset($entry['nom']) ? $entry['nom'] : '').'</td>';
                $html .= '<td>'.self::html(isset($entry['prenom']) ? $entry['prenom'] : '').'</td>';
                $html .= '<td>'.self::html(isset($entry['email']) ? $entry['email'] : '').'</td>';
                $html .= '<td><form method="post" action="'.self::url(array('view' => 'people')).'" data-imat-loading-form="1">';
                $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="create_person_from_ldap"><input type="hidden" name="ldap_login" value="'.self::html($entryLogin).'">';
                $html .= '<button class="btn btn-primary btn-xs" type="submit">Creer</button></form></td>';
                $html .= '</tr>';
            }
            if (count(self::$ldapPersonSearch) === 0) {
                $html .= '<tr><td colspan="5">Aucun resultat LDAP.</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }

        return self::modal('imat-person-ldap', 'Ajouter une personne depuis LDAP', $html, is_array(self::$ldapPersonSearch));
    }

    private static function peopleAssociationsPage($messages, $errors)
    {
        if (!InformatiqueMaterielSecurity::canManage()) {
            return self::startPage($messages, $errors).'<div class="alert alert-warning">Acces refuse.</div></div>';
        }

        $includeAssociated = isset($_GET['associated']) && (string) $_GET['associated'] === '1';
        $people = InformatiqueMaterielRepository::peopleForAssociations($includeAssociated);
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Associations GRR / LDAP</h1>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'people')).'">Retour personnes</a> ';
        $html .= $includeAssociated
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'people_associations')).'" data-imat-loading="1">Seulement non associees</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'people_associations', 'associated' => '1')).'" data-imat-loading="1">Toutes les personnes actives</a>';
        $html .= '</div>';
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Recherche LDAP</strong></div><div class="panel-body">';
        $html .= '<p>'.self::html(InformatiqueMaterielLdapDirectory::status()).'</p>';
        $html .= '</div></div>';

        $html .= '<form method="post" action="'.self::url(array('view' => 'people_associations', 'associated' => $includeAssociated ? '1' : '0')).'" data-imat-loading-form="1">';
        $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="save_person_associations">';
        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr>'
            .'<th>Selection</th><th>Personne</th><th>Identifiant personnel</th><th>Association actuelle</th><th>Email</th><th>Compte propose</th><th>Sources</th><th></th>'
            .'</tr></thead><tbody>';

        foreach ($people as $person) {
            $personId = (int) $person['id'];
            $suggestions = self::loginSuggestionsForPerson($person);
            $selectedLogin = self::associationSelectedLogin($person, $suggestions);
            $label = trim((string) $person['prenom'].' '.(string) $person['nom']);
            $identifier = (string) $person['identifiant_legacy'] !== '' ? (string) $person['identifiant_legacy'] : '#'.$personId;
            $html .= '<tr>';
            $html .= '<td><input type="checkbox" name="selected_people[]" value="'.$personId.'"></td>';
            $html .= '<td>'.self::html($label).'</td>';
            $html .= '<td>'.self::html($identifier).'</td>';
            $html .= '<td>'.self::html($person['login_grr']).'</td>';
            $html .= '<td>'.self::html(isset($person['email']) ? $person['email'] : '').'</td>';
            $html .= '<td><select class="form-control" name="login_grr['.$personId.']">'.self::loginOptions($suggestions, $selectedLogin).'</select></td>';
            $html .= '<td>'.self::html(self::associationSourcesLabel($suggestions)).'</td>';
            $html .= '<td><button class="btn btn-primary btn-xs" type="submit" name="association_scope" value="one:'.$personId.'">Valider</button></td>';
            $html .= '</tr>';
        }

        if (count($people) === 0) {
            $html .= '<tr><td colspan="8">Aucune personne a associer.</td></tr>';
        }
        $html .= '</tbody></table></div>';
        if (count($people) > 0) {
            $html .= '<div class="imat-actions">';
            $html .= '<button class="btn btn-primary" type="submit" name="association_scope" value="selected">Valider la selection</button> ';
            $html .= '<button class="btn btn-warning" type="submit" name="association_scope" value="all" onclick="return confirm(\'Valider toutes les associations affichees ?\')">Valider toutes les lignes</button>';
            $html .= '</div>';
        }
        $html .= '</form></div>';

        return $html;
    }

    private static function itemsPage($id, $messages, $errors)
    {
        $includeArchived = isset($_GET['archived']) && (string) $_GET['archived'] === '1';
        $filters = array(
            'q' => isset($_GET['q']) ? (string) $_GET['q'] : '',
            'categorie_id' => isset($_GET['categorie_id']) ? (int) $_GET['categorie_id'] : 0,
            'statut' => isset($_GET['statut']) ? (string) $_GET['statut'] : '',
        );
        $filtersActive = trim((string) $filters['q']) !== '' || (int) $filters['categorie_id'] > 0 || (string) $filters['statut'] !== '';
        $edit = $id > 0 ? InformatiqueMaterielRepository::item($id) : array();
        $values = $edit ? array_merge(InformatiqueMaterielRepository::emptyItemValues(), $edit) : InformatiqueMaterielRepository::emptyItemValues();
        $categories = InformatiqueMaterielRepository::categories(true);
        $items = InformatiqueMaterielRepository::items($includeArchived, $filters);
        $transferPeople = InformatiqueMaterielSecurity::canOperate() ? InformatiqueMaterielRepository::people(false) : array();
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Materiels</h1>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/informatique_materiel/export.php?type=items">Exporter CSV</a> ';
        $html .= self::modalButton('imat-item-filters', 'Filtrer', 'btn btn-default').' ';
        if ($filtersActive) {
            $html .= '<a class="btn btn-link" href="'.self::url(array('view' => 'items')).'">Reinitialiser</a> ';
        }
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= self::modalButton('imat-item-form', $edit ? 'Modifier le materiel' : 'Ajouter un materiel').' ';
        }
        $html .= $includeArchived
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'items')).'">Masquer les archives</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'items', 'archived' => '1')).'">Voir les archives</a>';
        $html .= '</div>';

        $filterForm = '<form method="get" action="compte.php">';
        $filterForm .= '<input type="hidden" name="pc" value="'.self::html(InformatiqueMaterielConfig::MODULE).'"><input type="hidden" name="view" value="items">';
        if ($includeArchived) {
            $filterForm .= '<input type="hidden" name="archived" value="1">';
        }
        $filterForm .= '<div class="form-group"><label>Recherche</label><input class="form-control" name="q" maxlength="100" value="'.self::html($filters['q']).'"></div>';
        $filterForm .= '<div class="form-group"><label>Categorie</label><select class="form-control" name="categorie_id">'.self::categoryOptions($categories, (int) $filters['categorie_id']).'</select></div>';
        $filterForm .= '<div class="form-group"><label>Statut</label><select class="form-control" name="statut">'.self::itemStatusFilterOptions($filters['statut']).'</select></div>';
        $filterForm .= '<button class="btn btn-primary" type="submit">Filtrer</button> <a class="btn btn-default" href="'.self::url(array('view' => 'items')).'">Reinitialiser</a>';
        $filterForm .= '</form>';
        $html .= self::modal('imat-item-filters', 'Filtrer les materiels', $filterForm, false);

        if (InformatiqueMaterielSecurity::canManage()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'items', 'id' => (int) $values['id'])).'">';
            $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="save_item"><input type="hidden" name="id" value="'.(int) $values['id'].'">';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>Identifiant</label><input class="form-control" name="identifiant" maxlength="100" value="'.self::html($values['identifiant']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Identifiant historique</label><input class="form-control" name="identifiant_legacy" maxlength="100" value="'.self::html($values['identifiant_legacy']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Categorie</label><select class="form-control" name="categorie_id" required>'.self::categoryOptions($categories, (int) $values['categorie_id']).'</select></div>';
            $form .= '<div class="col-md-3 form-group"><label>Statut</label><select class="form-control" name="statut">'.self::itemStatusOptions($values['statut']).'</select></div></div>';
            $form .= '<div class="form-group"><label><input type="checkbox" name="pret_multiple" value="1"'.((int) $values['pret_multiple'] === 1 ? ' checked' : '').'> Materiel generique / pret multiple</label><div class="help-block">Autorise plusieurs prets ouverts en meme temps pour les accessoires generiques : souris, clavier, cle USB, adaptateur, etc.</div></div>';
            $form .= '<div class="row"><div class="col-md-5 form-group"><label>Designation</label><input class="form-control" name="designation" maxlength="190" required value="'.self::html($values['designation']).'"></div>';
            $form .= '<div class="col-md-7 form-group"><label>Precision</label><input class="form-control" name="precision_materiel" maxlength="190" value="'.self::html($values['precision_materiel']).'"></div></div>';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>Marque</label><input class="form-control" name="marque" maxlength="100" value="'.self::html($values['marque']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Numero de serie</label><input class="form-control" name="numero_serie" maxlength="190" value="'.self::html($values['numero_serie']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Code-barres USMB</label><input class="form-control" name="code_barre_usmb" maxlength="100" value="'.self::html($values['code_barre_usmb']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>MAC</label><input class="form-control" name="mac" maxlength="100" value="'.self::html($values['mac']).'"></div></div>';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>OS</label><input class="form-control" name="os" maxlength="100" value="'.self::html($values['os']).'"></div>';
            $form .= '<div class="col-md-2 form-group"><label>Annee</label><input class="form-control" name="annee" maxlength="4" value="'.self::html($values['annee']).'"></div>';
            $form .= '<div class="col-md-7 form-group"><label>Localisation stockage</label><input class="form-control" name="localisation_stockage" maxlength="190" value="'.self::html($values['localisation_stockage']).'"></div></div>';
            $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="2">'.self::html($values['commentaire']).'</textarea></div>';
            $form .= '<div class="form-group"><label>Notes internes</label><textarea class="form-control" name="notes" rows="2">'.self::html($values['notes']).'</textarea></div>';
            $form .= '<button class="btn btn-primary" type="submit">Enregistrer</button> ';
            if ($edit) {
                $form .= '<a class="btn btn-default" href="'.self::url(array('view' => 'items')).'">Annuler</a>';
            }
            $form .= '</form>';
            $html .= self::modal('imat-item-form', $edit ? 'Modifier un materiel' : 'Ajouter un materiel', $form, (bool) $edit);
        }

        $html .= '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Identifiant</th><th>Designation</th><th>Categorie</th><th>Mode pret</th><th>Marque</th><th>Serie</th><th>Code-barres</th><th>Statut</th><th>Localisation</th><th>Etat</th><th></th></tr></thead><tbody>';
        $transferModals = '';
        foreach ($items as $item) {
            $category = trim((string) $item['categorie_prefixe'].' - '.(string) $item['categorie_designation']);
            $categoryCell = (int) $item['categorie_id'] > 0
                ? '<a href="'.self::url(array('view' => 'items', 'categorie_id' => (int) $item['categorie_id'])).'">'.self::html($category).'</a>'
                : self::html($category);
            $html .= '<tr><td><a href="'.self::url(array('view' => 'item', 'id' => (int) $item['id'])).'">'.self::html($item['identifiant']).'</a></td>';
            $html .= '<td><a href="'.self::url(array('view' => 'item', 'id' => (int) $item['id'])).'">'.self::html($item['designation']).'</a></td><td>'.$categoryCell.'</td><td>'.self::loanModeLabel($item).'</td><td>'.self::html($item['marque']).'</td><td>'.self::html($item['numero_serie']).'</td>';
            $html .= '<td>'.self::html($item['code_barre_usmb']).'</td><td>'.self::itemStatusLabel($item['statut']).'</td><td>'.self::html($item['localisation_stockage']).'</td><td>'.self::statusLabel((int) $item['actif'] === 1).'</td><td>';
            if (InformatiqueMaterielSecurity::canOperate() && isset($item['open_loan_count']) && (int) $item['open_loan_count'] > 0) {
                $transferId = 'imat-transfer-item-row-'.(int) $item['id'];
                $openLoans = InformatiqueMaterielRepository::openLoansForItem((int) $item['id']);
                if (count($openLoans) > 0) {
                    $html .= self::modalButton($transferId, 'Transferer', 'btn btn-primary btn-xs').' ';
                    $transferModals .= self::transferLoanModal($transferId, 'Transferer '.self::itemShortLabel($item), $openLoans, $transferPeople, array('view' => 'items', 'archived' => $includeArchived ? '1' : '0'));
                }
            }
            if (InformatiqueMaterielSecurity::canManage()) {
                $html .= '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'items', 'id' => (int) $item['id'], 'archived' => $includeArchived ? '1' : '0')).'">Modifier</a> ';
                if ((int) $item['actif'] === 1) {
                    $html .= '<form method="post" action="'.self::url(array('view' => 'items')).'" style="display:inline" onsubmit="return confirm(\'Archiver ce materiel ?\')">';
                    $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="archive_item"><input type="hidden" name="id" value="'.(int) $item['id'].'">';
                    $html .= '<button class="btn btn-warning btn-xs" type="submit">Archiver</button></form>';
                }
                $html .= ' <form method="post" action="'.self::url(array('view' => 'items')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement ce materiel ?\')">';
                $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_item"><input type="hidden" name="id" value="'.(int) $item['id'].'">';
                $html .= '<button class="btn btn-danger btn-xs" type="submit">Supprimer</button></form>';
            }
            $html .= '</td></tr>';
        }
        if (count($items) === 0) {
            $html .= '<tr><td colspan="11">Aucun materiel.</td></tr>';
        }
        $html .= '</tbody></table></div>'.$transferModals.'</div>';

        return $html;
    }

    private static function itemPage($id, $messages, $errors)
    {
        $item = $id > 0 ? InformatiqueMaterielRepository::item($id) : array();
        $html = self::startPage($messages, $errors);
        if (!$item) {
            $html .= '<div class="alert alert-warning">Materiel introuvable.</div></div>';
            return $html;
        }

        $category = trim((string) $item['categorie_prefixe'].' - '.(string) $item['categorie_designation']);
        $itemLoans = InformatiqueMaterielRepository::loansForItem((int) $item['id']);
        $openLoans = InformatiqueMaterielRepository::openLoansForItem((int) $item['id']);
        $transferPeople = InformatiqueMaterielSecurity::canOperate() ? InformatiqueMaterielRepository::people(false) : array();
        $html .= '<h1>Fiche materiel</h1>';
        $html .= '<div class="imat-actions"><a class="btn btn-default" href="'.self::url(array('view' => 'items')).'">Retour aux materiels</a> ';
        if (InformatiqueMaterielSecurity::canOperate() && count($openLoans) > 0) {
            $html .= self::modalButton('imat-transfer-item-'.(int) $item['id'], 'Transferer', 'btn btn-primary').' ';
        }
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= '<a class="btn btn-primary" href="'.self::url(array('view' => 'items', 'id' => (int) $item['id'])).'">Modifier</a> ';
            $html .= '<form method="post" action="'.self::url(array('view' => 'items')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement ce materiel ?\')">';
            $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_item"><input type="hidden" name="id" value="'.(int) $item['id'].'">';
            $html .= '<button class="btn btn-danger" type="submit">Supprimer</button></form>';
        }
        $html .= '</div>';
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>'.self::html($item['identifiant']).'</strong></div><div class="panel-body">';
        $html .= '<table class="table table-bordered"><tbody>';
        $html .= self::detailRow('Designation', $item['designation']);
        $html .= self::detailRow('Precision', $item['precision_materiel']);
        $html .= self::detailRow('Categorie', $category);
        $html .= self::detailRow('Statut', strip_tags(self::itemStatusLabel($item['statut'])));
        $html .= self::detailRow('Mode pret', (int) $item['pret_multiple'] === 1 ? 'Pret multiple autorise' : 'Pret unique');
        $html .= self::detailRow('Etat', (int) $item['actif'] === 1 ? 'Actif' : 'Archive');
        $html .= self::detailRow('Marque', $item['marque']);
        $html .= self::detailRow('Numero de serie', $item['numero_serie']);
        $html .= self::detailRow('Code-barres USMB', $item['code_barre_usmb']);
        $html .= self::detailRow('MAC', $item['mac']);
        $html .= self::detailRow('OS', $item['os']);
        $html .= self::detailRow('Annee', $item['annee']);
        $html .= self::detailRow('Localisation stockage', $item['localisation_stockage']);
        $html .= self::detailRow('Identifiant historique', $item['identifiant_legacy']);
        $html .= self::detailRow('Commentaire', $item['commentaire']);
        $html .= self::detailRow('Notes internes', $item['notes']);
        $html .= '</tbody></table></div></div>';
        if (InformatiqueMaterielSecurity::canOperate() && count($openLoans) > 0) {
            $html .= self::transferLoanModal('imat-transfer-item-'.(int) $item['id'], 'Transferer '.self::itemShortLabel($item), $openLoans, $transferPeople, array('view' => 'item', 'id' => (int) $item['id']));
        }
        $html .= self::documentsPanel($item);
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Historique des prets</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable($itemLoans, false, true, array('view' => 'item', 'id' => (int) $item['id']));
        $html .= '</div></div></div>';

        return $html;
    }

    private static function documentsPanel($item)
    {
        $itemId = isset($item['id']) ? (int) $item['id'] : 0;
        $includeArchived = isset($_GET['docs_archived']) && (string) $_GET['docs_archived'] === '1';
        $documents = InformatiqueMaterielRepository::documentsForItem($itemId, $includeArchived);
        $html = '<div class="panel panel-default" id="imat-documents"><div class="panel-heading"><strong>Documents</strong></div><div class="panel-body">';
        $html .= '<div class="imat-actions">';
        $html .= $includeArchived
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'item', 'id' => $itemId)).'#imat-documents">Masquer les archives</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'item', 'id' => $itemId, 'docs_archived' => '1')).'#imat-documents">Voir les archives</a>';
        $html .= '</div>';

        $html .= self::documentRowsTable($documents, $itemId);

        if (InformatiqueMaterielSecurity::canManage()) {
            if (!InformatiqueMaterielConfig::documentsEnabled()) {
                $html .= '<p class="help-block">Le depot de documents est desactive dans la configuration du module.</p>';
            } elseif ((int) $item['actif'] === 1 && (string) $item['statut'] !== 'archive') {
                $html .= self::documentUploadForm($itemId);
            } else {
                $html .= '<p class="help-block">Un document ne peut pas etre ajoute sur un materiel archive.</p>';
            }
        }

        return $html.'</div></div>';
    }

    private static function documentRowsTable($documents, $itemId)
    {
        $types = InformatiqueMaterielRepository::documentTypes();
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Type</th><th>Fichier</th><th>Description</th><th>Taille</th><th>Depot</th><th>Etat</th><th></th></tr></thead><tbody>';
        foreach ($documents as $document) {
            $type = isset($types[$document['type_document']]) ? $types[$document['type_document']] : $document['type_document'];
            $downloadUrl = '../personnalisation/modules/informatique_materiel/download.php?id='.(int) $document['id'];
            $fileCell = (int) $document['actif'] === 1
                ? '<a href="'.$downloadUrl.'">'.self::html($document['original_name']).'</a>'
                : self::html($document['original_name']);
            $html .= '<tr><td>'.self::html($type).'</td><td>'.$fileCell.'</td><td>'.nl2br(self::html($document['description'])).'</td>';
            $html .= '<td>'.self::html(self::formatBytes((int) $document['taille'])).'</td>';
            $html .= '<td>'.self::html(self::formatDateTime((int) $document['created_at'])).'<br><small>'.self::html($document['uploaded_by']).'</small></td>';
            $html .= '<td>'.self::statusLabel((int) $document['actif'] === 1).'</td><td>';
            if ((int) $document['actif'] === 1) {
                $html .= '<a class="btn btn-default btn-xs" href="'.$downloadUrl.'">Telecharger</a> ';
                if (InformatiqueMaterielSecurity::canManage()) {
                    $html .= '<form method="post" action="'.self::url(array('view' => 'item', 'id' => $itemId)).'#imat-documents" style="display:inline" onsubmit="return confirm(\'Archiver ce document ?\')">';
                    $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="archive_document"><input type="hidden" name="item_id" value="'.$itemId.'"><input type="hidden" name="document_id" value="'.(int) $document['id'].'">';
                    $html .= '<button class="btn btn-warning btn-xs" type="submit">Archiver</button></form>';
                }
            }
            $html .= '</td></tr>';
        }
        if (count($documents) === 0) {
            $html .= '<tr><td colspan="7">Aucun document.</td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    private static function documentUploadForm($itemId)
    {
        $accept = array();
        foreach (InformatiqueMaterielConfig::documentExtensions() as $extension) {
            $accept[] = '.'.$extension;
        }

        $form = '<form method="post" enctype="multipart/form-data" action="'.self::url(array('view' => 'item', 'id' => (int) $itemId)).'#imat-documents">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="upload_document"><input type="hidden" name="item_id" value="'.(int) $itemId.'">';
        $form .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.(int) InformatiqueMaterielConfig::documentMaxBytes().'">';
        $form .= '<div class="row"><div class="col-md-3 form-group"><label>Type</label><select class="form-control" name="type_document">'.self::documentTypeOptions('autre').'</select></div>';
        $form .= '<div class="col-md-5 form-group"><label>Fichier</label><input class="form-control" type="file" name="document_file" accept="'.self::html(implode(',', $accept)).'" required></div>';
        $form .= '<div class="col-md-4 form-group"><label>Description</label><input class="form-control" name="description" maxlength="5000"></div></div>';
        $form .= '<p class="help-block">Extensions autorisees : '.self::html(implode(', ', InformatiqueMaterielConfig::documentExtensions())).'. Taille maximale : '.self::html(self::formatBytes(InformatiqueMaterielConfig::documentMaxBytes())).'.</p>';
        $form .= '<button class="btn btn-primary" type="submit">Ajouter le document</button>';
        $form .= '</form>';

        $html = '<div class="imat-actions">'.self::modalButton('imat-document-form', 'Ajouter un document').'</div>';
        $html .= self::modal('imat-document-form', 'Ajouter un document', $form, false);

        return $html;
    }

    private static function uploadDocument($itemId, $values, $login)
    {
        $errors = array();
        if (!InformatiqueMaterielConfig::documentsEnabled()) {
            return array('ok' => false, 'errors' => array('Le depot de documents est desactive.'));
        }

        $types = InformatiqueMaterielRepository::documentTypes();
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
            return array('ok' => false, 'errors' => $errors);
        }

        $file = $_FILES['document_file'];
        $uploadError = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = self::uploadErrorMessage($uploadError);
            return array('ok' => false, 'errors' => $errors);
        }

        $originalName = self::sanitizeDocumentName(isset($file['name']) ? $file['name'] : '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, InformatiqueMaterielConfig::documentExtensions(), true)) {
            $errors[] = 'Extension de fichier non autorisee.';
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $maxBytes = InformatiqueMaterielConfig::documentMaxBytes();
        if ($size <= 0 || $size > $maxBytes) {
            $errors[] = 'La taille du fichier doit etre comprise entre 1 octet et '.self::formatBytes($maxBytes).'.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        if (!InformatiqueMaterielRepository::ensureDocumentStorage()) {
            return array('ok' => false, 'errors' => array('Le dossier de stockage des documents n est pas accessible en ecriture.'));
        }

        $storedName = self::generateDocumentStoredName();
        $targetPath = InformatiqueMaterielRepository::documentPath($storedName);
        $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($targetPath === '' || $tmpName === '' || !move_uploaded_file($tmpName, $targetPath)) {
            return array('ok' => false, 'errors' => array('Le fichier n a pas pu etre enregistre.'));
        }

        $actualSize = @filesize($targetPath);
        $actualSize = $actualSize === false ? 0 : (int) $actualSize;
        if ($actualSize <= 0 || $actualSize > $maxBytes) {
            @unlink($targetPath);
            return array('ok' => false, 'errors' => array('La taille du fichier enregistre est invalide.'));
        }

        $mimeType = self::documentMimeType($targetPath, isset($file['type']) ? $file['type'] : '');
        if (self::documentMimeRejected($mimeType)) {
            @unlink($targetPath);
            return array('ok' => false, 'errors' => array('Le type MIME du fichier est interdit.'));
        }

        $sha256 = hash_file('sha256', $targetPath);
        if (!is_string($sha256) || preg_match('/^[a-f0-9]{64}$/', $sha256) !== 1) {
            @unlink($targetPath);
            return array('ok' => false, 'errors' => array('Le controle d integrite du fichier a echoue.'));
        }
        if (!InformatiqueMaterielRepository::addDocument(
            $itemId,
            $type,
            $description,
            $originalName,
            $storedName,
            $mimeType,
            $actualSize,
            $sha256,
            $login
        )) {
            @unlink($targetPath);
            return array('ok' => false, 'errors' => array('Le document n a pas pu etre enregistre en base.'));
        }

        return array('ok' => true, 'errors' => array());
    }

    private static function loansPage($id, $messages, $errors)
    {
        $includeClosed = isset($_GET['closed']) && (string) $_GET['closed'] === '1';
        $filters = array(
            'q' => isset($_GET['q']) ? (string) $_GET['q'] : '',
            'statut' => isset($_GET['statut']) ? (string) $_GET['statut'] : '',
            'item_id' => isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0,
            'personne_id' => isset($_GET['personne_id']) ? (int) $_GET['personne_id'] : 0,
        );
        $filtersActive = trim((string) $filters['q']) !== '' || (string) $filters['statut'] !== '' || (int) $filters['item_id'] > 0 || (int) $filters['personne_id'] > 0;
        $people = InformatiqueMaterielRepository::people(false);
        $items = InformatiqueMaterielRepository::items(false);
        $values = InformatiqueMaterielRepository::emptyLoanValues();
        if ((int) $filters['item_id'] > 0) {
            $values['item_id'] = (int) $filters['item_id'];
        }
        if ((int) $filters['personne_id'] > 0) {
            $values['personne_id'] = (int) $filters['personne_id'];
        }
        $loans = InformatiqueMaterielRepository::loans($includeClosed, $filters);

        $html = self::startPage($messages, $errors);
        $html .= '<h1>Prets et restitutions</h1>';
        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="../personnalisation/modules/informatique_materiel/export.php?type=loans">Exporter CSV</a> ';
        $html .= self::modalButton('imat-loan-filters', 'Filtrer', 'btn btn-default').' ';
        if ($filtersActive) {
            $html .= '<a class="btn btn-link" href="'.self::url(array('view' => 'loans')).'">Reinitialiser</a> ';
        }
        if (InformatiqueMaterielSecurity::canOperate()) {
            $html .= self::modalButton('imat-loan-form', 'Creer un pret').' ';
        }
        $html .= $includeClosed
            ? '<a class="btn btn-default" href="'.self::url(array('view' => 'loans')).'">Afficher les prets ouverts</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'loans', 'closed' => '1')).'">Afficher tout l historique</a>';
        $html .= '</div>';

        $filterForm = '<form method="get" action="compte.php">';
        $filterForm .= '<input type="hidden" name="pc" value="'.self::html(InformatiqueMaterielConfig::MODULE).'"><input type="hidden" name="view" value="loans">';
        if ($includeClosed) {
            $filterForm .= '<input type="hidden" name="closed" value="1">';
        }
        $filterForm .= '<div class="form-group"><label>Recherche</label><input class="form-control" name="q" maxlength="100" value="'.self::html($filters['q']).'"></div>';
        $filterForm .= '<div class="form-group"><label>Materiel</label><select class="form-control" name="item_id">'.self::itemOptions($items, (int) $filters['item_id']).'</select></div>';
        $filterForm .= '<div class="form-group"><label>Personne</label><select class="form-control" name="personne_id">'.self::personOptions($people, (int) $filters['personne_id']).'</select></div>';
        $filterForm .= '<div class="form-group"><label>Statut</label><select class="form-control" name="statut">'.self::loanStatusFilterOptions($filters['statut']).'</select></div>';
        $filterForm .= '<button class="btn btn-primary" type="submit">Filtrer</button> <a class="btn btn-default" href="'.self::url(array('view' => 'loans')).'">Reinitialiser</a>';
        $filterForm .= '</form>';
        $html .= self::modal('imat-loan-filters', 'Filtrer les prets', $filterForm, false);

        if (InformatiqueMaterielSecurity::canOperate()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'loans')).'">';
            $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="create_loan">';
            $form .= '<div class="row"><div class="col-md-4 form-group"><label>Materiel</label><select class="form-control" name="item_id" required>'.self::itemOptions($items, (int) $values['item_id']).'</select></div>';
            $form .= '<div class="col-md-4 form-group"><label>Personne</label><select class="form-control" name="personne_id" required>'.self::personOptions($people, (int) $values['personne_id']).'</select></div>';
            $form .= '<div class="col-md-4 form-group"><label>Localisation</label><input class="form-control" name="localisation" maxlength="190" value="'.self::html($values['localisation']).'"></div></div>';
            $form .= '<div class="row"><div class="col-md-3 form-group"><label>Date debut</label><input class="form-control" type="date" name="date_debut" required value="'.self::html($values['date_debut']).'"></div>';
            $form .= '<div class="col-md-3 form-group"><label>Date fin prevue</label><input class="form-control" type="date" name="date_fin_prevue" value="'.self::html($values['date_fin_prevue']).'"></div>';
            $form .= '<div class="col-md-6 form-group"><label>Commentaire</label><input class="form-control" name="commentaire" maxlength="2000" value="'.self::html($values['commentaire']).'"></div></div>';
            $form .= '<button class="btn btn-primary" type="submit">Creer le pret</button>';
            $form .= '</form>';
            $html .= self::modal('imat-loan-form', 'Creer un pret', $form, false);
        }

        $html .= self::loanRowsTable($loans, true, false, array(), true);
        $html .= '</div>';

        return $html;
    }

    private static function loanPage($id, $messages, $errors)
    {
        $loan = $id > 0 ? InformatiqueMaterielRepository::loan($id) : array();
        $html = self::startPage($messages, $errors);
        if (!$loan) {
            $html .= '<div class="alert alert-warning">Pret introuvable.</div></div>';
            return $html;
        }

        $transferPeople = InformatiqueMaterielSecurity::canOperate() ? InformatiqueMaterielRepository::people(false) : array();
        $html .= '<h1>Fiche pret</h1>';
        $html .= '<div class="imat-actions"><a class="btn btn-default" href="'.self::url(array('view' => 'loans', 'closed' => '1')).'">Retour aux prets</a>';
        if ((string) $loan['statut'] === 'ouvert' && InformatiqueMaterielSecurity::canOperate()) {
            $html .= ' '.self::modalButton('imat-loan-close-page', 'Restituer');
            $html .= ' '.self::modalButton('imat-loan-extend-page', 'Prolonger', 'btn btn-primary');
            $html .= ' '.self::modalButton('imat-loan-transfer-page', 'Transferer', 'btn btn-primary');
        }
        if (InformatiqueMaterielSecurity::canManage()) {
            $html .= ' <form method="post" action="'.self::url(array('view' => 'loans')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement ce pret ?\')">';
            $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_loan"><input type="hidden" name="id" value="'.(int) $loan['id'].'">';
            $html .= '<button class="btn btn-danger" type="submit">Supprimer</button></form>';
        }
        $html .= '</div>';
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Pret #'.(int) $loan['id'].'</strong></div><div class="panel-body">';
        $html .= '<table class="table table-bordered"><tbody>';
        $html .= self::detailRow('Materiel', self::loanItemLabel($loan));
        $html .= self::detailRow('Personne', self::loanPersonLabel($loan));
        $html .= self::detailRow('Statut', strip_tags(self::loanStatusLabel($loan['statut'])));
        $html .= self::detailRow('Localisation', $loan['localisation']);
        $html .= self::detailRow('Date debut', $loan['date_debut']);
        $html .= self::detailRow('Date fin prevue', $loan['date_fin_prevue']);
        $html .= self::detailRow('Date retour effective', $loan['date_fin_effective']);
        $html .= self::detailRow('Commentaire', $loan['commentaire']);
        $html .= '</tbody></table>';

        if ((string) $loan['statut'] === 'ouvert' && InformatiqueMaterielSecurity::canOperate()) {
            $form = '<form method="post" action="'.self::url(array('view' => 'loans')).'">';
            $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="close_loan"><input type="hidden" name="id" value="'.(int) $loan['id'].'">';
            $form .= '<div class="row"><div class="col-md-4 form-group"><label>Date retour effective</label><input class="form-control" type="date" name="date_fin_effective" required value="'.self::html(date('Y-m-d')).'"></div>';
            $form .= '<div class="col-md-8 form-group"><label>Commentaire retour</label><input class="form-control" name="commentaire" maxlength="2000"></div></div>';
            $form .= '<button class="btn btn-primary" type="submit">Restituer</button></form>';
            $html .= self::modal('imat-loan-close-page', 'Restituer le pret #'.(int) $loan['id'], $form, false);

            $extendForm = '<form method="post" action="'.self::url(array('view' => 'loan', 'id' => (int) $loan['id'])).'">';
            $extendForm .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="extend_loan"><input type="hidden" name="pret_id" value="'.(int) $loan['id'].'">';
            $extendForm .= '<div class="form-group"><label>Nouvelle date de retour prevue</label><input class="form-control" type="date" name="new_date" required value="'.self::html($loan['date_fin_prevue']).'"></div>';
            $extendForm .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="2" required></textarea></div>';
            $extendForm .= '<button class="btn btn-primary" type="submit">Prolonger</button></form>';
            $html .= self::modal('imat-loan-extend-page', 'Prolonger le pret #'.(int) $loan['id'], $extendForm, false);
            $html .= self::transferLoanModal(
                'imat-loan-transfer-page',
                'Transferer le pret #'.(int) $loan['id'],
                array($loan),
                $transferPeople,
                array('view' => 'loan', 'id' => (int) $loan['id'])
            );
        }
        $html .= '</div></div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Historique du materiel</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable(InformatiqueMaterielRepository::loansForItem((int) $loan['item_id']), false);
        $html .= '</div></div>';
        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Historique de la personne</strong></div><div class="panel-body">';
        $html .= self::loanRowsTable(InformatiqueMaterielRepository::loansForPerson((int) $loan['personne_id']), false);
        $html .= '</div></div></div>';

        return $html;
    }

    private static function importPage($messages, $errors)
    {
        self::ensureImportClass($errors);
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Import CSV</h1>';

        if (!InformatiqueMaterielSecurity::canManage()) {
            $html .= '<div class="alert alert-warning">Acces refuse.</div></div>';
            return $html;
        }
        if (!class_exists('InformatiqueMaterielImport')) {
            $html .= '<div class="alert alert-danger">Le composant d import CSV est indisponible.</div></div>';
            return $html;
        }

        $html .= '<div class="imat-actions">'.self::modalButton('imat-import-form', 'Charger un fichier CSV').'</div>';
        $form = '<form method="post" enctype="multipart/form-data" action="'.self::url(array('view' => 'import')).'">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="upload_import">';
        $form .= '<div class="row"><div class="col-md-4 form-group"><label>Type de donnees</label><select class="form-control" name="import_type" required>'.self::importTypeOptions('').'</select></div>';
        $form .= '<div class="col-md-8 form-group"><label>Fichier CSV</label><input class="form-control" type="file" name="import_file" accept=".csv,.txt" required></div></div>';
        $form .= '<button class="btn btn-primary" type="submit">Previsualiser</button>';
        $form .= '</form>';
        $html .= self::modal('imat-import-form', 'Charger un fichier CSV', $form, false);

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Format attendu</strong></div><div class="panel-body">';
        $html .= '<p class="help-block">Ordre conseille : categories, personnes, materiels, puis prets.</p>';
        $html .= self::importExpectedColumns();
        $html .= '</div></div>';

        if (self::$importPreview !== null) {
            $html .= self::importPreviewPanel(self::$importPreview);
        }
        if (self::$importResult !== null) {
            $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Resultat du dernier import</strong></div><div class="panel-body">';
            $html .= '<p>Creees : '.(int) self::$importResult['created'].' ; conflits : '.(int) self::$importResult['conflicts'].' ; ignorees : '.(int) self::$importResult['skipped'].' ; erreurs : '.(int) self::$importResult['errors'].'.</p>';
            $html .= '</div></div>';
        }

        $html .= '<div class="panel panel-warning"><div class="panel-heading"><strong>Conflits de prets en attente</strong></div><div class="panel-body">';
        $html .= '<p><a class="btn btn-default" href="'.self::url(array('view' => 'conflicts')).'">Gerer les conflits</a></p>';
        $html .= self::loanConflictTable(InformatiqueMaterielRepository::loanConflicts('en_attente', 100), false);
        $html .= '</div></div>';

        $html .= '<div class="panel panel-default"><div class="panel-heading"><strong>Dernieres lignes journalisees</strong></div><div class="panel-body">';
        $html .= self::importLogTable(InformatiqueMaterielRepository::importLogs(50));
        $html .= '</div></div></div>';

        return $html;
    }

    private static function conflictsPage($messages, $errors)
    {
        $html = self::startPage($messages, $errors);
        $html .= '<h1>Conflits de prets</h1>';

        if (!InformatiqueMaterielSecurity::canManage()) {
            $html .= '<div class="alert alert-warning">Acces refuse.</div></div>';
            return $html;
        }

        $status = isset($_GET['status']) ? (string) $_GET['status'] : 'en_attente';
        if (!in_array($status, array('en_attente', 'all'), true)) {
            $status = 'en_attente';
        }
        $queryStatus = $status === 'all' ? '' : 'en_attente';
        $conflicts = InformatiqueMaterielRepository::loanConflicts($queryStatus, 300);

        $html .= '<div class="imat-actions">';
        $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'import')).'">Retour import</a> ';
        $html .= $status === 'all'
            ? '<a class="btn btn-primary" href="'.self::url(array('view' => 'conflicts')).'">Voir en attente</a>'
            : '<a class="btn btn-default" href="'.self::url(array('view' => 'conflicts', 'status' => 'all')).'">Voir tout</a>';
        $html .= '</div>';

        $html .= '<div class="panel panel-warning"><div class="panel-heading"><strong>Decisions a prendre</strong></div><div class="panel-body">';
        $html .= self::loanConflictTable($conflicts, true);
        $html .= '</div></div></div>';

        return $html;
    }

    private static function dashboardAlertsPanel($alerts, $total)
    {
        if (!InformatiqueMaterielConfig::alertsEnabled()) {
            return '<div class="panel panel-default" id="imat-alerts"><div class="panel-heading"><strong>Alertes</strong></div><div class="panel-body"><p>Les alertes sont desactivees par l administrateur.</p></div></div>';
        }

        $panelClass = (int) $total > 0 ? 'danger' : 'default';
        $alertColor = InformatiqueMaterielConfig::alertDangerColor();
        $panelStyle = (int) $total > 0 ? ' style="border-color:'.self::html($alertColor).'"' : '';
        $headingStyle = (int) $total > 0 ? ' style="background-color:'.self::html($alertColor).';border-color:'.self::html($alertColor).';color:#fff"' : '';
        $html = '<div class="panel panel-'.$panelClass.'" id="imat-alerts"'.$panelStyle.'><div class="panel-heading"'.$headingStyle.'><strong>Alertes</strong></div><div class="panel-body">';
        $html .= self::alertRowsTable($alerts, true);
        if ((int) $total > count($alerts)) {
            $html .= '<p><a class="btn btn-default" href="'.self::url(array('view' => 'alerts')).'">Voir toutes les alertes</a></p>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private static function card($label, $value)
    {
        return '<div class="imat-card"><span>'.self::html($label).'</span><strong>'.(int) $value.'</strong></div>';
    }

    private static function alertRowsTable($alerts, $showEmpty)
    {
        if (count($alerts) === 0) {
            return $showEmpty ? '<p>Aucune alerte.</p>' : '';
        }

        $modals = '';
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Type</th><th>Element</th><th>Detail</th><th>N&deg; du pret</th><th>Commentaire</th><th>Date</th><th></th></tr></thead><tbody>';
        foreach ($alerts as $alert) {
            $url = self::alertUrl($alert);
            $label = self::html($alert['label']);
            if ($url !== '') {
                $label = '<a href="'.$url.'">'.$label.'</a>';
            }
            $loanNumber = isset($alert['pret_numero']) ? trim((string) $alert['pret_numero']) : '';
            $loanNumberCell = $loanNumber !== '' ? self::html($loanNumber) : '<span class="text-muted">-</span>';
            if ($loanNumber !== '' && strpos($loanNumber, ',') === false && isset($alert['pret_id']) && (int) $alert['pret_id'] > 0) {
                $loanNumberCell = '<a href="'.self::html(self::url(array('view' => 'loan', 'id' => (int) $alert['pret_id']))).'">'.$loanNumberCell.'</a>';
            }
            $commentaire = isset($alert['commentaire']) ? trim((string) $alert['commentaire']) : '';
            $commentaireCell = $commentaire !== '' ? nl2br(self::html($commentaire)) : '<span class="text-muted">-</span>';

            $html .= '<tr><td>'.self::alertTypeBadge($alert).'</td><td>'.$label.'</td><td>'.self::html($alert['detail']).'</td><td>'.$loanNumberCell.'</td><td>'.$commentaireCell.'</td><td>'.self::html($alert['reference_date']).'</td><td>';
            if ($url !== '') {
                $html .= '<a class="btn btn-default btn-xs" href="'.$url.'">Ouvrir</a>';
            }
            $extensionModal = self::alertExtensionModal($alert);
            if ($extensionModal !== '') {
                $modalId = self::alertExtensionModalId($alert);
                $html .= ' '.self::modalButton($modalId, 'Prolonger', 'btn btn-primary btn-xs');
                $modals .= $extensionModal;
            }
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>'.$modals;

        return $html;
    }

    private static function alertExtensionModal($alert)
    {
        $type = isset($alert['type']) ? (string) $alert['type'] : '';
        $idField = '';
        $idValue = 0;
        $dateLabel = '';
        $title = '';

        if ($type === 'pret_en_retard' && InformatiqueMaterielSecurity::canOperate()) {
            $idValue = isset($alert['pret_id']) ? (int) $alert['pret_id'] : 0;
            $idField = 'pret_id';
            $dateLabel = 'Nouvelle date de retour prevue';
            $title = 'Prolonger le retour du materiel';
        } elseif ($type === 'personne_partie' && InformatiqueMaterielSecurity::canManage()) {
            $idValue = isset($alert['personne_id']) ? (int) $alert['personne_id'] : 0;
            $idField = 'personne_id';
            $dateLabel = 'Nouvelle date de depart';
            $title = 'Prolonger la date de depart';
        }

        if ($idValue <= 0 || $idField === '') {
            return '';
        }

        $form = '<form method="post" action="'.self::url(array('view' => 'alerts')).'">';
        $form .= InformatiqueMaterielSecurity::field();
        $form .= '<input type="hidden" name="imat_action" value="extend_alert">';
        $form .= '<input type="hidden" name="alert_type" value="'.self::html($type).'">';
        $form .= '<input type="hidden" name="'.self::html($idField).'" value="'.(int) $idValue.'">';
        $form .= '<div class="form-group"><label>'.self::html($dateLabel).'</label><input class="form-control" type="date" name="new_date" min="'.self::html(date('Y-m-d')).'" required></div>';
        $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="3" maxlength="2000" required></textarea></div>';
        $form .= '<button class="btn btn-primary" type="submit">Enregistrer la prolongation</button>';
        $form .= '</form>';

        return self::modal(self::alertExtensionModalId($alert), $title, $form, false);
    }

    private static function alertExtensionModalId($alert)
    {
        $type = isset($alert['type']) ? (string) $alert['type'] : '';
        if ($type === 'pret_en_retard') {
            return 'imat-alert-extend-loan-'.(isset($alert['pret_id']) ? (int) $alert['pret_id'] : 0);
        }
        if ($type === 'personne_partie') {
            return 'imat-alert-extend-person-'.(isset($alert['personne_id']) ? (int) $alert['personne_id'] : 0).'-loan-'.(isset($alert['pret_id']) ? (int) $alert['pret_id'] : 0);
        }

        return 'imat-alert-extend';
    }

    private static function alertTypeBadge($alert)
    {
        $class = isset($alert['severity']) && (string) $alert['severity'] === 'danger' ? 'danger' : 'warning';
        $color = $class === 'danger'
            ? InformatiqueMaterielConfig::alertDangerColor()
            : InformatiqueMaterielConfig::alertWarningColor();
        return '<span class="label label-'.$class.'" style="background-color:'.self::html($color).'">'.self::html(self::alertTypeLabel($alert['type'])).'</span>';
    }

    private static function alertTypeLabel($type)
    {
        $labels = array(
            'pret_en_retard' => 'Pret en retard',
            'personne_partie' => 'Personne partie',
            'materiel_sans_identifiant' => 'Sans identifiant',
            'materiel_sans_categorie' => 'Sans categorie',
            'code_barre_duplique' => 'Code-barres duplique',
            'prets_ouverts_multiples' => 'Prets multiples non generiques',
        );

        return isset($labels[$type]) ? $labels[$type] : $type;
    }

    private static function alertUrl($alert)
    {
        if (isset($alert['query']) && (string) $alert['query'] !== '') {
            return self::url(array('view' => 'items', 'q' => (string) $alert['query']));
        }
        if (isset($alert['pret_id']) && (int) $alert['pret_id'] > 0) {
            return self::url(array('view' => 'loan', 'id' => (int) $alert['pret_id']));
        }
        if (isset($alert['item_id']) && (int) $alert['item_id'] > 0) {
            return self::url(array('view' => 'item', 'id' => (int) $alert['item_id']));
        }
        if (isset($alert['personne_id']) && (int) $alert['personne_id'] > 0) {
            return self::url(array('view' => 'loans', 'personne_id' => (int) $alert['personne_id'], 'closed' => '1'));
        }

        return '';
    }

    private static function categoryOptions($categories, $selectedId)
    {
        $html = '<option value="">Choisir</option>';
        foreach ($categories as $category) {
            $label = trim((string) $category['prefixe'].' - '.(string) $category['designation']);
            if ((int) $category['actif'] !== 1) {
                $label .= ' (archive)';
            }
            $html .= '<option value="'.(int) $category['id'].'"'.((int) $category['id'] === (int) $selectedId ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function itemStatusOptions($selected)
    {
        $html = '';
        foreach (InformatiqueMaterielRepository::itemStatuses() as $key => $label) {
            $html .= '<option value="'.self::html($key).'"'.((string) $key === (string) $selected ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function itemStatusFilterOptions($selected)
    {
        return '<option value="">Tous</option>'.self::itemStatusOptions($selected);
    }

    private static function itemStatusLabel($status)
    {
        $statuses = InformatiqueMaterielRepository::itemStatuses();
        $label = isset($statuses[$status]) ? $statuses[$status] : $status;
        $class = 'default';
        if ($status === 'actif' || $status === 'stocke') {
            $class = 'success';
        } elseif ($status === 'en_pret') {
            $class = 'primary';
        } elseif ($status === 'pret_multiple') {
            $class = 'info';
        } elseif ($status === 'maintenance') {
            $class = 'warning';
        } elseif ($status === 'a_reformer' || $status === 'archive') {
            $class = 'default';
        }

        return '<span class="label label-'.$class.'">'.self::html($label).'</span>';
    }

    private static function loanModeLabel($item)
    {
        return isset($item['pret_multiple']) && (int) $item['pret_multiple'] === 1
            ? '<span class="label label-info">Pret multiple</span>'
            : '<span class="label label-default">Pret unique</span>';
    }

    private static function detailRow($label, $value)
    {
        return '<tr><th style="width:220px">'.self::html($label).'</th><td>'.nl2br(self::html($value)).'</td></tr>';
    }

    private static function personDisplayName($person)
    {
        $label = trim((string) $person['prenom'].' '.(string) $person['nom']);
        return $label !== '' ? $label : 'Personne #'.(int) $person['id'];
    }

    private static function personLink($person, $label)
    {
        return '<a href="'.self::url(array('view' => 'person', 'id' => (int) $person['id'])).'">'.self::html($label).'</a>';
    }

    private static function personLoginLink($person)
    {
        $login = isset($person['login_grr']) ? trim((string) $person['login_grr']) : '';
        return $login === '' ? '' : self::personLink($person, $login);
    }

    private static function personDeparted($person)
    {
        return self::personHasDepartureDate($person)
            && (string) $person['date_depart'] < date('Y-m-d');
    }

    private static function personHasDepartureDate($person)
    {
        return isset($person['date_depart'])
            && trim((string) $person['date_depart']) !== '';
    }

    private static function personStateLabel($person)
    {
        if ((int) $person['actif'] !== 1) {
            return '<span class="label label-default">Archive</span>';
        }
        if (self::personDeparted($person)) {
            return '<span class="label label-danger">Parti</span>';
        }

        $count = isset($person['open_loan_count']) ? (int) $person['open_loan_count'] : 0;
        if ($count <= 0) {
            return '<span class="label label-default">Pas de materiel attribue</span>';
        }

        return '<span class="label label-primary">Materiel attribue : '.$count.'</span>';
    }

    private static function personDepartureModal($person)
    {
        $id = (int) $person['id'];
        $form = '<form method="post" action="'.self::url(array('view' => 'people')).'">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="extend_person_departure"><input type="hidden" name="personne_id" value="'.$id.'">';
        $form .= '<div class="form-group"><label>Nouvelle date de depart</label><input class="form-control" type="date" name="new_date" required value="'.self::html($person['date_depart']).'"></div>';
        $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="2" required></textarea></div>';
        $form .= '<button class="btn btn-primary" type="submit">Prolonger</button></form>';

        return self::modal('imat-person-extend-'.$id, 'Prolonger le depart - '.self::personDisplayName($person), $form, false);
    }

    private static function personLoansDepartureModal($person, $openLoans)
    {
        if (count($openLoans) === 0 || !self::personHasDepartureDate($person)) {
            return '';
        }

        $id = (int) $person['id'];
        $form = '<form method="post" action="'.self::url(array('view' => 'person', 'id' => $id)).'">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="align_person_loans_departure"><input type="hidden" name="personne_id" value="'.$id.'">';
        $form .= '<p>'.(int) count($openLoans).' pret(s) ouvert(s) seront mis a jour avec une fin prevue au '.self::html($person['date_depart']).'.</p>';
        $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="3" maxlength="2000" required></textarea></div>';
        $form .= '<p class="help-block">Les prets restent ouverts. Seule leur date de fin prevue est alignee sur la date de depart de la personne.</p>';
        $form .= '<button class="btn btn-primary" type="submit">Mettre a jour les fins de prets</button></form>';

        return self::modal('imat-person-loans-departure-'.$id, 'Fin des prets - date de depart', $form, false);
    }

    private static function transferLoanModal($id, $title, $loans, $people, $returnParams = array())
    {
        if (count($loans) === 0) {
            return '';
        }

        if (count($returnParams) === 0) {
            $returnParams = array('view' => 'loans');
        }

        $sourcePeople = array();
        foreach ($loans as $loan) {
            $sourcePeople[(int) $loan['personne_id']] = (int) $loan['personne_id'];
        }
        $excludePersonId = count($sourcePeople) === 1 ? reset($sourcePeople) : 0;

        $form = '<form method="post" action="'.self::url($returnParams).'">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="transfer_loan">';
        if (count($loans) === 1) {
            $loan = reset($loans);
            $form .= '<input type="hidden" name="pret_id" value="'.(int) $loan['id'].'">';
            $form .= '<p><strong>'.self::html(self::loanItemLabel($loan)).'</strong><br><span class="text-muted">Actuellement : '.self::html(self::loanPersonLabel($loan)).'</span></p>';
        } else {
            $form .= '<div class="form-group"><label>Pret a transferer</label><select class="form-control" name="pret_id" required>';
            $form .= '<option value="">Choisir</option>';
            foreach ($loans as $loan) {
                $label = '#'.(int) $loan['id'].' - '.self::loanItemLabel($loan).' / '.self::loanPersonLabel($loan);
                $form .= '<option value="'.(int) $loan['id'].'">'.self::html($label).'</option>';
            }
            $form .= '</select></div>';
        }
        $form .= '<div class="row"><div class="col-md-6 form-group"><label>Nouvelle personne</label><select class="form-control" name="personne_id" required>'.self::personOptions($people, 0, $excludePersonId).'</select></div>';
        $form .= '<div class="col-md-6 form-group"><label>Date de transfert</label><input class="form-control" type="date" name="date_transfert" required value="'.self::html(date('Y-m-d')).'"></div></div>';
        $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="3" maxlength="2000" required></textarea></div>';
        $form .= '<p class="help-block">Le pret actuel sera clos et un nouveau pret ouvert sera cree pour la personne destinataire.</p>';
        $form .= '<button class="btn btn-primary" type="submit">Transferer</button></form>';

        return self::modal($id, $title, $form, false);
    }

    private static function transferPersonLoansModal($person, $openLoans, $people)
    {
        if (count($openLoans) === 0) {
            return '';
        }

        $personId = (int) $person['id'];
        $form = '<form method="post" action="'.self::url(array('view' => 'person', 'id' => $personId)).'">';
        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="transfer_person_loans"><input type="hidden" name="source_personne_id" value="'.$personId.'">';
        $form .= '<p>'.(int) count($openLoans).' pret(s) ouvert(s) seront transferes depuis '.self::html(self::personDisplayName($person)).'.</p>';
        $form .= '<div class="row"><div class="col-md-6 form-group"><label>Nouvelle personne</label><select class="form-control" name="personne_id" required>'.self::personOptions($people, 0, $personId).'</select></div>';
        $form .= '<div class="col-md-6 form-group"><label>Date de transfert</label><input class="form-control" type="date" name="date_transfert" required value="'.self::html(date('Y-m-d')).'"></div></div>';
        $form .= '<div class="form-group"><label>Commentaire</label><textarea class="form-control" name="commentaire" rows="3" maxlength="2000" required></textarea></div>';
        $form .= '<p class="help-block">Chaque pret ouvert sera clos, puis recree pour la personne destinataire.</p>';
        $form .= '<button class="btn btn-primary" type="submit">Transferer tout le materiel</button></form>';

        return self::modal('imat-transfer-person-loans-'.$personId, 'Transferer tout le materiel', $form, false);
    }

    private static function loanRowsTable($loans, $showActions, $showTransfer = false, $returnParams = array(), $showComment = false)
    {
        $showTransfer = $showTransfer && InformatiqueMaterielSecurity::canOperate();
        $people = $showTransfer ? InformatiqueMaterielRepository::people(false) : array();
        $modals = '';
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr>';
        $html .= '<th>N&deg; du pret</th><th>Materiel</th><th>Personne</th><th>Localisation</th><th>Debut</th><th>Fin prevue</th><th>Retour</th>';
        if ($showComment) {
            $html .= '<th>Commentaire</th>';
        }
        $html .= '<th>Statut</th>';
        if ($showActions || $showTransfer) {
            $html .= '<th></th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($loans as $loan) {
            $status = self::loanStatusLabel($loan['statut']);
            if ((string) $loan['statut'] === 'ouvert' && (string) $loan['date_fin_prevue'] !== '' && (string) $loan['date_fin_prevue'] < date('Y-m-d')) {
                $status .= ' <span class="label label-danger">Retard</span>';
            }

            $html .= '<tr><td><a href="'.self::url(array('view' => 'loan', 'id' => (int) $loan['id'])).'">'.(int) $loan['id'].'</a></td>';
            $html .= '<td><a href="'.self::url(array('view' => 'item', 'id' => (int) $loan['item_id'])).'">'.self::html(self::loanItemLabel($loan)).'</a></td>';
            $html .= '<td><a href="'.self::url(array('view' => 'person', 'id' => (int) $loan['personne_id'])).'">'.self::html(self::loanPersonLabel($loan)).'</a></td>';
            $html .= '<td>'.self::html($loan['localisation']).'</td>';
            $html .= '<td>'.self::html($loan['date_debut']).'</td>';
            $html .= '<td>'.self::html($loan['date_fin_prevue']).'</td>';
            $html .= '<td>'.self::html($loan['date_fin_effective']).'</td>';
            if ($showComment) {
                $commentaire = isset($loan['commentaire']) ? trim((string) $loan['commentaire']) : '';
                $html .= '<td>'.($commentaire !== '' ? nl2br(self::html($commentaire)) : '<span class="text-muted">-</span>').'</td>';
            }
            $html .= '<td>'.$status.'</td>';

            if ($showActions || $showTransfer) {
                $html .= '<td><div class="imat-table-actions">';
                if ((string) $loan['statut'] === 'ouvert' && InformatiqueMaterielSecurity::canOperate()) {
                    if ($showActions) {
                        $closeId = 'imat-close-loan-'.(int) $loan['id'];
                        $form = '<form method="post" action="'.self::url(array('view' => 'loans')).'">';
                        $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="close_loan"><input type="hidden" name="id" value="'.(int) $loan['id'].'">';
                        $form .= '<div class="row"><div class="col-md-4 form-group"><label>Date retour effective</label><input class="form-control" type="date" name="date_fin_effective" required value="'.self::html(date('Y-m-d')).'"></div>';
                        $form .= '<div class="col-md-8 form-group"><label>Commentaire</label><input class="form-control" name="commentaire" maxlength="2000"></div></div>';
                        $form .= '<button class="btn btn-primary" type="submit">Restituer</button></form>';
                        $html .= self::modalButton($closeId, 'Restituer', 'btn btn-primary btn-xs');
                        $html .= self::modal($closeId, 'Restituer le pret #'.(int) $loan['id'], $form, false);
                    }
                    if ($showTransfer && InformatiqueMaterielSecurity::canOperate()) {
                        $transferId = 'imat-transfer-loan-'.(int) $loan['id'];
                        $html .= ' '.self::modalButton($transferId, 'Transferer', 'btn btn-primary btn-xs');
                        $modals .= self::transferLoanModal($transferId, 'Transferer le pret #'.(int) $loan['id'], array($loan), $people, $returnParams);
                    }
                }
                if ($showActions && (string) $loan['statut'] !== 'annule' && InformatiqueMaterielSecurity::canManage()) {
                    $cancelId = 'imat-cancel-loan-'.(int) $loan['id'];
                    $form = '<form method="post" action="'.self::url(array('view' => 'loans')).'" onsubmit="return confirm(\'Annuler ce pret ?\')">';
                    $form .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="cancel_loan"><input type="hidden" name="id" value="'.(int) $loan['id'].'">';
                    $form .= '<div class="form-group"><label>Motif</label><input class="form-control" name="commentaire" maxlength="2000"></div>';
                    $form .= '<button class="btn btn-warning" type="submit">Annuler le pret</button></form>';
                    $html .= self::modalButton($cancelId, 'Annuler', 'btn btn-warning btn-xs');
                    $html .= self::modal($cancelId, 'Annuler le pret #'.(int) $loan['id'], $form, false);
                }
                if ($showActions && InformatiqueMaterielSecurity::canManage()) {
                    $html .= '<form method="post" action="'.self::url(array('view' => 'loans')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement ce pret ?\')">';
                    $html .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="delete_loan"><input type="hidden" name="id" value="'.(int) $loan['id'].'">';
                    $html .= '<button class="btn btn-danger btn-xs" type="submit">Supprimer</button></form>';
                }
                $html .= '</div></td>';
            }
            $html .= '</tr>';
        }

        if (count($loans) === 0) {
            $colspan = 8 + ($showComment ? 1 : 0) + ($showActions || $showTransfer ? 1 : 0);
            $html .= '<tr><td colspan="'.$colspan.'">Aucun pret.</td></tr>';
        }
        $html .= '</tbody></table></div>'.$modals;

        return $html;
    }

    private static function personOptions($people, $selectedId, $excludeId = 0)
    {
        $html = '<option value="">Choisir</option>';
        foreach ($people as $person) {
            if ((int) $excludeId > 0 && (int) $person['id'] === (int) $excludeId) {
                continue;
            }
            $label = trim((string) $person['prenom'].' '.(string) $person['nom']);
            if ($label === '') {
                $label = '#'.(int) $person['id'];
            }
            if ((string) $person['identifiant_legacy'] !== '') {
                $label .= ' - '.$person['identifiant_legacy'];
            }
            $html .= '<option value="'.(int) $person['id'].'"'.((int) $person['id'] === (int) $selectedId ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function loginSuggestionsForPerson($values)
    {
        if (!class_exists('InformatiqueMaterielLdapDirectory')) {
            return InformatiqueMaterielRepository::grrUsersForPerson(
                isset($values['prenom']) ? $values['prenom'] : '',
                isset($values['nom']) ? $values['nom'] : '',
                isset($values['login_grr']) ? $values['login_grr'] : ''
            );
        }

        return InformatiqueMaterielLdapDirectory::suggestionsForPerson(
            isset($values['prenom']) ? $values['prenom'] : '',
            isset($values['nom']) ? $values['nom'] : '',
            isset($values['login_grr']) ? $values['login_grr'] : ''
        );
    }

    private static function loginOptions($suggestions, $selectedLogin)
    {
        $selectedLogin = trim((string) $selectedLogin);
        $html = '<option value=""'.($selectedLogin === '' ? ' selected' : '').'>Non associe</option>';
        $seen = array();

        foreach ($suggestions as $suggestion) {
            $login = isset($suggestion['login']) ? trim((string) $suggestion['login']) : '';
            if ($login === '' || isset($seen[strtolower($login)])) {
                continue;
            }
            $seen[strtolower($login)] = true;
            $label = isset($suggestion['label']) ? (string) $suggestion['label'] : $login;
            $html .= '<option value="'.self::html($login).'"'.($login === $selectedLogin ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        if ($selectedLogin !== '' && !isset($seen[strtolower($selectedLogin)])) {
            $html .= '<option value="'.self::html($selectedLogin).'" selected>'.self::html('[Actuel] '.$selectedLogin).'</option>';
        }

        return $html;
    }

    private static function associationSelectedLogin($person, $suggestions)
    {
        $current = isset($person['login_grr']) ? trim((string) $person['login_grr']) : '';
        if ($current !== '') {
            return $current;
        }

        $unique = array();
        foreach ($suggestions as $suggestion) {
            $login = isset($suggestion['login']) ? trim((string) $suggestion['login']) : '';
            if ($login !== '') {
                $unique[strtolower($login)] = $login;
            }
        }

        return count($unique) === 1 ? reset($unique) : '';
    }

    private static function associationSourcesLabel($suggestions)
    {
        $sources = array();
        $seen = array();
        foreach ($suggestions as $suggestion) {
            $login = isset($suggestion['login']) ? trim((string) $suggestion['login']) : '';
            if ($login === '' || isset($seen[strtolower($login)])) {
                continue;
            }
            $seen[strtolower($login)] = true;
            $source = isset($suggestion['source']) ? (string) $suggestion['source'] : 'GRR';
            if (!isset($sources[$source])) {
                $sources[$source] = 0;
            }
            $sources[$source]++;
        }

        if (count($sources) === 0) {
            return 'Aucune';
        }

        $labels = array();
        foreach ($sources as $source => $count) {
            $labels[] = $source.' : '.$count;
        }

        return implode(' ; ', $labels);
    }

    private static function itemOptions($items, $selectedId)
    {
        $html = '<option value="">Choisir</option>';
        foreach ($items as $item) {
            $label = trim((string) $item['identifiant'].' - '.(string) $item['designation']);
            if ((string) $item['statut'] !== '') {
                $label .= ' ('.$item['statut'].')';
            }
            if (isset($item['pret_multiple']) && (int) $item['pret_multiple'] === 1) {
                $label .= ' - pret multiple';
            }
            $html .= '<option value="'.(int) $item['id'].'"'.((int) $item['id'] === (int) $selectedId ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function loanStatusOptions($selected)
    {
        $html = '';
        foreach (InformatiqueMaterielRepository::loanStatuses() as $key => $label) {
            $html .= '<option value="'.self::html($key).'"'.((string) $key === (string) $selected ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function loanStatusFilterOptions($selected)
    {
        return '<option value="">Tous</option>'.self::loanStatusOptions($selected);
    }

    private static function loanStatusLabel($status)
    {
        $statuses = InformatiqueMaterielRepository::loanStatuses();
        $label = isset($statuses[$status]) ? $statuses[$status] : $status;
        $class = 'default';
        if ($status === 'ouvert') {
            $class = 'primary';
        } elseif ($status === 'clos') {
            $class = 'success';
        }

        return '<span class="label label-'.$class.'">'.self::html($label).'</span>';
    }

    private static function loanPersonLabel($loan)
    {
        $label = trim((string) $loan['personne_prenom'].' '.(string) $loan['personne_nom']);
        return $label === '' ? '#'.(int) $loan['personne_id'] : $label;
    }

    private static function loanItemLabel($loan)
    {
        $label = trim((string) $loan['item_identifiant'].' - '.(string) $loan['item_designation']);
        return $label === '-' ? '#'.(int) $loan['item_id'] : $label;
    }

    private static function itemShortLabel($item)
    {
        $label = trim((string) $item['identifiant'].' - '.(string) $item['designation']);
        return $label === '-' || $label === '' ? 'materiel #'.(int) $item['id'] : $label;
    }

    private static function importTypeOptions($selected)
    {
        $html = '<option value="">Choisir</option>';
        foreach (InformatiqueMaterielImport::types() as $key => $label) {
            $html .= '<option value="'.self::html($key).'"'.((string) $key === (string) $selected ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function ensureImportClass(&$errors)
    {
        if (!class_exists('InformatiqueMaterielImport')) {
            $path = __DIR__.'/Import.php';
            if (is_file($path)) {
                require_once $path;
            }
        }

        if (!class_exists('InformatiqueMaterielImport')) {
            $errors[] = 'Le composant d import CSV est introuvable.';
            return false;
        }

        return true;
    }

    private static function importExpectedColumns()
    {
        $rows = array(
            array('Categories', 'prefixe ; designation ; description'),
            array('Personnes', 'id ; identifiant personnel ; prenom ; nom ; cadre de l usage ; date depart ; login grr ; notes'),
            array('Materiels', 'identifiant materiel ; designation ; precision ; mac ; marque ; numero de serie ; code barre usmb ; os ; annee ; commentaire ; localisation ; statut ; pret multiple'),
            array('Prets', 'identifiant personnel ; identifiant materiel ; localisation ; date debut ; date fin prevue ; date fin effective ; commentaire ; motif anomalie ; action_proposee ; justification'),
        );

        $html = '<div class="table-responsive"><table class="table table-condensed table-bordered"><thead><tr><th>Type</th><th>Colonnes reconnues</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr><td>'.self::html($row[0]).'</td><td><code>'.self::html($row[1]).'</code></td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    private static function importPreviewPanel($preview)
    {
        $summary = $preview['summary'];
        $typeLabels = InformatiqueMaterielImport::types();
        $label = isset($typeLabels[$preview['type']]) ? $typeLabels[$preview['type']] : $preview['type'];
        $html = '<div class="panel panel-default"><div class="panel-heading"><strong>Previsualisation - '.self::html($label).'</strong></div><div class="panel-body">';
        $html .= '<p>Fichier : '.self::html($preview['original_name']).'</p>';
        $html .= '<p>Total : '.(int) $summary['total'].' ; valides : '.(int) $summary['valid'].' ; erreurs : '.(int) $summary['errors'].' ; deja journalisees : '.(int) $summary['already_done'].'.</p>';
        $executeForm = '<form method="post" action="'.self::url(array('view' => 'import')).'">';
        $executeForm .= InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_action" value="execute_import">';
        $executeForm .= '<input type="hidden" name="import_type" value="'.self::html($preview['type']).'">';
        $executeForm .= '<input type="hidden" name="package_hash" value="'.self::html($preview['hash']).'">';
        $executeForm .= '<input type="hidden" name="stored_name" value="'.self::html($preview['stored_name']).'">';
        $executeForm .= '<input type="hidden" name="original_name" value="'.self::html($preview['original_name']).'">';
        $executeForm .= '<p>Confirmez l import des lignes valides de cette previsualisation.</p>';
        $executeForm .= '<button class="btn btn-primary" type="submit">Executer l import</button>';
        $executeForm .= '</form>';
        $html .= '<div class="imat-actions">'.self::modalButton('imat-import-execute', 'Executer l import').'</div>';
        $html .= self::modal('imat-import-execute', 'Executer l import', $executeForm, false);
        $html .= self::importPreviewTable($preview['rows']);
        $html .= '</div></div>';

        return $html;
    }

    private static function importPreviewTable($rows)
    {
        $html = '<div class="table-responsive" style="margin-top:12px"><table class="table table-bordered table-striped"><thead><tr><th>Ligne</th><th>Etat</th><th>Message</th><th>Donnees reconnues</th></tr></thead><tbody>';
        $count = 0;
        foreach ($rows as $row) {
            if ($count >= 50) {
                break;
            }
            $count++;
            $state = 'Valide';
            $message = '';
            if (!empty($row['already_done'])) {
                $state = 'Deja journalisee';
                $message = $row['status'].' '.$row['message'];
            } elseif (count($row['errors']) > 0) {
                $state = 'Erreur';
                $message = implode(' | ', $row['errors']);
            }
            $html .= '<tr><td>'.(int) $row['source_row'].'</td><td>'.self::html($state).'</td><td>'.self::html($message).'</td><td><code>'.self::html(self::compactValues($row['values'])).'</code></td></tr>';
        }
        if (count($rows) === 0) {
            $html .= '<tr><td colspan="4">Aucune ligne reconnue.</td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    private static function importLogTable($logs)
    {
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Fichier</th><th>Type</th><th>Ligne</th><th>Statut</th><th>Message</th></tr></thead><tbody>';
        foreach ($logs as $log) {
            $html .= '<tr><td>'.self::html(date('Y-m-d H:i', (int) $log['created_at'])).'</td><td>'.self::html($log['package_name']).'</td><td>'.self::html($log['source_table']).'</td><td>'.(int) $log['source_row'].'</td><td>'.self::html($log['status']).'</td><td>'.self::html($log['message']).'</td></tr>';
        }
        if (count($logs) === 0) {
            $html .= '<tr><td colspan="6">Aucun import journalise.</td></tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    private static function loanConflictTable($conflicts, $withActions = false)
    {
        $modals = '';
        $html = '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Conflit</th><th>Nouvelle entree</th><th>Pret existant</th><th>Periode demandee</th><th>Commentaire / decision</th>';
        if ($withActions) {
            $html .= '<th>Actions</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($conflicts as $conflict) {
            $newPerson = trim((string) $conflict['personne_prenom'].' '.(string) $conflict['personne_nom']);
            if ($newPerson === '') {
                $newPerson = '#'.(int) $conflict['personne_id'];
            }
            $newPersonCell = (int) $conflict['personne_id'] > 0
                ? '<a href="'.self::url(array('view' => 'person', 'id' => (int) $conflict['personne_id'])).'">'.self::html($newPerson).'</a>'
                : self::html($newPerson);
            $newItem = trim((string) $conflict['item_identifiant'].' - '.(string) $conflict['item_designation']);
            if ($newItem === '-') {
                $newItem = '#'.(int) $conflict['item_id'];
            }
            $newItemCell = (int) $conflict['item_id'] > 0
                ? '<a href="'.self::url(array('view' => 'item', 'id' => (int) $conflict['item_id'])).'">'.self::html($newItem).'</a>'
                : self::html($newItem);
            $existingPerson = trim((string) $conflict['existant_personne_prenom'].' '.(string) $conflict['existant_personne_nom']);
            $existing = (int) $conflict['pret_existant_id'] > 0
                ? '<a href="'.self::url(array('view' => 'loan', 'id' => (int) $conflict['pret_existant_id'])).'">Pret #'.(int) $conflict['pret_existant_id'].'</a><br>'
                    .self::html($existingPerson).'<br><small>'.self::html($conflict['existant_date_debut'].' -> '.$conflict['existant_date_fin_prevue']).'</small>'
                : '<span class="text-muted">Aucun pret ouvert lie</span>';
            $period = (string) $conflict['date_debut'].' -> '.(string) $conflict['date_fin_prevue'];
            if ((string) $conflict['date_fin_effective'] !== '') {
                $period .= ' ; retour '.(string) $conflict['date_fin_effective'];
            }
            $comment = trim((string) $conflict['commentaire']);
            $decision = trim((string) $conflict['decision']);
            $commentCell = $comment !== '' ? nl2br(self::html($comment)) : '<span class="text-muted">Aucun commentaire</span>';
            if ($decision !== '') {
                $commentCell .= '<hr style="margin:6px 0"><strong>Decision :</strong><br>'.nl2br(self::html($decision));
            }

            $html .= '<tr><td>'
                .self::loanConflictStatusLabel($conflict['statut']).'<br>'
                .'<small>'.self::html(self::formatDateTime((int) $conflict['created_at'])).' - ligne '.(int) $conflict['source_row'].'</small><br>'
                .'<small>'.self::html(self::loanConflictMotifLabel($conflict['motif'])).'</small>'
                .'</td><td><strong>Personne</strong><br>'.$newPersonCell.'<br><strong>Materiel</strong><br>'.$newItemCell.'</td>'
                .'<td>'.$existing.'</td><td>'.self::html($period).'</td><td>'.$commentCell.'</td>';
            if ($withActions) {
                $actions = self::loanConflictActions($conflict);
                $html .= '<td>'.$actions['buttons'].'</td>';
                $modals .= $actions['modals'];
            }
            $html .= '</tr>';
        }
        if (count($conflicts) === 0) {
            $html .= '<tr><td colspan="'.($withActions ? 6 : 5).'">Aucun conflit de pret en attente.</td></tr>';
        }
        $html .= '</tbody></table></div>'.$modals;

        return $html;
    }

    private static function loanConflictStatusLabel($status)
    {
        $labels = array(
            'en_attente' => array('En attente', 'warning'),
            'ignore' => array('Conserve', 'default'),
            'importe' => array('Integre', 'success'),
            'remplace' => array('Remplace', 'success'),
        );
        $label = isset($labels[$status]) ? $labels[$status][0] : $status;
        $class = isset($labels[$status]) ? $labels[$status][1] : 'default';

        return '<span class="label label-'.$class.'">'.self::html($label).'</span>';
    }

    private static function loanConflictMotifLabel($motif)
    {
        $labels = array(
            'pret_ouvert_doublon_materiel' => 'Materiel deja lie a un pret ouvert',
            'materiel_statut_en_pret' => 'Materiel marque en pret sans pret ouvert retrouve',
        );

        return isset($labels[$motif]) ? $labels[$motif] : $motif;
    }

    private static function loanConflictActions($conflict)
    {
        $viewButtons = array();
        $decisionButtons = array();
        $dangerButtons = array();
        $modals = '';
        $id = isset($conflict['id']) ? (int) $conflict['id'] : 0;

        if ((int) $conflict['pret_existant_id'] > 0) {
            $viewButtons[] = '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'loan', 'id' => (int) $conflict['pret_existant_id'])).'">Pret existant</a>';
        }
        if ((int) $conflict['item_id'] > 0) {
            $viewButtons[] = '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'item', 'id' => (int) $conflict['item_id'])).'">Materiel</a>';
        }
        if ((int) $conflict['personne_id'] > 0) {
            $viewButtons[] = '<a class="btn btn-default btn-xs" href="'.self::url(array('view' => 'person', 'id' => (int) $conflict['personne_id'])).'">Personne</a>';
        }

        if ($id > 0 && (string) $conflict['statut'] === 'en_attente') {
            $decisionButtons[] = self::modalButton('imat-conflict-ignore-'.$id, 'Conserver l existant', 'btn btn-default btn-xs');
            $modals .= self::loanConflictResolutionModal($conflict, 'ignore');

            $decisionButtons[] = self::modalButton('imat-conflict-create-'.$id, 'Integrer si libre', 'btn btn-primary btn-xs');
            $modals .= self::loanConflictResolutionModal($conflict, 'create');

            if ((int) $conflict['pret_existant_id'] > 0) {
                $decisionButtons[] = self::modalButton('imat-conflict-replace-'.$id, 'Remplacer l existant', 'btn btn-danger btn-xs');
                $modals .= self::loanConflictResolutionModal($conflict, 'replace');
            }
        }
        if ($id > 0 && InformatiqueMaterielSecurity::canManage()) {
            $dangerButtons[] = '<form method="post" action="'.self::url(array('view' => 'conflicts')).'" style="display:inline" onsubmit="return confirm(\'Supprimer definitivement ce conflit ?\')">'
                .InformatiqueMaterielSecurity::field()
                .'<input type="hidden" name="imat_action" value="delete_loan_conflict">'
                .'<input type="hidden" name="conflict_id" value="'.$id.'">'
                .'<button class="btn btn-danger btn-xs" type="submit">Supprimer</button></form>';
        }
        $html = '';
        if (count($viewButtons) > 0) {
            $html .= '<div class="imat-conflict-actions-group"><span class="imat-conflict-actions-title">Consulter</span>'.implode(' ', $viewButtons).'</div>';
        }
        if (count($decisionButtons) > 0) {
            $html .= '<div class="imat-conflict-actions-group"><span class="imat-conflict-actions-title">Decider</span>'.implode(' ', $decisionButtons).'</div>';
        }
        if (count($dangerButtons) > 0) {
            $html .= '<div class="imat-conflict-actions-group"><span class="imat-conflict-actions-title">Administration</span>'.implode(' ', $dangerButtons).'</div>';
        }
        if ($html === '') {
            $html = '<span class="text-muted">Aucune action disponible</span>';
        }

        return array(
            'buttons' => '<div class="imat-conflict-actions">'.$html.'</div>',
            'modals' => $modals,
        );
    }

    private static function loanConflictResolutionModal($conflict, $action)
    {
        $id = (int) $conflict['id'];
        $labels = array(
            'ignore' => array('Conserver l existant', 'Le conflit sera marque resolu sans creer de nouveau pret.'),
            'create' => array('Integrer la nouvelle entree', 'Le nouveau pret sera cree si le materiel n a plus de pret ouvert, ou si le materiel est marque en pret multiple.'),
            'replace' => array('Remplacer par la nouvelle entree', 'Le pret existant sera annule puis la nouvelle entree sera creee.'),
        );
        if (!isset($labels[$action])) {
            return '';
        }

        $form = '<form method="post" action="'.self::url(array('view' => 'conflicts')).'">';
        $form .= InformatiqueMaterielSecurity::field();
        $form .= '<input type="hidden" name="imat_action" value="resolve_loan_conflict">';
        $form .= '<input type="hidden" name="conflict_id" value="'.$id.'">';
        $form .= '<input type="hidden" name="resolution_action" value="'.self::html($action).'">';
        $form .= self::loanConflictModalSummary($conflict);
        $form .= '<p class="help-block">'.self::html($labels[$action][1]).'</p>';
        $form .= '<div class="form-group"><label>Decision / justification</label><textarea class="form-control" name="decision" rows="3" maxlength="2000" required></textarea></div>';
        if ($action === 'replace') {
            $form .= '<div class="form-group"><label>Confirmation</label><input class="form-control" name="confirmation" maxlength="20" placeholder="REMPLACER" required></div>';
        }
        $form .= '<button class="btn '.($action === 'replace' ? 'btn-danger' : 'btn-primary').'" type="submit">'.self::html($labels[$action][0]).'</button>';
        $form .= '</form>';

        return self::modal('imat-conflict-'.$action.'-'.$id, $labels[$action][0], $form, false);
    }

    private static function loanConflictModalSummary($conflict)
    {
        $person = trim((string) $conflict['personne_prenom'].' '.(string) $conflict['personne_nom']);
        if ($person === '') {
            $person = '#'.(int) $conflict['personne_id'];
        }
        $item = trim((string) $conflict['item_identifiant'].' - '.(string) $conflict['item_designation']);
        if ($item === '-') {
            $item = '#'.(int) $conflict['item_id'];
        }
        $existing = (int) $conflict['pret_existant_id'] > 0
            ? 'Pret #'.(int) $conflict['pret_existant_id'].' - '.trim((string) $conflict['existant_personne_prenom'].' '.(string) $conflict['existant_personne_nom'])
            : 'Aucun pret existant reference';

        return '<div class="panel panel-default"><div class="panel-body">'
            .'<p><strong>Nouvelle entree :</strong> '.self::html($person).' / '.self::html($item).'</p>'
            .'<p><strong>Periode :</strong> '.self::html((string) $conflict['date_debut'].' -> '.(string) $conflict['date_fin_prevue']).'</p>'
            .'<p><strong>Existant :</strong> '.self::html($existing).'</p>'
            .'</div></div>';
    }

    private static function compactValues($values)
    {
        $parts = array();
        foreach ($values as $key => $value) {
            if ($value !== '' && $value !== 0 && $value !== null) {
                $parts[] = $key.'='.$value;
            }
        }

        return implode(' ; ', $parts);
    }

    private static function documentTypeOptions($selected)
    {
        $html = '';
        foreach (InformatiqueMaterielRepository::documentTypes() as $key => $label) {
            $html .= '<option value="'.self::html($key).'"'.((string) $key === (string) $selected ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function sanitizeDocumentName($name)
    {
        $name = basename((string) $name);
        $name = str_replace(array("\r", "\n", '"'), '', $name);
        $name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name);
        $name = trim((string) $name);

        return $name === '' ? 'document' : substr($name, 0, 255);
    }

    private static function generateDocumentStoredName()
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $name = bin2hex(random_bytes(32));
            } catch (Throwable $exception) {
                $name = hash('sha256', uniqid('', true).mt_rand());
            }
            if (InformatiqueMaterielRepository::documentPath($name) !== '' && !is_file(InformatiqueMaterielRepository::documentPath($name))) {
                return $name;
            }
        }

        return hash('sha256', uniqid('', true).mt_rand());
    }

    private static function documentMimeType($path, $fallback)
    {
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string) @finfo_file($finfo, $path);
                @finfo_close($finfo);
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

    private static function documentMimeRejected($mimeType)
    {
        $mimeType = strtolower(trim((string) $mimeType));
        $forbidden = array(
            'application/javascript',
            'application/x-httpd-php',
            'application/x-msdownload',
            'application/x-php',
            'application/x-sh',
            'image/svg+xml',
            'text/html',
            'text/javascript',
        );

        return in_array($mimeType, $forbidden, true);
    }

    private static function uploadErrorMessage($code)
    {
        $messages = array(
            UPLOAD_ERR_INI_SIZE => 'Le fichier depasse la taille maximale PHP.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier depasse la taille maximale du formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le transfert du fichier est incomplet.',
            UPLOAD_ERR_NO_FILE => 'Selectionnez un fichier.',
            UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire PHP est indisponible.',
            UPLOAD_ERR_CANT_WRITE => 'Le fichier temporaire ne peut pas etre ecrit.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a bloque le transfert.',
        );

        return isset($messages[$code]) ? $messages[$code] : 'Transfert du fichier impossible.';
    }

    private static function formatBytes($bytes)
    {
        $bytes = max(0, (int) $bytes);
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' Mo';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' Ko';
        }

        return $bytes.' o';
    }

    private static function formatDateTime($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('Y-m-d H:i', $timestamp) : '';
    }

    private static function startPage($messages, $errors, $userOnly = false)
    {
        $html = self::assets();
        $html .= '<div class="informatique-materiel">';
        $html .= '<div class="imat-loading-overlay" id="imat-loading-overlay" role="status" aria-live="polite"><div class="imat-loading-box"><strong>Chargement</strong><span>Recherche LDAP en cours...</span></div></div>';
        if (!$userOnly && InformatiqueMaterielSecurity::canAccess()) {
            $html .= '<div class="imat-nav">';
            $html .= '<a class="btn btn-default" href="'.self::url(array()).'">Tableau de bord</a> ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'people')).'">Personnes</a> ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'categories')).'">Categories</a> ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'items')).'">Materiels</a> ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'loans')).'">Prets</a> ';
            $html .= '<a class="btn btn-default" href="'.self::url(array('view' => 'alerts')).'">Alertes</a>';
            if (InformatiqueMaterielSecurity::canManage()) {
                $html .= ' <a class="btn btn-default" href="'.self::url(array('view' => 'conflicts')).'">Conflits</a>';
                $html .= ' <a class="btn btn-default" href="'.self::url(array('view' => 'import')).'">Import CSV</a>';
            }
            if (InformatiqueMaterielSecurity::isAdmin()) {
                $html .= ' <a class="btn btn-default" href="'.self::url(array('admin' => '1')).'">Administration</a>';
            }
            $html .= '</div>';
        }
        if (!$userOnly && InformatiqueMaterielSecurity::canManage() && InformatiqueMaterielConfig::conflictBannerEnabled()) {
            $pendingConflicts = InformatiqueMaterielRepository::countPendingLoanConflicts();
            if ($pendingConflicts > 0) {
                $html .= self::conflictTopAlert($pendingConflicts);
            }
        }
        foreach ($messages as $message) {
            $html .= '<div class="alert alert-success">'.self::html($message).'</div>';
        }
        foreach ($errors as $error) {
            $html .= '<div class="alert alert-danger">'.self::html($error).'</div>';
        }

        return $html;
    }

    private static function conflictTopAlert($count)
    {
        $count = (int) $count;
        $label = $count.' conflit'.($count > 1 ? 's' : '').' de prets en attente';
        $color = InformatiqueMaterielConfig::conflictAlertColor();

        return '<div class="imat-conflict-top-alert" style="background-color:'.self::html($color).'">'
            .'<strong>'.self::html($label).'</strong> '
            .'<a href="'.self::url(array('view' => 'conflicts')).'">Traiter les conflits</a>'
            .'</div>';
    }

    private static function resultMessage($result, $success, &$messages, &$errors)
    {
        if (!empty($result['ok'])) {
            $messages[] = $success;
            return;
        }
        if (isset($result['errors']) && is_array($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $errors[] = $error;
            }
        } else {
            $errors[] = 'Operation impossible.';
        }
    }

    private static function booleanMessage($ok, $success, $failure, &$messages, &$errors)
    {
        if ($ok) {
            $messages[] = $success;
        } else {
            $errors[] = $failure;
        }
    }

    private static function statusLabel($active)
    {
        return $active
            ? '<span class="label label-success">Actif</span>'
            : '<span class="label label-default">Archive</span>';
    }

    private static function modalButton($id, $label, $class = 'btn btn-primary')
    {
        return '<button class="'.self::html($class).'" type="button" data-imat-modal-open="'.self::html($id).'">'.self::html($label).'</button>';
    }

    private static function modal($id, $title, $content, $open = false)
    {
        return '<div class="imat-modal'.($open ? ' is-open' : '').'" id="'.self::html($id).'" role="dialog" aria-modal="true">'
            .'<div class="imat-modal-dialog">'
            .'<div class="imat-modal-head"><strong>'.self::html($title).'</strong><button class="imat-modal-close" type="button" data-imat-modal-close>&times;</button></div>'
            .'<div class="imat-modal-body">'.$content.'</div>'
            .'</div></div>';
    }

    private static function url($params)
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';
        $query = array_merge(array('pc' => InformatiqueMaterielConfig::MODULE), $params);
        return $base.'?'.http_build_query($query, '', '&');
    }

    private static function assets()
    {
        return '<style>'
            .'.informatique-materiel{width:100%;max-width:none;margin:0 0 30px;box-sizing:border-box;}'
            .'.informatique-materiel *,#menu-compte .imat-account-btn{box-sizing:border-box;}'
            .'#menu-compte .imat-account-btn{display:block;width:100%;max-width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
            .'.informatique-materiel .imat-header{margin-bottom:18px;}'
            .'.informatique-materiel .imat-nav{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 15px;}'
            .'.informatique-materiel .imat-actions{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0;}'
            .'.informatique-materiel .imat-conflict-top-alert{margin:0 0 12px;padding:10px 12px;border-radius:4px;color:#fff;}'
            .'.informatique-materiel .imat-conflict-top-alert a{color:#fff;text-decoration:underline;font-weight:bold;}'
            .'.informatique-materiel .imat-cards{display:flex;flex-wrap:wrap;gap:10px;margin:15px 0;}'
            .'.informatique-materiel .imat-card{min-width:150px;border:1px solid #ddd;border-radius:4px;padding:10px 12px;background:#fff;}'
            .'.informatique-materiel .imat-card span{display:block;color:#555;font-size:12px;text-transform:uppercase;}'
            .'.informatique-materiel .imat-card strong{font-size:28px;line-height:1.2;}'
            .'.informatique-materiel .imat-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;margin:12px 0;}'
            .'.informatique-materiel .imat-modal{display:none;position:fixed;z-index:5000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.45);padding:30px 12px;}'
            .'.informatique-materiel .imat-modal.is-open{display:block;}'
            .'.informatique-materiel .imat-modal-dialog{background:#fff;margin:0 auto;width:100%;max-width:1120px;border-radius:4px;box-shadow:0 8px 28px rgba(0,0,0,.25);}'
            .'.informatique-materiel .imat-modal-head{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ddd;padding:12px 15px;font-size:16px;}'
            .'.informatique-materiel .imat-modal-body{padding:15px;}'
            .'.informatique-materiel .imat-modal-close{border:0;background:transparent;font-size:24px;line-height:1;}'
            .'.informatique-materiel .imat-table-actions{display:flex;flex-wrap:wrap;gap:4px;}'
            .'.informatique-materiel .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
            .'.informatique-materiel table{width:100%;}'
            .'.informatique-materiel th,.informatique-materiel td{overflow-wrap:anywhere;}'
            .'.informatique-materiel .imat-table-filter{margin:8px 0;max-width:360px;}'
            .'.informatique-materiel th[data-imat-sort]{cursor:pointer;}'
            .'.informatique-materiel th[data-imat-sort]:after{content:" \\2195";font-size:11px;color:#777;}'
            .'.informatique-materiel th[data-imat-sort-dir="asc"]:after{content:" \\2191";}'
            .'.informatique-materiel th[data-imat-sort-dir="desc"]:after{content:" \\2193";}'
            .'.informatique-materiel .imat-loading-overlay{display:none;position:fixed;z-index:7000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);align-items:center;justify-content:center;padding:20px;}'
            .'.informatique-materiel .imat-loading-overlay.is-visible{display:flex;}'
            .'.informatique-materiel .imat-loading-box{background:#fff;border-radius:4px;box-shadow:0 8px 28px rgba(0,0,0,.25);padding:18px 22px;min-width:260px;text-align:center;}'
            .'.informatique-materiel .imat-loading-box strong{display:block;font-size:18px;margin-bottom:5px;}'
            .'.informatique-materiel .imat-loading-box span{color:#555;}'
            .'.informatique-materiel .imat-conflict-actions{min-width:220px;}'
            .'.informatique-materiel .imat-conflict-actions-group{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:7px;align-items:center;}'
            .'.informatique-materiel .imat-conflict-actions-title{display:block;width:100%;font-size:11px;text-transform:uppercase;color:#555;font-weight:bold;}'
            .'@media (max-width:767px){.informatique-materiel .imat-nav .btn,.informatique-materiel .imat-actions .btn{width:100%;}.informatique-materiel .imat-modal{padding:10px;}.informatique-materiel .imat-modal-dialog{max-width:none;}.informatique-materiel .imat-modal-body{padding:12px;}.informatique-materiel .imat-table-filter{max-width:none;width:100%;}.informatique-materiel table[data-responsive-table="1"],.informatique-materiel table[data-responsive-table="1"] thead,.informatique-materiel table[data-responsive-table="1"] tbody,.informatique-materiel table[data-responsive-table="1"] tr,.informatique-materiel table[data-responsive-table="1"] th,.informatique-materiel table[data-responsive-table="1"] td{display:block;width:100%;}.informatique-materiel table[data-responsive-table="1"] thead{display:none;}.informatique-materiel table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}.informatique-materiel table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}.informatique-materiel table[data-responsive-table="1"] td:last-child{border-bottom:0;}.informatique-materiel table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}}'
            .'</style>'
            .'<script>(function(){if(window.imatModalReady){return;}window.imatModalReady=true;function showLoading(){var box=document.getElementById("imat-loading-overlay");if(box){box.classList.add("is-visible");}}function text(row,index){var cell=row.children[index];return cell?cell.textContent.trim().toLowerCase():"";}function sortTable(table,index,dir){var tbody=table.tBodies[0];if(!tbody){return;}Array.prototype.slice.call(tbody.rows).sort(function(a,b){var av=text(a,index),bv=text(b,index);var an=parseFloat(av.replace(",", ".")),bn=parseFloat(bv.replace(",", "."));if(!isNaN(an)&&!isNaN(bn)){return dir==="asc"?an-bn:bn-an;}return dir==="asc"?av.localeCompare(bv):bv.localeCompare(av);}).forEach(function(row){tbody.appendChild(row);});}function prepareTables(){document.querySelectorAll(".informatique-materiel table.table").forEach(function(table,n){if(table.getAttribute("data-imat-dynamic")==="1"){return;}table.setAttribute("data-imat-dynamic","1");var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(heads.length){table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});}var wrapper=table.closest(".table-responsive")||table.parentNode;if(wrapper&&!wrapper.querySelector(".imat-table-filter")){var input=document.createElement("input");input.className="form-control imat-table-filter";input.type="search";input.placeholder="Filtrer ce tableau";wrapper.insertBefore(input,table);input.addEventListener("input",function(){var q=input.value.toLowerCase();Array.prototype.forEach.call(table.tBodies[0]?table.tBodies[0].rows:[],function(row){row.style.display=row.textContent.toLowerCase().indexOf(q)===-1?"none":"";});});}Array.prototype.forEach.call(heads,function(th,index){th.setAttribute("data-imat-sort","1");th.addEventListener("click",function(){var dir=th.getAttribute("data-imat-sort-dir")==="asc"?"desc":"asc";Array.prototype.forEach.call(th.parentNode.cells,function(other){other.removeAttribute("data-imat-sort-dir");});th.setAttribute("data-imat-sort-dir",dir);sortTable(table,index,dir);});});});}document.addEventListener("DOMContentLoaded",prepareTables);setTimeout(prepareTables,0);document.addEventListener("click",function(e){var loading=e.target.closest("[data-imat-loading]");if(loading){showLoading();}var open=e.target.closest("[data-imat-modal-open]");if(open){e.preventDefault();var id=open.getAttribute("data-imat-modal-open");var modal=document.getElementById(id);if(modal){modal.classList.add("is-open");}}var close=e.target.closest("[data-imat-modal-close]");if(close){e.preventDefault();var box=close.closest(".imat-modal");if(box){box.classList.remove("is-open");}}if(e.target.classList&&e.target.classList.contains("imat-modal")){e.target.classList.remove("is-open");}});document.addEventListener("submit",function(e){var form=e.target.closest("form[data-imat-loading-form]");if(form){showLoading();}});document.addEventListener("keydown",function(e){if(e.key==="Escape"){document.querySelectorAll(".imat-modal.is-open").forEach(function(modal){modal.classList.remove("is-open");});}});})();</script>';
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
