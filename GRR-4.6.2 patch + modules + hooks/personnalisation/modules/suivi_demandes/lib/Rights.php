<?php

class SuiviDemandesRights
{
    public static function canAccessModule($login)
    {
        return SuiviDemandesConfig::isEnabled()
            && $login !== ''
            && SuiviDemandesRepository::userModuleEnabled($login);
    }

    public static function isAdmin($login)
    {
        return SecuAccess::UserLevel($login, -1) >= 6;
    }

    public static function canCreateDemand($login)
    {
        if (!self::canAccessModule($login)) {
            return false;
        }

        $right = SuiviDemandesConfig::creationRight();
        if ($right === 'admin') {
            return self::isAdmin($login);
        }

        if ($right === 'manager') {
            return self::isAdmin($login) || count(SuiviDemandesRepository::managedResources($login)) > 0;
        }

        if ($right === 'resource') {
            return count(SuiviDemandesRepository::visibleResources($login)) > 0;
        }

        return true;
    }

    public static function canCreateDemandForRoom($login, $roomId)
    {
        if (!self::canAccessModule($login) || (int) $roomId <= 0 || !SuiviDemandesRepository::roomModuleEnabled((int) $roomId)) {
            return false;
        }

        $right = SuiviDemandesConfig::creationRight();
        if ($right === 'admin') {
            return self::isAdmin($login);
        }

        if ($right === 'manager') {
            return self::isAdmin($login) || SuiviDemandesRepository::userManagesResource($login, (int) $roomId);
        }

        return SecuAccess::UserResource($login, (int) $roomId);
    }

    public static function canCreateDemandForOtherUser($login, $roomId = 0)
    {
        if (!self::canAccessModule($login)) {
            return false;
        }

        if (self::isAdmin($login)) {
            return true;
        }

        $roomId = (int) $roomId;
        if ($roomId > 0) {
            return SuiviDemandesRepository::userManagesResource($login, $roomId);
        }

        return count(SuiviDemandesRepository::managedResources($login)) > 0;
    }

    public static function canViewDemand($login, $demand)
    {
        if (!$demand || $login === '') {
            return false;
        }

        $demandeId = (int) $demand['id'];

        return self::isAdmin($login)
            || SuiviDemandesRepository::sameLogin($demand['createur'], $login)
            || SuiviDemandesRepository::isFollower($demandeId, $login)
            || SuiviDemandesRepository::userManagesDemandResource($demandeId, $login);
    }

    public static function canCloseDemand($login, $demand)
    {
        if (!$demand || $login === '' || $demand['statut'] === 'cloturee') {
            return false;
        }

        $demandeId = (int) $demand['id'];
        $right = SuiviDemandesConfig::closeRight();

        if ($right === 'admin') {
            return self::isAdmin($login);
        }

        if ($right === 'manager_admin') {
            return self::isAdmin($login)
                || SuiviDemandesRepository::userManagesDemandResource($demandeId, $login);
        }

        return self::isAdmin($login)
            || SuiviDemandesRepository::sameLogin($demand['createur'], $login)
            || SuiviDemandesRepository::userManagesDemandResource($demandeId, $login);
    }

    public static function canStartDemand($login, $demand)
    {
        if (!$demand || $login === '' || $demand['statut'] !== 'ouverte') {
            return false;
        }

        $demandeId = (int) $demand['id'];

        return self::isAdmin($login)
            || SuiviDemandesRepository::userManagesDemandResource($demandeId, $login);
    }

    public static function canReopenDemand($login, $demand)
    {
        return $demand
            && $login !== ''
            && $demand['statut'] === 'cloturee'
            && self::isAdmin($login);
    }

    public static function canCommentDemand($login, $demand)
    {
        if (!$demand || $login === '' || $demand['statut'] === 'cloturee') {
            return false;
        }

        return self::canViewDemand($login, $demand);
    }

    public static function canViewInternalComments($login, $demand)
    {
        if (!$demand || $login === '') {
            return false;
        }

        return self::isAdmin($login)
            || SuiviDemandesRepository::userManagesDemandResource((int) $demand['id'], $login);
    }

    public static function canAddInternalComment($login, $demand)
    {
        return self::canCommentDemand($login, $demand)
            && self::canViewInternalComments($login, $demand);
    }

    public static function canUploadAttachment($login, $demand)
    {
        return self::canCommentDemand($login, $demand);
    }

    public static function canViewAttachment($login, $demand, $attachment)
    {
        if (!$attachment || !self::canViewDemand($login, $demand)) {
            return false;
        }

        $commentId = isset($attachment['commentaire_id']) ? (int) $attachment['commentaire_id'] : 0;
        if ($commentId <= 0 || !SuiviDemandesRepository::commentIsInternal($commentId)) {
            return true;
        }

        return self::canViewInternalComments($login, $demand);
    }

    public static function canDeleteAttachment($login, $demand, $attachment)
    {
        if (!$demand || !$attachment || $login === '') {
            return false;
        }

        return self::canViewAttachment($login, $demand, $attachment)
            && (self::isAdmin($login)
                || ($demand['statut'] !== 'cloturee' && isset($attachment['uploader']) && $attachment['uploader'] === $login));
    }

    public static function canDeleteDemand($login, $demand)
    {
        return $demand
            && $login !== ''
            && self::isAdmin($login);
    }

    public static function canResendManagerNotification($login, $demand)
    {
        return $demand
            && $login !== ''
            && self::isAdmin($login);
    }

    public static function canManageFollowers($login, $demand)
    {
        if (!$demand || $login === '') {
            return false;
        }

        $demandeId = (int) $demand['id'];

        if (self::isAdmin($login)) {
            return true;
        }

        if ($demand['statut'] === 'cloturee') {
            return false;
        }

        return SuiviDemandesRepository::sameLogin($demand['createur'], $login)
            || SuiviDemandesRepository::userManagesDemandResource($demandeId, $login);
    }

    public static function canManageResources($login, $demand)
    {
        if (!$demand || $login === '') {
            return false;
        }

        if (self::isAdmin($login)) {
            return true;
        }

        if ($demand['statut'] === 'cloturee') {
            return false;
        }

        return SuiviDemandesRepository::userManagesDemandResource((int) $demand['id'], $login);
    }
}
