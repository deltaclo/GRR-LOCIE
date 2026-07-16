<?php

class StockChimiqueNotification
{
    public static function sendPending()
    {
        $result = array('sent' => 0, 'skipped' => 0, 'errors' => array());
        if (!StockChimiqueConfig::notificationsEnabled()) {
            $result['errors'][] = 'Les notifications du module sont désactivées.';
            return $result;
        }
        if (!StockChimiqueConfig::alertsEnabled()) {
            $result['errors'][] = 'Les alertes du module sont désactivées.';
            return $result;
        }
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
            $result['errors'][] = 'Adresse expéditeur GRR introuvable.';
            return $result;
        }

        $alerts = StockChimiqueRepository::alerts(1000);
        $recipients = StockChimiqueRepository::notificationRecipients();
        foreach ($recipients as $recipient) {
            $email = trim((string) $recipient['email']);
            if (!self::validEmail($email)) {
                $result['errors'][] = 'Adresse invalide pour '.$recipient['login'].'.';
                continue;
            }
            foreach ($alerts as $alert) {
                $key = (string) $alert['alert_key'];
                $login = (string) $recipient['login'];
                if (StockChimiqueRepository::notificationWasSent($key, $login)) {
                    $result['skipped']++;
                    continue;
                }
                $mail = self::sendGrrMail(
                    $email,
                    '[GRR] Stock chimique - '.self::typeLabel($alert['type']),
                    self::message($alert),
                    $from,
                    'stock_chimique_'.$alert['type'],
                    (int) $alert['produit_id']
                );
                if (is_array($mail) && !empty($mail['success'])) {
                    StockChimiqueRepository::logNotification(
                        $key,
                        $login,
                        (string) $alert['type'],
                        (int) $alert['produit_id'],
                        'sent',
                        'Notification envoyée à '.$email
                    );
                    $result['sent']++;
                } else {
                    $error = is_array($mail) && isset($mail['error']) ? (string) $mail['error'] : 'Erreur inconnue.';
                    StockChimiqueRepository::logNotification(
                        $key,
                        $login,
                        (string) $alert['type'],
                        (int) $alert['produit_id'],
                        'error',
                        $error
                    );
                    $result['errors'][] = $login.' : '.$error;
                }
            }
        }
        return $result;
    }

    private static function message($alert)
    {
        $baseUrl = function_exists('traite_grr_url') ? traite_grr_url('', 'y') : '';
        return "Bonjour,\n\n"
            ."Une alerte du module Stock chimique nécessite une vérification.\n\n"
            ."Type : ".self::typeLabel($alert['type'])."\n"
            ."Élément : ".$alert['label']."\n"
            ."Détail : ".$alert['detail']."\n\n"
            ."Lien : ".$baseUrl."compte/compte.php?pc=stock_chimique&view=product&id=".(int) $alert['produit_id']."\n\n"
            ."Message automatique émis par GRR.";
    }

    private static function typeLabel($type)
    {
        $labels = array(
            'stock_faible' => 'Stock faible',
            'peremption_proche' => 'Péremption proche',
            'perime' => 'Produit périmé',
            'fds_manquante' => 'FDS manquante',
            'fds_a_verifier' => 'FDS à vérifier',
        );
        return isset($labels[$type]) ? $labels[$type] : (string) $type;
    }

    private static function mailEnabled()
    {
        $method = Settings::get('grr_mail_method');
        return Settings::get('automatic_mail') === 'yes'
            && ($method === 'smtp' || $method === 'mail');
    }

    private static function validEmail($email)
    {
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
        if (!is_file($path)) {
            return false;
        }
        require_once $path;
        return class_exists('Email');
    }

    private static function sendGrrMail($to, $subject, $message, $from, $template, $objectId)
    {
        global $gNbMail, $gMaxMail;
        if (isset($gMaxMail, $gNbMail) && (int) $gMaxMail !== -1 && (int) $gNbMail >= (int) $gMaxMail) {
            return array('success' => false, 'error' => 'Limite d envoi GRR atteinte.');
        }
        try {
            $result = Email::Envois($to, $subject, $message, $from, '', '', '', $template, $objectId, 0);
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
}
