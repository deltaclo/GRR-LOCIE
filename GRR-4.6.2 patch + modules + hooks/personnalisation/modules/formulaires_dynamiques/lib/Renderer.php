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

        return self::renderDashboard($login, true);
    }

    public static function appPage($login)
    {
        if (!FormulairesDynamiquesConfig::isEnabled()) {
            return '<div class="alert alert-warning">Le module est desactive.</div>';
        }

        if ((string) $login === '') {
            return '<div class="alert alert-warning">Acces refuse. Connectez-vous a GRR pour acceder a ce module.</div>';
        }

        return self::renderDashboard((string) $login, false);
    }

    private static function renderDashboard($login, $fromAccount)
    {
        $canManage = FormulairesDynamiquesRights::canManageModule($login);
        $adminUrl = $fromAccount
            ? 'compte.php?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE).'&amp;admin=1'
            : 'compte/compte.php?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE).'&amp;admin=1';
        $appUrl = $fromAccount
            ? '../app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE)
            : 'app.php?p='.rawurlencode(FormulairesDynamiquesConfig::APP_PAGE);

        $html = '<section id="formulaires-dynamiques">'
            .self::assets()
            .'<h2>'.self::html(FormulairesDynamiquesConfig::displayName()).'</h2>'
            .'<p class="text-muted">Socle du module installe. La creation des formulaires sera ajoutee a l etape suivante.</p>'
            .'<div class="formdyn-actions">'
                .'<a class="btn btn-default" href="'.self::html($appUrl).'">Ouvrir via app.php</a> '
                .($canManage ? '<a class="btn btn-primary" href="'.self::html($adminUrl).'">Administration du module</a>' : '')
            .'</div>'
            .self::renderCounters()
            .self::renderAccessSummary($login, $canManage)
            .'</section>';

        return $html;
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
        $rows = array(
            'Utilisateur courant' => $login,
            'Profil module' => $canManage ? 'Gestionnaire' : 'Utilisateur',
            'Pages autonomes' => FormulairesDynamiquesConfig::autonomousEnabled() ? 'Activees' : 'Desactivees',
            'Notifications' => FormulairesDynamiquesConfig::notificationsEnabled() ? 'Activees' : 'Desactivees',
        );

        $html = '<section class="formdyn-panel"><h3>Etat du module</h3><table class="table table-striped"><tbody>';
        foreach ($rows as $label => $value) {
            $html .= '<tr><th>'.self::html($label).'</th><td>'.self::html($value).'</td></tr>';
        }

        return $html.'</tbody></table></section>';
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
            .'</style>';
    }

    public static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
