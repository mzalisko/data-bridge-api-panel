<?php

declare(strict_types=1);

namespace App\Core;

class Layout
{
    /**
     * SVG icons — 18×18, stroke-width 1.5, currentColor, fill none.
     */
    private static function icon(string $name): string
    {
        $icons = [
            'dashboard' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
            'groups'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2z"/><path d="M8 7v12M16 7v12"/></svg>',
            'sites'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>',
            'api-keys'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
            'logs'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            'settings'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
            'search'    => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'bell'      => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>',
            'sun'       => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>',
            'moon'      => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>',
            'plus'      => '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
            'more'      => '<svg width="10" height="10" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="2" r="1" fill="currentColor"/><circle cx="7" cy="7" r="1" fill="currentColor"/><circle cx="7" cy="12" r="1" fill="currentColor"/></svg>',
            'chevron'   => '<svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4L6 8L10 4"/></svg>',
        ];

        return $icons[$name] ?? '';
    }

    /**
     * Render opening HTML: doctype, head, rail, topbar, opening admin-content.
     *
     * @param string   $pageTitle   Displayed in <title> and topbar
     * @param string   $activeRoute Current route, marks rail item active
     * @param string   $ctaLabel    CTA button label. Empty = hide button.
     * @param string   $ctaHref     CTA button href.
     * @param string   $ctaModal    Optional data-modal-open ID.
     * @param array<array{label:string,href:string,active?:bool}> $tabs  Topbar tab nav items.
     */
    public static function start(
        string $pageTitle,
        string $activeRoute = '',
        string $ctaLabel = '',
        string $ctaHref = '#',
        string $ctaModal = '',
        array  $tabs = []
    ): void {
        $rawName   = (string) Session::get('user_name', 'User');
        $rawRole   = (string) Session::get('user_role', '');
        $userName  = htmlspecialchars($rawName,  ENT_QUOTES, 'UTF-8');
        $userInit  = htmlspecialchars(mb_strtoupper(mb_substr($rawName, 0, 2)), ENT_QUOTES, 'UTF-8');
        $userInit  = $userInit ?: 'U';
        $titleSafe = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        $ctaSafe   = htmlspecialchars($ctaLabel,  ENT_QUOTES, 'UTF-8');
        $ctaHrefS  = htmlspecialchars($ctaHref,   ENT_QUOTES, 'UTF-8');
        $ctaModalS = htmlspecialchars($ctaModal,  ENT_QUOTES, 'UTF-8');
        $ctaAttr   = $ctaModal !== ''
            ? " data-modal-open=\"{$ctaModalS}\""
            : " href=\"{$ctaHrefS}\"";

        $ctaButton = $ctaLabel !== ''
            ? "<a{$ctaAttr} class=\"topbar-cta\">" . self::icon('plus') . " {$ctaSafe}</a>"
            : '';

        // Tab nav HTML
        $tabsHtml = '';
        if (!empty($tabs)) {
            $tabsHtml = '<nav class="topbar-tabs">';
            foreach ($tabs as $tab) {
                $tabLabel  = htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8');
                $tabHref   = htmlspecialchars($tab['href'],  ENT_QUOTES, 'UTF-8');
                $tabActive = !empty($tab['active']) ? ' is-active' : '';
                $tabsHtml .= "<a href=\"{$tabHref}\" class=\"topbar-tab{$tabActive}\">{$tabLabel}</a>";
            }
            $tabsHtml .= '</nav>';
        }

        // Rail nav items
        $railNav  = self::railItems();
        $searchIc = self::icon('search');
        $bellIc   = self::icon('bell');
        $sunIc    = self::icon('sun');
        $moonIc   = self::icon('moon');

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleSafe} — DataBridgeApi</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/theme.js"></script>
</head>
<body>
<div class="admin-layout">

    <!-- Icon Rail -->
    <nav class="admin-rail">
        <a href="/dashboard" class="rail-logo" title="DataBridgeApi">DB</a>
HTML;

        foreach ($railNav as $item) {
            $active = ($item['route'] === $activeRoute) ? ' is-active' : '';
            $href   = htmlspecialchars($item['route'], ENT_QUOTES, 'UTF-8');
            $label  = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
            $icon   = self::icon($item['icon']);
            $sep    = !empty($item['sep_before']) ? '<div class="rail-sep"></div>' : '';

            echo <<<HTML
        {$sep}
        <a href="{$href}" class="rail-item{$active}" title="{$label}">{$icon}</a>
HTML;
        }

        echo <<<HTML

        <div class="rail-bottom">
            <a href="/logout" class="rail-avatar" title="{$userName} — Logout">{$userInit}</a>
        </div>
    </nav>

    <!-- Main -->
    <div class="admin-main">
        <header class="admin-topbar">
            <span class="topbar-title">{$titleSafe}</span>
            {$tabsHtml}
            <div class="topbar-spacer"></div>

            <div class="theme-toggle" id="theme-toggle" title="Switch theme">
                <button class="theme-toggle__btn" id="btn-light" type="button">{$sunIc} Light</button>
                <button class="theme-toggle__btn" id="btn-dark"  type="button">{$moonIc} Dark</button>
            </div>

            <div class="topbar-search">
                <span class="topbar-search__icon">{$searchIc}</span>
                <input class="topbar-search__input" type="text" placeholder="Пошук...">
            </div>
            <button class="topbar-icon-btn" type="button" title="Notifications">{$bellIc}</button>
            {$ctaButton}
        </header>
        <main class="admin-content">
HTML;
    }

    public static function end(): void
    {
        echo <<<HTML
        </main>
    </div>
</div>
<script src="/assets/js/layout.js"></script>
</body>
</html>
HTML;
    }

    /**
     * Rail navigation items.
     * @return array<array{route:string,label:string,icon:string,sep_before?:bool}>
     */
    private static function railItems(): array
    {
        return [
            ['route' => '/dashboard',  'label' => 'Dashboard',  'icon' => 'dashboard'],
            ['route' => '/site-groups','label' => 'Site Groups','icon' => 'groups'],
            ['route' => '/sites',      'label' => 'Sites',      'icon' => 'sites'],
            ['route' => '/api-keys',   'label' => 'API Keys',   'icon' => 'api-keys', 'sep_before' => true],
            ['route' => '/logs',       'label' => 'Logs',       'icon' => 'logs'],
            ['route' => '/settings',   'label' => 'Settings',   'icon' => 'settings'],
        ];
    }
}
