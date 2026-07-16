<?php

class SuiviDemandesNotification
{
    public static function notifyCreated($demandeId, $actorLogin)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('created')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        self::sendDemandNotification(
            $demand,
            'Nouvelle demande',
            "Une nouvelle demande a ete creee.",
            $actorLogin,
            true,
            true,
            array()
        );
    }

    public static function notifyComment($demandeId, $actorLogin, $comment)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('comment')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        $details = "Un commentaire a ete ajoute par ".$actorLogin.".\n\n"
            ."Commentaire :\n".trim((string) $comment);

        self::sendDemandNotification(
            $demand,
            'Nouveau commentaire',
            $details,
            $actorLogin,
            true,
            false,
            array()
        );
    }

    public static function notifyStatusChanged($demandeId, $actorLogin, $status)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('status')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        $details = "Le statut de la demande est maintenant : ".SuiviDemandesConfig::statusLabel($status).".";

        self::sendDemandNotification(
            $demand,
            'Changement de statut',
            $details,
            $actorLogin,
            true,
            false,
            array()
        );
    }

    public static function notifyFollowerChanged($demandeId, $actorLogin, $followerLogin, $added)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('follower')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        $details = $added
            ? "Le suiveur ".$followerLogin." a ete ajoute."
            : "Le suiveur ".$followerLogin." a ete retire.";

        self::sendDemandNotification(
            $demand,
            $added ? 'Suiveur ajoute' : 'Suiveur retire',
            $details,
            $actorLogin,
            true,
            false,
            array($followerLogin)
        );
    }

    public static function notifyResourceChanged($demandeId, $actorLogin, $roomId, $added)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('resource')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        $label = self::roomLabel((int) $roomId);
        $details = $added
            ? "La ressource ".$label." a ete ajoutee."
            : "La ressource ".$label." a ete retiree.";

        self::sendDemandNotification(
            $demand,
            $added ? 'Ressource ajoutee' : 'Ressource retiree',
            $details,
            $actorLogin,
            true,
            false,
            array()
        );
    }

    public static function notifyAttachmentChanged($demandeId, $actorLogin, $fileName, $added)
    {
        if (!SuiviDemandesConfig::notificationTypeEnabled('attachment')) {
            return;
        }

        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return;
        }

        $fileName = trim((string) $fileName);
        if ($fileName === '') {
            $fileName = 'piece jointe';
        }

        $details = $added
            ? "La piece jointe suivante a ete ajoutee : ".$fileName."."
            : "La piece jointe suivante a ete retiree : ".$fileName.".";

        self::sendDemandNotification(
            $demand,
            $added ? 'Piece jointe ajoutee' : 'Piece jointe retiree',
            $details,
            $actorLogin,
            true,
            false,
            array()
        );
    }

    public static function resendToManagers($demandeId, $actorLogin)
    {
        $demand = SuiviDemandesRepository::findById((int) $demandeId);
        if (!$demand) {
            return array('ok' => false, 'reason' => 'not_found', 'count' => 0);
        }

        if (!self::mailEnabled()) {
            return array('ok' => false, 'reason' => 'mail_disabled', 'count' => 0);
        }

        $emails = self::managerEmailsForDemand((int) $demand['id']);
        if (count($emails) === 0) {
            return array('ok' => false, 'reason' => 'no_recipient', 'count' => 0);
        }

        if (!self::loadMailClass()) {
            return array('ok' => false, 'reason' => 'mail_unavailable', 'count' => 0);
        }

        self::initMailGlobals();

        $from = Settings::get('grr_mail_from');
        if ($from === '' || $from === null) {
            $from = Settings::get('webmaster_email');
        }
        if ($from === '' || $from === null) {
            return array('ok' => false, 'reason' => 'mail_unavailable', 'count' => 0);
        }

        $demandeId = (int) $demand['id'];
        $eventLabel = 'Notification aux gestionnaires';
        $details = "Un administrateur a renvoye la notification de cette demande aux gestionnaires des ressources associees.";

        Email::Envois(
            implode(';', $emails),
            '[GRR] Demande #'.$demandeId.' - '.$eventLabel,
            self::messageBody($demand, $eventLabel, $details, $actorLogin),
            $from,
            '',
            '',
            '',
            'suivi_demandes_renvoi_gestionnaires',
            null,
            0
        );

        return array('ok' => true, 'reason' => 'sent', 'count' => count($emails));
    }

    private static function sendDemandNotification($demand, $eventLabel, $details, $actorLogin, $includeManagers, $includeActor, $extraLogins)
    {
        if (!self::mailEnabled()) {
            return;
        }

        $emails = self::recipientEmails($demand, $actorLogin, $includeManagers, $includeActor, $extraLogins);
        if (count($emails) === 0) {
            return;
        }

        if (!self::loadMailClass()) {
            return;
        }

        self::initMailGlobals();

        $demandeId = (int) $demand['id'];
        $subject = '[GRR] Demande #'.$demandeId.' - '.$eventLabel;
        $message = self::messageBody($demand, $eventLabel, $details, $actorLogin);
        $from = Settings::get('grr_mail_from');
        if ($from === '' || $from === null) {
            $from = Settings::get('webmaster_email');
        }
        if ($from === '' || $from === null) {
            return;
        }

        Email::Envois(
            implode(';', $emails),
            $subject,
            $message,
            $from,
            '',
            '',
            '',
            'suivi_demandes_'.$eventLabel,
            null,
            0
        );
    }

    private static function recipientEmails($demand, $actorLogin, $includeManagers, $includeActor, $extraLogins)
    {
        $logins = array();
        if (isset($demand['createur']) && $demand['createur'] !== '') {
            $logins[] = $demand['createur'];
        }

        foreach (SuiviDemandesRepository::followersForDemand((int) $demand['id']) as $follower) {
            if (isset($follower['login'])) {
                $logins[] = $follower['login'];
            }
        }

        foreach ($extraLogins as $login) {
            if ($login !== '') {
                $logins[] = $login;
            }
        }

        $emails = array();
        foreach ($logins as $login) {
            if (!$includeActor && $actorLogin !== '' && $login === $actorLogin) {
                continue;
            }
            self::addEmail($emails, self::emailForLogin($login));
        }

        if ($includeManagers) {
            foreach (self::managerEmailsForDemand((int) $demand['id']) as $email) {
                self::addEmail($emails, $email);
            }
        }

        if (!$includeActor && $actorLogin !== '') {
            $actorEmail = self::emailForLogin($actorLogin);
            if ($actorEmail !== '') {
                unset($emails[strtolower($actorEmail)]);
            }
        }

        return array_values($emails);
    }

    private static function managerEmailsForDemand($demandeId)
    {
        $emails = array();
        foreach (SuiviDemandesRepository::resourceIdsForDemand((int) $demandeId) as $roomId) {
            if (function_exists('find_active_user_room')) {
                $result = find_active_user_room((int) $roomId);
                if (isset($result[0]) && is_array($result[0])) {
                    foreach ($result[0] as $email) {
                        self::addEmail($emails, $email);
                    }
                }
            }
        }

        return array_values($emails);
    }

    private static function emailForLogin($login)
    {
        if ($login === '') {
            return '';
        }

        $row = SuiviDemandesRepository::userMailInfo($login);
        if (!$row || !isset($row['email'])) {
            return '';
        }

        return $row['email'];
    }

    private static function addEmail(&$emails, $email)
    {
        $email = trim((string) $email);
        if ($email === '' || !SecuChaine::ValideMail($email)) {
            return;
        }

        $emails[strtolower($email)] = $email;
    }

    private static function messageBody($demand, $eventLabel, $details, $actorLogin)
    {
        $demandeId = (int) $demand['id'];
        $url = self::demandUrl($demandeId);
        $resources = SuiviDemandesRepository::resourcesForDemand($demandeId);
        $categoryLine = SuiviDemandesConfig::categoriesEnabled()
            ? "Categorie : ".SuiviDemandesConfig::categoryLabel(isset($demand['categorie']) ? $demand['categorie'] : '')."\n"
            : '';

        return "Bonjour,\n\n"
            ."Evenement : ".$eventLabel."\n"
            ."Demande #".$demandeId." : ".$demand['titre']."\n"
            ."Statut : ".SuiviDemandesConfig::statusLabel($demand['statut'])."\n"
            ."Priorite : ".SuiviDemandesConfig::priorityLabel($demand['priorite'])."\n"
            .$categoryLine
            ."Createur : ".$demand['createur']."\n"
            ."Action realisee par : ".$actorLogin."\n"
            ."Ressources : ".implode(', ', $resources)."\n\n"
            .$details."\n\n"
            ."Lien : ".$url."\n\n"
            ."Message automatique emis par GRR.";
    }

    private static function demandUrl($demandeId)
    {
        $baseUrl = '';
        if (function_exists('traite_grr_url')) {
            $baseUrl = traite_grr_url('', 'y');
        }

        return $baseUrl.'compte/compte.php?pc=suivi_demandes&demande_id='.(int) $demandeId;
    }

    private static function roomLabel($roomId)
    {
        $row = SuiviDemandesRepository::roomInfo((int) $roomId);
        if (!$row) {
            return '#'.(int) $roomId;
        }

        return $row['area_name'].' > '.$row['room_name'].' (#'.(int) $roomId.')';
    }

    private static function mailEnabled()
    {
        if (!SuiviDemandesConfig::notificationsEnabled()) {
            return false;
        }

        $method = Settings::get('grr_mail_method');

        return Settings::get('automatic_mail') === 'yes'
            && ($method === 'smtp' || $method === 'mail');
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
