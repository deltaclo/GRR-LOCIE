<?php

class BoutonsPersoRenderer
{
    public static function calendarButtons()
    {
        if (!BoutonsPersoConfig::isEnabled()) {
            return '';
        }

        BoutonsPersoRepository::ensureTables();
        $buttons = self::visibleCalendarButtons();
        if (count($buttons) === 0) {
            return '';
        }

        $title = BoutonsPersoConfig::showTitle()
            ? '<div class="boutons-perso-title">'.self::html(BoutonsPersoConfig::displayName()).'</div>'
            : '';

        return '<div id="boutons-perso-calendrier" class="boutons-perso-calendrier">'
            .'<style>'
                .'#boutons-perso-calendrier{width:100%;max-width:100%;box-sizing:border-box;margin:0 0 10px 0;padding:8px;border:1px solid '.self::html(BoutonsPersoConfig::panelBorderColor()).';background:'.self::html(BoutonsPersoConfig::panelBgColor()).';border-radius:4px;}'
                .'#boutons-perso-calendrier *{box-sizing:border-box;}'
                .'#boutons-perso-calendrier .boutons-perso-title{font-size:12px;color:#60717d;margin:0 0 6px 0;text-align:center;}'
                .'#boutons-perso-calendrier .boutons-perso-list{display:flex;flex-direction:column;gap:6px;width:100%;}'
                .'#boutons-perso-calendrier .boutons-perso-btn{display:block;width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
                .self::buttonPaletteCss('#boutons-perso-calendrier .boutons-perso-btn')
            .'</style>'
            .$title
            .'<div class="boutons-perso-list">'
                .self::renderButtons($buttons)
            .'</div>'
        .'</div>';
    }

    private static function visibleCalendarButtons()
    {
        $login = self::currentLogin();
        $definitions = array();
        $visible = array();

        foreach (BoutonsPersoRepository::allButtons(false) as $button) {
            $sourceType = isset($button['source_type']) ? (string) $button['source_type'] : BoutonsPersoRepository::SOURCE_CUSTOM;
            if ($sourceType === BoutonsPersoRepository::SOURCE_CUSTOM) {
                $label = isset($button['label']) ? trim((string) $button['label']) : '';
                $url = isset($button['url']) ? trim((string) $button['url']) : '';
                if ($label !== '' && $url !== '') {
                    $visible[] = $button;
                }
                continue;
            }

            if ($sourceType !== BoutonsPersoRepository::SOURCE_MODULE) {
                continue;
            }

            $sourceKey = isset($button['source_key']) ? (string) $button['source_key'] : '';
            if (!isset($definitions[$sourceKey])) {
                $definitions[$sourceKey] = BoutonsPersoModuleRegistry::definition($sourceKey, $login);
            }
            $definition = $definitions[$sourceKey];
            if (
                !$definition
                || empty($definition['provider_available'])
                || empty($definition['external_active'])
                || empty($definition['enabled'])
                || empty($definition['can_access'])
            ) {
                continue;
            }

            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $url = isset($definition['url']) ? trim((string) $definition['url']) : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $button['label'] = $label;
            $button['url'] = $url;
            $visible[] = $button;
        }

        return $visible;
    }

    public static function managedAccountMenu()
    {
        if (!BoutonsPersoConfig::isEnabled() || !BoutonsPersoConfig::accountMenuEnabled()) {
            return '';
        }

        BoutonsPersoRepository::ensureTables();
        $buttons = self::visibleAccountMenuButtons();
        if (count($buttons) === 0) {
            return '';
        }

        return self::renderAccountMenuButtons($buttons);
    }

    private static function visibleAccountMenuButtons()
    {
        $login = self::currentLogin();
        $definitions = array();
        $visible = array();

        foreach (BoutonsPersoRepository::accountMenuModuleButtons(false) as $button) {
            $sourceKey = isset($button['source_key']) ? (string) $button['source_key'] : '';
            if (!isset($definitions[$sourceKey])) {
                $definitions[$sourceKey] = BoutonsPersoModuleRegistry::definition($sourceKey, $login);
            }
            $definition = $definitions[$sourceKey];
            if (
                !$definition
                || empty($definition['provider_available'])
                || empty($definition['external_active'])
                || empty($definition['enabled'])
                || empty($definition['can_access'])
            ) {
                continue;
            }

            $label = isset($definition['label']) ? trim((string) $definition['label']) : '';
            $url = isset($definition['url']) ? trim((string) $definition['url']) : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $button['label'] = $label;
            $button['url'] = $url;
            $visible[] = $button;
        }

        return $visible;
    }

    public static function accountMenu()
    {
        $login = self::currentLogin();
        if ($login === '' || !self::isAdmin($login)) {
            return '';
        }

        return '<br><br><a href="compte.php?pc=boutons_perso" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 boutons-perso-account-btn">'.self::html(BoutonsPersoConfig::displayName()).'</a>';
    }

    public static function accountPage()
    {
        $pc = isset($_GET['pc']) ? (string) $_GET['pc'] : '';
        if ($pc !== BoutonsPersoConfig::MODULE) {
            return '';
        }

        $login = self::currentLogin();
        if ($login === '' || !self::isAdmin($login)) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        ob_start();
        $boutons_perso_admin_embedded = true;
        include __DIR__.'/../admin.php';
        $html = ob_get_clean();

        return '<section id="boutons-perso-admin">'.$html.'</section>';
    }

    private static function renderButtons($buttons)
    {
        $html = '';
        foreach ($buttons as $button) {
            $label = isset($button['label']) ? (string) $button['label'] : '';
            $url = isset($button['url']) ? (string) $button['url'] : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $class = self::buttonClass($button);
            $style = self::buttonStyle($button);
            $attributes = '';
            if (isset($button['target_mode']) && $button['target_mode'] === 'new_tab') {
                $attributes .= ' target="_blank" rel="noopener noreferrer"';
            }

            $onclick = self::buttonOnclick($button);
            if ($onclick !== '') {
                $attributes .= ' onclick="'.self::html($onclick).'"';
            }

            $title = isset($button['tooltip']) && trim((string) $button['tooltip']) !== ''
                ? ' title="'.self::html($button['tooltip']).'"'
                : '';

            $html .= '<a class="'.self::html($class).'" href="'.self::html($url).'"'.$attributes.$title.$style.'>'.self::html($label).'</a>';
        }

        return $html;
    }

    private static function renderAccountMenuButtons($buttons)
    {
        $html = '<style>'
            .'#menu-compte .boutons-perso-account-btn{display:block;width:100%;max-width:100%;box-sizing:border-box;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
            .self::buttonPaletteCss('#menu-compte .boutons-perso-account-btn')
            .'@media (max-width:767px){#menu-compte .boutons-perso-account-btn{margin-bottom:8px;}}'
        .'</style>';
        foreach ($buttons as $button) {
            $label = isset($button['label']) ? (string) $button['label'] : '';
            $url = isset($button['url']) ? (string) $button['url'] : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $class = self::accountButtonClass($button);
            $style = self::buttonStyle($button);
            $attributes = '';
            if (isset($button['target_mode']) && $button['target_mode'] === 'new_tab') {
                $attributes .= ' target="_blank" rel="noopener noreferrer"';
            }

            $onclick = self::buttonOnclick($button);
            if ($onclick !== '') {
                $attributes .= ' onclick="'.self::html($onclick).'"';
            }

            $title = isset($button['tooltip']) && trim((string) $button['tooltip']) !== ''
                ? ' title="'.self::html($button['tooltip']).'"'
                : '';

            $html .= '<br><br><a class="'.self::html($class).'" href="'.self::html($url).'"'.$attributes.$title.$style.'>'.self::html($label).'</a>';
        }

        return $html;
    }

    private static function buttonClass($button)
    {
        $style = isset($button['button_style']) ? (string) $button['button_style'] : 'default';
        if ($style === 'custom') {
            $style = 'default';
        }

        if (!isset(BoutonsPersoConfig::buttonStyles()[$style])) {
            $style = 'default';
        }

        return 'btn btn-'.$style.' btn-sm boutons-perso-btn';
    }

    private static function accountButtonClass($button)
    {
        $style = isset($button['button_style']) ? (string) $button['button_style'] : 'default';
        if ($style === 'custom') {
            $style = 'default';
        }

        if (!isset(BoutonsPersoConfig::buttonStyles()[$style])) {
            $style = 'default';
        }

        return 'btn btn-'.$style.' col-lg-12 col-md-12 col-sm-12 col-xs-12 boutons-perso-account-btn';
    }

    private static function buttonStyle($button)
    {
        $style = isset($button['button_style']) ? (string) $button['button_style'] : 'default';
        if ($style !== 'custom') {
            return '';
        }

        $bgColor = isset($button['custom_bg_color']) ? BoutonsPersoConfig::normalizeColor($button['custom_bg_color'], '') : '';
        $textColor = isset($button['custom_text_color']) ? BoutonsPersoConfig::normalizeColor($button['custom_text_color'], '') : '';
        if ($bgColor === '' || $textColor === '') {
            return '';
        }

        return ' style="background-color:'.self::html($bgColor).';border-color:'.self::html($bgColor).';color:'.self::html($textColor).';"';
    }

    private static function buttonPaletteCss($selector)
    {
        return $selector.'.btn-secondary{background-color:#6c757d;border-color:#6c757d;color:#fff;}'
            .$selector.'.btn-secondary:hover,'.$selector.'.btn-secondary:focus{background-color:#5a6268;border-color:#545b62;color:#fff;}'
            .$selector.'.btn-dark{background-color:#343a40;border-color:#343a40;color:#fff;}'
            .$selector.'.btn-dark:hover,'.$selector.'.btn-dark:focus{background-color:#23272b;border-color:#1d2124;color:#fff;}'
            .$selector.'.btn-light{background-color:#f8f9fa;border-color:#d6d8db;color:#212529;}'
            .$selector.'.btn-light:hover,'.$selector.'.btn-light:focus{background-color:#e2e6ea;border-color:#dae0e5;color:#212529;}'
            .$selector.'.btn-purple{background-color:#6f42c1;border-color:#6f42c1;color:#fff;}'
            .$selector.'.btn-purple:hover,'.$selector.'.btn-purple:focus{background-color:#5a32a3;border-color:#512d92;color:#fff;}'
            .$selector.'.btn-maroon{background-color:#d81b60;border-color:#d81b60;color:#fff;}'
            .$selector.'.btn-maroon:hover,'.$selector.'.btn-maroon:focus{background-color:#b71550;border-color:#a31247;color:#fff;}'
            .$selector.'.btn-navy{background-color:#001f3f;border-color:#001f3f;color:#fff;}'
            .$selector.'.btn-navy:hover,'.$selector.'.btn-navy:focus{background-color:#00162d;border-color:#001020;color:#fff;}'
            .$selector.'.btn-teal{background-color:#39cccc;border-color:#39cccc;color:#fff;}'
            .$selector.'.btn-teal:hover,'.$selector.'.btn-teal:focus{background-color:#30b5b5;border-color:#2aa3a3;color:#fff;}'
            .$selector.'.btn-olive{background-color:#3d9970;border-color:#3d9970;color:#fff;}'
            .$selector.'.btn-olive:hover,'.$selector.'.btn-olive:focus{background-color:#327f5d;border-color:#2b6f51;color:#fff;}';
    }

    private static function buttonOnclick($button)
    {
        $scripts = array();
        $confirm = isset($button['confirm_message']) ? trim((string) $button['confirm_message']) : '';
        if ($confirm !== '') {
            $scripts[] = 'if (!confirm('.self::js($confirm).')) { return false; }';
        }

        if (isset($button['target_mode']) && $button['target_mode'] === 'new_window') {
            $id = isset($button['id']) ? (int) $button['id'] : 0;
            $width = self::windowSize(isset($button['window_width']) ? $button['window_width'] : 1000, 1000, 300, 2400);
            $height = self::windowSize(isset($button['window_height']) ? $button['window_height'] : 700, 700, 300, 1600);
            $name = isset($button['window_name']) ? self::windowName($button['window_name'], $id) : 'boutons_perso_'.$id;
            $features = 'width='.$width.',height='.$height.',scrollbars=yes,resizable=yes';
            $scripts[] = 'window.open(this.href, '.self::js($name).', '.self::js($features).'); return false;';
        }

        return implode(' ', $scripts);
    }

    private static function windowSize($value, $default, $min, $max)
    {
        $value = (int) $value;
        if ($value < $min || $value > $max) {
            return (int) $default;
        }

        return $value;
    }

    private static function windowName($value, $id)
    {
        $value = preg_replace('/[^a-zA-Z0-9_]/', '_', trim((string) $value));
        if ($value === '') {
            return 'boutons_perso_'.(int) $id;
        }

        return substr($value, 0, 80);
    }

    private static function isAdmin($login)
    {
        return class_exists('SecuAccess') && SecuAccess::UserLevel($login, -1) >= 6;
    }

    private static function currentLogin()
    {
        return function_exists('getUserName') ? (string) getUserName() : '';
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function js($value)
    {
        return json_encode((string) $value);
    }
}
