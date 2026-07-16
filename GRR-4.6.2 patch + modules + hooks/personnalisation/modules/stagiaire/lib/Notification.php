<?php

class StagiaireNotification
{
    public static function sendInitialConfirmation($entryId)
    {
        return self::sendInitialConfirmationForEntries(array((int) $entryId));
    }

    public static function sendInitialConfirmationForEntries($entryIds)
    {
        $entryIds = StagiaireRepository::normalizeEntryIds($entryIds);
        if (count($entryIds) === 0 || !self::mailEnabled()) {
            return false;
        }

        $entryId = self::firstUnsentEntryId($entryIds, 'mail_creation_sent');
        if ($entryId <= 0) {
            return false;
        }

        $data = StagiaireRepository::reservationData($entryId);
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if (!self::validEmail($email)) {
            return false;
        }

        $reservation = StagiaireRepository::reservationInfo($entryId);
        if (empty($reservation) || !self::loadMailClass()) {
            return false;
        }

        self::initMailGlobals();

        $from = self::mailFrom();
        if ($from === '') {
            return false;
        }

        $subject = '[GRR] Confirmation de reservation';
        $result = self::sendGrrMail(
            $email,
            $subject,
            self::initialMessage($data, $reservation, count($entryIds)),
            $from,
            'stagiaire_confirmation_creation',
            $entryId
        );

        if (is_array($result) && !empty($result['success'])) {
            StagiaireRepository::markCreationMailSentForEntries($entryIds);
            return true;
        }

        return false;
    }

    public static function sendModerationConfirmation($context)
    {
        if (!is_array($context) || !self::mailEnabled()) {
            return 0;
        }

        if (isset($context['send_mail']) && $context['send_mail'] !== 'yes') {
            return 0;
        }

        $decision = isset($context['decision']) ? (string) $context['decision'] : '';
        if ($decision !== 'accepted' && $decision !== 'refused') {
            return 0;
        }

        $entryIds = self::contextEntryIds($context);
        if (count($entryIds) === 0 || !self::loadMailClass()) {
            return 0;
        }

        self::initMailGlobals();

        $from = self::mailFrom();
        if ($from === '') {
            return 0;
        }

        return self::sendModerationConfirmationForEntries($entryIds, $decision, $context, $from) ? 1 : 0;
    }

    public static function sendDeletionConfirmation($context)
    {
        if (!is_array($context) || !self::mailEnabled()) {
            return 0;
        }

        if (isset($context['send_mail']) && $context['send_mail'] !== 'yes') {
            return 0;
        }

        $entryIds = self::contextEntryIds($context);
        if (count($entryIds) === 0 || !self::loadMailClass()) {
            return 0;
        }

        self::initMailGlobals();

        $from = self::mailFrom();
        if ($from === '') {
            return 0;
        }

        return self::sendDeletionConfirmationForEntries($entryIds, $context, $from) ? 1 : 0;
    }

    private static function sendModerationConfirmationForEntries($entryIds, $decision, $context, $from)
    {
        $entryIds = StagiaireRepository::normalizeEntryIds($entryIds);
        $entryId = self::firstUnsentEntryId($entryIds, 'mail_moderation_sent');
        if ($entryId <= 0) {
            return false;
        }

        $data = StagiaireRepository::reservationData($entryId);
        if (empty($data)) {
            return false;
        }

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if (!self::validEmail($email)) {
            return false;
        }

        $reservation = StagiaireRepository::reservationInfo($entryId);
        if (empty($reservation)) {
            return false;
        }

        $subject = $decision === 'accepted'
            ? '[GRR] Reservation acceptee'
            : '[GRR] Reservation refusee';
        $template = $decision === 'accepted'
            ? 'stagiaire_confirmation_moderation_accepted'
            : 'stagiaire_confirmation_moderation_refused';

        $result = self::sendGrrMail(
            $email,
            $subject,
            self::moderationMessage($data, $reservation, $decision, $context, count($entryIds)),
            $from,
            $template,
            $entryId
        );

        if (is_array($result) && !empty($result['success'])) {
            StagiaireRepository::markModerationMailSentForEntries($entryIds);
            return true;
        }

        return false;
    }

    private static function sendDeletionConfirmationForEntries($entryIds, $context, $from)
    {
        $entryIds = StagiaireRepository::normalizeEntryIds($entryIds);
        $entryId = self::firstEntryWithData($entryIds);
        if ($entryId <= 0) {
            return false;
        }

        $data = StagiaireRepository::reservationData($entryId);
        if (empty($data)) {
            return false;
        }

        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        if (!self::validEmail($email)) {
            return false;
        }

        $reservation = StagiaireRepository::reservationInfo($entryId);
        if (empty($reservation)) {
            return false;
        }

        $result = self::sendGrrMail(
            $email,
            '[GRR] Reservation supprimee',
            self::deletionMessage($data, $reservation, $context, count($entryIds)),
            $from,
            'stagiaire_confirmation_suppression',
            $entryId
        );

        return is_array($result) && !empty($result['success']);
    }

    private static function initialMessage($data, $reservation, $entryCount = 1)
    {
        $status = self::reservationStatusLabel(isset($reservation['moderate']) ? (int) $reservation['moderate'] : 0);
        $resource = self::reservationResourceLabel($reservation);
        $title = isset($reservation['name']) ? trim((string) $reservation['name']) : '';
        $start = self::formatDate(isset($reservation['start_time']) ? (int) $reservation['start_time'] : 0);
        $end = self::formatDate(isset($reservation['end_time']) ? (int) $reservation['end_time'] : 0);
        $link = self::reservationUrl(isset($reservation['id']) ? (int) $reservation['id'] : 0);

        return "Bonjour ".self::personLabel($data).",\n\n"
            ."Votre reservation a ete enregistree dans GRR.\n\n"
            ."Statut : ".$status."\n"
            ."Ressource : ".$resource."\n"
            ."Debut : ".$start."\n"
            ."Fin : ".$end."\n"
            ."Titre : ".$title."\n"
            .self::seriesLine($entryCount)
            ."\n"
            ."Lien : ".$link."\n\n"
            ."Message automatique emis par GRR.";
    }

    private static function moderationMessage($data, $reservation, $decision, $context, $entryCount = 1)
    {
        $resource = self::reservationResourceLabel($reservation);
        $title = isset($reservation['name']) ? trim((string) $reservation['name']) : '';
        $start = self::formatDate(isset($reservation['start_time']) ? (int) $reservation['start_time'] : 0);
        $end = self::formatDate(isset($reservation['end_time']) ? (int) $reservation['end_time'] : 0);
        $link = self::reservationUrl(isset($reservation['id']) ? (int) $reservation['id'] : 0);
        $moderator = isset($context['moderator']) ? trim((string) $context['moderator']) : '';
        $comment = isset($context['description']) ? trim((string) $context['description']) : '';
        $status = $decision === 'accepted' ? 'acceptee' : 'refusee';

        $message = "Bonjour ".self::personLabel($data).",\n\n"
            ."Votre reservation a ete ".$status." par moderation dans GRR.\n\n"
            ."Ressource : ".$resource."\n"
            ."Debut : ".$start."\n"
            ."Fin : ".$end."\n"
            ."Titre : ".$title."\n"
            .self::seriesLine($entryCount);

        if ($moderator !== '') {
            $message .= "Moderateur : ".$moderator."\n";
        }
        if ($comment !== '') {
            $message .= "Commentaire : ".$comment."\n";
        }

        $message .= "\nLien : ".$link."\n\n"
            ."Message automatique emis par GRR.";

        return $message;
    }

    private static function deletionMessage($data, $reservation, $context, $entryCount = 1)
    {
        $resource = self::reservationResourceLabel($reservation);
        $title = isset($reservation['name']) ? trim((string) $reservation['name']) : '';
        $start = self::formatDate(isset($reservation['start_time']) ? (int) $reservation['start_time'] : 0);
        $end = self::formatDate(isset($reservation['end_time']) ? (int) $reservation['end_time'] : 0);
        $deletedBy = isset($context['deleted_by']) ? trim((string) $context['deleted_by']) : '';

        $message = "Bonjour ".self::personLabel($data).",\n\n"
            ."Votre reservation a ete supprimee dans GRR.\n\n"
            ."Ressource : ".$resource."\n"
            ."Debut : ".$start."\n"
            ."Fin : ".$end."\n"
            ."Titre : ".$title."\n"
            .self::seriesLine($entryCount);

        if ($deletedBy !== '') {
            $message .= "Suppression realisee par : ".$deletedBy."\n";
        }

        $message .= "\nMessage automatique emis par GRR.";

        return $message;
    }

    private static function contextEntryIds($context)
    {
        $entryIds = array();
        if (isset($context['entry_ids']) && is_array($context['entry_ids'])) {
            foreach ($context['entry_ids'] as $entryId) {
                $entryId = (int) $entryId;
                if ($entryId > 0) {
                    $entryIds[$entryId] = $entryId;
                }
            }
        }

        if (count($entryIds) === 0 && isset($context['entry_id'])) {
            $entryId = (int) $context['entry_id'];
            if ($entryId > 0) {
                $entryIds[$entryId] = $entryId;
            }
        }

        return array_values($entryIds);
    }

    private static function firstUnsentEntryId($entryIds, $field)
    {
        foreach (StagiaireRepository::normalizeEntryIds($entryIds) as $entryId) {
            $data = StagiaireRepository::reservationData($entryId);
            if (!empty($data) && (!isset($data[$field]) || (int) $data[$field] !== 1)) {
                return $entryId;
            }
        }

        return 0;
    }

    private static function firstEntryWithData($entryIds)
    {
        foreach (StagiaireRepository::normalizeEntryIds($entryIds) as $entryId) {
            if (!empty(StagiaireRepository::reservationData($entryId))) {
                return $entryId;
            }
        }

        return 0;
    }

    private static function seriesLine($entryCount)
    {
        $entryCount = (int) $entryCount;
        if ($entryCount <= 1) {
            return '';
        }

        return 'Nombre de reservations dans la serie : '.$entryCount."\n";
    }

    private static function personLabel($data)
    {
        $parts = array();
        if (isset($data['prenom']) && trim((string) $data['prenom']) !== '') {
            $parts[] = trim((string) $data['prenom']);
        }
        if (isset($data['nom']) && trim((string) $data['nom']) !== '') {
            $parts[] = trim((string) $data['nom']);
        }

        return count($parts) > 0 ? implode(' ', $parts) : 'stagiaire';
    }

    private static function reservationStatusLabel($moderate)
    {
        if ((int) $moderate === 1) {
            return 'en attente de moderation';
        }
        if ((int) $moderate === 2) {
            return 'validee par moderation';
        }
        if ((int) $moderate === 3) {
            return 'refusee par moderation';
        }

        return 'validee';
    }

    private static function reservationResourceLabel($reservation)
    {
        $area = isset($reservation['area_name']) ? trim((string) $reservation['area_name']) : '';
        $room = isset($reservation['room_name']) ? trim((string) $reservation['room_name']) : '';
        if ($area !== '' && $room !== '') {
            return $area.' > '.$room;
        }
        if ($room !== '') {
            return $room;
        }

        return '#'.(isset($reservation['room_id']) ? (int) $reservation['room_id'] : 0);
    }

    private static function reservationUrl($entryId)
    {
        $baseUrl = '';
        if (function_exists('traite_grr_url')) {
            $baseUrl = traite_grr_url('', 'y');
        }

        return $baseUrl.'app.php?p=vuereservation&id='.(int) $entryId;
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        global $dformat;
        if (function_exists('time_date_string') && isset($dformat) && $dformat !== '') {
            return time_date_string($timestamp, $dformat);
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private static function mailEnabled()
    {
        if (!StagiaireConfig::isEnabled() || !StagiaireConfig::mailEnabled()) {
            return false;
        }

        $method = Settings::get('grr_mail_method');

        return Settings::get('automatic_mail') === 'yes'
            && ($method === 'smtp' || $method === 'mail');
    }

    private static function validEmail($email)
    {
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

        $path = __DIR__.'/../../../../include/mail.class.php';
        if (!file_exists($path)) {
            return false;
        }

        require_once $path;
        return class_exists('Email');
    }

    private static function sendGrrMail($to, $subject, $message, $from, $template, $entryId)
    {
        global $gNbMail, $gMaxMail;

        if (isset($gMaxMail) && (int) $gMaxMail !== -1 && isset($gNbMail) && (int) $gNbMail >= (int) $gMaxMail) {
            error_log('Stagiaire module: limite d envoi mail GRR atteinte.');
            return array('success' => false, 'error' => 'Limite d envoi mail GRR atteinte.');
        }

        try {
            $result = Email::Envois(
                $to,
                $subject,
                $message,
                $from,
                '',
                '',
                '',
                $template,
                $entryId,
                0
            );
        } catch (Throwable $exception) {
            error_log('Stagiaire module: erreur envoi mail - '.$exception->getMessage());
            return array('success' => false, 'error' => $exception->getMessage());
        }

        if (!is_array($result)) {
            return array('success' => false, 'error' => 'Retour mail GRR invalide.');
        }

        return $result;
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
}
