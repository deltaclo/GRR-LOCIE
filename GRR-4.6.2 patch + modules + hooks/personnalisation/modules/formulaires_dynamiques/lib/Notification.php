<?php

class FormulairesDynamiquesNotification
{
    public static function mailEnabled()
    {
        if (!class_exists('Settings') || !FormulairesDynamiquesConfig::notificationsEnabled()) {
            return false;
        }

        $method = Settings::get('grr_mail_method');

        return Settings::get('automatic_mail') === 'yes'
            && ($method === 'smtp' || $method === 'mail');
    }

    public static function notifyResponseCreated($form, $responseId)
    {
        $result = array(
            'sent' => 0,
            'skipped' => 0,
            'errors' => array(),
        );

        $formId = (int) (isset($form['id']) ? $form['id'] : 0);
        $responseId = (int) $responseId;
        if ($formId <= 0 || $responseId <= 0) {
            $result['errors'][] = 'Contexte de notification invalide.';
            return $result;
        }

        if (!FormulairesDynamiquesConfig::notificationsEnabled()) {
            $result['skipped']++;
            return $result;
        }

        $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
        if (!$response) {
            $response = array(
                'id' => $responseId,
                'created_at' => time(),
                'source' => '',
                'values' => array(),
            );
        }
        $fields = FormulairesDynamiquesRepository::fields($formId, true);
        $recipients = self::matchingRecipients($formId, isset($response['values']) ? $response['values'] : array());
        $confirmation = self::confirmationRecipient($form, $response, $fields);
        if (count($recipients) === 0 && !$confirmation) {
            $result['skipped']++;
            return $result;
        }

        if (!self::mailEnabled()) {
            $result['errors'][] = 'Les mails GRR ne sont pas actifs.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }

        if (!self::loadMailClass()) {
            $result['errors'][] = 'La classe mail GRR est introuvable.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }

        self::initMailGlobals();
        $from = self::mailFrom();
        if ($from === '') {
            $result['errors'][] = 'Adresse expediteur GRR introuvable.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }

        $subject = self::subject($form, $response, $fields);
        $message = self::message($form, $response, $fields);

        foreach ($recipients as $recipient) {
            $email = isset($recipient['email']) ? trim((string) $recipient['email']) : '';
            if (!self::validEmail($email)) {
                $result['errors'][] = 'Adresse email invalide : '.$email;
                continue;
            }

            $mail = self::sendGrrMail(
                $email,
                $subject,
                $message,
                $from,
                'formulaires_dynamiques_reponse',
                $responseId
            );

            if (is_array($mail) && !empty($mail['success'])) {
                $result['sent']++;
            } else {
                $error = is_array($mail) && isset($mail['error']) ? (string) $mail['error'] : 'Erreur inconnue.';
                $result['errors'][] = $email.' : '.$error;
            }
        }

        if ($confirmation) {
            $mail = self::sendGrrMail(
                $confirmation['email'],
                self::confirmationSubject($form, $response),
                self::confirmationMessage($form, $response, $fields),
                $from,
                'formulaires_dynamiques_confirmation',
                $responseId
            );

            if (is_array($mail) && !empty($mail['success'])) {
                $result['sent']++;
            } else {
                $error = is_array($mail) && isset($mail['error']) ? (string) $mail['error'] : 'Erreur inconnue.';
                $result['errors'][] = $confirmation['email'].' : '.$error;
            }
        }

        self::logResult($formId, $responseId, $result);

        return $result;
    }

    public static function sendResponseEditLink($form, $responseId, $senderLogin)
    {
        $result = array(
            'sent' => 0,
            'skipped' => 0,
            'errors' => array(),
        );

        $formId = (int) (isset($form['id']) ? $form['id'] : 0);
        $responseId = (int) $responseId;
        if ($formId <= 0 || $responseId <= 0 || empty($form['allow_user_edit'])) {
            $result['errors'][] = 'Lien de modification indisponible pour cette reponse.';
            return $result;
        }

        $response = FormulairesDynamiquesRepository::responseWithValues($responseId);
        if (!$response || (int) $response['formulaire_id'] !== $formId) {
            $result['errors'][] = 'Reponse introuvable.';
            return $result;
        }

        $email = self::responseEmail($response, FormulairesDynamiquesRepository::fields($formId, true));
        if (!self::validEmail($email)) {
            $result['errors'][] = 'Adresse email du declarant introuvable ou invalide.';
            return $result;
        }
        if (trim((string) (isset($response['submitter_login']) ? $response['submitter_login'] : '')) === '') {
            $result['errors'][] = 'La reponse n est pas liee a un utilisateur GRR connecte.';
            return $result;
        }

        $url = self::responseEditUrl($form, $response);
        if ($url === '') {
            $result['errors'][] = 'Aucun lien formulaire actif ne permet la modification.';
            return $result;
        }

        if (!self::mailEnabled()) {
            $result['errors'][] = 'Les mails GRR ne sont pas actifs.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }
        if (!self::loadMailClass()) {
            $result['errors'][] = 'La classe mail GRR est introuvable.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }

        self::initMailGlobals();
        $from = self::mailFrom();
        if ($from === '') {
            $result['errors'][] = 'Adresse expediteur GRR introuvable.';
            self::logResult($formId, $responseId, $result);
            return $result;
        }

        $mail = self::sendGrrMail(
            $email,
            self::editLinkSubject($form, $response),
            self::editLinkMessage($form, $response, $url, $senderLogin),
            $from,
            'formulaires_dynamiques_lien_modification',
            $responseId
        );

        if (is_array($mail) && !empty($mail['success'])) {
            $result['sent']++;
        } else {
            $error = is_array($mail) && isset($mail['error']) ? (string) $mail['error'] : 'Erreur inconnue.';
            $result['errors'][] = $email.' : '.$error;
        }

        self::logResult($formId, $responseId, $result);

        return $result;
    }

    private static function matchingRecipients($formId, $values)
    {
        $recipients = FormulairesDynamiquesRepository::notificationRecipients($formId, true);
        $matching = array();
        foreach ($recipients as $recipient) {
            if (FormulairesDynamiquesRepository::notificationMatchesValues($recipient, $values)) {
                $matching[] = $recipient;
            }
        }

        return $matching;
    }

    private static function confirmationRecipient($form, $response, $fields)
    {
        if (empty($form['confirmation_email_enabled']) || !is_array($response)) {
            return false;
        }

        $email = self::responseEmail($response, $fields);
        if (!self::validEmail($email)) {
            return false;
        }

        return array('email' => $email);
    }

    private static function subject($form, $response, $fields)
    {
        $template = isset($form['notification_subject_template']) ? trim((string) $form['notification_subject_template']) : '';
        if ($template !== '') {
            return self::applyTemplate($template, $fields, $response, $form);
        }

        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';
        $responseId = (int) (isset($response['id']) ? $response['id'] : 0);

        return '[GRR] Nouvelle reponse - '.$title.' #'.(int) $responseId;
    }

    private static function message($form, $response, $fields)
    {
        $template = isset($form['notification_body_template']) ? trim((string) $form['notification_body_template']) : '';
        if ($template !== '') {
            $message = self::applyTemplate($template, $fields, $response, $form);
            $editUrl = self::responseEditUrl($form, $response);
            if ($editUrl !== '' && strpos($template, '{lien_modification}') === false) {
                $message .= "\n\nLien de modification declarant : ".$editUrl;
            }

            return $message;
        }

        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';
        $responseId = (int) (isset($response['id']) ? $response['id'] : 0);
        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();

        $message = "Bonjour,\n\n"
            ."Une nouvelle reponse a ete enregistree.\n\n"
            ."Formulaire : ".$title."\n"
            ."Reference : #".$responseId."\n"
            ."Date : ".self::formatDate(isset($response['created_at']) ? (int) $response['created_at'] : 0)."\n"
            ."Source : ".self::sourceLabel(isset($response['source']) ? $response['source'] : '')."\n"
            ."Declarant : ".self::submitterLabel($response)."\n\n";

        $fieldLines = array();
        foreach ((array) $fields as $field) {
            if (!FormulairesDynamiquesRepository::fieldStoresResponse($field)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $label = isset($field['libelle']) && trim((string) $field['libelle']) !== ''
                ? trim((string) $field['libelle'])
                : 'Champ '.$fieldId;
            $value = self::fieldValueForMessage($field, isset($values[$fieldId]) ? $values[$fieldId] : '');
            $fieldLines[] = $label.' : '.($value !== '' ? $value : '-');
        }

        if (count($fieldLines) > 0) {
            $message .= "Contenu :\n".implode("\n", $fieldLines)."\n\n";
        }

        $editUrl = self::responseEditUrl($form, $response);
        if ($editUrl !== '') {
            $message .= "Lien de modification declarant : ".$editUrl."\n";
        }

        $message .= "Lien de gestion : ".self::managementUrl((int) (isset($form['id']) ? $form['id'] : 0))."\n\n"
            ."Message automatique emis par GRR.";

        return $message;
    }

    private static function applyTemplate($template, $fields, $response, $form = array())
    {
        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        $replacements = array(
            '{reference}' => '#'.(int) (isset($response['id']) ? $response['id'] : 0),
            '{date}' => self::formatDate(isset($response['created_at']) ? (int) $response['created_at'] : 0),
            '{source}' => self::sourceLabel(isset($response['source']) ? $response['source'] : ''),
            '{declarant}' => self::submitterLabel($response),
            '{lien_modification}' => self::responseEditUrl($form, $response),
        );

        foreach ((array) $fields as $field) {
            if (!FormulairesDynamiquesRepository::fieldStoresResponse($field)) {
                continue;
            }
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $label = isset($field['libelle']) ? (string) $field['libelle'] : '';
            $value = self::fieldValueForMessage($field, isset($values[$fieldId]) ? $values[$fieldId] : '');
            $replacements['{field:'.$fieldId.'}'] = $value;
            if ($label !== '') {
                $replacements['{champ:'.$label.'}'] = $value;
            }
        }

        return str_replace(array_keys($replacements), array_values($replacements), (string) $template);
    }

    private static function confirmationSubject($form, $response)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';

        return '[GRR] Confirmation - '.$title.' #'.(int) (isset($response['id']) ? $response['id'] : 0);
    }

    private static function confirmationMessage($form, $response, $fields)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';
        $responseId = (int) (isset($response['id']) ? $response['id'] : 0);

        $message = "Bonjour,\n\n"
            ."Votre reponse a ete enregistree.\n\n"
            ."Formulaire : ".$title."\n"
            ."Reference : #".$responseId."\n"
            ."Date : ".self::formatDate(isset($response['created_at']) ? (int) $response['created_at'] : 0)."\n";

        $editUrl = self::responseEditUrl($form, $response);
        if ($editUrl !== '') {
            $message .= "\nVous pouvez modifier votre reponse avec le lien suivant :\n".$editUrl."\n";
        }

        $message .= "\nMessage automatique emis par GRR.";

        return $message;
    }

    private static function editLinkSubject($form, $response)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';

        return '[GRR] Lien de modification - '.$title.' #'.(int) (isset($response['id']) ? $response['id'] : 0);
    }

    private static function editLinkMessage($form, $response, $url, $senderLogin)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';

        $message = "Bonjour,\n\n"
            ."Un gestionnaire vous transmet le lien de modification de votre reponse.\n\n"
            ."Formulaire : ".$title."\n"
            ."Reference : #".(int) (isset($response['id']) ? $response['id'] : 0)."\n"
            ."Lien de modification : ".$url."\n";

        $senderLogin = trim((string) $senderLogin);
        if ($senderLogin !== '') {
            $message .= "Envoye par : ".$senderLogin."\n";
        }

        $message .= "\nMessage automatique emis par GRR.";

        return $message;
    }

    private static function responseEmail($response, $fields)
    {
        $email = isset($response['submitter_email']) ? trim((string) $response['submitter_email']) : '';
        if (self::validEmail($email)) {
            return $email;
        }

        $login = isset($response['submitter_login']) ? trim((string) $response['submitter_login']) : '';
        if ($login !== '') {
            $user = FormulairesDynamiquesRepository::userByLogin($login);
            $email = isset($user['email']) ? trim((string) $user['email']) : '';
            if (self::validEmail($email)) {
                return $email;
            }
        }

        $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        foreach ((array) $fields as $field) {
            if ((isset($field['type_champ']) ? (string) $field['type_champ'] : '') !== 'email') {
                continue;
            }
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $candidate = isset($values[$fieldId]) ? trim((string) $values[$fieldId]) : '';
            if (self::validEmail($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private static function responseEditUrl($form, $response)
    {
        if (empty($form['allow_user_edit']) || !is_array($response)) {
            return '';
        }
        if (trim((string) (isset($response['submitter_login']) ? $response['submitter_login'] : '')) === '') {
            return '';
        }

        $formId = (int) (isset($form['id']) ? $form['id'] : (isset($response['formulaire_id']) ? $response['formulaire_id'] : 0));
        $responseId = (int) (isset($response['id']) ? $response['id'] : 0);
        if ($formId <= 0 || $responseId <= 0) {
            return '';
        }

        $token = self::firstEditableFormToken($formId);
        if ($token === '') {
            return '';
        }

        return self::baseUrl().'app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE)
            .'&view=formulaire&token='.rawurlencode($token)
            .'&edit_response_id='.(int) $responseId;
    }

    private static function firstEditableFormToken($formId)
    {
        foreach (FormulairesDynamiquesRepository::tokens((int) $formId, false) as $token) {
            if ((isset($token['type_token']) ? (string) $token['type_token'] : '') !== 'formulaire') {
                continue;
            }
            $tokenValue = isset($token['token_public']) ? trim((string) $token['token_public']) : '';
            if ($tokenValue === '') {
                continue;
            }
            $expiresAt = (int) (isset($token['expires_at']) ? $token['expires_at'] : 0);
            if ($expiresAt > 0 && $expiresAt < time()) {
                continue;
            }

            return $tokenValue;
        }

        return '';
    }

    private static function fieldValueForMessage($field, $value)
    {
        $type = isset($field['type_champ']) ? (string) $field['type_champ'] : '';
        $value = is_array($value) ? implode("\n", $value) : trim((string) $value);
        if ($type === 'signature') {
            return class_exists('FormulairesDynamiquesRepository')
                && FormulairesDynamiquesRepository::signatureValueValid($value)
                ? 'Signature fournie'
                : '';
        }

        return $value;
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

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('d/m/Y H:i', $timestamp) : '-';
    }

    private static function managementUrl($formId)
    {
        return self::baseUrl().'compte/compte.php?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE).'&form_id='.(int) $formId;
    }

    private static function baseUrl()
    {
        $baseUrl = '';
        if (function_exists('traite_grr_url')) {
            $baseUrl = traite_grr_url('', 'y');
        }

        $baseUrl = trim((string) $baseUrl);
        if ($baseUrl !== '' && substr($baseUrl, -1) !== '/') {
            $baseUrl .= '/';
        }

        return $baseUrl;
    }

    private static function validEmail($email)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return false;
        }

        if (class_exists('SecuChaine') && method_exists('SecuChaine', 'ValideMail')) {
            return SecuChaine::ValideMail($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function mailFrom()
    {
        $from = Settings::get('grr_mail_from');
        if ($from === '' || $from === null) {
            $from = Settings::get('webmaster_email');
        }

        return trim((string) $from);
    }

    private static function loadMailClass()
    {
        if (class_exists('Email')) {
            return true;
        }

        $root = defined('GRR_FORMDYN_ROOT') ? GRR_FORMDYN_ROOT : dirname(__DIR__, 4);
        $path = $root.'/include/mail.class.php';
        if (!is_file($path)) {
            return false;
        }

        require_once $path;
        return class_exists('Email');
    }

    private static function sendGrrMail($to, $subject, $message, $from, $template, $responseId)
    {
        global $gNbMail, $gMaxMail;

        if (isset($gMaxMail) && (int) $gMaxMail !== -1 && isset($gNbMail) && (int) $gNbMail >= (int) $gMaxMail) {
            return array('success' => false, 'error' => 'Limite d envoi mail GRR atteinte.');
        }

        try {
            $result = Email::Envois($to, $subject, $message, $from, '', '', '', $template, (int) $responseId, 0);
        } catch (Throwable $exception) {
            return array('success' => false, 'error' => $exception->getMessage());
        }

        return is_array($result) ? $result : array('success' => false, 'error' => 'Retour mail GRR invalide.');
    }

    private static function initMailGlobals()
    {
        global $gNbMail, $gMaxMail, $gMailExpediteur;

        if (!isset($gNbMail)) {
            $gNbMail = 0;
        }
        if (!isset($gMaxMail)) {
            $gMaxMail = -1;
        }
        if (!isset($gMailExpediteur)) {
            $gMailExpediteur = '';
        }
    }

    private static function logResult($formId, $responseId, $result)
    {
        if (!class_exists('FormulairesDynamiquesRepository')) {
            return;
        }

        $sent = isset($result['sent']) ? (int) $result['sent'] : 0;
        $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : array();
        if (count($errors) === 0) {
            FormulairesDynamiquesRepository::recordResponseNotification(
                $formId,
                $responseId,
                'notification_reponse',
                'Notifications envoyees : '.$sent
            );
            return;
        }

        FormulairesDynamiquesRepository::recordResponseNotification(
            $formId,
            $responseId,
            'notification_erreur',
            implode("\n", $errors)
        );
    }
}
