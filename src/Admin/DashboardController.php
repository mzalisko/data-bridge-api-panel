<?php

declare(strict_types=1);

namespace App\Admin;

use App\Auth\AuthGuard;
use App\Core\Layout;

class DashboardController
{
    public function index(): void
    {
        AuthGuard::require();

        // TODO: replace with real DB queries when SiteRepository is built
        $stats = [
            'sites'        => 142,
            'groups'       => 18,
            'api_keys'     => 67,
            'conn_ok'      => 138,
            'conn_pause'   => 2,
            'conn_off'     => 2,
            'sites_trend'  => '+3 сьогодні',
            'groups_trend' => '+1 сьогодні',
        ];

        // Mock site data grouped by group name — replace with DB query
        $groups = [
            'Group Alpha' => [
                [
                    'name'        => 'example.com',
                    'domain'      => 'https://example.com',
                    'api_key'     => 'dbapi_a1b2c3d4e5f6g7h8',
                    'conn'        => 'ok',
                    'phones'      => 312,
                    'addresses'   => 891,
                    'socials'     => 45,
                    'prices'      => 2100,
                    'last_sync'   => '2026-04-02 14:32',
                ],
                [
                    'name'        => 'news.portal.com',
                    'domain'      => 'https://news.portal.com',
                    'api_key'     => 'dbapi_z9y8x7w6v5u4t3s2',
                    'conn'        => 'pause',
                    'phones'      => 0,
                    'addresses'   => 120,
                    'socials'     => 8,
                    'prices'      => 450,
                    'last_sync'   => '2026-04-01 09:15',
                ],
            ],
            'Group Beta' => [
                [
                    'name'        => 'shop.store.ua',
                    'domain'      => 'https://shop.store.ua',
                    'api_key'     => 'dbapi_m1n2o3p4q5r6s7t8',
                    'conn'        => 'ok',
                    'phones'      => 55,
                    'addresses'   => 210,
                    'socials'     => 12,
                    'prices'      => 8800,
                    'last_sync'   => '2026-04-02 16:00',
                ],
                [
                    'name'        => 'api.service.io',
                    'domain'      => 'https://api.service.io',
                    'api_key'     => 'dbapi_u1v2w3x4y5z6a7b8',
                    'conn'        => 'ok',
                    'phones'      => 10,
                    'addresses'   => 34,
                    'socials'     => 3,
                    'prices'      => 290,
                    'last_sync'   => '2026-04-02 15:45',
                ],
            ],
            'Group Gamma' => [
                [
                    'name'        => 'old-site.net',
                    'domain'      => 'https://old-site.net',
                    'api_key'     => 'dbapi_c1d2e3f4g5h6i7j8',
                    'conn'        => 'off',
                    'phones'      => 0,
                    'addresses'   => 0,
                    'socials'     => 0,
                    'prices'      => 0,
                    'last_sync'   => 'ніколи',
                ],
            ],
        ];

        Layout::start('Dashboard', '/dashboard', 'Новий сайт', '/sites/create');

        echo self::renderStatCards($stats);
        echo self::renderKanbanBoard($groups);

        Layout::end();
    }

    /**
     * Render 4 stat cards in Format Y layout.
     *
     * @param array<string, mixed> $stats
     */
    private static function renderStatCards(array $stats): string
    {
        $sitesIcon  = '<svg width="13" height="13" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.4"/><path d="M8 1C8 1 5.5 4 5.5 8S8 15 8 15" stroke="currentColor" stroke-width="1.4"/><path d="M8 1C8 1 10.5 4 10.5 8S8 15 8 15" stroke="currentColor" stroke-width="1.4"/><path d="M1.5 8H14.5" stroke="currentColor" stroke-width="1.4"/></svg>';
        $groupsIcon = '<svg width="13" height="13" viewBox="0 0 16 16" fill="none"><path d="M8 1L15 4.5L8 8L1 4.5L8 1Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M1 8L8 11.5L15 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
        $keysIcon   = '<svg width="13" height="13" viewBox="0 0 16 16" fill="none"><circle cx="5.5" cy="8" r="3.5" stroke="currentColor" stroke-width="1.4"/><path d="M9 8H15M13 6V8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';
        $wifiIcon   = '<svg width="13" height="13" viewBox="0 0 16 16" fill="none"><path d="M1 8C1 8 3 4 8 4C13 4 15 8 15 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M4 8C4 8 5.5 6.5 8 6.5S12 8 12 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="8" cy="10.5" r="1.5" fill="currentColor"/></svg>';

        $connOk    = (int) $stats['conn_ok'];
        $connPause = (int) $stats['conn_pause'];
        $connOff   = (int) $stats['conn_off'];
        $totalSites  = (int) $stats['sites'];
        $totalGroups = (int) $stats['groups'];
        $totalKeys   = (int) $stats['api_keys'];

        $sitesTrend  = htmlspecialchars((string) $stats['sites_trend'],  ENT_QUOTES, 'UTF-8');
        $groupsTrend = htmlspecialchars((string) $stats['groups_trend'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="stat-row">

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">Всього сайтів</span>
            <span class="stat-card__icon">{$sitesIcon}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$totalSites}</div>
            <div class="stat-card__trend">↑ {$sitesTrend}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">Групи сайтів</span>
            <span class="stat-card__icon">{$groupsIcon}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$totalGroups}</div>
            <div class="stat-card__trend">↑ {$groupsTrend}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">API Ключі</span>
            <span class="stat-card__icon">{$keysIcon}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$totalKeys}</div>
            <div class="stat-card__trend stat-card__trend--neutral">Без змін</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">Підключення</span>
            <span class="stat-card__icon">{$wifiIcon}</span>
        </div>
        <div class="stat-card__body">
            <div class="conn-breakdown">
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-ok)"></span>
                        Підключений
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--ok">{$connOk}</span>
                </div>
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-pause)"></span>
                        На паузі
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--pause">{$connPause}</span>
                </div>
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-off)"></span>
                        Відключений
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--off">{$connOff}</span>
                </div>
            </div>
        </div>
    </div>

</div>
HTML;
    }

    /**
     * Render kanban board: groups as columns, site cards inside.
     *
     * @param array<string, list<array{name:string, domain:string, api_key:string, conn:string, phones:int, addresses:int, socials:int, prices:int, last_sync:string}>> $groups
     */
    private static function renderKanbanBoard(array $groups): string
    {
        $connLabels = ['ok' => 'Підключений', 'pause' => 'На паузі', 'off' => 'Відключений'];
        $chevron    = '<svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $totalSites = array_sum(array_map('count', $groups));

        $columns = '';
        foreach ($groups as $groupName => $sites) {
            $groupSafe  = htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8');
            $siteCount  = count($sites);

            $cards = '';
            foreach ($sites as $site) {
                $conn        = $site['conn'];
                $allowedConn = ['ok', 'pause', 'off'];
                $connSafe    = in_array($conn, $allowedConn, true) ? $conn : 'off';
                $badgeClass  = 'conn-badge--' . $connSafe;
                $badgeLabel = htmlspecialchars($connLabels[$conn] ?? 'Невідомо', ENT_QUOTES, 'UTF-8');
                $name       = htmlspecialchars($site['name'],    ENT_QUOTES, 'UTF-8');
                $domain     = htmlspecialchars($site['domain'],  ENT_QUOTES, 'UTF-8');
                $apiKey     = $site['api_key'];
                $apiMasked  = htmlspecialchars(substr($apiKey, 0, 10) . '••••••••', ENT_QUOTES, 'UTF-8');
                $apiKeyEsc  = htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');
                $phones     = (int) $site['phones'];
                $addresses  = (int) $site['addresses'];
                $socials    = (int) $site['socials'];
                $prices     = (int) $site['prices'];
                $lastSync   = htmlspecialchars($site['last_sync'], ENT_QUOTES, 'UTF-8');

                $cards .= <<<HTML

                <div class="site-card">
                    <div class="site-card__summary">
                        <div class="site-card__top">
                            <div>
                                <div class="site-card__name">{$name}</div>
                                <div class="site-card__domain">{$domain}</div>
                            </div>
                            <span class="site-card__chevron">{$chevron}</span>
                        </div>
                        <div class="site-card__row">
                            <span class="conn-badge {$badgeClass}">{$badgeLabel}</span>
                        </div>
                        <div class="site-card__row">
                            <span class="site-card__apikey">{$apiMasked}</span>
                            <button class="site-card__copy-btn" data-key="{$apiKeyEsc}" title="Copy API key">copy</button>
                        </div>
                    </div>
                    <div class="site-card__details">
                        <div class="site-card__detail-row">
                            <span class="site-card__detail-label">Телефони</span>
                            <span class="site-card__detail-value">{$phones}</span>
                        </div>
                        <div class="site-card__detail-row">
                            <span class="site-card__detail-label">Адреси</span>
                            <span class="site-card__detail-value">{$addresses}</span>
                        </div>
                        <div class="site-card__detail-row">
                            <span class="site-card__detail-label">Соц. мережі</span>
                            <span class="site-card__detail-value">{$socials}</span>
                        </div>
                        <div class="site-card__detail-row">
                            <span class="site-card__detail-label">Ціни</span>
                            <span class="site-card__detail-value">{$prices}</span>
                        </div>
                        <div class="site-card__detail-row">
                            <span class="site-card__detail-label">Синхронізація</span>
                            <span class="site-card__detail-value">{$lastSync}</span>
                        </div>
                        <div class="site-card__actions">
                            <a href="/sites/edit" class="site-card__action-btn">Налаштування</a>
                            <a href="/sites/panel" class="site-card__action-btn site-card__action-btn--primary">Site Panel</a>
                        </div>
                    </div>
                </div>
HTML;
            }

            $columns .= <<<HTML

        <div class="kanban-col">
            <div class="kanban-col__header">
                <span class="kanban-col__name">{$groupSafe}</span>
                <span class="kanban-col__badge">{$siteCount}</span>
            </div>
            <div class="kanban-col__cards">
                {$cards}
            </div>
        </div>
HTML;
        }

        return <<<HTML
<div class="kanban-toolbar">
    <span class="kanban-toolbar__title">Сайти за групами</span>
    <span class="kanban-toolbar__count">{$totalSites} всього</span>
    <div class="kanban-toolbar__spacer"></div>
    <button class="kanban-filter-btn">Фільтр ▾</button>
</div>
<div class="kanban-board">
    {$columns}
</div>
HTML;
    }
}
