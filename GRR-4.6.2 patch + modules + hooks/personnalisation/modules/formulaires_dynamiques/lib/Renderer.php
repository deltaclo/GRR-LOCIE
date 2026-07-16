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

        $html = '<section id="formulaires-dynamiques">'
            .self::assets()
            .'<h2>Gestion - '.self::html(FormulairesDynamiquesConfig::displayName()).'</h2>'
            .'<div class="formdyn-actions">'
                .($canManage ? '<a class="btn btn-primary" href="'.self::html($adminUrl).'">Configuration du module</a>' : '')
            .'</div>'
            .self::renderAlerts($messages, 'success')
            .self::renderAlerts($errors, 'danger')
            .self::renderCounters()
            .self::renderFormEditor($postedValues, $login)
            .self::renderFormLinksPanel($login)
            .self::renderFormManagersPanel($login)
            .self::renderNotificationPanel($postedNotificationValues, $login)
            .self::renderFieldBuilder($postedFieldValues, $login)
            .self::renderHistoryPanel($login)
            .self::renderFormsTable($login)
            .self::renderAccessSummary($login, $canManage)
            .self::renderDisplayRouteSummary()
            .'</section>';

        return $html;
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

        if ($action !== 'save_form') {
            return $result;
        }

        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        $values = FormulairesDynamiquesRepository::normalizeFormValues($_POST);
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

            self::redirectToManagement(array('form_id' => $formId, 'saved' => 1));
        }

        $createdId = FormulairesDynamiquesRepository::createForm($values, $login);
        if ($createdId <= 0) {
            $result['errors'][] = 'Le formulaire n a pas pu etre cree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $createdId, 'created' => 1));
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

            self::redirectToManagement(array('form_id' => $formId, 'field_id' => $fieldId, 'field_saved' => 1));
        }

        $createdId = FormulairesDynamiquesRepository::createField($formId, $values, $login);
        if ($createdId <= 0) {
            $result['errors'][] = 'Le champ n a pas pu etre cree.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'field_id' => $createdId, 'field_created' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'field_disabled' => 1));
        return $result;
    }

    private static function handleFormTokenCreate($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRepository::form($formId)) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $token = FormulairesDynamiquesRepository::createToken($formId, 'formulaire', $login);
        if ($token === '') {
            $result['errors'][] = 'Le lien formulaire n a pas pu etre genere.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'form_token' => $token, 'token_created' => 1));
        return $result;
    }

    private static function handleResultsTokenCreate($login, $result)
    {
        $formId = isset($_POST['form_id']) ? (int) $_POST['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRepository::form($formId)) {
            $result['errors'][] = 'Le formulaire est introuvable.';
            return $result;
        }

        $token = FormulairesDynamiquesRepository::createToken($formId, 'resultats', $login);
        if ($token === '') {
            $result['errors'][] = 'Le lien resultats n a pas pu etre genere.';
            return $result;
        }

        self::redirectToManagement(array('form_id' => $formId, 'results_token' => $token, 'results_token_created' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'notification_created' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'notification_disabled' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'manager_added' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'manager_removed' => 1));
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

        self::redirectToManagement(array('form_id' => $formId, 'token_disabled' => 1));
        return $result;
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

        if (in_array($action, array(
            'save_field',
            'disable_field',
            'generate_form_token',
            'generate_results_token',
            'save_notification',
            'disable_notification',
            'add_form_manager',
            'remove_form_manager',
            'disable_token',
        ), true)) {
            return $formId > 0 && FormulairesDynamiquesRights::canManageForm($login, $formId);
        }

        return false;
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
            .'<form method="post" action="'.self::html(self::managementUrl()).'">'
            .'<input type="hidden" name="formdyn_action" value="save_form">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $values['id']).'">'
            .'<div class="formdyn-form-grid">'
                .'<label>Titre<br><input class="form-control" type="text" name="titre" maxlength="190" required value="'.self::html($values['titre']).'"></label>'
                .'<label>Statut<br><select class="form-control" name="statut">'.self::statusOptionsHtml($values['statut']).'</select></label>'
            .'</div>'
            .'<label>Description<br><textarea class="form-control" name="description" rows="4">'.self::html($values['description']).'</textarea></label>'
            .'<p class="formdyn-actions">'
                .'<button class="btn btn-primary" type="submit">'.($editing ? 'Enregistrer' : 'Creer le formulaire').'</button>'
                .($editing ? ' <a class="btn btn-default" href="'.self::html(self::managementUrl()).'">Nouveau formulaire</a>' : '')
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
            $html .= '<div class="alert alert-info">Copiez ces liens maintenant. Pour des raisons de securite, le jeton complet ne sera pas reaffiche ensuite.</div>'
                .'<label>Affichage integre GRR<br><input class="form-control" readonly value="'.self::html($integratedUrl).'"></label>'
                .'<label>Affichage autonome<br><input class="form-control" readonly value="'.self::html($standaloneUrl).'"></label>';
        }

        if ($newResultsToken !== '') {
            $integratedUrl = self::resultsDisplayUrl($newResultsToken, true);
            $standaloneUrl = self::resultsDisplayUrl($newResultsToken, false);
            $html .= '<div class="alert alert-info">Copiez ces liens de resultats maintenant. Le jeton complet ne sera pas reaffiche ensuite.</div>'
                .'<label>Resultats integres GRR<br><input class="form-control" readonly value="'.self::html($integratedUrl).'"></label>'
                .'<label>Resultats autonomes<br><input class="form-control" readonly value="'.self::html($standaloneUrl).'"></label>';
        }

        $html .= '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="generate_form_token">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<button class="btn btn-primary" type="submit">Generer un lien formulaire</button>'
            .'</form>'
            .'<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="generate_results_token">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<button class="btn btn-default" type="submit">Generer un lien resultats</button>'
            .'</form>'
            .self::renderTokensTable($formId)
            .'</section>';

        return $html;
    }

    private static function renderTokensTable($formId)
    {
        $tokens = FormulairesDynamiquesRepository::tokens($formId, true);
        if (count($tokens) === 0) {
            return '<div class="alert alert-info">Aucun jeton genere pour ce formulaire.</div>';
        }

        $html = '<div class="formdyn-subpanel"><h4>Jetons</h4>'
            .'<div class="table-responsive"><table class="table table-striped">'
            .'<thead><tr><th>Type</th><th>Cree le</th><th>Etat</th><th>Actions</th></tr></thead><tbody>';
        foreach ($tokens as $token) {
            $active = isset($token['actif']) && (int) $token['actif'] === 1;
            $type = isset($token['type_token']) ? (string) $token['type_token'] : '';
            $html .= '<tr'.($active ? '' : ' class="text-muted"').'>'
                .'<td>'.self::html($type === 'resultats' ? 'Resultats' : 'Formulaire').'</td>'
                .'<td>'.self::html(self::formatDate(isset($token['created_at']) ? $token['created_at'] : 0)).'</td>'
                .'<td>'.($active ? '<span class="label label-success">Actif</span>' : '<span class="label label-default">Inactif</span>').'</td>'
                .'<td>'.($active ? self::disableTokenForm($formId, (int) $token['id']) : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div></div>';
    }

    private static function disableTokenForm($formId, $tokenId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_token">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="token_id" value="'.self::html((int) $tokenId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce jeton ?\');">Desactiver</button>'
            .'</form>';
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
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="add_form_manager">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<div class="formdyn-notification-grid">'
                .'<label>Login GRR<br><input class="form-control" type="text" name="manager_login" maxlength="190" required></label>'
                .'<div class="formdyn-help">Le login doit correspondre a un utilisateur GRR existant.</div>'
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
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
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
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_notification">'
            .'<input type="hidden" name="form_id" value="'.self::html($formId).'">'
            .'<input type="hidden" name="actif" value="1">'
            .'<div class="formdyn-notification-grid">'
                .'<label>Nom<br><input class="form-control" type="text" name="nom" maxlength="190" value="'.self::html($values['nom']).'"></label>'
                .'<label>Email<br><input class="form-control" type="email" name="email" maxlength="190" required value="'.self::html($values['email']).'"></label>'
            .'</div>'
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
            'actif' => 1,
        );

        if (is_array($postedNotificationValues) && count($postedNotificationValues) > 0) {
            return array_merge($defaults, FormulairesDynamiquesRepository::normalizeNotificationValues($postedNotificationValues));
        }

        return $defaults;
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
                .'<th>Etat</th>'
                .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($recipients as $recipient) {
            $recipientId = (int) (isset($recipient['id']) ? $recipient['id'] : 0);
            $active = isset($recipient['actif']) && (int) $recipient['actif'] === 1;
            $html .= '<tr'.($active ? '' : ' class="text-muted"').'>'
                .'<td>'.self::html(isset($recipient['nom']) ? $recipient['nom'] : '').'</td>'
                .'<td>'.self::html(isset($recipient['email']) ? $recipient['email'] : '').'</td>'
                .'<td>'.($active ? '<span class="label label-success">Actif</span>' : '<span class="label label-default">Inactif</span>').'</td>'
                .'<td>'.($active ? self::disableNotificationForm((int) $formId, $recipientId) : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function disableNotificationForm($formId, $notificationId)
    {
        return '<form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_notification">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="notification_id" value="'.self::html((int) $notificationId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce destinataire ?\');">Desactiver</button>'
            .'</form>';
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
            .'<form method="post" action="'.self::html(self::managementUrl(array('form_id' => (int) $form['id']))).'">'
            .'<input type="hidden" name="formdyn_action" value="save_field">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $form['id']).'">'
            .'<input type="hidden" name="field_id" value="'.self::html((int) $values['id']).'">'
            .'<input type="hidden" name="obligatoire" value="0">'
            .'<input type="hidden" name="actif" value="0">'
            .'<div class="formdyn-field-grid">'
                .'<label>Libelle<br><input class="form-control" type="text" name="libelle" maxlength="190" required value="'.self::html($values['libelle']).'"></label>'
                .'<label>Type<br><select class="form-control" name="type_champ">'.self::fieldTypeOptionsHtml($values['type_champ']).'</select></label>'
                .'<label>Ordre<br><input class="form-control" type="number" min="0" name="ordre" value="'.self::html((int) $values['ordre']).'"></label>'
            .'</div>'
            .'<label>Aide<br><textarea class="form-control" name="aide" rows="2">'.self::html($values['aide']).'</textarea></label>'
            .'<label>Options<br><textarea class="form-control" name="options" rows="4">'.self::html($values['options']).'</textarea></label>'
            .'<div class="formdyn-help">Une option par ligne pour liste deroulante, choix unique ou choix multiples.</div>'
            .'<label>Valeur par defaut<br><input class="form-control" type="text" name="valeur_defaut" value="'.self::html($values['valeur_defaut']).'"></label>'
            .'<div class="formdyn-checks">'
                .'<label><input type="checkbox" name="obligatoire" value="1"'.((int) $values['obligatoire'] === 1 ? ' checked' : '').'> Obligatoire</label>'
                .'<label><input type="checkbox" name="actif" value="1"'.((int) $values['actif'] === 1 ? ' checked' : '').'> Actif</label>'
            .'</div>'
            .'<p class="formdyn-actions">'
                .'<button class="btn btn-primary" type="submit">'.($editing ? 'Enregistrer le champ' : 'Ajouter le champ').'</button>'
                .($editing ? ' <a class="btn btn-default" href="'.self::html(self::managementUrl(array('form_id' => (int) $form['id']))).'">Nouveau champ</a>' : '')
            .'</p>'
            .'</form>'
            .'</div>';

        return $html;
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

        $html = '<div class="table-responsive"><table class="table table-striped formdyn-fields-table">'
            .'<thead><tr>'
                .'<th>Ordre</th>'
                .'<th>Libelle</th>'
                .'<th>Type</th>'
                .'<th>Obligatoire</th>'
                .'<th>Etat</th>'
                .'<th>Actions</th>'
            .'</tr></thead><tbody>';

        foreach ($fields as $field) {
            $fieldId = isset($field['id']) ? (int) $field['id'] : 0;
            $active = isset($field['actif']) && (int) $field['actif'] === 1;
            $html .= '<tr'.($active ? '' : ' class="text-muted"').'>'
                .'<td>'.self::html((int) (isset($field['ordre']) ? $field['ordre'] : 0)).'</td>'
                .'<td><strong>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</strong>'.self::fieldOptionsPreview($field).'</td>'
                .'<td>'.self::html(FormulairesDynamiquesRepository::fieldTypeLabel(isset($field['type_champ']) ? $field['type_champ'] : '')).'</td>'
                .'<td>'.((isset($field['obligatoire']) && (int) $field['obligatoire'] === 1) ? 'Oui' : 'Non').'</td>'
                .'<td>'.($active ? '<span class="label label-success">Actif</span>' : '<span class="label label-default">Inactif</span>').'</td>'
                .'<td>'
                    .'<a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => (int) $form['id'], 'field_id' => $fieldId))).'">Editer</a>'
                    .($active ? self::disableFieldForm((int) $form['id'], $fieldId) : '')
                .'</td>'
            .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function fieldOptionsPreview($field)
    {
        $options = FormulairesDynamiquesRepository::fieldOptionsArray($field);
        if (count($options) === 0) {
            return '';
        }

        return '<br><small>'.self::html(implode(', ', $options)).'</small>';
    }

    private static function disableFieldForm($formId, $fieldId)
    {
        return ' <form class="formdyn-inline-form" method="post" action="'.self::html(self::managementUrl(array('form_id' => $formId))).'">'
            .'<input type="hidden" name="formdyn_action" value="disable_field">'
            .'<input type="hidden" name="form_id" value="'.self::html((int) $formId).'">'
            .'<input type="hidden" name="field_id" value="'.self::html((int) $fieldId).'">'
            .'<button class="btn btn-default btn-sm" type="submit" onclick="return confirm(\'Desactiver ce champ ?\');">Desactiver</button>'
            .'</form>';
    }

    private static function currentFormEditorValues($postedValues)
    {
        $defaults = array(
            'id' => 0,
            'titre' => '',
            'description' => '',
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
                .'<td><a class="btn btn-default btn-sm" href="'.self::html(self::managementUrl(array('form_id' => $formId))).'">Editer</a></td>'
            .'</tr>';
        }

        return $html.'</tbody></table></div></section>';
    }

    private static function renderHistoryPanel($login = '')
    {
        $formId = isset($_GET['form_id']) ? (int) $_GET['form_id'] : 0;
        if ($formId <= 0 || !FormulairesDynamiquesRights::canManageForm($login, $formId)) {
            return '';
        }

        $history = FormulairesDynamiquesRepository::history($formId, 50);
        $html = '<section class="formdyn-panel"><h3>Historique recent</h3>';
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
            'notification' => 'Notification',
            'retrait_gestionnaire' => 'Retrait gestionnaire',
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

        self::loadExportEngine();
        if (!class_exists('FormulairesDynamiquesExport')) {
            return self::displayShell(
                '<h2>Export</h2>'
                .'<div class="alert alert-danger">Le moteur d export est introuvable.</div>'
            );
        }

        $fields = self::resultFields(FormulairesDynamiquesRepository::fields((int) $form['id'], true));
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

        $fields = self::resultFields(FormulairesDynamiquesRepository::fields((int) $form['id'], true));
        $responseId = isset($_GET['response_id']) ? (int) $_GET['response_id'] : 0;
        if ($responseId > 0) {
            return self::renderResponseDetail($form, $fields, $responseId);
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
        $rows = array(
            'Mode' => $mode === 'autonomous' ? 'Autonome' : 'Integre GRR',
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

    private static function renderResponseDetail($form, $fields, $responseId)
    {
        $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
        if (!$response || (int) $response['formulaire_id'] !== (int) (isset($form['id']) ? $form['id'] : 0)) {
            return '<article class="formdyn-results">'
                .'<h2>Resultats - '.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
                .'<div class="alert alert-warning">Cette reponse est introuvable pour ce formulaire.</div>'
                .'<p><a class="btn btn-default" href="'.self::html(self::displayUrl(array(), array('response_id'))).'">Retour aux resultats</a></p>'
                .'</article>';
        }

        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        $html = '<article class="formdyn-results">'
            .'<h2>Reponse #'.self::html((int) $responseId).' - '.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>'
            .'<p><a class="btn btn-default" href="'.self::html(self::displayUrl(array(), array('response_id'))).'">Retour aux resultats</a></p>'
            .self::renderResponseExportActions($responseId)
            .'<table class="table table-striped"><tbody>'
                .'<tr><th>Date</th><td>'.self::html(self::formatDate(isset($response['created_at']) ? $response['created_at'] : 0)).'</td></tr>'
                .'<tr><th>Source</th><td>'.self::html(self::sourceLabel(isset($response['source']) ? $response['source'] : '')).'</td></tr>'
                .'<tr><th>Declarant</th><td>'.self::html(self::submitterLabel($response)).'</td></tr>'
            .'</tbody></table>';

        if (count($fields) === 0) {
            return $html.'<div class="alert alert-info">Aucun champ a afficher.</div></article>';
        }

        $html .= '<table class="table table-striped formdyn-response-detail"><thead><tr><th>Champ</th><th>Valeur</th></tr></thead><tbody>';
        foreach ($fields as $field) {
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $html .= '<tr>'
                .'<th>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</th>'
                .'<td>'.self::responseValueHtml(isset($values[$fieldId]) ? $values[$fieldId] : '').'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></article>';
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

        $html = '<article class="formdyn-public-form">'
            .'<h2>'.self::html(isset($form['titre']) ? $form['titre'] : 'Formulaire').'</h2>';
        if (isset($form['description']) && trim((string) $form['description']) !== '') {
            $html .= '<div class="formdyn-description">'.nl2br(self::html($form['description'])).'</div>';
        }

        if (count($fields) === 0) {
            return $html.'<div class="alert alert-info">Ce formulaire ne contient encore aucun champ actif.</div></article>';
        }

        $errors = array_merge(
            $responseResult['errors'],
            self::flattenResponseErrors($responseResult['field_errors'])
        );
        $html .= self::renderAlerts($errors, 'danger')
            .'<form method="post" action="">'
            .'<input type="hidden" name="formdyn_action" value="submit_response">';
        foreach ($fields as $field) {
            $html .= self::renderDisplayField(
                $field,
                $responseResult['values'],
                $responseResult['field_errors'],
                $responseResult['submitted']
            );
        }
        $html .= '<p class="formdyn-actions"><button class="btn btn-primary" type="submit">Envoyer</button></p>'
            .'</form>'
            .'</article>';

        return $html;
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
        $result['values'] = FormulairesDynamiquesRepository::normalizeResponseValues($fields, $_POST);
        $result['field_errors'] = FormulairesDynamiquesRepository::validateResponseValues($fields, $result['values']);
        if (count($result['field_errors']) > 0) {
            return $result;
        }

        $responseId = FormulairesDynamiquesRepository::createResponse(
            (int) (isset($form['id']) ? $form['id'] : 0),
            $fields,
            $result['values'],
            self::responseMeta($mode, $login)
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
        if ($type === 'separator') {
            return '<div class="formdyn-display-separator"><h3>'.self::html(isset($field['libelle']) ? $field['libelle'] : '').'</h3></div>';
        }

        $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
        $id = 'formdyn-field-'.$fieldId;
        $name = 'field_'.$fieldId;
        $label = isset($field['libelle']) ? (string) $field['libelle'] : '';
        $required = isset($field['obligatoire']) && (int) $field['obligatoire'] === 1;
        $requiredAttr = $required ? ' required' : '';
        $default = isset($field['valeur_defaut']) ? (string) $field['valeur_defaut'] : '';
        $value = $submitted && array_key_exists($fieldId, $values) ? $values[$fieldId] : $default;
        $error = self::firstFieldError($fieldId, $fieldErrors);
        $html = '<div class="formdyn-display-field">'
            .'<label for="'.self::html($id).'">'.self::html($label).($required ? ' <span class="formdyn-required">*</span>' : '').'</label>'
            .self::renderDisplayControl($type, $id, $name, $field, $value, $requiredAttr);

        if (isset($field['aide']) && trim((string) $field['aide']) !== '') {
            $html .= '<div class="formdyn-help">'.self::html($field['aide']).'</div>';
        }
        if ($error !== '') {
            $html .= '<div class="formdyn-field-error">'.self::html($error).'</div>';
        }

        return $html.'</div>';
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
                $html .= '<label><input type="radio" name="'.self::html($name).'" value="'.self::html($option).'"'.($value === $option ? ' checked' : '').$required.'> '.self::html($option).'</label>';
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

        $inputType = in_array($type, array('email', 'number', 'date'), true) ? $type : 'text';
        return '<input class="form-control" id="'.self::html($id).'" type="'.self::html($inputType).'" name="'.self::html($name).'" value="'.self::html(self::scalarDisplayValue($value)).'"'.$required.'>';
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
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type !== 'separator') {
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

        return nl2br(self::html($value));
    }

    private static function responseMeta($mode, $login)
    {
        $source = $mode === 'autonomous' ? 'autonomous' : 'grr';
        $login = $source === 'grr' ? trim((string) $login) : '';

        return array(
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
            .'#formulaires-dynamiques .formdyn-actions{margin:12px 0 18px;}'
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
            .'#formulaires-dynamiques .btn[disabled]{opacity:.65;cursor:not-allowed;}'
            .'#formulaires-dynamiques .label{display:inline-block;padding:3px 6px;color:#fff;background:#777;}'
            .'#formulaires-dynamiques .label-success{background:#5cb85c;}'
            .'#formulaires-dynamiques .label-warning{background:#f0ad4e;}'
            .'#formulaires-dynamiques .label-default{background:#777;}'
            .'#formulaires-dynamiques .formdyn-public-form{max-width:900px;margin:0 auto;}'
            .'#formulaires-dynamiques .formdyn-description{margin:8px 0 16px;color:#444;}'
            .'#formulaires-dynamiques .formdyn-display-field{margin:14px 0;}'
            .'#formulaires-dynamiques .formdyn-display-field>label{font-weight:bold;}'
            .'#formulaires-dynamiques .formdyn-field-error{color:#a94442;font-size:12px;margin:4px 0 0;}'
            .'#formulaires-dynamiques .formdyn-choice-group label{font-weight:normal;margin:6px 0;}'
            .'#formulaires-dynamiques .formdyn-display-separator{border-top:1px solid #ddd;margin:22px 0 12px;padding-top:10px;}'
            .'#formulaires-dynamiques .formdyn-required{color:#a94442;}'
            .'#formulaires-dynamiques .formdyn-response-ref{color:#555;}'
            .'#formulaires-dynamiques .formdyn-results-table th,#formulaires-dynamiques .formdyn-results-table td{min-width:120px;}'
            .'#formulaires-dynamiques .formdyn-results-table th:first-child,#formulaires-dynamiques .formdyn-results-table td:first-child{min-width:80px;}'
            .'#formulaires-dynamiques .formdyn-response-detail th{width:240px;}'
            .'#formulaires-dynamiques .formdyn-pagination{display:flex;gap:10px;align-items:center;margin:14px 0;}'
            .'#formulaires-dynamiques table{width:100%;border-collapse:collapse;}'
            .'#formulaires-dynamiques th,#formulaires-dynamiques td{border-top:1px solid #ddd;padding:7px;text-align:left;vertical-align:top;}'
            .'#formulaires-dynamiques code{white-space:normal;overflow-wrap:anywhere;}'
            .'@media (max-width:767px){#formulaires-dynamiques .formdyn-form-grid,#formulaires-dynamiques .formdyn-field-grid,#formulaires-dynamiques .formdyn-notification-grid,#formulaires-dynamiques .formdyn-filter-grid{grid-template-columns:minmax(0,1fr);}}'
            .'</style>';
    }

    public static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
