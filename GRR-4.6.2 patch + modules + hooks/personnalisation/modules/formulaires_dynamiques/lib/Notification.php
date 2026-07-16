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

        $recipients = FormulairesDynamiquesRepository::notificationRecipients($formId, true);
        if (count($recipients) === 0) {
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
        $subject = self::subject($form, $responseId);
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

        self::logResult($formId, $responseId, $result);

        return $result;
    }

    private static function subject($form, $responseId)
    {
        $title = isset($form['titre']) && trim((string) $form['titre']) !== ''
            ? trim((string) $form['titre'])
            : 'Formulaire';

        return '[GRR] Nouvelle reponse - '.$title.' #'.(int) $responseId;
    }

    private static function message($form, $response, $fields)
    {
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
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type === 'separator') {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $label = isset($field['libelle']) && trim((string) $field['libelle']) !== ''
                ? trim((string) $field['libelle'])
                : 'Champ '.$fieldId;
            $value = isset($values[$fieldId]) ? trim((string) $values[$fieldId]) : '';
            $fieldLines[] = $label.' : '.($value !== '' ? $value : '-');
        }

        if (count($fieldLines) > 0) {
            $message .= "Contenu :\n".implode("\n", $fieldLines)."\n\n";
        }

        $message .= "Lien de gestion : ".self::managementUrl((int) (isset($form['id']) ? $form['id'] : 0))."\n\n"
            ."Message automatique emis par GRR.";

        return $message;
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
        $baseUrl = '';
        if (function_exists('traite_grr_url')) {
            $baseUrl = traite_grr_url('', 'y');
        }

        return $baseUrl.'compte/compte.php?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE).'&form_id='.(int) $formId;
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
