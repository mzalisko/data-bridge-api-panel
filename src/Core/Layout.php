<?php

declare(strict_types=1);

namespace App\Core;

class Layout
{
    /**
     * SVG icon definitions. Returns inline SVG string.
     * All icons: 13×13px, stroke-width 1.4, currentColor, fill none.
     */
    private static function icon(string $name): string
    {
        $icons = [
            'dashboard' => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="1" width="6" height="6" stroke="currentColor" stroke-width="1.4"/><rect x="1" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="9" width="6" height="6" stroke="currentColor" stroke-width="1.4"/></svg>',
            'groups'    => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 1L15 4.5L8 8L1 4.5L8 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M1 8L8 11.5L15 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M1 11.5L8 15L15 11.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'sites'     => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M8 1C8 1 5.5 4 5.5 8S8 15 8 15" stroke="currentColor" stroke-width="1.4"/><path d="M8 1C8 1 10.5 4 10.5 8S8 15 8 15" stroke="currentColor" stroke-width="1.4"/><path d="M1.5 8H14.5" stroke="currentColor" stroke-width="1.4"/></svg>',
            'api-keys'  => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="5.5" cy="8" r="3.5" stroke="currentColor" stroke-width="1.4"/><path d="M9 8H15M13 6V8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'logs'      => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 4H13M3 8H13M3 12H9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'settings'  => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="8" cy="8" r="2.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 1.5V3M8 13V14.5M1.5 8H3M13 8H14.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M3.4 3.4L4.5 4.5M11.5 11.5L12.6 12.6M12.6 3.4L11.5 4.5M4.5 11.5L3.4 12.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'search'    => '<svg width="13" height="13" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M9 9L13 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'bell'      => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 2C5.8 2 4 4 4 6V10L2 12H14L12 10V6C12 4 10.2 2 8 2Z" stroke="currentColor" stroke-width="1.3"/><path d="M6 12C6 13.1 6.9 14 8 14S10 13.1 10 12" stroke="currentColor" stroke-width="1.3"/></svg>',
            'moon'      => '<svg width="13" height="13" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.5 10.5A6 6 0 015.5 2.5a6 6 0 108 8z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'more'      => '<svg width="10" height="10" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="7" cy="2" r="1" fill="currentColor"/><circle cx="7" cy="7" r="1" fill="currentColor"/><circle cx="7" cy="12" r="1" fill="currentColor"/></svg>',
            'chevron'   => '<svg width="10" height="10" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$name] ?? '';
    }

    /**
     * Render opening HTML: doctype, head, sidebar, topbar, opening admin-content.
     *
     * @param string $pageTitle   Displayed in <title> and topbar
     * @param string $activeRoute Current route, marks sidebar item active
     * @param string $ctaLabel    CTA button label in topbar. Empty = hide button.
     * @param string $ctaHref     CTA button href.
     */
    public static function start(
        string $pageTitle,
        string $activeRoute = '',
        string $ctaLabel = '',
        string $ctaHref = '#'
    ): void {
        $userName  = htmlspecialchars((string) Session::get('user_name', 'User'), ENT_QUOTES, 'UTF-8');
        $userRole  = htmlspecialchars((string) Session::get('user_role', ''), ENT_QUOTES, 'UTF-8');
        $userInit  = mb_strtoupper(mb_substr($userName, 0, 2));
        $titleSafe = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
        $ctaSafe   = htmlspecialchars($ctaLabel, ENT_QUOTES, 'UTF-8');
        $ctaHrefS  = htmlspecialchars($ctaHref, ENT_QUOTES, 'UTF-8');

        $ctaButton = $ctaLabel !== ''
            ? "<a href=\"{$ctaHrefS}\" class=\"topbar-cta\">+ {$ctaSafe}</a>"
            : '';

        $nav = self::navItems();

        $bellIcon   = self::icon('bell');
        $searchIcon = self::icon('search');
        $moonIcon   = self::icon('moon');

        header('Content-Type: text/html; charset=UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="uk" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titleSafe} — DataBridgeApi</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/theme.js"></script>
</head>
<body>
<div class="admin-layout">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="/dashboard" class="sidebar-logo">
            <span class="sidebar-logo__mark">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" fill="#fff"/><rect x="9" y="1" width="6" height="6" fill="#fff" opacity=".7"/><rect x="1" y="9" width="6" height="6" fill="#fff" opacity=".7"/><rect x="9" y="9" width="6" height="6" fill="#fff" opacity=".4"/></svg>
            </span>
            <span>
                <div class="sidebar-logo__brand">DataBridge</div>
                <div class="sidebar-logo__ver">Admin Panel v1</div>
            </span>
        </a>

        <nav class="sidebar-nav">
HTML;

        foreach ($nav as $section => $items) {
            $sectionSafe = htmlspecialchars($section, ENT_QUOTES, 'UTF-8');
            echo "            <span class=\"sidebar-nav__section\">{$sectionSafe}</span>\n";

            foreach ($items as $item) {
                $active  = ($item['route'] === $activeRoute) ? ' is-active' : '';
                $href    = htmlspecialchars($item['route'], ENT_QUOTES, 'UTF-8');
                $label   = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
                $icon    = self::icon($item['icon']);
                $counter = isset($item['count'])
                    ? '<span class="sidebar-nav__count">' . (int) $item['count'] . '</span>'
                    : '';

                echo <<<HTML
            <a href="{$href}" class="sidebar-nav__item{$active}">
                <span class="sidebar-nav__icon">{$icon}</span>
                <span class="sidebar-nav__label">{$label}</span>
                {$counter}
            </a>
HTML;
            }
        }

        echo <<<HTML
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-user__avatar">{$userInit}</div>
                <div>
                    <div class="sidebar-user__name">{$userName}</div>
                    <div class="sidebar-user__role">{$userRole}</div>
                </div>
                <a href="/logout" class="sidebar-user__logout" title="Logout">&#x2192;</a>
            </div>
        </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <header class="admin-topbar">
            <span class="topbar-title">{$titleSafe}</span>
            <div class="topbar-spacer"></div>
            <div class="topbar-search">{$searchIcon} Search...</div>
            <button class="topbar-icon-btn" title="Notifications">{$bellIcon}</button>
            <button class="topbar-icon-btn topbar-theme-btn" id="theme-toggle" title="Toggle theme">{$moonIcon}</button>
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
<script>
// Kanban card expand/collapse
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.site-card').forEach(function (card) {
        card.addEventListener('click', function (e) {
            // Don't toggle if clicking copy or action buttons
            if (e.target.closest('.site-card__copy-btn') || e.target.closest('.site-card__action-btn')) return;
            card.classList.toggle('is-open');
        });
    });

    // Copy API key to clipboard
    document.querySelectorAll('.site-card__copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.dataset.key;
            if (key && navigator.clipboard) {
                navigator.clipboard.writeText(key).then(function () {
                    var orig = btn.textContent;
                    btn.textContent = '✓';
                    setTimeout(function () { btn.textContent = orig; }, 1200);
                });
            }
        });
    });
});
</script>
</body>
</html>
HTML;
    }

    /**
     * Nav items definition.
     * 'count' key is optional — shows counter badge when present.
     *
     * @return array<string, list<array{route:string, label:string, icon:string, count?:int}>>
     */
    private static function navItems(): array
    {
        return [
            'Головне' => [
                ['route' => '/dashboard',   'label' => 'Dashboard',   'icon' => 'dashboard'],
                ['route' => '/site-groups', 'label' => 'Site Groups', 'icon' => 'groups',   'count' => 0],
                ['route' => '/sites',       'label' => 'Sites',       'icon' => 'sites',    'count' => 0],
            ],
            'Доступ' => [
                ['route' => '/api-keys',    'label' => 'API Keys',    'icon' => 'api-keys', 'count' => 0],
                ['route' => '/logs',        'label' => 'Logs',        'icon' => 'logs'],
            ],
            'Система' => [
                ['route' => '/settings',    'label' => 'Settings',    'icon' => 'settings'],
            ],
        ];
    }
}
