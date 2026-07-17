<?php

class FormulairesDynamiquesRenderer
{
    public static function accountMenu()
    {
        $login = FormulairesDynamiquesRights::currentLogin();
        if (!FormulairesDynamiquesRights::canAccessAccountPage($login)) {
            return '';
        }

        return '<br><br><a href="compte.php?pc='.self::html(FormulairesDynamiquesConfig::MODULE).'" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 formdyn-account-btn">'.self::html(FormulairesDynamiquesConfig::displayName()).'</a>';
    }

    public static function accountPage()
    {
        $pc = isset($_GET['pc']) ? (string) $_GET['pc'] : '';
        if ($pc !== FormulairesDynamiquesConfig::MODULE) {
            return '';
        }

        $login = FormulairesDynamiquesRights::currentLogin();
        if (!FormulairesDynamiquesRights::canAccessAccountPage($login)) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        if (isset($_GET['admin']) && $_GET['admin'] === '1') {
            if (!FormulairesDynamiquesRights::isAdmin($login)) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            return self::renderEmbeddedAdminPage();
        }

        if (self::managementSection() === 'resultats' && self::requestedDisplayView() === 'export') {
            return self::renderManagementExportDisplay($login);
        }

        $postResult = self::handleManagementPost($login);

        return self::renderManagementHome(
            $login,
            $postResult['messages'],
            $postResult['errors'],
            $postResult['values'],
            $postResult['field_values'],
            $postResult['notification_values']
        );
    }

    public static function appPage($login)
    {
        return self::renderDisplayRoute('grr', (string) $login);
    }

    public static function standalonePage()
    {
        $title = FormulairesDynamiquesConfig::displayName();
        $content = self::renderDisplayRoute('autonomous', '');

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<meta name="robots" content="noindex">'
            .'<title>'.self::html($title).'</title>'
            .'<style>'
                .'body{margin:0;background:#f5f6f8;color:#222;font-family:Arial,sans-serif;}'
                .'.formdyn-standalone{max-width:980px;margin:0 auto;padding:24px 14px;}'
            .'</style>'
            .'</head><body><main class="formdyn-standalone">'.$content.'</main></body></html>';
    }

    private static function renderManagementHome($login, $messages = array(), $errors = array(), $postedValues = array(), $postedFieldValues = array(), $postedNotificationValues = array())
    {
        FormulairesDynamiquesRepository::ensureTables();

        $canManage = FormulairesDynamiquesRights::canManageModule($login);
        $adminUrl = 'compte.php?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE).'&admin=1';
        $messages = array_merge(self::managementMessagesFromRequest(), $messages);
        $section = self::managementSection();
        $body = self::renderManagementSection(
            $section,
            $login,
            $postedValues,
            $postedFieldValues,
            $postedNotificationValues
        );

        $html = '<section id="formulaires-dynamiques">'
            .self::assets()
            .'<h2>Gestion - '.self::html(FormulairesDynamiquesConfig::displayName()).'</h2>'
            .'<div class="formdyn-actions">'
                .($canManage ? '<a class="btn btn-primary" href="'.self::html($adminUrl).'">Configuration du module</a>' : '')
            .'</div>'
            .self::renderManagementNavigation($section, $login)
            .self::renderAlerts($messages, 'success')
            .self::renderAlerts($errors, 'danger')
            .$body
            .'</section>';

        return $html;
    }

    private static function renderManagementSection($section, $login, $postedValues, $postedFieldValues, $postedNotificationValues)
    {
        if ($section === 'edit') {
            return self::renderFormEditor($postedValues, $login);
        }
        if ($section === 'fields') {
            return self::renderFieldBuilder($postedFieldValues, $login);
        }
        if ($section === 'diffusion') {
            return self::renderFormLinksPanel($login).self::renderFormManagersPanel($login);
        }
        if ($section === 'notifications') {
            return self::renderNotificationPanel($postedNotificationValues, $login);
        }
        if ($section === 'layout') {
            return self::renderLayoutPanel($login);
        }
        if ($section === 'preview') {
            return self::renderPreviewPanel($login);
        }
        if ($section === 'resultats') {
            return self::renderResultsPanel($login);
        }
        if ($section === 'stats') {
            return self::renderStatsPanel($login);
        }
        if ($section === 'tools') {
            return self::renderToolsPanel($login);
        }
        if ($section === 'history') {
            return self::renderHistoryPanel($login);
        }
        if ($section === 'help') {
            return self::renderAccessSummary($login, FormulairesDynamiquesRights::canManageModule($login))
                .self::renderDisplayRouteSummary();
        }

        return self::renderCounters().self::renderFormsTable($login).self::renderGlobalImportPanel($login);
    }

    private static function renderManagementNavigation($section, $login)
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        $items = array(
            'forms' => 'Formulaires',
        );
        if (FormulairesDynamiquesRights::canCreateForms($login) || $formId > 0) {
            $items['edit'] = $formId > 0 ? 'Fiche' : 'Nouveau';
        }
        if ($formId > 0 && FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            $items['fields'] = 'Champs';
            $items['diffusion'] = 'Diffusion';
            $items['notifications'] = 'Notifications';
            $items['layout'] = 'Mise en page';
            $items['preview'] = 'Apercu';
            $items['resultats'] = 'Resultats';
            $items['stats'] = 'Statistiques';
            $items['tools'] = 'Outils';
            $items['history'] = 'Historique';
        }
        $items['help'] = 'Aide';

        $html = '<nav class="formdyn-tabs">';
        foreach ($items as $key => $label) {
            $params = array('section' => $key);
            if ($formId > 0 && $key !== 'forms') {
                $params['form_id'] = $formId;
            }
            $class = $section === $key ? ' class="active"' : '';
            $html .= '<a'.$class.' href="'.self::html(self::managementUrl($params)).'">'.self::html($label).'</a>';
        }

        return $html.'</nav>';
    }

    private static function managementSection()
    {
        $section = isset($_GET['section']) ? strtolower(trim((string) $_GET['section'])) : 'forms';
        return in_array($section, array('forms', 'edit', 'fields', 'diffusion', 'notifications', 'layout', 'preview', 'resultats', 'stats', 'tools', 'history', 'help'), true)
            ? $section
            : 'forms';
    }

    private static function handleManagementPost($login)
    {
        $result = array(
            'messages' => array(),
            'errors' => array(),
            'values' => array(),
            'field_values' => array(),
            'notification_values' => array(),
        );

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        $action = isset($_POST['formdyn_action']) ? (string) $_POST['formdyn_action'] : '';
        if ($action === '') {
            return $result;
        }
        if ($action === 'update_response') {
            return $result;
        }

        if (!self::canHandleManagementAction($action, $login)) {
            $result['errors'][] = 'Acces refuse.';
            return $result;
        }

        if ($action === 'save_field') {
            return self::handleFieldSave($login, $result);
        }

        if ($action === 'disable_field') {
            return self::handleFieldDisable($login, $result);
        }

        if ($action === 'save_field_order') {
            return self::handleFieldOrderSave($login, $result);
        }

        if ($action === 'generate_form_token') {
            return self::handleFormTokenCreate($login, $result);
        }

        if ($action === 'generate_results_token') {
            return self::handleResultsTokenCreate($login, $result);
        }

        if ($action === 'save_notification') {
            return self::handleNotificationSave($login, $result);
        }

        if ($action === 'disable_notification') {
            return self::handleNotificationDisable($login, $result);
        }

        if ($action === 'add_form_manager') {
            return self::handleFormManagerAdd($login, $result);
        }

        if ($action === 'remove_form_manager') {
            return self::handleFormManagerRemove($login, $result);
        }

        if ($action === 'disable_token') {
            return self::handleTokenDisable($login, $result);
        }

        if ($action === 'delete_token') {
            return self::handleTokenDelete($login, $result);
        }

        if ($action === 'duplicate_form') {
            return self::handleFormDuplicate($login, $result);
        }

        if ($action === 'delete_form') {
            return self::handleFormDelete($login, $result);
        }

        if ($action === 'export_form_json') {
            return self::handleFormJsonExport($login, $result);
        }

        if ($action === 'import_form_json') {
            return self::handleFormJsonImport($login, $result);
        }

        if ($action === 'save_layout') {
            return self::handleLayoutSave($login, $result);
        }

        if ($action !== 'save_form') {
            return $result;
        }

        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $baseValues = $formId > 0 ? FormulairesDynamiquesRepository::form($formId) : array();
        $values = FormulairesDynamiquesRepository::normalizeFormValues(array_merge($baseValues, $_POST));
        $values['id'] = $formId;
        $result['values'] = $values;
        $result['errors'] = FormulairesDynamiquesRepository::validateFormValues($values);
        if (count($result['errors']) > 0) {
            return $result;
        }

        if ($formId > 0) {
            if (!FormulairesDynamiquesRepository::updateForm($formId, $values, $login)) {
                $result['errors'][] = 'Le formulaire n a pas pu etre modifie.';
                return $result;
            }

            self::redirectToManagement(array('form_id' => $formId, 'section' => 'edit', 'saved' => 1));
        }

        $createdId = FormulairesDynamiquesRepository::createForm($values, $login);
        if ($createdId <= 0) {
            $result['errors'][] = 'Le formulaire n a pas pu etre cree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $createdId, 'section' => 'fields', 'created' => 1));
        return $result;
    }

    private static function handleFieldSave($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $fieldId = isset($_POST['field_id']) ? (int) $_POST['field_id'] : 0;
        $values = FormulairesDynamiquesRepository::normalizeFieldValues(array_merge($_POST, array(
            'formulaire_id' => $formId,
            'id' => $fieldId,
        )));
        $result['field_values'] = $values;
        $result['errors'] = FormulairesDynamiquesRepository::validateFieldValues($values);
        if (count($result['errors']) > 0) {
            return $result;
        }

        if ($fieldId > 0) {
            $field = FormulairesDynamiquesRepository::field($fieldId);
            if (!$field || (int) $field['formulaire_id'] !== $formId) {
                $result['errors'][] = 'Le champ est introuvable pour ce formulaire.';
                return $result;
            }

            if (!FormulairesDynamiquesRepository::updateField($fieldId, $values, $login)) {
                $result['errors'][] = 'Le champ n a pas pu etre modifie.';
                return $result;
            }

            self::redirectToManagement(array('form_id' => $formId, 'field_id' => $fieldId, 'section' => 'fields', 'field_saved' => 1));
        }

        $createdId = FormulairesDynamiquesRepository::createField($formId, $values, $login);
        if ($createdId <= 0) {
            $result['errors'][] = 'Le champ n a pas pu etre cree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'field_id' => $createdId, 'section' => 'fields', 'field_created' => 1));
        return $result;
    }

    private static function handleFieldDisable($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $fieldId = isset($_POST['field_id']) ? (int) $_POST['field_id'] : 0;
        $field = FormulairesDynamiquesRepository::field($fieldId);
        if (!$field || (int) $field['formulaire_id'] !== $formId) {
            $result['errors'][] = 'Le champ est introuvable pour ce formulaire.';
            return $result;
        }
        if ($fieldId <= 0 || !FormulairesDynamiquesRepository::disableField($fieldId, $login)) {
            $result['errors'][] = 'Le champ n a pas pu etre desactive.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'fields', 'field_disabled' => 1));
        return $result;
    }

    private static function handleFieldOrderSave($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $fieldIds = self::postedIdList('field_order');
        if ($formId <= 0 || count($fieldIds) === 0) {
            $result['errors'][] = 'Aucun ordre de champ a enregistrer.';
            return $result;
        }

        if (!FormulairesDynamiquesRepository::updateFieldOrder($formId, $fieldIds, $login)) {
            $result['errors'][] = 'L ordre des champs n a pas pu etre enregistre.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'fields', 'field_order_saved' => 1));
        return $result;
    }

    private static function handleFormTokenCreate($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRepository::form($formId)) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $token = FormulairesDynamiquesRepository::createToken($formId, 'formulaire', $login, self::postedTokenOptions());
        if ($token === '') {
            $result['errors'][] = 'Le lien formulaire n a pas pu etre genere.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'form_token' => $token, 'token_created' => 1));
        return $result;
    }

    private static function handleResultsTokenCreate($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRepository::form($formId)) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $token = FormulairesDynamiquesRepository::createToken($formId, 'resultats', $login, self::postedTokenOptions());
        if ($token === '') {
            $result['errors'][] = 'Le lien resultats n a pas pu etre genere.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'results_token' => $token, 'results_token_created' => 1));
        return $result;
    }

    private static function handleNotificationSave($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $values = FormulairesDynamiquesRepository::normalizeNotificationValues(array_merge($_POST, array(
            'formulaire_id' => $formId,
        )));
        $result['notification_values'] = $values;
        $result['errors'] = FormulairesDynamiquesRepository::validateNotificationValues($values);
        if (count($result['errors']) > 0) {
            return $result;
        }

        $createdId = FormulairesDynamiquesRepository::createNotificationRecipient($formId, $values, $login);
        if ($createdId <= 0) {
            $result['errors'][] = 'Le destinataire n a pas pu etre ajoute.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'notifications', 'notification_created' => 1));
        return $result;
    }

    private static function handleNotificationDisable($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $notificationId = isset($_POST['notification_id']) ? (int) $_POST['notification_id'] : 0;
        $recipient = FormulairesDynamiquesRepository::notificationRecipient($notificationId);
        if (!$recipient || (int) $recipient['formulaire_id'] !== $formId) {
            $result['errors'][] = 'Le destinataire est introuvable pour ce formulaire.';
            return $result;
        }
        if ($notificationId <= 0 || !FormulairesDynamiquesRepository::disableNotificationRecipient($notificationId, $login)) {
            $result['errors'][] = 'Le destinataire n a pas pu etre desactive.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'notifications', 'notification_disabled' => 1));
        return $result;
    }

    private static function handleFormManagerAdd($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $managerLogin = isset($_POST['manager_login']) ? trim((string) $_POST['manager_login']) : '';
        if ($formId <= 0 || $managerLogin === '') {
            $result['errors'][] = 'Le login du gestionnaire est obligatoire.';
            return $result;
        }
        if (!FormulairesDynamiquesRepository::userByLogin($managerLogin)) {
            $result['errors'][] = 'Le login indique ne correspond pas a un utilisateur GRR actif.';
            return $result;
        }

        if (!FormulairesDynamiquesRepository::addFormManager($formId, $managerLogin, $login)) {
            $result['errors'][] = 'Le gestionnaire n a pas pu etre ajoute.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'manager_added' => 1));
        return $result;
    }

    private static function handleFormManagerRemove($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $managerLogin = isset($_POST['manager_login']) ? trim((string) $_POST['manager_login']) : '';
        if ($formId <= 0 || $managerLogin === '') {
            $result['errors'][] = 'Le gestionnaire est introuvable.';
            return $result;
        }

        if (!FormulairesDynamiquesRepository::removeFormManager($formId, $managerLogin, $login)) {
            $result['errors'][] = 'Le gestionnaire n a pas pu etre retire.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'manager_removed' => 1));
        return $result;
    }

    private static function handleTokenDisable($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $tokenId = isset($_POST['token_id']) ? (int) $_POST['token_id'] : 0;
        if (!self::tokenBelongsToForm($tokenId, $formId)) {
            $result['errors'][] = 'Le jeton est introuvable pour ce formulaire.';
            return $result;
        }
        if ($tokenId <= 0 || !FormulairesDynamiquesRepository::disableToken($tokenId, $login)) {
            $result['errors'][] = 'Le jeton n a pas pu etre desactive.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'token_disabled' => 1));
        return $result;
    }

    private static function handleTokenDelete($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $tokenId = isset($_POST['token_id']) ? (int) $_POST['token_id'] : 0;
        if (!self::tokenBelongsToForm($tokenId, $formId)) {
            $result['errors'][] = 'Le jeton est introuvable pour ce formulaire.';
            return $result;
        }
        if (!FormulairesDynamiquesRepository::deleteToken($tokenId, $login)) {
            $result['errors'][] = 'Le jeton n a pas pu etre supprime.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'diffusion', 'token_deleted' => 1));
        return $result;
    }

    private static function handleFormDuplicate($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        if ($formId <= 0) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $newFormId = FormulairesDynamiquesRepository::duplicateForm($formId, $login);
        if ($newFormId <= 0) {
            $result['errors'][] = 'Le formulaire n a pas pu etre duplique.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $newFormId, 'section' => 'edit', 'duplicated' => 1));
        return $result;
    }

    private static function handleFormDelete($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $form = FormulairesDynamiquesRepository::form($formId);
        if (!self::canDeleteForm($login, $form)) {
            $result['errors'][] = 'Suppression refusee pour ce formulaire.';
            return $result;
        }

        if (!FormulairesDynamiquesRepository::deleteForm($formId)) {
            $result['errors'][] = 'Le formulaire n a pas pu etre supprime.';
            return $result;
        }

        self::redirectToManagement(array('section' => 'forms', 'form_deleted' => 1));
        return $result;
    }

    private static function handleFormJsonExport($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $payload = FormulairesDynamiquesRepository::exportFormDefinition($formId);
        if (!$payload) {
            $result['errors'][] = 'Le formulaire n a pas pu etre exporte.';
            return $result;
        }

        $form = isset($payload['form']) && is_array($payload['form']) ? $payload['form'] : array();
        $title = isset($form['titre']) ? (string) $form['titre'] : 'formulaire';
        $filename = self::safeFilename($title).'-formulaire.json';
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function handleFormJsonImport($login, $result)
    {
        if (!isset($_FILES['formdyn_json']) || !is_array($_FILES['formdyn_json'])) {
            $result['errors'][] = 'Selectionnez un fichier JSON.';
            return $result;
        }

        $file = $_FILES['formdyn_json'];
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK || !isset($file['tmp_name'])) {
            $result['errors'][] = 'Le fichier JSON n a pas pu etre recu.';
            return $result;
        }

        $import = FormulairesDynamiquesRepository::importFormDefinition($file['tmp_name'], $login);
        $errors = isset($import['errors']) && is_array($import['errors']) ? $import['errors'] : array();
        if (count($errors) > 0) {
            $result['errors'] = array_merge($result['errors'], $errors);
        }

        $formId = isset($import['form_id']) ? (int) $import['form_id'] : 0;
        if ($formId <= 0) {
            $result['errors'][] = 'Le formulaire importe n a pas pu etre cree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'edit', 'json_imported' => 1));
        return $result;
    }

    private static function handleLayoutSave($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $values = array_merge($form, array(
            'result_list_template' => isset($_POST['result_list_template']) ? $_POST['result_list_template'] : '',
            'result_detail_template' => isset($_POST['result_detail_template']) ? $_POST['result_detail_template'] : '',
            'result_columns' => isset($_POST['result_columns']) ? $_POST['result_columns'] : array(),
            'notification_subject_template' => isset($_POST['notification_subject_template']) ? $_POST['notification_subject_template'] : '',
            'notification_body_template' => isset($_POST['notification_body_template']) ? $_POST['notification_body_template'] : '',
        ));
        if (!FormulairesDynamiquesRepository::updateForm($formId, $values, $login)) {
            $result['errors'][] = 'La mise en page n a pas pu etre enregistree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'section' => 'layout', 'layout_saved' => 1));
        return $result;
    }

    private static function postedTokenOptions()
    {
        return array(
            'expires_at' => isset($_POST['expires_at']) ? (string) $_POST['expires_at'] : '',
            'max_responses' => isset($_POST['max_responses']) ? (int) $_POST['max_responses'] : 0,
        );
    }

    private static function canHandleManagementAction($action, $login)
    {
        $action = (string) $action;
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;

        if ($action === 'save_form') {
            return $formId > 0
                ? FormulairesDynamiquesRights::canManageForm($login, $formId)
                : FormulairesDynamiquesRights::canCreateForms($login);
        }
        if ($action === 'import_form_json') {
            return FormulairesDynamiquesRights::canCreateForms($login);
        }
        if (in_array($action, array('duplicate_form', 'export_form_json'), true)) {
            return $formId > 0 && FormulairesDynamiquesRights::canManageForm($login, $formId);
        }
        if ($action === 'delete_form') {
            return self::canDeleteForm($login, FormulairesDynamiquesRepository::form($formId));
        }

        if (in_array($action, array(
            'save_field',
            'disable_field',
            'save_field_order',
            'generate_form_token',
            'generate_results_token',
            'save_notification',
            'disable_notification',
            'add_form_manager',
            'remove_form_manager',
            'disable_token',
            'delete_token',
            'save_layout',
        ), true)) {
            return $formId > 0 && FormulairesDynamiquesRights::canManageForm($login, $formId);
        }

        return false;
    }

    private static function canDeleteForm($login, $form)
    {
        $login = trim((string) $login);
        if ($login === '' || !is_array($form) || empty($form)) {
            return false;
        }

        $formId = (int) (isset($form['id']) ? $form['id'] : 0);
        if ($formId <= 0) {
            return false;
        }

        if (FormulairesDynamiquesRights::isAdmin($login)) {
            return true;
        }

        if (FormulairesDynamiquesRepository::userCanManageForm($login, $formId)) {
            return true;
        }

        $creator = isset($form['created_by']) ? trim((string) $form['created_by']) : '';
        return $creator !== ''
            && $creator === $login
            && FormulairesDynamiquesConfig::isManager($login);
    }

    private static function tokenBelongsToForm($tokenId, $formId)
    {
        foreach (FormulairesDynamiquesRepository::tokens($formId, true) as $token) {
            if ((int) (isset($token['id']) ? $token['id'] : 0) === (int) $tokenId) {
                return true;
            }
        }

        return false;
    }

    private static function postedIdList($key)
    {
        $raw = isset($_POST[$key]) ? (string) $_POST[$key] : '';
        $parts = preg_split('/[,;]+/', $raw);
        $ids = array();
        foreach ($parts as $part) {
            $id = (int) trim((string) $part);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private static function managementMessagesFromRequest()
    {
        $messages = array();
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $messages[] = 'Formulaire cree.';
        }
        if (isset($_GET['saved']) && $_GET['saved'] === '1') {
            $messages[] = 'Formulaire enregistre.';
        }
        if (isset($_GET['field_created']) && $_GET['field_created'] === '1') {
            $messages[] = 'Champ cree.';
        }
        if (isset($_GET['field_saved']) && $_GET['field_saved'] === '1') {
            $messages[] = 'Champ enregistre.';
        }
        if (isset($_GET['field_disabled']) && $_GET['field_disabled'] === '1') {
            $messages[] = 'Champ desactive.';
        }
        if (isset($_GET['field_order_saved']) && $_GET['field_order_saved'] === '1') {
            $messages[] = 'Ordre des champs enregistre.';
        }
        if (isset($_GET['token_created']) && $_GET['token_created'] === '1') {
            $messages[] = 'Lien formulaire genere.';
        }
        if (isset($_GET['results_token_created']) && $_GET['results_token_created'] === '1') {
            $messages[] = 'Lien resultats genere.';
        }
        if (isset($_GET['notification_created']) && $_GET['notification_created'] === '1') {
            $messages[] = 'Destinataire de notification ajoute.';
        }
        if (isset($_GET['notification_disabled']) && $_GET['notification_disabled'] === '1') {
            $messages[] = 'Destinataire de notification desactive.';
        }
        if (isset($_GET['manager_added']) && $_GET['manager_added'] === '1') {
            $messages[] = 'Gestionnaire ajoute au formulaire.';
        }
        if (isset($_GET['manager_removed']) && $_GET['manager_removed'] === '1') {
            $messages[] = 'Gestionnaire retire du formulaire.';
        }
        if (isset($_GET['token_disabled']) && $_GET['token_disabled'] === '1') {
            $messages[] = 'Jeton desactive.';
        }
        if (isset($_GET['token_deleted']) && $_GET['token_deleted'] === '1') {
            $messages[] = 'Jeton supprime.';
        }
        if (isset($_GET['layout_saved']) && $_GET['layout_saved'] === '1') {
            $messages[] = 'Mise en page enregistree.';
        }
        if (isset($_GET['duplicated']) && $_GET['duplicated'] === '1') {
            $messages[] = 'Formulaire duplique en brouillon.';
        }
        if (isset($_GET['form_deleted']) && $_GET['form_deleted'] === '1') {
            $messages[] = 'Formulaire supprime avec ses reponses.';
        }
        if (isset($_GET['json_imported']) && $_GET['json_imported'] === '1') {
            $messages[] = 'Formulaire importe depuis le JSON.';
        }

        return $messages;
    }

    private static function renderAlerts($messages, $type)
    {
        if (!is_array($messages) || count($messages) === 0) {
            return '';
        }

        $class = $type === 'danger' ? 'alert-danger' : 'alert-success';
        $html = '';
        foreach ($messages as $message) {
            $html .= '<div class="alert '.$class.'">'.self::html($message).'</div>';
        }

        return $html;
    }

    private static function renderFormEditor($postedValues = array(), $login = '')
    {
        $values = self::currentFormEditorValues($postedValues);
        $editing = isset($values['id']) && (int) $values['id'] > 0;
        if ($editing && !FormulairesDynamiquesRights::canManageForm($login, (int) $values['id'])) {
            return '';
        }
        if (!$editing && !FormulairesDynamiquesRights::canCreateForms($login)) {
            return '';
        }

        $title = $editing ? 'Modifier le formulaire' : 'Nouveau formulaire';

        $html = '<section class="formdyn-panel">'
            .'<h3>'.self::html($title).'</h3>'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => (int) $values['id'], 'section' => 'edit'))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_form">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $values['id']).'">'
            .'<div class="formdyn-form-grid">'
                .'<label>Titre<br><input class="form-control" type="text" name="titre" maxlength="190" required value="'.self::html($values['titre']).'"></label>'
                .'<label>Statut<br><select class="form-control" name="statut">'.self::statusOptionsHtml($values['statut']).'</select></label>'
            .'</div>'
            .'<label>Description<br><textarea class="form-control" name="description" rows="4">'.self::html($values['description']).'</textarea></label>'
            .'<p class="formdyn-actions">'
                .'<button class="btn btn-primary" type="submit">'.($editing ? 'Enregistrer' : 'Creer le formulaire').'</button>'
                .($editing ? ' <a class="btn btn-default" href="'.self::html(self::managementUrl(array('section' => 'edit'))).'">Nouveau formulaire</a>' : '')
            .'</p>'
            .'</form>'
            .'</section>';

        return $html;
    }

    private static function renderFormLinksPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0) {
            return '';
        }
        if (!FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return '';
        }

        $formTokenCount = FormulairesDynamiquesRepository::activeTokenCount($formId, 'formulaire');
        $resultsTokenCount = FormulairesDynamiquesRepository::activeTokenCount($formId, 'resultats');
        $newFormToken = isset($_GET['form_token']) ? trim((string) $_GET['form_token']) : '';
        $newResultsToken = isset($_GET['results_token']) ? trim((string) $_GET['results_token']) : '';

        $html = '<section class="formdyn-panel">'
            .'<h3>Diffusion du formulaire</h3>'
            .'<table class="table table-striped"><tbody>'
                .'<tr><th>Statut</th><td>'.self::statusBadge(isset($form['statut']) ? $form['statut'] : '').'</td></tr>'
                .'<tr><th>Liens formulaire actifs</th><td>'.self::html($formTokenCount).'</td></tr>'
                .'<tr><th>Liens resultats actifs</th><td>'.self::html($resultsTokenCount).'</td></tr>'
            .'</tbody></table>';

        if (!isset($form['statut']) || (string) $form['statut'] !== 'publie') {
            $html .= '<div class="alert alert-warning">Le formulaire doit etre au statut Publie pour etre affichable par lien.</div>';
        }

        if ($newFormToken !== '') {
            $integratedUrl = self::formDisplayUrl($newFormToken, true);
            $standaloneUrl = self::formDisplayUrl($newFormToken, false);
            $html .= '<div class="alert alert-info">Nouveau lien formulaire genere. Il restera affichable dans le tableau des jetons.</div>'
                .'<label>Affichage integre GRR<br><input class="form-control" readonly value="'.self::html($integratedUrl).'"></label>'
                .'<label>Affichage autonome<br><input class="form-control" readonly value="'.self::html($standaloneUrl).'"></label>';
        }

        if ($newResultsToken !== '') {
            $integratedUrl = self::resultsDisplayUrl($newResultsToken, true);
            $standaloneUrl = self::resultsDisplayUrl($newResultsToken, false);
            $html .= '<div class="alert alert-info">Nouveau lien resultats genere. Il restera affichable dans le tableau des jetons.</div>'
                .'<label>Resultats integres GRR<br><input class="form-control" readonly value="'.self::html($integratedUrl).'"></label>'
                .'<label>Resultats autonomes<br><input class="form-control" readonly value="'.self::html($standaloneUrl).'"></label>';
        }

        $html .= '<div class="formdyn-token-create-grid">'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="generate_form_token">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<h4>Lien formulaire</h4>'
            .self::renderTokenOptionInputs(true)
            .'<button class="btn btn-primary" type="submit">Generer un lien formulaire</button>'
            .'</form>'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="generate_results_token">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<h4>Lien resultats</h4>'
            .self::renderTokenOptionInputs(false)
            .'<button class="btn btn-default" type="submit">Generer un lien resultats</button>'
            .'</form>'
            .'</div>'
            .self::renderTokensTable($formId)
            .'</section>';

        return $html;
    }

    private static function renderTokenOptionInputs($withLimit)
    {
        return '<div class="formdyn-token-options">'
            .'<label>Expiration<br><input class="form-control" type="datetime-local" name="expires_at"></label>'
            .($withLimit ? '<label>Limite de reponses<br><input class="form-control" type="number" min="0" name="max_responses" value="0"></label>' : '<input type="hidden" name="max_responses" value="0">')
            .'</div>';
    }

    private static function renderTokensTable($formId)
    {
        $tokens = FormulairesDynamiquesRepository::tokens($formId, true);
        if (count($tokens) === 0) {
            return '<div class="alert alert-info">Aucun jeton genere pour ce formulaire.</div>';
        }

        $html = '<div class="formdyn-subpanel"><h4>Jetons</h4>'
            .'<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr><th>Type</th><th>Cree le</th><th>Etat</th><th>Contraintes</th><th>Liens</th><th>Actions</th></tr></thead><tbody>';
        foreach ($tokens as $token) {
            $active = isset($token['actif']) && (int) $token['actif'] === 1;
            $type = isset($token['type_token']) ? (string) $token['type_token'] : '';
            $html .= '<tr'.($active ? '' : ' class="text-muted"').'>'
                .'<td>'.self::html($type === 'resultats' ? 'Resultats' : 'Formulaire').'</td>'
                .'<td>'.self::html(self::formatDate(isset($token['created_at']) ? $token['created_at'] : 0)).'</td>'
                .'<td>'.self::tokenStatusLabel($token).'</td>'
                .'<td>'.self::tokenConstraintLabel($token).'</td>'
                .'<td>'.self::renderTokenLinks($token).'</td>'
                .'<td>'.($active ? self::disableTokenForm($formId, (int) $token['id']) : '').self::deleteTokenForm($formId, (int) $token['id']).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div>';
    }

    private static function tokenStatusLabel($token)
    {
        $active = isset($token['actif']) && (int) $token['actif'] === 1;
        if (!$active) {
            return '<span class="label label-default">Inactif</span>';
        }

        $expiresAt = (int) (isset($token['expires_at']) ? $token['expires_at'] : 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            return '<span class="label label-warning">Expire</span>';
        }

        $max = (int) (isset($token['max_responses']) ? $token['max_responses'] : 0);
        $used = (int) (isset($token['response_count']) ? $token['response_count'] : 0);
        if ($max > 0 && $used >= $max) {
            return '<span class="label label-warning">Limite atteinte</span>';
        }

        return '<span class="label label-success">Actif</span>';
    }

    private static function tokenConstraintLabel($token)
    {
        $parts = array();
        $expiresAt = (int) (isset($token['expires_at']) ? $token['expires_at'] : 0);
        if ($expiresAt > 0) {
            $parts[] = 'Expire le '.self::formatDate($expiresAt);
        }
        $max = (int) (isset($token['max_responses']) ? $token['max_responses'] : 0);
        $used = (int) (isset($token['response_count']) ? $token['response_count'] : 0);
        if ($max > 0) {
            $parts[] = $used.' / '.$max.' reponse(s)';
        }

        return count($parts) > 0 ? self::html(implode(' - ', $parts)) : '<span class="text-muted">Aucune</span>';
    }

    private static function disableTokenForm($formId, $tokenId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_token">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="token_id" value="'.self::html((int) $tokenId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce jeton ?\');">Desactiver</button>'
            .'</form>';
    }

    private static function deleteTokenForm($formId, $tokenId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="delete_token">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="token_id" value="'.self::html((int) $tokenId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Supprimer definitivement ce jeton ?\');">Supprimer</button>'
            .'</form>';
    }

    private static function renderTokenLinks($token)
    {
        $tokenValue = isset($token['token_public']) ? trim((string) $token['token_public']) : '';
        if ($tokenValue === '') {
            return '<span class="text-muted">Ancien jeton : lien non reaffichable. Regenerer un lien si necessaire.</span>';
        }

        $type = isset($token['type_token']) ? (string) $token['type_token'] : 'formulaire';
        $integrated = $type === 'resultats'
            ? self::resultsDisplayUrl($tokenValue, true)
            : self::formDisplayUrl($tokenValue, true);
        $standalone = $type === 'resultats'
            ? self::resultsDisplayUrl($tokenValue, false)
            : self::formDisplayUrl($tokenValue, false);

        return self::copyableValue('Jeton', $tokenValue)
            .self::copyableValue('Integre GRR', $integrated)
            .self::copyableValue('Autonome', $standalone)
            .self::qrCodeBlock($standalone);
    }

    private static function copyableValue($label, $value)
    {
        return '<label>'.self::html($label).'<br>'
            .'<span class="formdyn-copy-line">'
                .'<input class="form-control" readonly value="'.self::html($value).'">'
                .'<button class="btn btn-default btn-sm formdyn-copy-btn" type="button">Copier</button>'
            .'</span>'
            .'</label>';
    }

    private static function qrCodeBlock($value)
    {
        if (trim((string) $value) === '') {
            return '';
        }

        $src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data='.rawurlencode((string) $value);
        return '<details class="formdyn-qr"><summary>QR code autonome</summary>'
            .'<img src="'.self::html($src).'" alt="QR code">'
            .'<div class="formdyn-help">QR code genere par un service externe.</div>'
            .'</details>';
    }

    private static function renderFormManagersPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return '';
        }

        $html = '<section class="formdyn-panel">'
            .'<h3>Gestionnaires du formulaire</h3>'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="add_form_manager">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<div class="formdyn-notification-grid">'
                .'<label>Utilisateur GRR<br><select class="form-control" name="manager_login" required>'.self::activeUserOptionsHtml('').'</select></label>'
                .'<div class="formdyn-help">La liste contient les utilisateurs GRR actifs.</div>'
            .'</div>'
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Ajouter le gestionnaire</button></p>'
            .'</form>'
            .self::renderFormManagersTable($formId)
            .'</section>';

        return $html;
    }

    private static function renderFormManagersTable($formId)
    {
        $managers = FormulairesDynamiquesRepository::formManagers($formId);
        if (count($managers) === 0) {
            return '<div class="alert alert-info">Aucun gestionnaire specifique pour ce formulaire.</div>';
        }

        $html = '<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr><th>Login</th><th>Utilisateur</th><th>Email</th><th>Actions</th></tr></thead><tbody>';
        foreach ($managers as $manager) {
            $managerLogin = isset($manager['login']) ? (string) $manager['login'] : '';
            $name = trim((isset($manager['prenom']) ? (string) $manager['prenom'] : '').' '.(isset($manager['nom']) ? (string) $manager['nom'] : ''));
            $html .= '<tr>'
                .'<td>'.self::html($managerLogin).'</td>'
                .'<td>'.self::html($name !== '' ? $name : '-').'</td>'
                .'<td>'.self::html(isset($manager['email']) ? $manager['email'] : '').'</td>'
                .'<td>'.self::removeFormManagerForm($formId, $managerLogin).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function removeFormManagerForm($formId, $managerLogin)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">'
            .'<input type="hidden" name="formdyn_action" value="remove_form_manager">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="manager_login" value="'.self::html($managerLogin).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Retirer ce gestionnaire ?\');">Retirer</button>'
            .'</form>';
    }

    private static function renderNotificationPanel($postedNotificationValues = array(), $login = '')
    {
        $formId = 0;
        if (is_array($postedNotificationValues) && isset($postedNotificationValues['formulaire_id'])) {
            $formId = (int) $postedNotificationValues['formulaire_id'];
        }
        if ($formId <= 0 && isset($_GET['form_id'])) {
            $formId = (int) $_GET['form_id'];
        }

        if ($formId <= 0) {
            return '';
        }
        if (!FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return '';
        }

        $values = self::currentNotificationValues($postedNotificationValues, $formId);
        $mailReady = FormulairesDynamiquesNotification::mailEnabled();

        $html = '<section class="formdyn-panel">'
            .'<h3>Notifications</h3>'
            .'<div class="alert '.($mailReady ? 'alert-success' : 'alert-warning').'">'
                .($mailReady ? 'Les mails automatiques GRR sont actifs.' : 'Les mails automatiques GRR ne sont pas actifs ou les notifications du module sont desactivees.')
            .'</div>'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'notifications'))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_notification">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<input type="hidden" name="actif" value="1">'
            .'<div class="formdyn-notification-grid">'
                .'<label>Nom<br><input class="form-control" type="text" name="nom" maxlength="190" value="'.self::html($values['nom']).'"></label>'
                .'<label>Email<br><input class="form-control" type="email" name="email" maxlength="190" required value="'.self::html($values['email']).'"></label>'
            .'</div>'
            .self::renderNotificationConditionControls($formId, $values)
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Ajouter le destinataire</button></p>'
            .'</form>'
            .self::renderNotificationRecipientsTable($formId)
            .'</section>';

        return $html;
    }

    private static function currentNotificationValues($postedNotificationValues, $formId)
    {
        $defaults = array(
            'id' => 0,
            'formulaire_id' => (int) $formId,
            'email' => '',
            'nom' => '',
            'condition_champ_id' => 0,
            'condition_operateur' => '',
            'condition_valeur' => '',
            'actif' => 1,
        );

        if (is_array($postedNotificationValues) && count($postedNotificationValues) > 0) {
            return array_merge($defaults, FormulairesDynamiquesRepository::normalizeNotificationValues($postedNotificationValues));
        }

        return $defaults;
    }

    private static function renderNotificationConditionControls($formId, $values)
    {
        $fields = FormulairesDynamiquesRepository::conditionalFields($formId);
        if (count($fields) === 0) {
            return '<div class="formdyn-help">Ajoutez une liste, un choix unique ou des cases a cocher pour activer les notifications conditionnelles.</div>';
        }

        $html = '<div class="formdyn-notification-condition">'
            .'<h4>Condition d envoi</h4>'
            .'<div class="formdyn-notification-grid">'
            .'<label>Champ<br><select class="form-control" name="condition_champ_id">'
                .'<option value="0">Toujours envoyer</option>';
        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $html .= '<option value="'.self::html($fieldId).'"'.((int) $values['condition_champ_id'] === $fieldId ? ' selected' : '').'>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</option>';
        }
        $html .= '</select></label>'
            .'<label>Operateur<br><select class="form-control" name="condition_operateur">';
        foreach (FormulairesDynamiquesRepository::notificationConditionOperators() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.($values['condition_operateur'] === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }
        $html .= '</select></label>'
            .'</div>'
            .'<label>Valeur attendue<br><input class="form-control" type="text" name="condition_valeur" maxlength="190" value="'.self::html($values['condition_valeur']).'"></label>'
            .'<div class="formdyn-help">Pour les cases a cocher, la condition "contient" verifie si le choix est coche.</div>'
            .'</div>';

        return $html;
    }

    private static function renderFieldVisibilityControls($formId, $currentFieldId, $values)
    {
        $fields = FormulairesDynamiquesRepository::conditionalFields($formId);
        if (count($fields) === 0) {
            return '<div class="formdyn-help">Ajoutez une liste, un choix unique ou des cases a cocher pour activer l affichage conditionnel.</div>';
        }

        $selectedField = (int) (isset($values['visibility_champ_id']) ? $values['visibility_champ_id'] : 0);
        $selectedOperator = isset($values['visibility_operateur']) ? (string) $values['visibility_operateur'] : '';
        $selectedValue = isset($values['visibility_valeur']) ? (string) $values['visibility_valeur'] : '';

        $html = '<div class="formdyn-notification-condition">'
            .'<h4>Affichage conditionnel</h4>'
            .'<div class="formdyn-notification-grid">'
            .'<label>Afficher si le champ<br><select class="form-control" name="visibility_champ_id">'
                .'<option value="0">Toujours afficher</option>';
        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0 || $fieldId === (int) $currentFieldId) {
                continue;
            }
            $html .= '<option value="'.self::html($fieldId).'"'.($selectedField === $fieldId ? ' selected' : '').'>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</option>';
        }
        $html .= '</select></label>'
            .'<label>Operateur<br><select class="form-control" name="visibility_operateur">';
        foreach (FormulairesDynamiquesRepository::notificationConditionOperators() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.($selectedOperator === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }
        $html .= '</select></label>'
            .'</div>'
            .'<label>Valeur attendue<br><input class="form-control" type="text" name="visibility_valeur" maxlength="190" value="'.self::html($selectedValue).'"></label>'
            .'<div class="formdyn-help">Pour les cases a cocher, la condition "contient" verifie si le choix est coche.</div>'
            .'</div>';

        return $html;
    }

    private static function renderNotificationRecipientsTable($formId)
    {
        $recipients = FormulairesDynamiquesRepository::notificationRecipients($formId, false);
        if (count($recipients) === 0) {
            return '<div class="alert alert-info">Aucun destinataire configure pour ce formulaire.</div>';
        }

        $html = '<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr>'
                .'<th>Nom</th>'
                .'<th>Email</th>'
                .'<th>Condition</th>'
                .'<th>Etat</th>'
                .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($recipients as $recipient) {
            $recipientId = (int) (isset($recipient['id']) ? $recipient['id'] : 0);
            $active = isset($recipient['actif']) && (int) $recipient['actif'] === 1;
            $html .= '<tr'.($active ? '' : ' class="text-muted"').'>'
                .'<td>'.self::html(isset($recipient['nom']) ? $recipient['nom'] : '').'</td>'
                .'<td>'.self::html(isset($recipient['email']) ? $recipient['email'] : '').'</td>'
                .'<td>'.self::html(self::notificationConditionLabel($recipient)).'</td>'
                .'<td>'.($active ? '<span class="label label-success">Actif</span>' : '<span class="label label-default">Inactif</span>').'</td>'
                .'<td>'.($active ? self::disableNotificationForm((int) $formId, $recipientId) : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function disableNotificationForm($formId, $notificationId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'notifications'))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_notification">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="notification_id" value="'.self::html((int) $notificationId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce destinataire ?\');">Desactiver</button>'
            .'</form>';
    }

    private static function notificationConditionLabel($recipient)
    {
        $fieldId = (int) (isset($recipient['condition_champ_id']) ? $recipient['condition_champ_id'] : 0);
        if ($fieldId <= 0) {
            return 'Toujours';
        }

        $field = FormulairesDynamiquesRepository::field($fieldId);
        $operators = FormulairesDynamiquesRepository::notificationConditionOperators();
        $operator = isset($recipient['condition_operateur']) ? (string) $recipient['condition_operateur'] : '';
        $label = isset($operators[$operator]) ? $operators[$operator] : $operator;
        $value = isset($recipient['condition_valeur']) ? (string) $recipient['condition_valeur'] : '';

        return (isset($field['libelle']) ? $field['libelle'] : 'Champ '.$fieldId).' '.$label.($value !== '' ? ' '.$value : '');
    }

    private static function renderLayoutPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return '';
        }
        $fields = self::resultFields(FormulairesDynamiquesRepository::fields($formId, true));

        return '<section class="formdyn-panel">'
            .'<h3>Mise en page des resultats</h3>'
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'layout'))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_layout">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<label>Modele liste globale<br><textarea class="form-control" name="result_list_template" rows="7">'.self::html(isset($form['result_list_template']) ? $form['result_list_template'] : '').'</textarea></label>'
            .'<label>Modele detail individuel<br><textarea class="form-control" name="result_detail_template" rows="7">'.self::html(isset($form['result_detail_template']) ? $form['result_detail_template'] : '').'</textarea></label>'
            .'<div class="formdyn-help">Placeholders : {reference}, {date}, {source}, {declarant}, {champ:Libelle exact}, {field:ID}. Si le modele est vide, l affichage tableau standard est utilise.</div>'
            .'<h4>Colonnes de la liste des resultats</h4>'
            .self::renderResultColumnChoices($form, $fields)
            .'<h4>Notification e-mail</h4>'
            .'<label>Modele objet<br><input class="form-control" type="text" name="notification_subject_template" maxlength="190" value="'.self::html(isset($form['notification_subject_template']) ? $form['notification_subject_template'] : '').'"></label>'
            .'<label>Modele message<br><textarea class="form-control" name="notification_body_template" rows="8">'.self::html(isset($form['notification_body_template']) ? $form['notification_body_template'] : '').'</textarea></label>'
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Enregistrer la mise en page</button></p>'
            .'</form>'
            .'</section>';
    }

    private static function renderResultColumnChoices($form, $fields)
    {
        $selected = array();
        foreach (preg_split('/[,;\s]+/', (string) (isset($form['result_columns']) ? $form['result_columns'] : '')) as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }
        $useAll = count($selected) === 0;
        if (count($fields) === 0) {
            return '<div class="alert alert-info">Aucune colonne disponible.</div>';
        }

        $html = '<div class="formdyn-checks formdyn-column-choices">';
        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $checked = $useAll || isset($selected[$fieldId]) ? ' checked' : '';
            $html .= '<label><input type="checkbox" name="result_columns[]" value="'.self::html($fieldId).'"'.$checked.'> '.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</label>';
        }

        return $html.'</div><div class="formdyn-help">Si toutes les cases sont decochees, toutes les colonnes sont affichees.</div>';
    }

    private static function renderFieldBuilder($postedFieldValues = array(), $login = '')
    {
        $form = self::selectedFormForBuilder($postedFieldValues);
        if (!$form) {
            return '<section class="formdyn-panel"><h3>Champs du formulaire</h3>'
                .'<div class="alert alert-info">Selectionnez ou creez un formulaire pour ajouter des champs.</div>'
                .'</section>';
        }
        if (!FormulairesDynamiquesRights::canManageForm($login, (int) $form['id'])) {
            return '';
        }

        return '<section class="formdyn-panel">'
            .'<h3>Champs - '.self::html(isset($form['titre']) ? $form['titre'] : '').'</h3>'
            .self::renderFieldEditor($form, $postedFieldValues)
            .self::renderFieldsTable($form)
            .'</section>';
    }

    private static function selectedFormForBuilder($postedFieldValues)
    {
        $formId = 0;
        if (is_array($postedFieldValues) && isset($postedFieldValues['formulaire_id'])) {
            $formId = (int) $postedFieldValues['formulaire_id'];
        }
        if ($formId <= 0 && isset($_GET['form_id'])) {
            $formId = (int) $_GET['form_id'];
        }

        return $formId > 0 ? FormulairesDynamiquesRepository::form($formId) : array();
    }

    private static function renderFieldEditor($form, $postedFieldValues)
    {
        $values = self::currentFieldEditorValues($form, $postedFieldValues);
        $editing = isset($values['id']) && (int) $values['id'] > 0;
        $title = $editing ? 'Modifier un champ' : 'Ajouter un champ';

        $html = '<div class="formdyn-subpanel">'
            .'<h4>'.self::html($title).'</h4>'
            .'<form class="formdyn-field-editor" method="post" action="'.self::html(self::managementUrl(array('form_id' => (int) $form['id'], 'section' => 'fields'))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_field">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $form['id']).'">'
            .'<input type="hidden" name="field_id" value="'.self::html((int) $values['id']).'">'
            .'<input type="hidden" name="obligatoire" value="0">'
            .'<input type="hidden" name="actif" value="0">'
            .'<div class="formdyn-field-grid">'
                .'<label data-field-config="label">Libelle<br><input class="form-control" type="text" name="libelle" maxlength="190" value="'.self::html($values['libelle']).'"></label>'
                .'<label>Type<br><select class="form-control" name="type_champ">'.self::fieldTypeOptionsHtml($values['type_champ']).'</select></label>'
                .'<label>Ordre<br><input class="form-control" type="number" min="0" name="ordre" value="'.self::html((int) $values['ordre']).'"></label>'
                .'<label>Page / section<br><input class="form-control" type="text" name="page_titre" maxlength="190" value="'.self::html($values['page_titre']).'"></label>'
            .'</div>'
            .'<div class="formdyn-help" data-field-config="separator-label-help">Le libelle peut rester vide pour un separateur.</div>'
            .'<label data-field-config="help">Aide<br><textarea class="form-control" name="aide" rows="2">'.self::html($values['aide']).'</textarea></label>'
            .'<label data-field-config="options">Options<br><textarea class="form-control" name="options" rows="4">'.self::html($values['options']).'</textarea></label>'
            .'<div class="formdyn-help" data-field-config="options">Une option par ligne pour liste deroulante, choix unique ou choix multiples.</div>'
            .'<label data-field-config="default"><span data-field-default-label>Valeur par defaut</span><br><input class="form-control" type="text" name="valeur_defaut" value="'.self::html($values['valeur_defaut']).'"></label>'
            .'<div class="formdyn-help" data-field-config="default" data-field-default-help></div>'
            .'<label data-field-config="image-size">Taille affichage image<br><input class="form-control" type="text" name="image_display_size" maxlength="20" placeholder="480px, 60%, small, medium, large, full" value="'.self::html(FormulairesDynamiquesRepository::imageDisplaySize($values)).'"></label>'
            .'<div class="formdyn-help" data-field-config="image-size">Laissez vide pour une image responsive pleine largeur.</div>'
            .'<label data-field-config="columns">Colonnes suivantes<br><select class="form-control" name="separator_columns">'.self::formColumnOptionsHtml(FormulairesDynamiquesRepository::layoutColumns($values)).'</select></label>'
            .'<div class="formdyn-help" data-field-config="columns">Les champs suivants utilisent ce nombre de colonnes jusqu au prochain changement.</div>'
            .self::renderFieldVisibilityControls((int) $form['id'], (int) $values['id'], $values)
            .'<div class="formdyn-checks">'
                .'<label data-field-config="required"><input type="checkbox" name="obligatoire" value="1"'.((int) $values['obligatoire'] === 1 ? ' checked' : '').'> Obligatoire</label>'
                .'<label><input type="checkbox" name="actif" value="1"'.((int) $values['actif'] === 1 ? ' checked' : '').'> Actif</label>'
            .'</div>'
            .'<p class="formdyn-actions">'
                .'<button class="btn btn-primary" type="submit">'.($editing ? 'Enregistrer le champ' : 'Ajouter le champ').'</button>'
                .($editing ? ' <a class="btn btn-default" href="'.self::html(self::managementUrl(array('form_id' => (int) $form['id'], 'section' => 'fields'))).'">Nouveau champ</a>' : '')
            .'</p>'
            .'</form>'
            .self::fieldEditorBehaviorScript()
            .'</div>';

        return $html;
    }

    private static function fieldEditorBehaviorScript()
    {
        return '<script>'
            .'(function(){'
            .'function isChoice(type){return type==="select"||type==="radio"||type==="checkboxes";}'
            .'function isResponseField(type){return type!=="separator"&&type!=="image"&&type!=="empty";}'
            .'function hasDefault(type){return type==="text"||type==="textarea"||type==="email"||type==="number"||type==="date"||isChoice(type)||type==="image";}'
            .'function hasHelp(type){return isResponseField(type)||type==="image";}'
            .'function visible(config,type){if(config==="label"){return type!=="empty";}if(config==="separator-label-help"){return type==="separator";}if(config==="help"){return hasHelp(type);}if(config==="options"){return isChoice(type);}if(config==="default"){return hasDefault(type);}if(config==="image-size"){return type==="image";}if(config==="columns"){return type==="separator"||type==="empty";}if(config==="required"){return isResponseField(type);}return true;}'
            .'function setDisabled(block,disabled){var controls=block.querySelectorAll("input,select,textarea");for(var i=0;i<controls.length;i++){controls[i].disabled=disabled;}}'
            .'function defaultLabel(type){return type==="image"?"URL image":"Valeur par defaut";}'
            .'function defaultHelp(type){if(type==="image"){return "Indiquez une URL http(s) ou un chemin relatif vers l image.";}if(type==="checkboxes"){return "Pour plusieurs valeurs cochees par defaut, indiquez une valeur par ligne.";}if(type==="radio"){return "Indiquez la valeur a cocher par defaut.";}if(type==="select"){return "Indiquez la valeur a selectionner par defaut.";}return "Utilise comme valeur pre-remplie dans le formulaire.";}'
            .'function sync(form){var typeSelect=form.querySelector("[name=type_champ]");if(!typeSelect){return;}var type=typeSelect.value||"text";var blocks=form.querySelectorAll("[data-field-config]");for(var i=0;i<blocks.length;i++){var block=blocks[i];var show=visible(block.getAttribute("data-field-config")||"",type);block.hidden=!show;setDisabled(block,!show);}var label=form.querySelector("[data-field-default-label]");if(label){label.textContent=defaultLabel(type);}var help=form.querySelector("[data-field-default-help]");if(help){help.textContent=defaultHelp(type);}}'
            .'var forms=document.querySelectorAll("#formulaires-dynamiques form.formdyn-field-editor");for(var f=0;f<forms.length;f++){(function(form){var typeSelect=form.querySelector("[name=type_champ]");if(typeSelect){typeSelect.addEventListener("change",function(){sync(form);});}sync(form);})(forms[f]);}'
            .'})();'
            .'</script>';
    }

    private static function currentFieldEditorValues($form, $postedFieldValues)
    {
        $defaults = array(
            'id' => 0,
            'formulaire_id' => (int) $form['id'],
            'type_champ' => 'text',
            'libelle' => '',
            'aide' => '',
            'options' => '',
            'valeur_defaut' => '',
            'page_titre' => '',
            'visibility_champ_id' => 0,
            'visibility_operateur' => '',
            'visibility_valeur' => '',
            'obligatoire' => 0,
            'ordre' => 0,
            'actif' => 1,
        );

        if (is_array($postedFieldValues) && count($postedFieldValues) > 0) {
            return array_merge($defaults, FormulairesDynamiquesRepository::normalizeFieldValues($postedFieldValues));
        }

        $fieldId = isset($_GET['field_id']) ? (int) $_GET['field_id'] : 0;
        if ($fieldId > 0) {
            $field = FormulairesDynamiquesRepository::field($fieldId);
            if ($field && (int) $field['formulaire_id'] === (int) $form['id']) {
                return array_merge($defaults, FormulairesDynamiquesRepository::normalizeFieldValues($field));
            }
        }

        return $defaults;
    }

    private static function fieldTypeOptionsHtml($selected)
    {
        $selected = (string) $selected;
        $html = '';
        foreach (FormulairesDynamiquesRepository::fieldTypeOptions() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.($selected === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function renderFieldsTable($form)
    {
        $fields = FormulairesDynamiquesRepository::fields((int) $form['id'], true);
        if (count($fields) === 0) {
            return '<div class="alert alert-info">Aucun champ ajoute.</div>';
        }

        $formId = (int) $form['id'];
        $fieldOrder = array();
        foreach ($fields as $field) {
            $fieldOrder[] = (int) (isset($field['id']) ? $field['id'] : 0);
        }

        $html = '<form id="formdyn-field-order-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'fields'))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_field_order">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<input type="hidden" id="formdyn-field-order-input" name="field_order" value="'.self::html(implode(',', $fieldOrder)).'">'
            .'</form>'
            .'<div class="table-responsive"><table class="table table-striped formdyn-fields-table">'
            .'<thead><tr>'
                .'<th></th>'
                .'<th>Ordre</th>'
                .'<th>Libelle</th>'
                .'<th>Type</th>'
                .'<th>Page</th>'
                .'<th>Condition</th>'
                .'<th>Obligatoire</th>'
                .'<th>Etat</th>'
                .'<th>Actions</th>'
            .'</tr></thead><tbody id="formdyn-field-order">';

        foreach ($fields as $field) {
            $fieldId = isset($field['id']) ? (int) $field['id'] : 0;
            $active = isset($field['actif']) && (int) $field['actif'] === 1;
            $html .= '<tr draggable="true" data-field-id="'.self::html($fieldId).'"'.($active ? '' : ' class="text-muted"').'>'
                .'<td class="formdyn-drag-handle" title="Glisser pour reordonner">::</td>'
                .'<td>'.self::html((int) (isset($field['ordre']) ? $field['ordre'] : 0)).'</td>'
                .'<td><strong>'.self::html(self::fieldListLabel($field)).'</strong>'.self::fieldOptionsPreview($field).'</td>'
                .'<td>'.self::html(FormulairesDynamiquesRepository::fieldTypeLabel(isset($field['type_champ']) ? $field['type_champ'] : '')).'</td>'
                .'<td>'.self::html(isset($field['page_titre']) && trim((string) $field['page_titre']) !== '' ? $field['page_titre'] : '-').'</td>'
                .'<td>'.self::html(self::fieldVisibilityLabel($field)).'</td>'
                .'<td>'.((isset($field['obligatoire']) && (int) $field['obligatoire'] === 1) ? 'Oui' : 'Non').'</td>'
                .'<td>'.($active ? '<span class="label label-success">Actif</span>' : '<span class="label label-default">Inactif</span>').'</td>'
                .'<td>'
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'field_id' => $fieldId, 'section' => 'fields'))).'">Editer</a>'
                    .($active ? self::disableFieldForm($formId, $fieldId) : '')
                .'</td>'
            .'</tr>';
        }

        return $html.'</tbody></table></div>'
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit" form="formdyn-field-order-form">Enregistrer l ordre</button></p>'
            .self::fieldOrderScript();
    }

    private static function fieldOptionsPreview($field)
    {
        if (isset($field['type_champ']) && (string) $field['type_champ'] === 'image') {
            $size = FormulairesDynamiquesRepository::imageDisplaySize($field);
            return $size !== '' ? '<br><small>Taille image : '.self::html($size).'</small>' : '';
        }
        if (isset($field['type_champ']) && (string) $field['type_champ'] === 'separator') {
            $columns = FormulairesDynamiquesRepository::layoutColumns($field);
            return '<br><small>Colonnes suivantes : '.self::html($columns).'</small>';
        }
        if (isset($field['type_champ']) && (string) $field['type_champ'] === 'empty') {
            $columns = FormulairesDynamiquesRepository::layoutColumns($field);
            return '<br><small>Espace vide - colonnes suivantes : '.self::html($columns).'</small>';
        }

        $options = FormulairesDynamiquesRepository::fieldOptionsArray($field);
        if (count($options) === 0) {
            return '';
        }

        return '<br><small>'.self::html(implode(', ', $options)).'</small>';
    }

    private static function fieldListLabel($field)
    {
        $label = isset($field['libelle']) ? trim((string) $field['libelle']) : '';
        if ($label !== '') {
            return $label;
        }

        if (isset($field['type_champ']) && (string) $field['type_champ'] === 'separator') {
            return 'Separateur sans libelle';
        }
        if (isset($field['type_champ']) && (string) $field['type_champ'] === 'empty') {
            return 'Champ vide';
        }

        return '';
    }

    private static function fieldVisibilityLabel($field)
    {
        $conditionFieldId = (int) (isset($field['visibility_champ_id']) ? $field['visibility_champ_id'] : 0);
        if ($conditionFieldId <= 0) {
            return '-';
        }

        $conditionField = FormulairesDynamiquesRepository::field($conditionFieldId);
        $operators = FormulairesDynamiquesRepository::notificationConditionOperators();
        $operator = isset($field['visibility_operateur']) ? (string) $field['visibility_operateur'] : '';
        $label = isset($operators[$operator]) ? $operators[$operator] : $operator;
        $value = isset($field['visibility_valeur']) ? (string) $field['visibility_valeur'] : '';

        return (isset($conditionField['libelle']) ? $conditionField['libelle'] : 'Champ '.$conditionFieldId)
            .' '.$label.($value !== '' ? ' '.$value : '');
    }

    private static function disableFieldForm($formId, $fieldId)
    {
        return ' <form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'fields'))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_field">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="field_id" value="'.self::html((int) $fieldId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce champ ?\');">Desactiver</button>'
            .'</form>';
    }

    private static function fieldOrderScript()
    {
        return '<script>'
            .'(function(){'
            .'var body=document.getElementById("formdyn-field-order");'
            .'var input=document.getElementById("formdyn-field-order-input");'
            .'if(!body||!input){return;}'
            .'var dragged=null;'
            .'function sync(){var ids=[];var rows=body.querySelectorAll("tr[data-field-id]");for(var i=0;i<rows.length;i++){ids.push(rows[i].getAttribute("data-field-id"));}input.value=ids.join(",");}'
            .'body.addEventListener("dragstart",function(e){var row=e.target.closest("tr[data-field-id]");if(!row){return;}dragged=row;e.dataTransfer.effectAllowed="move";row.classList.add("formdyn-dragging");});'
            .'body.addEventListener("dragend",function(){if(dragged){dragged.classList.remove("formdyn-dragging");dragged=null;}sync();});'
            .'body.addEventListener("dragover",function(e){e.preventDefault();var row=e.target.closest("tr[data-field-id]");if(!row||!dragged||row===dragged){return;}var rect=row.getBoundingClientRect();var after=(e.clientY-rect.top)>rect.height/2;body.insertBefore(dragged,after?row.nextSibling:row);});'
            .'sync();'
            .'})();'
            .'</script>';
    }

    private static function currentFormEditorValues($postedValues)
    {
        $defaults = array(
            'id' => 0,
            'titre' => '',
            'description' => '',
            'form_columns' => 1,
            'result_list_template' => '',
            'result_detail_template' => '',
            'result_columns' => '',
            'notification_subject_template' => '',
            'notification_body_template' => '',
            'statut' => 'brouillon',
        );

        if (is_array($postedValues) && count($postedValues) > 0) {
            return array_merge($defaults, FormulairesDynamiquesRepository::normalizeFormValues($postedValues), array(
                'id' => isset($postedValues['id']) ? (int) $postedValues['id'] : 0,
            ));
        }

        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId > 0) {
            $form = FormulairesDynamiquesRepository::form($formId);
            if ($form) {
                return array_merge($defaults, FormulairesDynamiquesRepository::normalizeFormValues($form), array(
                    'id' => (int) $form['id'],
                ));
            }
        }

        return $defaults;
    }

    private static function statusOptionsHtml($selected)
    {
        $selected = (string) $selected;
        $html = '';
        foreach (FormulairesDynamiquesRepository::statusOptions() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.($selected === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function formColumnOptionsHtml($selected)
    {
        $selected = FormulairesDynamiquesRepository::normalizeFormColumns($selected);
        $html = '';
        foreach (array(1, 2, 3, 4) as $value) {
            $label = $value === 1 ? '1 colonne' : $value.' colonnes';
            $html .= '<option value="'.self::html($value).'"'.($selected === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function activeUserOptionsHtml($selected)
    {
        $selected = (string) $selected;
        $html = '<option value=""></option>';
        foreach (FormulairesDynamiquesRepository::activeUsers() as $user) {
            $login = isset($user['login']) ? (string) $user['login'] : '';
            $label = isset($user['label']) ? (string) $user['label'] : $login;
            $html .= '<option value="'.self::html($login).'"'.($selected === $login ? ' selected' : '').'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function renderFormsTable($login = '')
    {
        $forms = FormulairesDynamiquesRights::canManageModule($login)
            ? FormulairesDynamiquesRepository::forms(true)
            : FormulairesDynamiquesRepository::formsForLogin($login, true);

        $html = '<section class="formdyn-panel"><h3>Formulaires</h3>';
        if (count($forms) === 0) {
            return $html.'<div class="alert alert-info">Aucun formulaire accessible.</div></section>';
        }

        $html .= '<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr>'
                .'<th>Titre</th>'
                .'<th>Statut</th>'
                .'<th>Champs</th>'
                .'<th>Reponses</th>'
                .'<th>Mis a jour</th>'
                .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($forms as $form) {
            $formId = isset($form['id']) ? (int) $form['id'] : 0;
            $html .= '<tr>'
                .'<td><strong>'.self::html(isset($form['titre']) ? $form['titre'] : '').'</strong><br><small>'.self::html(isset($form['created_by']) ? $form['created_by'] : '').'</small></td>'
                .'<td>'.self::statusBadge(isset($form['statut']) ? $form['statut'] : '').'</td>'
                .'<td>'.self::html((int) (isset($form['field_count']) ? $form['field_count'] : 0)).'</td>'
                .'<td>'.self::html((int) (isset($form['response_count']) ? $form['response_count'] : 0)).'</td>'
                .'<td>'.self::html(self::formatDate(isset($form['updated_at']) ? $form['updated_at'] : 0)).'</td>'
                .'<td>'
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'edit'))).'">Fiche</a> '
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'fields'))).'">Champs</a> '
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'diffusion'))).'">Diffusion</a> '
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'notifications'))).'">Notifications</a> '
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'preview'))).'">Apercu</a> '
                    .self::formResultsButton($formId)
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'stats'))).'">Stats</a> '
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'tools'))).'">Outils</a> '
                    .self::duplicateFormButton($formId)
                    .self::deleteFormButton($login, $form)
                .'</td>'
            .'</tr>';
        }

        return $html.'</tbody></table></div></section>';
    }

    private static function formResultsButton($formId)
    {
        return '<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => (int) $formId, 'section' => 'resultats'))).'">Resultats</a> ';
    }

    private static function duplicateFormButton($formId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('section' => 'forms'))).'">'
            .'<input type="hidden" name="formdyn_action" value="duplicate_form">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Dupliquer ce formulaire en brouillon ?\');">Dupliquer</button>'
            .'</form>';
    }

    private static function deleteFormButton($login, $form)
    {
        if (!self::canDeleteForm($login, $form)) {
            return '';
        }

        $formId = (int) (isset($form['id']) ? $form['id'] : 0);
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('section' => 'forms'))).'">'
            .'<input type="hidden" name="formdyn_action" value="delete_form">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<button class="btn btn-danger btn-sm" type="submit" onclick="return confirm(\'Supprimer definitivement ce formulaire, ses champs, ses jetons et toutes ses reponses ?\');">Supprimer</button>'
            .'</form>';
    }

    private static function renderGlobalImportPanel($login = '')
    {
        if (!FormulairesDynamiquesRights::canCreateForms($login)) {
            return '';
        }

        return '<section class="formdyn-panel"><h3>Importer un formulaire</h3>'
            .'<form method="post" enctype="multipart/form-data" action="'.self::html(self::managementUrl(array('section' => 'forms'))).'">'
            .'<input type="hidden" name="formdyn_action" value="import_form_json">'
            .'<label>Fichier JSON<br><input class="form-control" type="file" name="formdyn_json" accept=".json,application/json" required></label>'
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Importer</button></p>'
            .'</form>'
            .'</section>';
    }

    private static function renderPreviewPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        $fields = FormulairesDynamiquesRepository::fields($formId, false);
        if (!$form) {
            return '';
        }

        return '<section class="formdyn-panel">'
            .'<h3>Apercu du formulaire</h3>'
            .'<div class="alert alert-info">Cet apercu ne cree pas de reponse et ne necessite pas de jeton public.</div>'
            .self::renderFormArticle($form, $fields, array(), array(), false, true)
            .'</section>';
    }

    private static function renderResultsPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return '';
        }

        return '<section class="formdyn-panel">'
            .'<div class="alert alert-info">Ces resultats sont affiches depuis Gerer mon compte et ne necessitent pas de jeton public.</div>'
            .self::renderResultsForForm($form, 'management', $login)
            .'</section>';
    }

    private static function renderStatsPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $stats = FormulairesDynamiquesRepository::responseStats($formId);
        $html = '<section class="formdyn-panel"><h3>Statistiques</h3>'
            .'<div class="formdyn-counter"><span class="formdyn-counter-value">'.self::html((int) $stats['total']).'</span><span class="formdyn-counter-label">Reponses</span></div>';

        if (empty($stats['fields'])) {
            return $html.'<div class="alert alert-info">Aucun champ a choix ne permet encore de calculer une repartition.</div></section>';
        }

        foreach ($stats['fields'] as $fieldStats) {
            $html .= '<div class="formdyn-subpanel"><h4>'.self::html(isset($fieldStats['libelle']) ? $fieldStats['libelle'] : '').'</h4>'
                .'<table class="table table-striped"><thead><tr><th>Valeur</th><th>Reponses</th></tr></thead><tbody>';
            foreach ((array) (isset($fieldStats['counts']) ? $fieldStats['counts'] : array()) as $value => $count) {
                $html .= '<tr><td>'.self::html($value).'</td><td>'.self::html((int) $count).'</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }

        return $html.'</section>';
    }

    private static function renderToolsPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $importForm = FormulairesDynamiquesRights::canCreateForms($login)
            ? '<form method="post" enctype="multipart/form-data" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'tools'))).'">'
                    .'<input type="hidden" name="formdyn_action" value="import_form_json">'
                    .'<h4>Import JSON</h4>'
                    .'<label>Fichier JSON<br><input class="form-control" type="file" name="formdyn_json" accept=".json,application/json" required></label>'
                    .'<button class="btn btn-default" type="submit">Importer un formulaire</button>'
                .'</form>'
            : '';

        return '<section class="formdyn-panel"><h3>Outils formulaire</h3>'
            .'<div class="formdyn-tool-grid">'
                .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'tools'))).'">'
                    .'<input type="hidden" name="formdyn_action" value="duplicate_form">'
                    .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
                    .'<h4>Duplication</h4>'
                    .'<p>Copie le formulaire en brouillon, avec champs, gestionnaires et notifications.</p>'
                    .'<button class="btn btn-primary" type="submit">Dupliquer</button>'
                .'</form>'
                .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId, 'section' => 'tools'))).'">'
                    .'<input type="hidden" name="formdyn_action" value="export_form_json">'
                    .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
                    .'<h4>Export JSON</h4>'
                    .'<p>Exporte la structure du formulaire sans reponses ni jetons.</p>'
                    .'<button class="btn btn-default" type="submit">Exporter</button>'
                .'</form>'
                .$importForm
            .'</div>'
            .'</section>';
    }

    private static function renderHistoryPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $filters = array(
            'action' => self::requestText('history_action'),
            'q' => self::requestText('history_q'),
        );
        $history = FormulairesDynamiquesRepository::history($formId, 50, $filters);
        $html = '<section class="formdyn-panel"><h3>Historique recent</h3>'
            .self::renderHistoryFilters($filters);
        if (count($history) === 0) {
            return $html.'<div class="alert alert-info">Aucun evenement enregistre.</div></section>';
        }

        $html .= '<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr><th>Date</th><th>Action</th><th>Auteur</th><th>Reponse</th><th>Details</th></tr></thead><tbody>';
        foreach ($history as $event) {
            $responseId = (int) (isset($event['reponse_id']) ? $event['reponse_id'] : 0);
            $html .= '<tr>'
                .'<td>'.self::html(self::formatDate(isset($event['created_at']) ? $event['created_at'] : 0)).'</td>'
                .'<td>'.self::html(self::historyActionLabel(isset($event['action']) ? $event['action'] : '')).'</td>'
                .'<td>'.self::html(isset($event['auteur']) && $event['auteur'] !== '' ? $event['auteur'] : '-').'</td>'
                .'<td>'.self::html($responseId > 0 ? '#'.$responseId : '-').'</td>'
                .'<td>'.self::html(isset($event['details']) ? $event['details'] : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></section>';
    }

    private static function renderHistoryFilters($filters)
    {
        $action = isset($filters['action']) ? (string) $filters['action'] : '';
        $q = isset($filters['q']) ? (string) $filters['q'] : '';
        $actions = array(
            '' => '',
            'creation_reponse' => 'Creation reponse',
            'modification_reponse' => 'Modification reponse',
            'export_reponses' => 'Export reponses',
            'creation_champ' => 'Creation champ',
            'modification_champ' => 'Modification champ',
            'creation_notification' => 'Creation notification',
            'desactivation_notification' => 'Desactivation notification',
            'desactivation_jeton' => 'Desactivation jeton',
            'suppression_jeton' => 'Suppression jeton',
            'duplication_formulaire' => 'Duplication formulaire',
            'import_formulaire_json' => 'Import JSON',
        );

        $html = '<form class="formdyn-results-filters" method="get" action="">'
            .self::hiddenDisplayParams(array('history_action', 'history_q'))
            .'<div class="formdyn-filter-grid">'
            .'<label>Action<br><select class="form-control" name="history_action">';
        foreach ($actions as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.($action === $value ? ' selected' : '').'>'.self::html($label).'</option>';
        }
        $html .= '</select></label>'
            .'<label>Recherche<br><input class="form-control" type="text" name="history_q" value="'.self::html($q).'"></label>'
            .'</div>'
            .'<p class="formdyn-actions"><button class="btn btn-default" type="submit">Filtrer</button></p>'
            .'</form>';

        return $html;
    }

    private static function historyActionLabel($action)
    {
        $labels = array(
            'ajout_gestionnaire' => 'Ajout gestionnaire',
            'creation_champ' => 'Creation champ',
            'creation_formulaire' => 'Creation formulaire',
            'creation_notification' => 'Creation notification',
            'creation_reponse' => 'Creation reponse',
            'desactivation_champ' => 'Desactivation champ',
            'desactivation_jeton' => 'Desactivation jeton',
            'desactivation_notification' => 'Desactivation notification',
            'export_reponses' => 'Export reponses',
            'modification_champ' => 'Modification champ',
            'modification_formulaire' => 'Modification formulaire',
            'modification_reponse' => 'Modification reponse',
            'notification' => 'Notification',
            'retrait_gestionnaire' => 'Retrait gestionnaire',
            'suppression_jeton' => 'Suppression jeton',
            'duplication_formulaire' => 'Duplication formulaire',
            'import_formulaire_json' => 'Import formulaire JSON',
            'ordre_champs' => 'Ordre des champs',
        );
        $action = (string) $action;
        if (isset($labels[$action])) {
            return $labels[$action];
        }
        if (strpos($action, 'creation_jeton_') === 0) {
            return 'Creation jeton '.substr($action, strlen('creation_jeton_'));
        }

        return $action;
    }

    private static function statusBadge($status)
    {
        $status = (string) $status;
        $class = 'default';
        if ($status === 'publie') {
            $class = 'success';
        } elseif ($status === 'archive') {
            $class = 'default';
        } elseif ($status === 'brouillon') {
            $class = 'warning';
        }

        return '<span class="label label-'.$class.'">'.self::html(FormulairesDynamiquesRepository::statusLabel($status)).'</span>';
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '-';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private static function managementUrl($params = array())
    {
        $params = array_merge(array('pc' => FormulairesDynamiquesConfig::MODULE), $params);
        $query = array();
        foreach ($params as $key => $value) {
            $query[] = rawurlencode((string) $key).'='.rawurlencode((string) $value);
        }

        return 'compte.php?'.implode('&', $query);
    }

    private static function safeFilename($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9_-]+/', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? substr($value, 0, 80) : 'formulaire';
    }

    private static function formDisplayUrl($token, $integrated)
    {
        if ($integrated) {
            return '../app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE)
                .'&view=formulaire&token='.rawurlencode((string) $token);
        }

        return '../personnalisation/modules/'.rawurlencode(FormulairesDynamiquesConfig::MODULE)
            .'/public.php?view=formulaire&token='.rawurlencode((string) $token);
    }

    private static function resultsDisplayUrl($token, $integrated)
    {
        if ($integrated) {
            return '../app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE)
                .'&view=resultats&token='.rawurlencode((string) $token);
        }

        return '../personnalisation/modules/'.rawurlencode(FormulairesDynamiquesConfig::MODULE)
            .'/public.php?view=resultats&token='.rawurlencode((string) $token);
    }

    private static function redirectToManagement($params = array())
    {
        header('Location: '.self::managementUrl($params));
        exit;
    }

    private static function renderDisplayRoute($mode, $login)
    {
        if (!FormulairesDynamiquesConfig::isEnabled()) {
            return self::displayShell('<div class="alert alert-warning">Le module est desactive.</div>');
        }

        if ($mode === 'autonomous' && !FormulairesDynamiquesConfig::autonomousEnabled()) {
            return self::displayShell('<div class="alert alert-warning">Les pages autonomes sont desactivees.</div>');
        }

        $view = self::requestedDisplayView();
        $token = self::requestedDisplayToken();
        if ($view === '' || $token === '') {
            return self::displayShell(
                '<h2>'.self::html(FormulairesDynamiquesConfig::displayName()).'</h2>'
                .'<div class="alert alert-info">Aucun formulaire ou page de resultats n est selectionne.</div>'
            );
        }

        if ($view === 'formulaire') {
            return self::displayShell(self::renderFormDisplay($mode, $token, $login));
        }

        if ($view === 'resultats') {
            return self::displayShell(self::renderResultsDisplay($mode, $token, $login));
        }

        if ($view === 'export') {
            return self::renderExportDisplay($token, $login);
        }

        return self::displayShell('<div class="alert alert-warning">Type d affichage inconnu.</div>');
    }

    private static function renderExportDisplay($token, $login)
    {
        $form = FormulairesDynamiquesRepository::formByToken($token, 'resultats');
        if (!$form) {
            return self::displayShell(
                '<h2>Export</h2>'
                .'<div class="alert alert-warning">Le lien d export est invalide ou desactive.</div>'
            );
        }

        return self::renderExportForForm($form, $login);
    }

    private static function renderManagementExportDisplay($login)
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return self::displayShell(
                '<h2>Export</h2>'
                .'<div class="alert alert-warning">Acces refuse.</div>'
            );
        }

        $form = FormulairesDynamiquesRepository::form($formId);
        if (!$form) {
            return self::displayShell(
                '<h2>Export</h2>'
                .'<div class="alert alert-warning">Le formulaire est introuvable.</div>'
            );
        }

        return self::renderExportForForm($form, $login);
    }

    private static function renderExportForForm($form, $login)
    {
        self::loadExportEngine();
        if (!class_exists('FormulairesDynamiquesExport')) {
            return self::displayShell(
                '<h2>Export</h2>'
                .'<div class="alert alert-danger">Le moteur d export est introuvable.</div>'
            );
        }

        $fields = FormulairesDynamiquesRepository::resultFieldsForForm(
            $form,
            self::resultFields(FormulairesDynamiquesRepository::fields((int) $form['id'], true))
        );
        $format = FormulairesDynamiquesExport::normalizeFormat(isset($_GET['format']) ? $_GET['format'] : 'csv');
        $scope = isset($_GET['scope']) && $_GET['scope'] === 'response' ? 'response' : 'all';
        $responseId = isset($_GET['response_id']) ? (int) $_GET['response_id'] : 0;
        $responses = array();

        if ($scope === 'response') {
            $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
            if (!$response || (int) $response['formulaire_id'] !== (int) $form['id']) {
                return self::displayShell(
                    '<h2>Export</h2>'
                    .'<div class="alert alert-warning">Cette reponse est introuvable pour ce formulaire.</div>'
                );
            }
            $responses[] = $response;
        } else {
            $responses = FormulairesDynamiquesRepository::allResponsesWithValues((int) $form['id'], self::responseFiltersFromRequest());
        }

        FormulairesDynamiquesRepository::recordExport(
            (int) $form['id'],
            $scope === 'response' ? $responseId : 0,
            $login !== '' ? $login : 'lien_export',
            strtoupper($format).' '.$scope
        );
        FormulairesDynamiquesExport::download($form, $fields, $responses, $format, $scope);

        return '';
    }

    private static function loadExportEngine()
    {
        if (class_exists('FormulairesDynamiquesExport')) {
            return;
        }

        $path = __DIR__.'/Export.php';
        if (is_file($path)) {
            require_once $path;
        }
    }

    private static function renderResultsDisplay($mode, $token, $login)
    {
        $form = FormulairesDynamiquesRepository::formByToken($token, 'resultats');
        if (!$form) {
            return '<h2>Resultats</h2>'
                .'<div class="alert alert-warning">Le lien de resultats est invalide ou desactive.</div>';
        }

        return self::renderResultsForForm($form, $mode, $login);
    }

    private static function renderResultsForForm($form, $mode, $login)
    {
        $allFields = self::resultFields(FormulairesDynamiquesRepository::fields((int) $form['id'], true));
        $fields = FormulairesDynamiquesRepository::resultFieldsForForm($form, $allFields);
        $responseId = isset($_GET['response_id']) ? (int) $_GET['response_id'] : 0;
        if ($responseId > 0) {
            return self::renderResponseDetail($form, $allFields, $responseId, $login);
        }

        $filters = self::responseFiltersFromRequest();
        $perPage = self::resultsPerPage();
        $page = self::resultsPage();
        $total = FormulairesDynamiquesRepository::countFilteredResponses((int) $form['id'], $filters);
        $offset = ($page - 1) * $perPage;
        if ($offset >= $total && $total > 0) {
            $page = 1;
            $offset = 0;
        }
        $responses = FormulairesDynamiquesRepository::responsesWithValues((int) $form['id'], $perPage, $filters, $offset);

        $html = '<article class="formdyn-results">'
            .'<h2>Resultats - '.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
            .self::renderResultsSummary($form, count($responses), $mode, $login, $total)
            .self::renderResultsFilters($filters)
            .self::renderResultsExportActions();

        if (count($responses) === 0) {
            return $html.'<div class="alert alert-info">Aucune reponse enregistree pour ce formulaire.</div></article>';
        }

        if (isset($form['result_list_template']) && trim((string) $form['result_list_template']) !== '') {
            return $html.self::renderTemplateResultsList($form, $fields, $responses).self::renderPagination($page, $perPage, $total).'</article>';
        }

        $html .= '<div class="table-responsive"><table class="table table-striped formdyn-results-table">'
            .'<thead><tr>'
                .'<th>Reference</th>'
                .'<th>Date</th>'
                .'<th>Source</th>'
                .'<th>Declarant</th>';
        foreach ($fields as $field) {
            $html .= '<th>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</th>';
        }
        $html .= '<th>Actions</th></tr></thead><tbody>';

        foreach ($responses as $response) {
            $responseId = (int) (isset($response['id']) ? $response['id'] : 0);
            $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
            $html .= '<tr>'
                .'<td>#'.self::html($responseId).'</td>'
                .'<td>'.self::html(self::formatDate(isset($response['created_at']) ? $response['created_at'] : 0)).'</td>'
                .'<td>'.self::html(self::sourceLabel(isset($response['source']) ? $response['source'] : '')).'</td>'
                .'<td>'.self::html(self::submitterLabel($response)).'</td>';
            foreach ($fields as $field) {
                $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
                $html .= '<td>'.self::html(self::responsePreview(isset($values[$fieldId]) ? $values[$fieldId] : '')).'</td>';
            }
            $html .= '<td><a class="btn btn-default btn-sm" href="'.self::html(self::displayUrl(array('response_id' => $responseId))).'">Voir</a></td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>'.self::renderPagination($page, $perPage, $total).'</article>';
    }

    private static function renderResultsSummary($form, $shownCount, $mode, $login, $total)
    {
        $modeLabel = 'Integre GRR';
        if ($mode === 'autonomous') {
            $modeLabel = 'Autonome';
        } elseif ($mode === 'management') {
            $modeLabel = 'Gerer mon compte';
        }

        $rows = array(
            'Mode' => $modeLabel,
            'Reponses affichees' => (int) $shownCount,
            'Reponses filtrees' => (int) $total,
            'Total formulaire' => (int) (isset($form['response_count']) ? $form['response_count'] : 0),
        );
        if ((string) $login !== '') {
            $rows['Utilisateur GRR'] = (string) $login;
        }

        $html = '<table class="table table-striped formdyn-results-summary"><tbody>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><th>'.self::html($label).'</th><td>'.self::html($value).'</td></tr>';
        }

        return $html.'</tbody></table>';
    }

    private static function renderResponseDetail($form, $fields, $responseId, $login = '')
    {
        $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
        if (!$response || (int) $response['formulaire_id'] !== (int) (isset($form['id']) ? $form['id'] : 0)) {
            return '<article class="formdyn-results">'
                .'<h2>Resultats - '.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
                .'<div class="alert alert-warning">Cette reponse est introuvable pour ce formulaire.</div>'
                .'<p><a class="btn btn-default" href="'.self::html(self::displayUrl(array(), array('response_id'))).'">Retour aux resultats</a></p>'
                .'</article>';
        }

        $editResult = self::handleResponseEditPost($form, $fields, $response, $login);
        if ($editResult['saved']) {
            $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
        }

        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        $html = '<article class="formdyn-results">'
            .'<h2>Reponse #'.self::html((int) $responseId).' - '.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
            .'<p><a class="btn btn-default" href="'.self::html(self::displayUrl(array(), array('response_id'))).'">Retour aux resultats</a></p>'
            .($editResult['saved'] ? '<div class="alert alert-success">Reponse modifiee.</div>' : '')
            .self::renderAlerts($editResult['errors'], 'danger')
            .self::renderResponseExportActions($responseId)
            .'<table class="table table-striped"><tbody>'
                .'<tr><th>Date</th><td>'.self::html(self::formatDate(isset($response['created_at']) ? $response['created_at'] : 0)).'</td></tr>'
                .'<tr><th>Source</th><td>'.self::html(self::sourceLabel(isset($response['source']) ? $response['source'] : '')).'</td></tr>'
                .'<tr><th>Declarant</th><td>'.self::html(self::submitterLabel($response)).'</td></tr>'
                .(((int) (isset($response['updated_at']) ? $response['updated_at'] : 0) > (int) (isset($response['created_at']) ? $response['created_at'] : 0))
                    ? '<tr><th>Derniere modification</th><td>'.self::html(self::formatDate($response['updated_at'])).' par '.self::html(isset($response['updated_by']) && $response['updated_by'] !== '' ? $response['updated_by'] : '-').'</td></tr>'
                    : '')
            .'</tbody></table>';

        if (count($fields) === 0) {
            return $html.'<div class="alert alert-info">Aucun champ a afficher.</div></article>';
        }

        if (isset($form['result_detail_template']) && trim((string) $form['result_detail_template']) !== '') {
            $html .= '<div class="formdyn-result-card">'
                .self::applyResponseTemplate($form['result_detail_template'], $fields, $response)
                .'</div>';
        } else {
            $html .= '<table class="table table-striped formdyn-response-detail"><thead><tr><th>Champ</th><th>Valeur</th></tr></thead><tbody>';
            foreach ($fields as $field) {
                $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
                $html .= '<tr>'
                    .'<th>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</th>'
                    .'<td>'.self::responseValueHtml(isset($values[$fieldId]) ? $values[$fieldId] : '').'</td>'
                    .'</tr>';
            }

            $html .= '</tbody></table>';
        }

        if (FormulairesDynamiquesRights::canManageForm($login, (int) $form['id'])) {
            $html .= self::renderResponseEditForm($form, $fields, $response);
        }

        return $html.'</article>';
    }

    private static function handleResponseEditPost($form, $fields, $response, $login)
    {
        $result = array('saved' => false, 'errors' => array());
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }
        if (!isset($_POST['formdyn_action']) || $_POST['formdyn_action'] !== 'update_response') {
            return $result;
        }
        if (!FormulairesDynamiquesRights::canManageForm($login, (int) $form['id'])) {
            $result['errors'][] = 'Acces refuse.';
            return $result;
        }

        $values = FormulairesDynamiquesRepository::normalizeResponseValues($fields, $_POST, isset($_FILES) ? $_FILES : array());
        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ((isset($field['type_champ']) ? (string) $field['type_champ'] : '') === 'file'
                && isset($values[$fieldId]) && is_array($values[$fieldId])
                && (!isset($values[$fieldId]['error']) || (int) $values[$fieldId]['error'] === UPLOAD_ERR_NO_FILE)) {
                unset($values[$fieldId]);
            }
        }

        if (!FormulairesDynamiquesRepository::updateResponse((int) $response['id'], $fields, $values, $login)) {
            $result['errors'][] = 'La reponse n a pas pu etre modifiee.';
            return $result;
        }

        $result['saved'] = true;
        return $result;
    }

    private static function renderResponseEditForm($form, $fields, $response)
    {
        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        return '<section class="formdyn-subpanel"><h3>Modifier la reponse</h3>'
            .'<form method="post" enctype="multipart/form-data" action="'.self::html(self::displayUrl()).'">'
            .'<input type="hidden" name="formdyn_action" value="update_response">'
            .self::renderDisplayFields($form, $fields, $values, array(), true)
            .'<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Enregistrer la reponse</button></p>'
            .'</form>'
            .self::displayBehaviorScript()
            .'</section>';
    }

    private static function renderTemplateResultsList($form, $fields, $responses)
    {
        $template = isset($form['result_list_template']) ? (string) $form['result_list_template'] : '';
        $html = '<div class="formdyn-result-cards">';
        foreach ($responses as $response) {
            $responseId = (int) (isset($response['id']) ? $response['id'] : 0);
            $html .= '<article class="formdyn-result-card">'
                .self::applyResponseTemplate($template, $fields, $response)
                .'<p class="formdyn-actions"><a class="btn btn-default btn-sm" href="'.self::html(self::displayUrl(array('response_id' => $responseId))).'">Voir</a></p>'
                .'</article>';
        }

        return $html.'</div>';
    }

    private static function applyResponseTemplate($template, $fields, $response)
    {
        $template = self::html((string) $template);
        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        $replacements = array(
            '{reference}' => '#'.(int) (isset($response['id']) ? $response['id'] : 0),
            '{date}' => self::formatDate(isset($response['created_at']) ? $response['created_at'] : 0),
            '{source}' => self::sourceLabel(isset($response['source']) ? $response['source'] : ''),
            '{declarant}' => self::submitterLabel($response),
        );

        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $label = isset($field['libelle']) ? (string) $field['libelle'] : '';
            $value = isset($values[$fieldId]) ? (string) $values[$fieldId] : '';
            $replacements['{field:'.$fieldId.'}'] = $value;
            if ($label !== '') {
                $replacements['{champ:'.$label.'}'] = $value;
            }
        }

        foreach ($replacements as $key => $value) {
            $template = str_replace(self::html($key), self::html($value), $template);
        }

        return nl2br($template);
    }

    private static function renderResultsFilters($filters)
    {
        $filters = is_array($filters) ? $filters : array();

        return '<form class="formdyn-results-filters" method="get" action="">'
            .self::hiddenDisplayParams(array('q', 'source', 'date_from', 'date_to', 'page', 'per_page'))
            .'<div class="formdyn-filter-grid">'
                .'<label>Recherche<br><input class="form-control" type="text" name="q" maxlength="190" value="'.self::html(isset($filters['q']) ? $filters['q'] : '').'"></label>'
                .'<label>Source<br><select class="form-control" name="source">'
                    .'<option value=""></option>'
                    .'<option value="grr"'.((isset($filters['source']) && $filters['source'] === 'grr') ? ' selected' : '').'>Integre GRR</option>'
                    .'<option value="autonomous"'.((isset($filters['source']) && $filters['source'] === 'autonomous') ? ' selected' : '').'>Autonome</option>'
                .'</select></label>'
                .'<label>Du<br><input class="form-control" type="date" name="date_from" value="'.self::html(self::requestText('date_from')).'"></label>'
                .'<label>Au<br><input class="form-control" type="date" name="date_to" value="'.self::html(self::requestText('date_to')).'"></label>'
                .'<label>Par page<br><select class="form-control" name="per_page">'.self::perPageOptionsHtml(self::resultsPerPage()).'</select></label>'
            .'</div>'
            .'<p class="formdyn-actions">'
                .'<button class="btn btn-primary" type="submit">Filtrer</button> '
                .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array(), array('q', 'source', 'date_from', 'date_to', 'page', 'per_page'))).'">Reinitialiser</a>'
            .'</p>'
            .'</form>';
    }

    private static function renderResultsExportActions()
    {
        return '<div class="formdyn-actions">'
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'csv', 'scope' => 'all'), array('response_id', 'page'))).'">CSV</a> '
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'xlsx', 'scope' => 'all'), array('response_id', 'page'))).'">XLSX</a> '
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'pdf', 'scope' => 'all'), array('response_id', 'page'))).'">PDF</a>'
            .'</div>';
    }

    private static function renderResponseExportActions($responseId)
    {
        return '<div class="formdyn-actions">'
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'csv', 'scope' => 'response', 'response_id' => (int) $responseId))).'">CSV</a> '
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'xlsx', 'scope' => 'response', 'response_id' => (int) $responseId))).'">XLSX</a> '
            .'<a class="btn btn-default" href="'.self::html(self::displayUrl(array('view' => 'export', 'format' => 'pdf', 'scope' => 'response', 'response_id' => (int) $responseId))).'">PDF</a>'
            .'</div>';
    }

    private static function renderPagination($page, $perPage, $total)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $total = max(0, (int) $total);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($pages <= 1) {
            return '';
        }

        $html = '<div class="formdyn-pagination">';
        if ($page > 1) {
            $html .= '<a class="btn btn-default btn-sm" href="'.self::html(self::displayUrl(array('page' => $page - 1))).'">Precedent</a> ';
        }
        $html .= '<span>Page '.self::html($page).' / '.self::html($pages).'</span>';
        if ($page < $pages) {
            $html .= ' <a class="btn btn-default btn-sm" href="'.self::html(self::displayUrl(array('page' => $page + 1))).'">Suivant</a>';
        }

        return $html.'</div>';
    }

    private static function responseFiltersFromRequest()
    {
        return FormulairesDynamiquesRepository::normalizeResponseFilters(array(
            'q' => self::requestText('q'),
            'source' => self::requestText('source'),
            'date_from' => self::requestText('date_from'),
            'date_to' => self::requestText('date_to'),
        ));
    }

    private static function resultsPerPage()
    {
        $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 50;
        return in_array($perPage, array(25, 50, 100, 200), true) ? $perPage : 50;
    }

    private static function resultsPage()
    {
        return max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
    }

    private static function perPageOptionsHtml($selected)
    {
        $selected = (int) $selected;
        $html = '';
        foreach (array(25, 50, 100, 200) as $value) {
            $html .= '<option value="'.self::html($value).'"'.($selected === $value ? ' selected' : '').'>'.self::html($value).'</option>';
        }

        return $html;
    }

    private static function requestText($key)
    {
        if (!isset($_GET[$key]) || is_array($_GET[$key])) {
            return '';
        }

        return trim((string) $_GET[$key]);
    }

    private static function hiddenDisplayParams($excludeKeys = array())
    {
        $excluded = array();
        foreach ((array) $excludeKeys as $key) {
            $excluded[(string) $key] = true;
        }

        $html = '';
        foreach ($_GET as $key => $value) {
            $key = (string) $key;
            if (isset($excluded[$key]) || is_array($value)) {
                continue;
            }

            $html .= '<input type="hidden" name="'.self::html($key).'" value="'.self::html((string) $value).'">';
        }

        return $html;
    }

    private static function renderFormDisplay($mode, $token, $login)
    {
        $form = FormulairesDynamiquesRepository::formByToken($token, 'formulaire');
        if (!$form) {
            return '<h2>Formulaire</h2>'
                .'<div class="alert alert-warning">Le lien de formulaire est invalide ou desactive.</div>';
        }

        if (!isset($form['statut']) || (string) $form['statut'] !== 'publie') {
            return '<h2>'.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
                .'<div class="alert alert-warning">Ce formulaire n est pas publie.</div>';
        }

        $fields = FormulairesDynamiquesRepository::fields((int) $form['id'], false);
        $savedResponseId = self::savedResponseIdForForm($form);
        if ($savedResponseId > 0) {
            return self::renderResponseConfirmation($form, $savedResponseId);
        }

        $responseResult = self::handleResponsePost($form, $fields, $mode, $login);
        if ($responseResult['saved']) {
            return self::renderResponseConfirmation($form, $responseResult['response_id']);
        }

        $errors = array_merge(
            $responseResult['errors'],
            self::flattenResponseErrors($responseResult['field_errors'])
        );
        return self::renderAlerts($errors, 'danger')
            .self::renderFormArticle($form, $fields, $responseResult['values'], $responseResult['field_errors'], $responseResult['submitted'], false);
    }

    private static function renderFormArticle($form, $fields, $values = array(), $fieldErrors = array(), $submitted = false, $preview = false)
    {
        $html = '<article class="formdyn-public-form">'
            .'<h2>'.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>';
        if (isset($form['description']) && trim((string) $form['description']) !== '') {
            $html .= '<div class="formdyn-description">'.nl2br(self::html($form['description'])).'</div>';
        }

        if (count($fields) === 0) {
            return $html.'<div class="alert alert-info">Ce formulaire ne contient encore aucun champ actif.</div></article>';
        }

        $html .= '<form method="post" action="" enctype="multipart/form-data"'.($preview ? ' onsubmit="return false;"' : '').'>'
            .'<input type="hidden" name="formdyn_action" value="submit_response">'
            .self::renderDisplayFields($form, $fields, $values, $fieldErrors, $submitted)
            .'<p class="formdyn-actions">'
                .($preview ? '<button class="btn btn-default" type="button" disabled>Envoyer</button>' : '<button class="btn btn-primary" type="submit">Envoyer</button>')
            .'</p>'
            .'</form>'
            .self::displayBehaviorScript()
            .'</article>';

        return $html;
    }

    private static function renderDisplayFields($form, $fields, $values, $fieldErrors, $submitted)
    {
        $defaultColumns = FormulairesDynamiquesRepository::normalizeFormColumns(isset($form['form_columns']) ? $form['form_columns'] : 1);
        $pages = array();
        foreach ($fields as $field) {
            $pageTitle = isset($field['page_titre']) ? trim((string) $field['page_titre']) : '';
            if ($pageTitle === '') {
                $pageTitle = 'Formulaire';
            }
            if (!isset($pages[$pageTitle])) {
                $pages[$pageTitle] = array();
            }
            $pages[$pageTitle][] = $field;
        }

        if (count($pages) <= 1) {
            return self::renderDisplayBlocks($fields, $defaultColumns, $values, $fieldErrors, $submitted);
        }

        $html = '<div class="formdyn-page-flow">';
        $index = 0;
        $total = count($pages);
        foreach ($pages as $title => $pageFields) {
            $html .= '<fieldset class="formdyn-form-page" data-page-index="'.self::html($index).'"'.($index === 0 ? '' : ' hidden').'>'
                .'<legend>'.self::html($title).'</legend>'
                .self::renderDisplayBlocks($pageFields, $defaultColumns, $values, $fieldErrors, $submitted)
                .'<div class="formdyn-page-actions">'
                .($index > 0 ? '<button class="btn btn-default formdyn-page-prev" type="button">Precedent</button> ' : '')
                .($index < $total - 1 ? '<button class="btn btn-default formdyn-page-next" type="button">Suivant</button>' : '')
                .'</div>'
                .'</fieldset>';
            $index++;
        }

        return $html.'</div>';
    }

    private static function renderDisplayBlocks($fields, $defaultColumns, $values, $fieldErrors, $submitted)
    {
        $columns = FormulairesDynamiquesRepository::normalizeFormColumns($defaultColumns);
        $buffer = array();
        $html = '';

        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type === 'separator') {
                $html .= self::renderDisplayGrid($buffer, $columns, $values, $fieldErrors, $submitted)
                    .self::renderDisplayField($field, $values, $fieldErrors, $submitted);
                $buffer = array();
                $columns = FormulairesDynamiquesRepository::layoutColumns($field);
                continue;
            }
            if ($type === 'empty') {
                $buffer[] = $field;
                $html .= self::renderDisplayGrid($buffer, $columns, $values, $fieldErrors, $submitted);
                $buffer = array();
                $columns = FormulairesDynamiquesRepository::layoutColumns($field);
                continue;
            }

            $buffer[] = $field;
        }

        return $html.self::renderDisplayGrid($buffer, $columns, $values, $fieldErrors, $submitted);
    }

    private static function renderDisplayGrid($fields, $columns, $values, $fieldErrors, $submitted)
    {
        if (count($fields) === 0) {
            return '';
        }

        $columns = FormulairesDynamiquesRepository::normalizeFormColumns($columns);
        $html = '<div class="formdyn-display-grid formdyn-display-grid-'.$columns.'">';
        foreach ($fields as $field) {
            $html .= self::renderDisplayField($field, $values, $fieldErrors, $submitted);
        }

        return $html.'</div>';
    }

    private static function handleResponsePost($form, $fields, $mode, $login)
    {
        $result = array(
            'submitted' => false,
            'saved' => false,
            'response_id' => 0,
            'values' => array(),
            'field_errors' => array(),
            'errors' => array(),
        );

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }

        $action = isset($_POST['formdyn_action']) ? (string) $_POST['formdyn_action'] : '';
        if ($action !== 'submit_response') {
            return $result;
        }

        $result['submitted'] = true;
        $result['values'] = FormulairesDynamiquesRepository::normalizeResponseValues($fields, $_POST, isset($_FILES) ? $_FILES : array());
        $result['field_errors'] = FormulairesDynamiquesRepository::validateResponseValues($fields, $result['values']);
        if (count($result['field_errors']) > 0) {
            return $result;
        }

        $responseId = FormulairesDynamiquesRepository::createResponse(
            (int) (isset($form['id']) ? $form['id'] : 0),
            $fields,
            $result['values'],
            self::responseMeta($mode, $login, $form)
        );
        if ($responseId <= 0) {
            $result['errors'][] = 'La reponse n a pas pu etre enregistree.';
            return $result;
        }

        FormulairesDynamiquesNotification::notifyResponseCreated($form, $responseId);

        self::redirectToResponseConfirmation($responseId);

        $result['saved'] = true;
        $result['response_id'] = $responseId;

        return $result;
    }

    private static function renderResponseConfirmation($form, $responseId)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== '' ? (string) $form['titre'] : 'Formulaire';

        return '<article class="formdyn-public-form">'
            .'<h2>'.self::html($title).'</h2>'
            .'<div class="alert alert-success">Votre reponse a ete enregistree.</div>'
            .'<p class="formdyn-response-ref">Reference : #'.self::html((int) $responseId).'</p>'
            .'</article>';
    }

    private static function renderDisplayField($field, $values = array(), $fieldErrors = array(), $submitted = false)
    {
        $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
        $attributes = self::fieldConditionAttributes($field);
        if ($type === 'separator') {
            $label = isset($field['libelle']) ? trim((string) $field['libelle']) : '';
            return '<div class="formdyn-display-separator formdyn-display-full"'.$attributes.'>'
                .($label !== '' ? '<h3>'.self::html($label).'</h3>' : '')
                .'</div>';
        }
        if ($type === 'empty') {
            return '<div class="formdyn-display-empty"'.$attributes.' aria-hidden="true"></div>';
        }
        if ($type === 'image') {
            $src = isset($field['valeur_defaut']) ? trim((string) $field['valeur_defaut']) : '';
            $caption = isset($field['aide']) ? trim((string) $field['aide']) : '';
            $displaySize = FormulairesDynamiquesRepository::imageDisplaySize($field);
            $style = $displaySize !== '' ? ' style="width:100%;max-width:'.self::html($displaySize).';"' : '';
            if ($src === '') {
                return '';
            }

            return '<figure class="formdyn-display-image"'.$attributes.'>'
                .'<img src="'.self::html($src).'" alt="'.self::html(isset($field['libelle']) ? $field['libelle'] : 'Image').'"'.$style.'>'
                .($caption !== '' ? '<figcaption>'.self::html($caption).'</figcaption>' : '')
                .'</figure>';
        }

        $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
        $id = 'formdyn-field-'.$fieldId;
        $name = 'field_'.$fieldId;
        $label = isset($field['libelle']) ? (string) $field['libelle'] : '';
        $required = isset($field['obligatoire']) && (int) $field['obligatoire'] === 1;
        $requiredAttr = '';
        $default = isset($field['valeur_defaut']) ? (string) $field['valeur_defaut'] : '';
        $value = $submitted && array_key_exists($fieldId, $values) ? $values[$fieldId] : $default;
        $error = self::firstFieldError($fieldId, $fieldErrors);
        $html = '<div class="formdyn-display-field"'.$attributes.'>'
            .'<label for="'.self::html($id).'">'.self::html($label).($required ? ' <span class="formdyn-required">*</span>' : '').'</label>'
            .self::renderDisplayControl($type, $id, $name, $field, $value, $requiredAttr);
        if ($type === 'file' && !is_array($value) && trim((string) $value) !== '') {
            $html .= '<div class="formdyn-help">Fichier actuel : '.self::responseValueHtml($value).'</div>';
        }

        if (isset($field['aide']) && trim((string) $field['aide']) !== '') {
            $html .= '<div class="formdyn-help">'.self::html($field['aide']).'</div>';
        }
        if ($error !== '') {
            $html .= '<div class="formdyn-field-error">'.self::html($error).'</div>';
        }

        return $html.'</div>';
    }

    private static function fieldConditionAttributes($field)
    {
        $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
        $conditionFieldId = (int) (isset($field['visibility_champ_id']) ? $field['visibility_champ_id'] : 0);
        $attrs = ' data-field-id="'.self::html($fieldId).'"';
        if ($conditionFieldId > 0) {
            $attrs .= ' data-condition-field="'.self::html($conditionFieldId).'"'
                .' data-condition-operator="'.self::html(isset($field['visibility_operateur']) ? $field['visibility_operateur'] : '').'"'
                .' data-condition-value="'.self::html(isset($field['visibility_valeur']) ? $field['visibility_valeur'] : '').'"';
        }

        return $attrs;
    }

    private static function renderDisplayControl($type, $id, $name, $field, $value, $required)
    {
        if ($type === 'textarea') {
            return '<textarea class="form-control" id="'.self::html($id).'" name="'.self::html($name).'" rows="4"'.$required.'>'.self::html(self::scalarDisplayValue($value)).'</textarea>';
        }

        if ($type === 'select') {
            $value = self::scalarDisplayValue($value);
            $html = '<select class="form-control" id="'.self::html($id).'" name="'.self::html($name).'"'.$required.'>'
                .'<option value=""></option>';
            foreach (FormulairesDynamiquesRepository::fieldOptionsArray($field) as $option) {
                $html .= '<option value="'.self::html($option).'"'.($value === $option ? ' selected' : '').'>'.self::html($option).'</option>';
            }

            return $html.'</select>';
        }

        if ($type === 'radio') {
            $value = self::scalarDisplayValue($value);
            $html = '<div class="formdyn-choice-group" id="'.self::html($id).'">';
            foreach (FormulairesDynamiquesRepository::fieldOptionsArray($field) as $option) {
                $html .= '<label><input class="formdyn-single-choice" type="checkbox" name="'.self::html($name).'" value="'.self::html($option).'"'.($value === $option ? ' checked' : '').$required.'> '.self::html($option).'</label>';
            }

            return $html.'</div>';
        }

        if ($type === 'checkboxes') {
            $defaults = is_array($value) ? $value : self::defaultValues($value);
            $html = '<div class="formdyn-choice-group" id="'.self::html($id).'">';
            foreach (FormulairesDynamiquesRepository::fieldOptionsArray($field) as $option) {
                $html .= '<label><input type="checkbox" name="'.self::html($name).'[]" value="'.self::html($option).'"'.(in_array($option, $defaults, true) ? ' checked' : '').'> '.self::html($option).'</label>';
            }

            return $html.'</div>';
        }

        if ($type === 'file') {
            return '<input class="form-control" id="'.self::html($id).'" type="file" name="'.self::html($name).'"'.$required.'>';
        }

        $inputType = in_array($type, array('email', 'number', 'date'), true) ? $type : 'text';
        return '<input class="form-control" id="'.self::html($id).'" type="'.self::html($inputType).'" name="'.self::html($name).'" value="'.self::html(self::scalarDisplayValue($value)).'"'.$required.'>';
    }

    private static function displayBehaviorScript()
    {
        return '<script>'
            .'(function(){'
            .'function valuesFor(id){var nodes=document.querySelectorAll("[name=\'field_"+id+"\'],[name=\'field_"+id+"[]\']");var values=[];for(var i=0;i<nodes.length;i++){var n=nodes[i];if((n.type==="checkbox"||n.type==="radio")&&!n.checked){continue;}values.push(n.value||"");}return values;}'
            .'function matches(values,op,expected){var joined=values.join("\\n");if(op==="empty"){return joined==="";}if(op==="not_empty"){return joined!=="";}if(op==="not_equals"){return values.indexOf(expected)<0&&joined!==expected;}if(op==="contains"){return values.indexOf(expected)>=0||joined.indexOf(expected)>=0;}if(op==="not_contains"){return values.indexOf(expected)<0&&joined.indexOf(expected)<0;}return values.indexOf(expected)>=0||joined===expected;}'
            .'function syncConditions(){var blocks=document.querySelectorAll("[data-condition-field]");for(var i=0;i<blocks.length;i++){var b=blocks[i];var ok=matches(valuesFor(b.getAttribute("data-condition-field")),b.getAttribute("data-condition-operator")||"equals",b.getAttribute("data-condition-value")||"");b.hidden=!ok;var controls=b.querySelectorAll("input,select,textarea");for(var j=0;j<controls.length;j++){controls[j].disabled=!ok;}}}'
            .'function syncSingleChoice(target){if(!target.classList||!target.classList.contains("formdyn-single-choice")||!target.checked){return;}var nodes=document.querySelectorAll("[name=\'"+target.name+"\']");for(var i=0;i<nodes.length;i++){if(nodes[i]!==target){nodes[i].checked=false;}}}'
            .'document.addEventListener("change",function(e){syncSingleChoice(e.target);syncConditions();});syncConditions();'
            .'var pages=document.querySelectorAll(".formdyn-form-page");var current=0;function showPage(i){if(!pages.length){return;}current=Math.max(0,Math.min(i,pages.length-1));for(var p=0;p<pages.length;p++){pages[p].hidden=p!==current;}}'
            .'document.addEventListener("click",function(e){if(e.target.classList.contains("formdyn-page-next")){showPage(current+1);}if(e.target.classList.contains("formdyn-page-prev")){showPage(current-1);}if(e.target.classList.contains("formdyn-copy-btn")){var input=e.target.parentNode.querySelector("input");if(input){input.select();try{document.execCommand("copy");e.target.textContent="Copie";}catch(err){}}}});'
            .'showPage(0);'
            .'})();'
            .'</script>';
    }

    private static function displayShell($content)
    {
        return '<section id="formulaires-dynamiques">'.self::assets().$content.'</section>';
    }

    private static function renderEmbeddedAdminPage()
    {
        ob_start();
        $formulaires_dynamiques_admin_embedded = true;
        include __DIR__.'/../admin.php';
        $html = ob_get_clean();

        return '<section id="formulaires-dynamiques">'.self::assets().$html.'</section>';
    }

    private static function renderCounters()
    {
        return '<div class="row formdyn-counters">'
            .self::counter('Formulaires', FormulairesDynamiquesRepository::countForms())
            .self::counter('Champs', FormulairesDynamiquesRepository::countFields())
            .self::counter('Reponses', FormulairesDynamiquesRepository::countResponses())
            .'</div>';
    }

    private static function counter($label, $value)
    {
        return '<div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">'
            .'<div class="formdyn-counter">'
                .'<span class="formdyn-counter-value">'.self::html((int) $value).'</span>'
                .'<span class="formdyn-counter-label">'.self::html($label).'</span>'
            .'</div>'
            .'</div>';
    }

    private static function renderAccessSummary($login, $canManage)
    {
        $profile = $canManage ? 'Gestionnaire module' : 'Utilisateur';
        if (!$canManage && FormulairesDynamiquesRights::hasManagedForms($login)) {
            $profile = 'Gestionnaire formulaire';
        }

        $rows = array(
            'Utilisateur courant' => $login,
            'Profil module' => $profile,
            'Pages autonomes' => FormulairesDynamiquesConfig::autonomousEnabled() ? 'Activees' : 'Desactivees',
            'Notifications' => FormulairesDynamiquesConfig::notificationsEnabled() ? 'Activees' : 'Desactivees',
        );

        $html = '<section class="formdyn-panel"><h3>Etat du module</h3><table class="table table-striped"><tbody>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><th>'.self::html($label).'</th><td>'.self::html($value).'</td></tr>';
        }

        return $html.'</tbody></table></section>';
    }

    private static function renderDisplayRouteSummary()
    {
        $integratedForm = '../app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE).'&amp;view=formulaire&amp;token=...';
        $integratedResults = '../app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE).'&amp;view=resultats&amp;token=...';
        $standaloneForm = '../personnalisation/modules/'.rawurlencode(FormulairesDynamiquesConfig::MODULE).'/public.php?view=formulaire&amp;token=...';
        $standaloneResults = '../personnalisation/modules/'.rawurlencode(FormulairesDynamiquesConfig::MODULE).'/public.php?view=resultats&amp;token=...';

        return '<section class="formdyn-panel">'
            .'<h3>Routes d affichage</h3>'
            .'<table class="table table-striped"><tbody>'
                .'<tr><th>Formulaire integre GRR</th><td><code>'.$integratedForm.'</code></td></tr>'
                .'<tr><th>Resultats integres GRR</th><td><code>'.$integratedResults.'</code></td></tr>'
                .'<tr><th>Formulaire autonome</th><td><code>'.$standaloneForm.'</code></td></tr>'
                .'<tr><th>Resultats autonomes</th><td><code>'.$standaloneResults.'</code></td></tr>'
            .'</tbody></table>'
            .'</section>';
    }

    private static function renderDisplayContext($mode, $token, $login)
    {
        $rows = array(
            'Mode' => $mode === 'autonomous' ? 'Autonome' : 'Integre GRR',
            'Jeton' => self::tokenPreview($token),
        );
        if ((string) $login !== '') {
            $rows['Utilisateur GRR'] = (string) $login;
        }

        $html = '<section class="formdyn-panel"><h3>Contexte</h3><table class="table table-striped"><tbody>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><th>'.self::html($label).'</th><td>'.self::html($value).'</td></tr>';
        }

        return $html.'</tbody></table></section>';
    }

    private static function requestedDisplayView()
    {
        $view = isset($_GET['view']) ? strtolower(trim((string) $_GET['view'])) : '';
        if ($view === '') {
            $view = isset($_GET['v']) ? strtolower(trim((string) $_GET['v'])) : '';
        }

        if (in_array($view, array('formulaire', 'form'), true)) {
            return 'formulaire';
        }
        if (in_array($view, array('resultats', 'resultat', 'results', 'responses', 'reponses'), true)) {
            return 'resultats';
        }
        if (in_array($view, array('export', 'exports'), true)) {
            return 'export';
        }

        return $view;
    }

    private static function requestedDisplayToken()
    {
        if (isset($_GET['token'])) {
            return trim((string) $_GET['token']);
        }
        if (isset($_GET['t'])) {
            return trim((string) $_GET['t']);
        }

        return '';
    }

    private static function tokenPreview($token)
    {
        $token = trim((string) $token);
        if ($token === '') {
            return '';
        }
        if (strlen($token) <= 12) {
            return $token;
        }

        return substr($token, 0, 8).'...'.substr($token, -4);
    }

    private static function resultFields($fields)
    {
        if (!is_array($fields)) {
            return array();
        }

        $resultFields = array();
        foreach ($fields as $field) {
            if (FormulairesDynamiquesRepository::fieldStoresResponse($field)) {
                $resultFields[] = $field;
            }
        }

        return $resultFields;
    }

    private static function sourceLabel($source)
    {
        $source = (string) $source;
        if ($source === 'autonomous') {
            return 'Autonome';
        }
        if ($source === 'grr') {
            return 'Integre GRR';
        }

        return $source !== '' ? $source : '-';
    }

    private static function submitterLabel($response)
    {
        if (!is_array($response)) {
            return 'Anonyme';
        }

        $name = isset($response['submitter_name']) ? trim((string) $response['submitter_name']) : '';
        $login = isset($response['submitter_login']) ? trim((string) $response['submitter_login']) : '';
        $email = isset($response['submitter_email']) ? trim((string) $response['submitter_email']) : '';

        if ($name !== '' && $login !== '') {
            return $name.' ('.$login.')';
        }
        if ($name !== '') {
            return $name;
        }
        if ($login !== '') {
            return $login;
        }
        if ($email !== '') {
            return $email;
        }

        return 'Anonyme';
    }

    private static function responsePreview($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '-';
        }
        if (strpos($value, 'uploads/form_') === 0) {
            $value = basename($value);
        }

        $value = preg_replace('/\s+/', ' ', $value);
        if (strlen($value) > 120) {
            return substr($value, 0, 117).'...';
        }

        return $value;
    }

    private static function responseValueHtml($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '<span class="text-muted">-</span>';
        }
        if (strpos($value, 'uploads/form_') === 0) {
            return '<a href="'.self::html(self::uploadUrl($value)).'" target="_blank" rel="noopener">'.self::html(basename($value)).'</a>';
        }

        return nl2br(self::html($value));
    }

    private static function uploadUrl($path)
    {
        $path = ltrim((string) $path, '/');
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        if (strpos($script, '/personnalisation/modules/'.FormulairesDynamiquesConfig::MODULE.'/') !== false) {
            return $path;
        }

        return 'personnalisation/modules/'.FormulairesDynamiquesConfig::MODULE.'/'.$path;
    }

    private static function responseMeta($mode, $login, $form = array())
    {
        $source = $mode === 'autonomous' ? 'autonomous' : 'grr';
        $login = $source === 'grr' ? trim((string) $login) : '';

        return array(
            'token_id' => (int) (isset($form['token_id']) ? $form['token_id'] : 0),
            'submitter_login' => $login,
            'source' => $source,
            'ip_hash' => self::requestIpHash(),
        );
    }

    private static function savedResponseIdForForm($form)
    {
        if (!isset($_GET['response_saved']) || $_GET['response_saved'] !== '1') {
            return 0;
        }

        $responseId = isset($_GET['response_id']) ? (int) $_GET['response_id'] : 0;
        if ($responseId <= 0) {
            return 0;
        }

        $response = FormulairesDynamiquesRepository::response($responseId);
        if (!$response || (int) $response['formulaire_id'] !== (int) (isset($form['id']) ? $form['id'] : 0)) {
            return 0;
        }

        return $responseId;
    }

    private static function redirectToResponseConfirmation($responseId)
    {
        if (headers_sent()) {
            return;
        }

        $params = $_GET;
        $params['response_saved'] = '1';
        $params['response_id'] = (int) $responseId;
        header('Location: '.self::currentDisplayUrl($params), true, 303);
        exit;
    }

    private static function displayUrl($changes = array(), $remove = array())
    {
        $params = $_GET;
        foreach ((array) $remove as $key) {
            unset($params[$key]);
        }
        foreach ((array) $changes as $key => $value) {
            $params[$key] = $value;
        }

        return self::currentDisplayUrl($params);
    }

    private static function currentDisplayUrl($params)
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        if ($script === '' && isset($_SERVER['REQUEST_URI'])) {
            $script = strtok((string) $_SERVER['REQUEST_URI'], '?');
        }
        if ($script === '') {
            $script = '';
        }

        $query = http_build_query($params, '', '&');
        return $script.($query !== '' ? '?'.$query : '');
    }

    private static function requestIpHash()
    {
        $address = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        if ($address === '') {
            return '';
        }

        return hash('sha256', $address.'|formulaires_dynamiques');
    }

    private static function flattenResponseErrors($fieldErrors)
    {
        if (!is_array($fieldErrors) || count($fieldErrors) === 0) {
            return array();
        }

        $messages = array();
        foreach ($fieldErrors as $errors) {
            if (!is_array($errors)) {
                $errors = array($errors);
            }
            foreach ($errors as $error) {
                $error = trim((string) $error);
                if ($error !== '') {
                    $messages[$error] = $error;
                }
            }
        }

        return array_values($messages);
    }

    private static function firstFieldError($fieldId, $fieldErrors)
    {
        $fieldId = (int) $fieldId;
        if (!is_array($fieldErrors) || !isset($fieldErrors[$fieldId]) || !is_array($fieldErrors[$fieldId])) {
            return '';
        }

        foreach ($fieldErrors[$fieldId] as $error) {
            $error = trim((string) $error);
            if ($error !== '') {
                return $error;
            }
        }

        return '';
    }

    private static function scalarDisplayValue($value)
    {
        return is_array($value) ? '' : (string) $value;
    }

    private static function defaultValues($value)
    {
        $tokens = preg_split('/[\r\n,;]+/', (string) $value);
        $values = array();
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $values[$token] = $token;
            }
        }

        return array_values($values);
    }

    private static function assets()
    {
        return '<style>'
            .'#formulaires-dynamiques{margin:0 auto;max-width:1200px;white-space:normal;}'
            .'#formulaires-dynamiques [hidden]{display:none!important;}'
            .'#formulaires-dynamiques .formdyn-actions{margin:12px 0 18px;}'
            .'#formulaires-dynamiques .formdyn-tabs{display:flex;gap:6px;flex-wrap:wrap;margin:10px 0 18px;border-bottom:1px solid #ddd;}'
            .'#formulaires-dynamiques .formdyn-tabs a{display:inline-block;padding:8px 10px;border:1px solid #ddd;border-bottom:0;background:#f7f7f7;color:#222;text-decoration:none;}'
            .'#formulaires-dynamiques .formdyn-tabs a.active{background:#fff;font-weight:bold;}'
            .'#formulaires-dynamiques .formdyn-counters{margin-bottom:18px;}'
            .'#formulaires-dynamiques .formdyn-counter{border:1px solid #ddd;background:#fff;padding:14px;margin-bottom:10px;}'
            .'#formulaires-dynamiques .formdyn-counter-value{display:block;font-size:28px;font-weight:bold;line-height:1.1;}'
            .'#formulaires-dynamiques .formdyn-counter-label{display:block;color:#555;margin-top:4px;}'
            .'#formulaires-dynamiques .formdyn-panel{border:1px solid #ddd;background:#fff;padding:14px;margin-bottom:18px;}'
            .'#formulaires-dynamiques .formdyn-panel h3{margin-top:0;}'
            .'#formulaires-dynamiques .alert{padding:10px 12px;margin:10px 0;border:1px solid transparent;background:#eef5fb;}'
            .'#formulaires-dynamiques .alert-warning{background:#fcf8e3;border-color:#faebcc;color:#6b5f2a;}'
            .'#formulaires-dynamiques .alert-info{background:#eef5fb;border-color:#c7dceb;color:#245269;}'
            .'#formulaires-dynamiques .alert-success{background:#dff0d8;border-color:#d6e9c6;color:#2b542c;}'
            .'#formulaires-dynamiques .alert-danger{background:#f2dede;border-color:#ebccd1;color:#843534;}'
            .'#formulaires-dynamiques .formdyn-form-grid{display:grid;grid-template-columns:minmax(0,1fr) 180px;gap:12px;}'
            .'#formulaires-dynamiques .formdyn-field-grid{display:grid;grid-template-columns:minmax(0,1fr) 220px 110px;gap:12px;}'
            .'#formulaires-dynamiques .formdyn-notification-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(220px,1fr);gap:12px;}'
            .'#formulaires-dynamiques .formdyn-filter-grid{display:grid;grid-template-columns:minmax(220px,2fr) minmax(140px,1fr) minmax(130px,1fr) minmax(130px,1fr) 110px;gap:12px;align-items:end;}'
            .'#formulaires-dynamiques .formdyn-token-create-grid,#formulaires-dynamiques .formdyn-tool-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin:12px 0;}'
            .'#formulaires-dynamiques .formdyn-token-create-grid form,#formulaires-dynamiques .formdyn-tool-grid form{border:1px solid #ddd;padding:10px;background:#fafafa;}'
            .'#formulaires-dynamiques .formdyn-token-options{display:grid;grid-template-columns:minmax(0,1fr) 130px;gap:10px;}'
            .'#formulaires-dynamiques .formdyn-notification-condition{border-top:1px solid #ddd;margin-top:10px;padding-top:10px;}'
            .'#formulaires-dynamiques .formdyn-subpanel{border-top:1px solid #ddd;margin-top:12px;padding-top:12px;}'
            .'#formulaires-dynamiques .formdyn-checks{display:flex;gap:18px;flex-wrap:wrap;margin:8px 0;}'
            .'#formulaires-dynamiques .formdyn-help{color:#666;font-size:12px;margin:-3px 0 8px;}'
            .'#formulaires-dynamiques .formdyn-inline-form{display:inline-block;margin:0 0 0 6px;}'
            .'#formulaires-dynamiques .formdyn-fields-table small{color:#666;}'
            .'#formulaires-dynamiques label{display:block;margin:8px 0;}'
            .'#formulaires-dynamiques .form-control{display:block;width:100%;max-width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid #ccc;background:#fff;}'
            .'#formulaires-dynamiques textarea.form-control{max-width:100%;}'
            .'#formulaires-dynamiques .btn{display:inline-block;padding:6px 10px;border:1px solid #999;background:#eee;color:#222;text-decoration:none;cursor:pointer;}'
            .'#formulaires-dynamiques .btn-primary{background:#337ab7;border-color:#2e6da4;color:#fff;}'
            .'#formulaires-dynamiques .btn-default{background:#fff;border-color:#ccc;color:#333;}'
            .'#formulaires-dynamiques .btn-danger{background:#d9534f;border-color:#d43f3a;color:#fff;}'
            .'#formulaires-dynamiques .btn[disabled],#formulaires-dynamiques .btn.disabled{opacity:.65;cursor:not-allowed;pointer-events:none;}'
            .'#formulaires-dynamiques .label{display:inline-block;padding:3px 6px;color:#fff;background:#777;}'
            .'#formulaires-dynamiques .label-success{background:#5cb85c;}'
            .'#formulaires-dynamiques .label-warning{background:#f0ad4e;}'
            .'#formulaires-dynamiques .label-default{background:#777;}'
            .'#formulaires-dynamiques .formdyn-public-form{max-width:900px;margin:0 auto;}'
            .'#formulaires-dynamiques .formdyn-description{margin:8px 0 16px;color:#444;}'
            .'#formulaires-dynamiques .formdyn-display-grid{display:grid;gap:12px 18px;align-items:start;}'
            .'#formulaires-dynamiques .formdyn-display-grid-1{grid-template-columns:minmax(0,1fr);}'
            .'#formulaires-dynamiques .formdyn-display-grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}'
            .'#formulaires-dynamiques .formdyn-display-grid-3{grid-template-columns:repeat(3,minmax(0,1fr));}'
            .'#formulaires-dynamiques .formdyn-display-grid-4{grid-template-columns:repeat(4,minmax(0,1fr));}'
            .'#formulaires-dynamiques .formdyn-display-full{grid-column:1/-1;}'
            .'#formulaires-dynamiques .formdyn-display-field{margin:14px 0;}'
            .'#formulaires-dynamiques .formdyn-display-grid .formdyn-display-field{margin:0 0 10px;}'
            .'#formulaires-dynamiques .formdyn-display-field>label{font-weight:bold;}'
            .'#formulaires-dynamiques .formdyn-field-error{color:#a94442;font-size:12px;margin:4px 0 0;}'
            .'#formulaires-dynamiques .formdyn-choice-group label{font-weight:normal;margin:6px 0;}'
            .'#formulaires-dynamiques .formdyn-display-separator{border-top:1px solid #ddd;margin:22px 0 12px;padding-top:10px;}'
            .'#formulaires-dynamiques .formdyn-display-empty{min-height:1px;}'
            .'#formulaires-dynamiques .formdyn-display-image{margin:18px 0;text-align:center;}'
            .'#formulaires-dynamiques .formdyn-display-image img{max-width:100%;height:auto;border:1px solid #ddd;background:#fff;}'
            .'#formulaires-dynamiques .formdyn-display-image figcaption{color:#666;font-size:13px;margin-top:6px;}'
            .'#formulaires-dynamiques .formdyn-copy-line{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:6px;align-items:center;}'
            .'#formulaires-dynamiques .formdyn-qr img{margin:8px 0;border:1px solid #ddd;background:#fff;}'
            .'#formulaires-dynamiques .formdyn-form-page{border:0;margin:0;padding:0;}'
            .'#formulaires-dynamiques .formdyn-form-page legend{font-size:18px;font-weight:bold;margin:12px 0;}'
            .'#formulaires-dynamiques .formdyn-page-actions{margin:16px 0;}'
            .'#formulaires-dynamiques .formdyn-required{color:#a94442;}'
            .'#formulaires-dynamiques .formdyn-response-ref{color:#555;}'
            .'#formulaires-dynamiques .formdyn-results-table th,#formulaires-dynamiques .formdyn-results-table td{min-width:120px;}'
            .'#formulaires-dynamiques .formdyn-results-table th:first-child,#formulaires-dynamiques .formdyn-results-table td:first-child{min-width:80px;}'
            .'#formulaires-dynamiques .formdyn-response-detail th{width:240px;}'
            .'#formulaires-dynamiques .formdyn-result-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;}'
            .'#formulaires-dynamiques .formdyn-result-card{border:1px solid #ddd;background:#fff;padding:12px;margin-bottom:12px;}'
            .'#formulaires-dynamiques .formdyn-pagination{display:flex;gap:10px;align-items:center;margin:14px 0;}'
            .'#formulaires-dynamiques .formdyn-drag-handle{width:30px;text-align:center;cursor:move;color:#555;font-weight:bold;}'
            .'#formulaires-dynamiques .formdyn-dragging{opacity:.55;}'
            .'#formulaires-dynamiques table{width:100%;border-collapse:collapse;}'
            .'#formulaires-dynamiques th,#formulaires-dynamiques td{border-top:1px solid #ddd;padding:7px;text-align:left;vertical-align:top;}'
            .'#formulaires-dynamiques code{white-space:normal;overflow-wrap:anywhere;}'
            .'@media (max-width:767px){#formulaires-dynamiques .formdyn-form-grid,#formulaires-dynamiques .formdyn-field-grid,#formulaires-dynamiques .formdyn-notification-grid,#formulaires-dynamiques .formdyn-filter-grid,#formulaires-dynamiques .formdyn-token-options,#formulaires-dynamiques .formdyn-display-grid{grid-template-columns:minmax(0,1fr);}}'
            .'</style>'
            .'<script>(function(){document.addEventListener("click",function(e){if(!e.target.classList.contains("formdyn-copy-btn")){return;}var input=e.target.parentNode.querySelector("input");if(!input){return;}input.select();try{document.execCommand("copy");e.target.textContent="Copie";}catch(err){}});})();</script>';
    }

    public static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
