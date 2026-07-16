<?php

class GestionMaterielNotification
{
    public static function mailStatus()
    {
        $method = Settings::get('grr_mail_method');
        $automatic = Settings::get('automatic_mail');

        return array(
            'enabled' => self::mailEnabled(),
            'automatic_mail' => $automatic,
            'method' => $method,
        );
    }

    public static function sendDueNotifications($days = 30)
    {
        $result = array(
            'sent' => 0,
            'skipped' => 0,
            'errors' => array(),
        );

        if (!self::mailEnabled()) {
            $result['errors'][] = 'Les mails GRR ne sont pas actifs.';
            return $result;
        }

        if (!self::loadMailClass()) {
            $result['errors'][] = 'La classe mail GRR est introuvable.';
            return $result;
        }

        self::initMailGlobals();
        $from = self::mailFrom();
        if ($from === '') {
            $result['errors'][] = 'Adresse expediteur GRR introuvable.';
            return $result;
        }

        $allNotifications = GestionMaterielRepository::upcomingNotifications($days, true);
        $pendingNotifications = GestionMaterielRepository::upcomingNotifications($days, false);
        $result['skipped'] = count($allNotifications) - count($pendingNotifications);

        foreach ($pendingNotifications as $notification) {
            $email = isset($notification['email']) ? trim((string) $notification['email']) : '';
            if (!self::validEmail($email)) {
                GestionMaterielRepository::logNotification(
                    (int) $notification['item_id'],
                    (string) $notification['login'],
                    (string) $notification['type_notification'],
                    (int) $notification['echeance'],
                    'error',
                    'Adresse email invalide.'
                );
                $result['errors'][] = 'Adresse email invalide pour '.$notification['login'].'.';
                continue;
            }

            $mailResult = self::sendGrrMail(
                $email,
                self::subject($notification),
                self::message($notification),
                $from,
                'gestion_materiel_'.$notification['type_notification'],
                (int) $notification['item_id']
            );

            if (is_array($mailResult) && !empty($mailResult['success'])) {
                GestionMaterielRepository::logNotification(
                    (int) $notification['item_id'],
                    (string) $notification['login'],
                    (string) $notification['type_notification'],
                    (int) $notification['echeance'],
                    'sent',
                    'Notification envoyee a '.$email.'.'
                );
                $result['sent']++;
            } else {
                $error = is_array($mailResult) && isset($mailResult['error']) ? (string) $mailResult['error'] : 'Erreur inconnue.';
                GestionMaterielRepository::logNotification(
                    (int) $notification['item_id'],
                    (string) $notification['login'],
                    (string) $notification['type_notification'],
                    (int) $notification['echeance'],
                    'error',
                    $error
                );
                $result['errors'][] = $notification['login'].' : '.$error;
            }
        }

        return $result;
    }

    private static function subject($notification)
    {
        $type = isset($notification['type_notification']) ? (string) $notification['type_notification'] : '';
        $label = $type === 'etalonnage' ? 'Etalonnage' : 'Maintenance';

        return '[GRR] '.$label.' materiel a prevoir';
    }

    private static function message($notification)
    {
        $type = isset($notification['type_notification']) ? (string) $notification['type_notification'] : '';
        $label = $type === 'etalonnage' ? 'etalonnage' : 'maintenance';
        $reference = isset($notification['reference']) ? trim((string) $notification['reference']) : '';
        $itemName = isset($notification['item_nom']) ? trim((string) $notification['item_nom']) : '';
        $itemLabel = $reference !== '' ? $reference.' - '.$itemName : $itemName;

        return "Bonjour,\n\n"
            ."Une echeance de ".$label." est a prevoir pour le materiel suivant :\n\n"
            ."Materiel : ".$itemLabel."\n"
            ."Echeance : ".self::formatDate(isset($notification['echeance']) ? (int) $notification['echeance'] : 0)."\n"
            ."Utilisateur assigne : ".(isset($notification['user_label']) ? $notification['user_label'] : $notification['login'])."\n\n"
            ."Lien : ".self::moduleUrl((int) $notification['item_id'])."\n\n"
            ."Message automatique emis par GRR.";
    }

    private static function moduleUrl($itemId)
    {
        $baseUrl = '';
        if (function_exists('traite_grr_url')) {
            $baseUrl = traite_grr_url('', 'y');
        }

        return $baseUrl.'compte/compte.php?pc=gestion_materiel&view=item&id='.(int) $itemId;
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        return $timestamp > 0 ? date('d/m/Y', $timestamp) : '';
    }

    private static function mailEnabled()
    {
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

    private static function sendGrrMail($to, $subject, $message, $from, $template, $itemId)
    {
        global $gNbMail, $gMaxMail;

        if (isset($gMaxMail) && (int) $gMaxMail !== -1 && isset($gNbMail) && (int) $gNbMail >= (int) $gMaxMail) {
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
                $itemId,
                0
            );
        } catch (Throwable $exception) {
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
