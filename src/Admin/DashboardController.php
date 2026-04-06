<?php

declare(strict_types=1);

namespace App\Admin;

use App\Auth\AuthGuard;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Layout;
use App\Core\Session;
use PDO;

class DashboardController
{
    public function index(): void
    {
        AuthGuard::require();

        $pdo = Database::getInstance()->getConnection();

        // ── Stats ───────────────────────────────────────────────────────────

        $stats['sites']    = (int) $pdo->query('SELECT COUNT(*) FROM sites')->fetchColumn();
        $stats['groups']   = (int) $pdo->query('SELECT COUNT(*) FROM site_groups')->fetchColumn();
        $stats['api_keys'] = (int) $pdo->query('SELECT COUNT(*) FROM api_keys WHERE is_active = 1')->fetchColumn();

        $connRow = $pdo->query(
            "SELECT
                SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END)                       AS cnt_off,
                SUM(CASE WHEN s.is_active = 1 AND ak.id IS NULL THEN 1 ELSE 0 END)     AS cnt_pause,
                SUM(CASE WHEN s.is_active = 1 AND ak.id IS NOT NULL THEN 1 ELSE 0 END) AS cnt_ok
             FROM sites s
             LEFT JOIN api_keys ak ON ak.site_id = s.id AND ak.is_active = 1"
        )->fetch(PDO::FETCH_ASSOC);

        $stats['conn_ok']    = (int) ($connRow['cnt_ok']    ?? 0);
        $stats['conn_pause'] = (int) ($connRow['cnt_pause'] ?? 0);
        $stats['conn_off']   = (int) ($connRow['cnt_off']   ?? 0);

        // ── Groups ──────────────────────────────────────────────────────────

        $stmt = $pdo->query(
            "SELECT sg.*, 
                    COUNT(s.id) AS site_count,
                    SUM(CASE WHEN s.is_active = 0 THEN 1 ELSE 0 END) AS cnt_disabled,
                    SUM(CASE WHEN s.is_active = 1 AND ak.id IS NULL THEN 1 ELSE 0 END) AS cnt_off,
                    SUM(CASE WHEN s.is_active = 1 AND ak.id IS NOT NULL AND ak.is_active = 0 THEN 1 ELSE 0 END) AS cnt_pause,
                    SUM(CASE WHEN s.is_active = 1 AND ak.id IS NOT NULL AND ak.is_active = 1 THEN 1 ELSE 0 END) AS cnt_ok
             FROM site_groups sg
             LEFT JOIN sites s ON s.group_id = sg.id
             LEFT JOIN api_keys ak ON ak.site_id = s.id AND ak.is_active = 1
             GROUP BY sg.id
             ORDER BY sg.name"
        );
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flash = Session::get('flash_error', '');
        Session::forget('flash_error');

        // ── Render ──────────────────────────────────────────────────────────

        $tabs = [
            ['label' => 'Огляд',     'href' => '/dashboard', 'active' => true],
            ['label' => 'Групи',     'href' => '/site-groups'],
            ['label' => 'Аналітика', 'href' => '#'],
        ];
        Layout::start('Dashboard', '/dashboard', 'Нова група', '#', 'modal-new-group', $tabs);

        if ($flash) {
            echo '<div class="flash-error">' . htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        echo self::renderStatCards($stats);

        $groupCount = count($groups);
        echo <<<HTML
<div class="section-hd">
    <div class="section-hd__left">
        <span class="section-title">Групи сайтів</span>
        <span class="section-meta">{$groupCount} груп · {$stats['sites']} сайтів</span>
    </div>
    <div class="section-actions">
        <button class="sec-btn" type="button">Фільтр</button>
        <button class="sec-btn sec-btn--primary" type="button" data-modal-open="modal-new-group">+ Нова група</button>
    </div>
</div>
HTML;

        echo self::renderGroupGrid($groups);
        
        echo '<meta name="_csrf" id="csrf-token" content="' . htmlspecialchars(CSRF::getToken(), ENT_QUOTES, 'UTF-8') . '">';
        echo SiteGroupsController::renderModals($groups, CSRF::getToken());
        echo '<script src="/assets/js/site-groups.js"></script>';

        Layout::end();
    }

    /**
     * @param list<array<string,mixed>> $groups
     */
    private static function renderGroupGrid(array $groups): string
    {
        $html = '<div class="group-grid">';

        foreach ($groups as $group) {
            $id      = (int) $group['id'];
            $name    = htmlspecialchars((string) $group['name'],               ENT_QUOTES, 'UTF-8');
            $desc    = htmlspecialchars((string) ($group['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $color   = htmlspecialchars((string) ($group['color'] ?? '#6366F1'), ENT_QUOTES, 'UTF-8');
            $count   = (int) $group['site_count'];
            $ok      = (int) ($group['cnt_ok']       ?? 0);
            $pause   = (int) ($group['cnt_pause']    ?? 0);
            $off     = (int) ($group['cnt_off']      ?? 0);
            $disabled = (int) ($group['cnt_disabled'] ?? 0);
            $active  = $ok;

            // Status: show worst state
            if ($ok > 0 && $pause === 0 && $off === 0 && $disabled === 0) {
                $dotColor   = 'var(--dot-ok)';
                $statusText = 'Active';
            } elseif ($pause > 0 || $off > 0) {
                $dotColor   = $off > 0 ? 'var(--dot-off)' : 'var(--dot-pause)';
                $statusText = $off > 0 ? 'Issues' : 'Pause';
            } else {
                $dotColor   = 'var(--dot-disabled)';
                $statusText = 'Disabled';
            }

            $descHtml = $desc !== ''
                ? "<div class=\"gc-desc\">{$desc}</div>"
                : '';

            $html .= <<<HTML
        <a href="/site-groups/{$id}" class="group-card" style="--group-color:{$color}; border-top-color:{$color};">
            <div class="gc-name">{$name}</div>
            {$descHtml}
            <div class="gc-stats">
                <div class="gc-stat-item">
                    <span class="gc-stat-n">{$count}</span>
                    <span class="gc-stat-l">Сайтів</span>
                </div>
                <div class="gc-stat-item">
                    <span class="gc-stat-n">{$active}</span>
                    <span class="gc-stat-l">Активних</span>
                </div>
            </div>
            <div class="gc-footer">
                <span class="gc-status-dot" style="background:{$dotColor};"></span>
                <span class="gc-status-txt">{$statusText}</span>
                <span class="gc-more" title="Дії">···</span>
            </div>
        </a>
HTML;
        }

        // "New group" ghost card
        $html .= <<<HTML
        <div class="group-card-new" data-modal-open="modal-new-group">
            <span class="group-card-new__plus">+</span>
            <span>Нова група</span>
        </div>
HTML;

        $html .= '</div>';
        return $html;
    }

    /**
     * @param array<string, mixed> $stats
     */
    private static function renderStatCards(array $stats): string
    {
        $sitesIc  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>';
        $groupsIc = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>';
        $keysIc   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
        $connIc   = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>';

        $s = (int) $stats['sites'];
        $g = (int) $stats['groups'];
        $k = (int) $stats['api_keys'];
        $ok    = (int) $stats['conn_ok'];
        $pause = (int) $stats['conn_pause'];
        $off   = (int) $stats['conn_off'];

        return <<<HTML
<div class="stat-row">

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">Сайти</span>
            <span class="stat-card__icon">{$sitesIc}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$s}</div>
            <div class="stat-card__trend">Всього в системі</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">Групи</span>
            <span class="stat-card__icon">{$groupsIc}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$g}</div>
            <div class="stat-card__trend">Груп сайтів</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">API Ключі</span>
            <span class="stat-card__icon">{$keysIc}</span>
        </div>
        <div class="stat-card__body">
            <div class="stat-card__value">{$k}</div>
            <div class="stat-card__trend">Активних ключів</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card__head">
            <span class="stat-card__label">З'єднання</span>
            <span class="stat-card__icon">{$connIc}</span>
        </div>
        <div class="stat-card__body">
            <div class="conn-breakdown">
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-ok)"></span>
                        Активні
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--ok">{$ok}</span>
                </div>
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-pause)"></span>
                        Пауза
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--pause">{$pause}</span>
                </div>
                <div class="conn-breakdown__row">
                    <span class="conn-breakdown__label">
                        <span class="conn-breakdown__dot" style="background:var(--dot-off)"></span>
                        Вимкнені
                    </span>
                    <span class="conn-breakdown__count conn-breakdown__count--off">{$off}</span>
                </div>
            </div>
        </div>
    </div>

</div>
HTML;
    }
}
