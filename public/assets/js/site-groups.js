'use strict';

// ─── Modals ──────────────────────────────────────────────────────────────────

function initModals() {
  // Open modal triggers: [data-modal-open="overlay-id"]
  document.querySelectorAll('[data-modal-open]').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      var overlayId = trigger.dataset.modalOpen;
      var overlay   = document.getElementById(overlayId);
      if (!overlay) return;

      // Pre-fill data for group-level triggers (add-site, edit-group, delete-group)
      var groupId   = trigger.dataset.groupId   || '';
      var groupName = trigger.dataset.groupName || '';
      var groupDesc = trigger.dataset.groupDesc || '';
      var siteId    = trigger.dataset.siteId    || '';
      var siteName  = trigger.dataset.siteName  || '';
      var siteUrl   = trigger.dataset.siteUrl   || '';
      var siteGroup = trigger.dataset.siteGroup || '';

      // "Add site" modal — set hidden group_id + generate API key
      var groupInput = overlay.querySelector('[name="group_id"]');
      if (groupInput) groupInput.value = groupId;

      var apiKeyInput = overlay.querySelector('[name="api_key"]');
      if (apiKeyInput && !apiKeyInput.value) {
        apiKeyInput.value = generateKey();
      }

      // "Edit group" modal — set form action + pre-fill name + description
      var editGroupForm = overlay.querySelector('.js-edit-group-form');
      if (editGroupForm) {
        editGroupForm.action = '/site-groups/' + groupId + '/update';
        var nameInput = editGroupForm.querySelector('[name="name"]');
        if (nameInput) nameInput.value = groupName;
        var descInput = editGroupForm.querySelector('[name="description"]');
        if (descInput) descInput.value = groupDesc;
      }

      // "Delete group" modal — set form action + group name in text
      var deleteGroupForm = overlay.querySelector('.js-delete-group-form');
      if (deleteGroupForm) {
        deleteGroupForm.action = '/site-groups/' + groupId + '/delete';
        var nameSpan = overlay.querySelector('.js-group-name');
        if (nameSpan) nameSpan.textContent = groupName;
      }

      // "Edit site" modal — set form action + pre-fill fields
      var editSiteForm = overlay.querySelector('.js-edit-site-form');
      if (editSiteForm) {
        editSiteForm.action = '/sites/' + siteId + '/update';
        var siteNameInput  = editSiteForm.querySelector('[name="name"]');
        var siteUrlInput   = editSiteForm.querySelector('[name="url"]');
        var siteGroupSelect = editSiteForm.querySelector('[name="group_id"]');
        if (siteNameInput)   siteNameInput.value  = siteName;
        if (siteUrlInput)    siteUrlInput.value   = siteUrl;
        if (siteGroupSelect) siteGroupSelect.value = siteGroup;
      }

      // "Delete site" modal — set form action
      var deleteSiteForm = overlay.querySelector('.js-delete-site-form');
      if (deleteSiteForm) {
        deleteSiteForm.action = '/sites/' + siteId + '/delete';
        var siteNameSpan = overlay.querySelector('.js-site-name');
        if (siteNameSpan) siteNameSpan.textContent = siteName;
      }

      overlay.classList.add('is-open');
    });
  });

  // Close: × button inside modal
  document.querySelectorAll('.modal__close').forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.closest('.modal-overlay').classList.remove('is-open');
    });
  });

  // Close: click on overlay background
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) overlay.classList.remove('is-open');
    });
  });

  // Close: ESC key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.is-open').forEach(function (o) {
        o.classList.remove('is-open');
      });
    }
  });

  // Ghost column "new group" click
  var colNew = document.querySelector('.col-new');
  if (colNew) {
    colNew.addEventListener('click', function () {
      var overlay = document.getElementById('modal-new-group');
      if (overlay) overlay.classList.add('is-open');
    });
  }

  // Reset "add site" API key field when modal closes so next open regenerates
  var addSiteOverlay = document.getElementById('modal-add-site');
  if (addSiteOverlay) {
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (m.attributeName === 'class' && !addSiteOverlay.classList.contains('is-open')) {
          var keyInput = addSiteOverlay.querySelector('[name="api_key"]');
          if (keyInput) keyInput.value = '';
        }
      });
    });
    observer.observe(addSiteOverlay, { attributes: true });
  }
}

function generateKey() {
  var hex = '';
  var arr = new Uint8Array(16);
  crypto.getRandomValues(arr);
  arr.forEach(function (b) { hex += b.toString(16).padStart(2, '0'); });
  return 'dbapi_' + hex;
}

// ─── Column ⋮ dropdown menus ─────────────────────────────────────────────────

function initColMenus() {
  document.querySelectorAll('.col-menu__btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var menu = btn.closest('.col-menu');
      var isOpen = menu.classList.contains('is-open');
      // Close all open menus
      document.querySelectorAll('.col-menu.is-open').forEach(function (m) {
        m.classList.remove('is-open');
      });
      if (!isOpen) menu.classList.add('is-open');
    });
  });

  // Close menus on outside click
  document.addEventListener('click', function () {
    document.querySelectorAll('.col-menu.is-open').forEach(function (m) {
      m.classList.remove('is-open');
    });
  });
}

// ─── Drag & Drop ─────────────────────────────────────────────────────────────

function initDragDrop() {
  var draggingCard = null;

  document.querySelectorAll('.site-card[draggable="true"]').forEach(function (card) {
    card.addEventListener('dragstart', function (e) {
      draggingCard = card;
      card.classList.add('card--dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', card.dataset.siteId);
    });

    card.addEventListener('dragend', function () {
      card.classList.remove('card--dragging');
      draggingCard = null;
      document.querySelectorAll('.col--drag-over').forEach(function (col) {
        col.classList.remove('col--drag-over');
      });
    });
  });

  document.querySelectorAll('.kanban-col').forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      if (!col.classList.contains('col--drag-over')) {
        col.classList.add('col--drag-over');
      }
    });

    col.addEventListener('dragleave', function (e) {
      // Only remove if leaving the column (not entering a child)
      if (!col.contains(e.relatedTarget)) {
        col.classList.remove('col--drag-over');
      }
    });

    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('col--drag-over');

      var siteId  = e.dataTransfer.getData('text/plain');
      var groupId = col.dataset.groupId;

      if (!siteId || !groupId || !draggingCard) return;
      if (draggingCard.dataset.groupId === groupId) return; // same column, no-op

      // Optimistic DOM move
      var cardsContainer = col.querySelector('.kanban-col__cards');
      if (cardsContainer) {
        draggingCard.dataset.groupId = groupId;
        cardsContainer.appendChild(draggingCard);
        updateColumnCounts();
      }

      // AJAX persist
      var csrfMeta = document.getElementById('csrf-token');
      var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

      fetch('/api/v1/sites/' + siteId + '/move', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ group_id: parseInt(groupId, 10), _csrf: csrfToken }),
      }).then(function (res) {
        if (!res.ok) {
          // Reload on failure to restore correct state
          window.location.reload();
          return;
        }
        return res.json();
      }).then(function (data) {
        if (data && data.csrf) {
          // Update CSRF token for subsequent form submissions
          if (csrfMeta) csrfMeta.setAttribute('content', data.csrf);
          document.querySelectorAll('input[name="_csrf"]').forEach(function (el) {
            el.value = data.csrf;
          });
        }
      }).catch(function () {
        window.location.reload();
      });
    });
  });
}

function updateColumnCounts() {
  document.querySelectorAll('.kanban-col').forEach(function (col) {
    var badge = col.querySelector('.kanban-col__badge');
    var count = col.querySelectorAll('.site-card').length;
    if (badge) badge.textContent = count;
  });
}

// ─── Search ──────────────────────────────────────────────────────────────────

function initSearch() {
  var input = document.getElementById('sg-search');
  if (!input) return;

  input.addEventListener('input', function () {
    var query = input.value.trim().toLowerCase();
    filterCards(query, getStatusFilter());
  });
}

// ─── Status filter ───────────────────────────────────────────────────────────

function initFilters() {
  var select = document.getElementById('sg-status');
  if (!select) return;

  select.addEventListener('change', function () {
    filterCards(getSearchQuery(), select.value);
  });
}

function getSearchQuery() {
  var input = document.getElementById('sg-search');
  return input ? input.value.trim().toLowerCase() : '';
}

function getStatusFilter() {
  var select = document.getElementById('sg-status');
  return select ? select.value : '';
}

function filterCards(query, status) {
  document.querySelectorAll('.kanban-col').forEach(function (col) {
    var colName    = (col.dataset.groupName || '').toLowerCase();
    var colVisible = !query || colName.includes(query);
    var anyCard    = false;

    col.querySelectorAll('.site-card').forEach(function (card) {
      var name   = (card.dataset.siteName || '').toLowerCase();
      var conn   = card.dataset.conn || '';
      var nameOk   = !query  || name.includes(query) || colName.includes(query);
      var statusOk = !status || conn === status;
      var show = nameOk && statusOk;
      card.style.display = show ? '' : 'none';
      if (show) anyCard = true;
    });

    // Hide whole column only if query targets name and no match at all
    col.style.display = (query && !colVisible && !anyCard) ? 'none' : '';
  });
}

// ─── Boot ────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  initModals();
  initColMenus();
  initDragDrop();
  initSearch();
  initFilters();
});
