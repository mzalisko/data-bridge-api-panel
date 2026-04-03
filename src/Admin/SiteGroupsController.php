<?php

declare(strict_types=1);

namespace App\Admin;

use App\Auth\AuthGuard;
use App\Core\Database;
use App\Core\Layout;
use App\Core\Session;
use PDO;
use PDOException;

class SiteGroupsController
{
    // ──────────────────────────────────────────────────────────────────────
    //  View
    // ──────────────────────────────────────────────────────────────────────

    public function index(): void
    {
        AuthGuard::require();

        $pdo = Database::getInstance()->getConnection();

        // Groups with site counts
        $stmt = $pdo->query(
            'SELECT sg.*, COUNT(s.id) AS site_count
             FROM site_groups sg
             LEFT JOIN sites s ON s.group_id = sg.id
             GROUP BY sg.id
             ORDER BY sg.name'
        );
        $groups = $stmt->fetchAll();

        $flash = Session::get('flash_error', '');
        Session::forget('flash_error');

        if (empty($groups)) {
            Layout::start('Site Groups', '/site-groups');
            if ($flash) echo '<div class="flash-error">' . htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') . '</div>';
            echo self::renderToolbar(0, 0);
            echo self::renderKanbanBoard([], []);
            echo self::renderModals([]);
            echo '<script src="/assets/js/site-groups.js"></script>';
            Layout::end();
            return;
        }

        // Sites for all groups with their active api_key
        $groupIds     = array_column($groups, 'id');
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));

        $stmt = $pdo->prepare(
            "SELECT s.*, ak.key_hash, ak.is_active AS key_active
             FROM sites s
             LEFT JOIN api_keys ak ON ak.site_id = s.id AND ak.is_active = 1
             WHERE s.group_id IN ({$placeholders})
             ORDER BY s.name"
        );
        $stmt->execute($groupIds);
        $allSites = $stmt->fetchAll();

        // Index sites by group_id
        $sitesByGroup = [];
        foreach ($allSites as $site) {
            $sitesByGroup[(int) $site['group_id']][] = $site;
        }

        $totalSites = count($allSites);

        Layout::start('Site Groups', '/site-groups');
        if ($flash) {
            echo '<div class="flash-error">' . htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo self::renderToolbar(count($groups), $totalSites);
        echo self::renderKanbanBoard($groups, $sitesByGroup);
        echo self::renderModals($groups);
        echo '<script src="/assets/js/site-groups.js"></script>';
        Layout::end();
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Group CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function createGroup(): void
    {
        AuthGuard::require();

        $name = trim((string) ($_POST['name'] ?? ''));
        $desc = trim((string) ($_POST['description'] ?? ''));

        if ($name === '') {
            Session::set('flash_error', 'Назва групи є обов\'язковою.');
            header('Location: /site-groups');
            exit;
        }

        $userId = (int) Session::get('user_id');
        if ($userId <= 0) {
            Session::set('flash_error', 'Сесія недійсна. Увійдіть знову.');
            header('Location: /site-groups');
            exit;
        }
        $pdo = Database::getInstance()->getConnection();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO site_groups (name, description, created_by) VALUES (?, ?, ?)'
            );
            $stmt->execute([$name, $desc ?: null, $userId]);
        } catch (PDOException $e) {
            error_log('SiteGroupsController::createGroup — ' . $e->getMessage());
            Session::set('flash_error', 'Помилка при створенні групи.');
        }

        header('Location: /site-groups');
        exit;
    }

    /** @param array<string,string> $params */
    public function updateGroup(array $params): void
    {
        AuthGuard::require();

        $id   = (int) ($params['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($id <= 0 || $name === '') {
            Session::set('flash_error', 'Невірні дані для оновлення групи.');
            header('Location: /site-groups');
            exit;
        }

        $pdo = Database::getInstance()->getConnection();
        try {
            $stmt = $pdo->prepare('UPDATE site_groups SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
        } catch (PDOException $e) {
            error_log('SiteGroupsController::updateGroup — ' . $e->getMessage());
            Session::set('flash_error', 'Помилка при оновленні групи.');
        }

        header('Location: /site-groups');
        exit;
    }

    /** @param array<string,string> $params */
    public function deleteGroup(array $params): void
    {
        AuthGuard::require();

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /site-groups');
            exit;
        }

        $pdo = Database::getInstance()->getConnection();

        try {
            $stmt = $pdo->prepare('DELETE FROM site_groups WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // FK RESTRICT: sites still belong to this group
            error_log('SiteGroupsController::deleteGroup — ' . $e->getMessage());
            Session::set('flash_error', 'Неможливо видалити групу: спочатку видаліть або перемістіть її сайти.');
        }

        header('Location: /site-groups');
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Site CRUD
    // ──────────────────────────────────────────────────────────────────────

    public function createSite(): void
    {
        AuthGuard::require();

        $name    = trim((string) ($_POST['name']     ?? ''));
        $url     = trim((string) ($_POST['url']      ?? ''));
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $apiKey  = trim((string) ($_POST['api_key']  ?? ''));

        if ($name === '' || $url === '' || $groupId <= 0) {
            Session::set('flash_error', 'Назва, URL та група є обов\'язковими.');
            header('Location: /site-groups');
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            Session::set('flash_error', 'Невірний формат URL.');
            header('Location: /site-groups');
            exit;
        }

        // Fallback server-side key if client didn't provide a valid one
        if ($apiKey === '' || !preg_match('/^dbapi_[0-9a-f]{32}$/', $apiKey)) {
            $apiKey = 'dbapi_' . bin2hex(random_bytes(16));
        }

        $pdo = Database::getInstance()->getConnection();

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO sites (group_id, name, url, is_active) VALUES (?, ?, ?, 1)'
            );
            $stmt->execute([$groupId, $name, $url]);
            $siteId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO api_keys (site_id, key_hash, is_active) VALUES (?, ?, 1)'
            );
            $stmt->execute([$siteId, $apiKey]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('SiteGroupsController::createSite — ' . $e->getMessage());
            Session::set('flash_error', 'Помилка при створенні сайту.');
        }

        header('Location: /site-groups');
        exit;
    }

    /** @param array<string,string> $params */
    public function updateSite(array $params): void
    {
        AuthGuard::require();

        $id      = (int) ($params['id'] ?? 0);
        $name    = trim((string) ($_POST['name']     ?? ''));
        $url     = trim((string) ($_POST['url']      ?? ''));
        $groupId = (int) ($_POST['group_id'] ?? 0);

        if ($id <= 0 || $name === '' || $url === '' || $groupId <= 0) {
            Session::set('flash_error', 'Невірні дані для оновлення сайту.');
            header('Location: /site-groups');
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
            Session::set('flash_error', 'Невірний формат URL.');
            header('Location: /site-groups');
            exit;
        }

        $pdo = Database::getInstance()->getConnection();
        try {
            $stmt = $pdo->prepare(
                'UPDATE sites SET name = ?, url = ?, group_id = ? WHERE id = ?'
            );
            $stmt->execute([$name, $url, $groupId, $id]);
        } catch (PDOException $e) {
            error_log('SiteGroupsController::updateSite — ' . $e->getMessage());
            Session::set('flash_error', 'Помилка при оновленні сайту.');
        }

        header('Location: /site-groups');
        exit;
    }

    /** @param array<string,string> $params */
    public function deleteSite(array $params): void
    {
        AuthGuard::require();

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /site-groups');
            exit;
        }

        $pdo = Database::getInstance()->getConnection();
        try {
            $stmt = $pdo->prepare('DELETE FROM sites WHERE id = ?');
            $stmt->execute([$id]);
            // api_keys cascade-deletes automatically (ON DELETE CASCADE)
        } catch (PDOException $e) {
            error_log('SiteGroupsController::deleteSite — ' . $e->getMessage());
            Session::set('flash_error', 'Помилка при видаленні сайту.');
        }

        header('Location: /site-groups');
        exit;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Drag & drop AJAX
    // ──────────────────────────────────────────────────────────────────────

    /** @param array<string,string> $params */
    public function moveSite(array $params): void
    {
        AuthGuard::require();

        header('Content-Type: application/json');

        $id   = (int) ($params['id'] ?? 0);
        $body = json_decode((string) file_get_contents('php://input'), true);

        if (!is_array($body)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON.']);
            return;
        }

        $groupId = (int) ($body['group_id'] ?? 0);

        if ($id <= 0 || $groupId <= 0) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'site_id and group_id required.']);
            return;
        }

        $pdo = Database::getInstance()->getConnection();

        // Verify target group exists
        $stmt = $pdo->prepare('SELECT id FROM site_groups WHERE id = ?');
        $stmt->execute([$groupId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Group not found.']);
            return;
        }

        try {
            $stmt = $pdo->prepare('UPDATE sites SET group_id = ? WHERE id = ?');
            $stmt->execute([$groupId, $id]);
        } catch (PDOException $e) {
            error_log('SiteGroupsController::moveSite — ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Помилка при переміщенні сайту.']);
            return;
        }

        echo json_encode(['status' => 'ok', 'data' => ['site_id' => $id, 'group_id' => $groupId]]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Render helpers
    // ──────────────────────────────────────────────────────────────────────

    private static function renderToolbar(int $groupCount, int $siteCount): string
    {
        $searchIcon = '<svg width="11" height="11" viewBox="0 0 14 14" fill="none"><circle cx="6" cy="6" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M9 9L13 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';

        return <<<HTML
<div class="page-toolbar">
  <div class="page-toolbar__row">
    <span class="page-toolbar__title">Site Groups</span>
    <span class="page-toolbar__meta">{$groupCount} груп &middot; {$siteCount} сайтів</span>
    <span class="page-toolbar__spacer"></span>
    <button class="page-toolbar__cta" data-modal-open="modal-new-group">+ Нова група</button>
  </div>
  <div class="page-toolbar__row">
    <label class="page-toolbar__search">
      {$searchIcon}
      <input id="sg-search" type="text" placeholder="Пошук сайтів або груп...">
    </label>
    <select id="sg-status" class="page-toolbar__filter">
      <option value="">Всі статуси</option>
      <option value="ok">Підключений</option>
      <option value="pause">На паузі</option>
      <option value="off">Відключений</option>
    </select>
  </div>
</div>
HTML;
    }

    /**
     * @param list<array<string,mixed>> $groups
     * @param array<int,list<array<string,mixed>>> $sitesByGroup
     */
    private static function renderKanbanBoard(array $groups, array $sitesByGroup): string
    {
        $columns = '';
        foreach ($groups as $group) {
            $sites    = $sitesByGroup[(int) $group['id']] ?? [];
            $columns .= self::renderColumn($group, $sites);
        }

        $columns .= <<<HTML

    <div class="col-new" title="Створити нову групу">
      <span>+</span>
      <span>Нова група</span>
    </div>
HTML;

        return <<<HTML
<div class="kanban-board">
    {$columns}
</div>
HTML;
    }

    /**
     * @param array<string,mixed> $group
     * @param list<array<string,mixed>> $sites
     */
    private static function renderColumn(array $group, array $sites): string
    {
        $groupId   = (int) $group['id'];
        $groupName = htmlspecialchars((string) $group['name'], ENT_QUOTES, 'UTF-8');
        $siteCount = count($sites);

        $cards    = '';
        foreach ($sites as $site) {
            $cards .= self::renderCard($site, $groupId);
        }

        $dropHint = '<div class="drop-hint">&#8595; Перетягни сюди</div>';

        return <<<HTML

    <div class="kanban-col" data-group-id="{$groupId}" data-group-name="{$groupName}">
      <div class="kanban-col__header">
        <span class="kanban-col__name">{$groupName}</span>
        <span class="kanban-col__badge">{$siteCount}</span>
        <div class="kanban-col__actions">
          <div class="col-menu">
            <button class="col-menu__btn" title="Дії з групою">&#8942;</button>
            <div class="col-menu__dropdown">
              <button class="col-menu__item"
                data-modal-open="modal-edit-group"
                data-group-id="{$groupId}"
                data-group-name="{$groupName}">Редагувати групу</button>
              <button class="col-menu__item col-menu__item--danger"
                data-modal-open="modal-delete-group"
                data-group-id="{$groupId}"
                data-group-name="{$groupName}">Видалити групу</button>
            </div>
          </div>
          <button class="col-add-btn"
            data-modal-open="modal-add-site"
            data-group-id="{$groupId}"
            title="Додати сайт">+</button>
        </div>
      </div>
      <div class="kanban-col__cards">
        {$dropHint}
        {$cards}
      </div>
    </div>
HTML;
    }

    /** @param array<string,mixed> $site */
    private static function renderCard(array $site, int $groupId): string
    {
        $siteId    = (int) $site['id'];
        $name      = htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8');
        $url       = htmlspecialchars((string) $site['url'],  ENT_QUOTES, 'UTF-8');
        $apiKey    = (string) ($site['key_hash'] ?? '');
        $apiMasked = htmlspecialchars(
            $apiKey !== '' ? substr($apiKey, 0, 10) . '••••••••' : '(немає ключа)',
            ENT_QUOTES, 'UTF-8'
        );
        $apiKeyEsc = htmlspecialchars($apiKey, ENT_QUOTES, 'UTF-8');

        // Connection status
        $siteActive = (int) ($site['is_active'] ?? 1);
        $keyActive  = $site['key_active'] !== null ? (int) $site['key_active'] : 0;

        if ($siteActive === 0) {
            $conn = 'off';
        } elseif ($keyActive === 0) {
            $conn = 'pause';
        } else {
            $conn = 'ok';
        }

        $allowedConn = ['ok', 'pause', 'off'];
        $conn = in_array($conn, $allowedConn, true) ? $conn : 'off';

        $connLabels = ['ok' => 'Підключений', 'pause' => 'На паузі', 'off' => 'Відключений'];
        $badgeLabel = htmlspecialchars($connLabels[$conn], ENT_QUOTES, 'UTF-8');
        $badgeClass = 'conn-badge--' . $conn;

        $chevron   = '<svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M2 4L6 8L10 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $nameAttr  = htmlspecialchars((string) $site['name'], ENT_QUOTES, 'UTF-8');
        $urlAttr   = htmlspecialchars((string) $site['url'],  ENT_QUOTES, 'UTF-8');

        return <<<HTML

        <div class="site-card"
          draggable="true"
          data-site-id="{$siteId}"
          data-group-id="{$groupId}"
          data-site-name="{$nameAttr}"
          data-site-url="{$urlAttr}"
          data-conn="{$conn}">
          <div class="site-card__summary">
            <div class="site-card__top">
              <div>
                <div class="site-card__name">{$name}</div>
                <div class="site-card__domain">{$url}</div>
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
            <div class="site-card__actions">
              <button class="site-card__action-btn"
                data-modal-open="modal-edit-site"
                data-site-id="{$siteId}"
                data-site-name="{$nameAttr}"
                data-site-url="{$urlAttr}"
                data-site-group="{$groupId}">Налаштування</button>
              <a href="/sites/{$siteId}" class="site-card__action-btn site-card__action-btn--primary">&rarr; Site Panel</a>
            </div>
          </div>
        </div>
HTML;
    }

    /**
     * Render all modal overlays once per page.
     *
     * @param list<array<string,mixed>> $groups used for "Edit Site" group select options
     */
    private static function renderModals(array $groups): string
    {
        $groupOptions = '';
        foreach ($groups as $g) {
            $id   = (int) $g['id'];
            $name = htmlspecialchars((string) $g['name'], ENT_QUOTES, 'UTF-8');
            $groupOptions .= "<option value=\"{$id}\">{$name}</option>\n";
        }

        return <<<HTML

<!-- New Group modal -->
<div class="modal-overlay" id="modal-new-group">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Нова група</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="/site-groups/create">
      <div class="modal__body">
        <div class="field-group">
          <label class="field-label" for="ng-name">Назва групи *</label>
          <input class="field-input" id="ng-name" name="name" type="text" required autocomplete="off">
        </div>
        <div class="field-group">
          <label class="field-label" for="ng-desc">Опис</label>
          <input class="field-input" id="ng-desc" name="description" type="text" autocomplete="off">
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--primary">Створити</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Group modal -->
<div class="modal-overlay" id="modal-edit-group">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Редагувати групу</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="#" class="js-edit-group-form">
      <div class="modal__body">
        <div class="field-group">
          <label class="field-label" for="eg-name">Назва групи *</label>
          <input class="field-input" id="eg-name" name="name" type="text" required autocomplete="off">
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--primary">Зберегти</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Group modal -->
<div class="modal-overlay" id="modal-delete-group">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Видалити групу</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="#" class="js-delete-group-form">
      <div class="modal__body">
        <p class="modal__confirm-text">
          Видалити групу &laquo;<strong class="js-group-name"></strong>&raquo;?<br>
          Якщо у групі є сайти — видалення буде заблоковане.
        </p>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--danger">Видалити</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Site modal -->
<div class="modal-overlay" id="modal-add-site">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Додати сайт</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="/sites/create">
      <input type="hidden" name="group_id" value="">
      <div class="modal__body">
        <div class="field-group">
          <label class="field-label" for="as-name">Назва сайту *</label>
          <input class="field-input" id="as-name" name="name" type="text" required autocomplete="off">
        </div>
        <div class="field-group">
          <label class="field-label" for="as-url">URL *</label>
          <input class="field-input" id="as-url" name="url" type="url" required placeholder="https://" autocomplete="off">
        </div>
        <div class="field-group">
          <label class="field-label" for="as-key">API Key (авто)</label>
          <input class="field-input" id="as-key" name="api_key" type="text" readonly>
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--primary">Додати</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Site modal -->
<div class="modal-overlay" id="modal-edit-site">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Редагувати сайт</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="#" class="js-edit-site-form">
      <div class="modal__body">
        <div class="field-group">
          <label class="field-label" for="es-name">Назва сайту *</label>
          <input class="field-input" id="es-name" name="name" type="text" required autocomplete="off">
        </div>
        <div class="field-group">
          <label class="field-label" for="es-url">URL *</label>
          <input class="field-input" id="es-url" name="url" type="url" required autocomplete="off">
        </div>
        <div class="field-group">
          <label class="field-label" for="es-group">Група *</label>
          <select class="field-input" id="es-group" name="group_id" required>
            {$groupOptions}
          </select>
        </div>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--primary">Зберегти</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Site modal -->
<div class="modal-overlay" id="modal-delete-site">
  <div class="modal">
    <div class="modal__head">
      <span class="modal__title">Видалити сайт</span>
      <button class="modal__close" type="button">&times;</button>
    </div>
    <form method="POST" action="#" class="js-delete-site-form">
      <div class="modal__body">
        <p class="modal__confirm-text">
          Видалити сайт &laquo;<strong class="js-site-name"></strong>&raquo;?<br>
          API ключі сайту будуть видалені автоматично.
        </p>
      </div>
      <div class="modal__footer">
        <button type="button" class="btn btn--ghost modal__close">Скасувати</button>
        <button type="submit" class="btn btn--danger">Видалити</button>
      </div>
    </form>
  </div>
</div>
HTML;
    }
}
