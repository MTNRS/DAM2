
(() => {
  'use strict';

  // Rutas absolutas calculadas desde la ubicación del script
  const scriptURL = document.currentScript?.src ||
    [...document.scripts].find(s =>
      /(?:^|\/)api-endpoints\.js(?:\?|$)/.test(s.src)
    )?.src ||
    new URL('js/api-endpoints.js', document.baseURI).href;

  const API = new URL(
    '../../backend/api/endpoints.php',
    scriptURL
  ).href;

  const PUBLIC_API = new URL(
    '../../backend/api/public.php',
    scriptURL
  ).href;

  let entities = {};
  let items = [];
  let current = null;
  let secret = '';
  let preferred = '';

  const root = () => document.getElementById('ce-overlay');
  const q = selector => root()?.querySelector(selector);

  const esc = value => String(value ?? '').replace(
    /[&<>"']/g,
    c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[c])
  );

  async function call(action, data) {
    const response = await fetch(API + '?action=' + action, {
      method: data ? 'POST' : 'GET',
      credentials: 'same-origin',
      headers: data
        ? { 'Content-Type': 'application/json' }
        : {},
      body: data ? JSON.stringify(data) : undefined
    });

    const raw = await response.text();
    let result;

    try {
      result = JSON.parse(raw);
    } catch {
      throw new Error(
        `Respuesta no JSON (HTTP ${response.status}): ` +
        raw.slice(0, 200)
      );
    }

    if (!response.ok) {
      throw new Error(result.error || `HTTP ${response.status}`);
    }

    return result;
  }

  function status(message, error = false) {
    const element = q('.ce-status');

    if (element) {
      element.textContent = message;
      element.dataset.error = error ? '1' : '0';
    }
  }

  function fields(selector, values, selected) {
    const container = q(selector);
    container.replaceChildren();

    values.forEach(value => {
      const label = document.createElement('label');
      label.className = 'ce-check';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = value;
      checkbox.checked = selected.includes(value);

      label.append(
        checkbox,
        document.createTextNode(' ' + value)
      );

      container.append(label);
    });
  }

  function chosen(selector) {
    return [
      ...q(selector).querySelectorAll('input:checked')
    ].map(input => input.value);
  }

  function showKey(key) {
    secret = key || '';
    q('.ce-key').hidden = !secret;
    q('.ce-key-value').textContent = secret;
  }

  function url() {
    const endpoint = new URL(PUBLIC_API);

    endpoint.searchParams.set(
      'ruta',
      q('.ce-route').value.trim()
    );

    q('.ce-url').textContent = endpoint.href;
  }

  function list() {
    const container = q('.ce-items');
    container.replaceChildren();

    if (!items.length) {
      container.innerHTML =
        '<p class="ce-empty">Todavía no hay endpoints guardados.</p>';
      return;
    }

    items.forEach(item => {
      const button = document.createElement('button');
      button.type = 'button';

      button.className = 'ce-item' + (
        current &&
        String(current.Identificador) === String(item.Identificador)
          ? ' is-selected'
          : ''
      );

      const active = String(item.activo) === '1';

      button.innerHTML = `
        <strong>${esc(item.ruta)}</strong>
        <small>
          ${esc(item.entidad)} ·
          ${active ? 'Activo' : 'Inactivo'}
        </small>
      `;

      button.onclick = () => select(item);
      container.append(button);
    });
  }

  function select(item) {
    current = item || null;
    showKey('');

    const table =
      item?.entidad ||
      preferred ||
      Object.keys(entities)[0] ||
      '';

    q('.ce-entity').value = table;

    q('.ce-route').value =
      item?.ruta ||
      table.toLowerCase()
        .replace(/[^a-z0-9-]/g, '-');

    q('.ce-enabled').checked =
      String(item?.activo) === '1';

    const availableFields = entities[table] || [];

    const defaultFields = availableFields.includes('Identificador')
      ? ['Identificador']
      : [];

    fields(
      '.ce-fields',
      availableFields,
      item?.campos || defaultFields
    );

    fields(
      '.ce-methods',
      ['GET', 'POST', 'PATCH', 'DELETE'],
      item?.metodos || ['GET']
    );

    q('.ce-rotate').disabled = !item;
    q('.ce-delete').disabled = !item;

    q('.ce-mode').textContent = item
      ? 'Editar endpoint'
      : 'Nuevo endpoint';

    list();
    url();
    status('');
  }

  async function reload(id) {
    const [entitiesResponse, listResponse] =
      await Promise.all([
        call('entities'),
        call('list')
      ]);

    entities = entitiesResponse.entities || {};
    items = listResponse.items || [];

    const selector = q('.ce-entity');
    selector.replaceChildren();

    Object.keys(entities).forEach(table => {
      const option = document.createElement('option');
      option.value = table;
      option.textContent = table;
      selector.append(option);
    });

    const selectedItem = items.find(item =>
      String(item.Identificador) ===
      String(id ?? current?.Identificador)
    );

    select(selectedItem || null);
  }

  async function open(entity = '') {
    preferred = entity;
    root()?.remove();

    const overlay = document.createElement('div');
    overlay.id = 'ce-overlay';

    overlay.innerHTML = `
      <div id="ce-dialog"
           role="dialog"
           aria-modal="true"
           aria-label="Gestión de API endpoints">

        <header class="ce-top">
          <div>
            <h2>API endpoints</h2>
            <p>Publicación y acceso a entidades del ERP</p>
          </div>
          <button class="ce-close" type="button">
            Cerrar ×
          </button>
        </header>

        <div class="ce-layout">

          <aside class="ce-list">
            <div class="ce-listhead">
              <h3>Endpoints configurados</h3>
              <button class="ce-new" type="button">
                + Nuevo
              </button>
            </div>
            <div class="ce-items"></div>
          </aside>

          <section class="ce-editor">

            <h3 class="ce-mode">Nuevo endpoint</h3>

            <p class="ce-status"
               role="status"
               aria-live="polite"></p>

            <div class="ce-two">
              <label>
                Entidad
                <select class="ce-entity"></select>
              </label>

              <label>
                Ruta pública
                <input class="ce-route"
                       type="text"
                       spellcheck="false">
              </label>
            </div>

            <label class="ce-switch">
              <input class="ce-enabled" type="checkbox">
              Endpoint activo
            </label>

            <div class="ce-panel">
              <div class="ce-panelhead">
                <h3>Campos compartidos</h3>
                <button class="ce-all" type="button">
                  Todos
                </button>
                <button class="ce-none" type="button">
                  Ninguno
                </button>
              </div>
              <div class="ce-fields"></div>
            </div>

            <div class="ce-panel">
              <h3>Métodos autorizados</h3>
              <div class="ce-methods"></div>
            </div>

            <div class="ce-actions">
              <button class="ce-save" type="button">
                Guardar endpoint
              </button>
              <button class="ce-rotate" type="button">
                Regenerar clave
              </button>
              <button class="ce-delete" type="button">
                Eliminar
              </button>
            </div>

            <div class="ce-key" hidden>
              <h3>Clave API: cópiala ahora</h3>
              <div class="ce-secret ce-key-value"></div>
              <button class="ce-copy" type="button">
                Copiar clave
              </button>
            </div>

            <div class="ce-panel">
              <h3>URL pública</h3>
              <div class="ce-secret ce-url"></div>
              <small>
                La URL solo estará operativa cuando
                el endpoint esté guardado y activado.
              </small>
            </div>

          </section>
        </div>
      </div>
    `;

    document.body.append(overlay);

    q('.ce-close').onclick = () => overlay.remove();

    overlay.addEventListener('click', event => {
      if (event.target === overlay) {
        overlay.remove();
      }
    });

    q('.ce-new').onclick = () => {
      preferred = '';
      select(null);
    };

    q('.ce-entity').onchange = () => {
      const table = q('.ce-entity').value;
      const available = entities[table] || [];

      fields(
        '.ce-fields',
        available,
        available.includes('Identificador')
          ? ['Identificador']
          : []
      );

      if (!current) {
        q('.ce-route').value = table.toLowerCase()
          .replace(/[^a-z0-9-]/g, '-');
      }

      url();
    };

    q('.ce-route').oninput = url;

    q('.ce-all').onclick = () => {
      q('.ce-fields')
        .querySelectorAll('input')
        .forEach(input => input.checked = true);
    };

    q('.ce-none').onclick = () => {
      q('.ce-fields')
        .querySelectorAll('input')
        .forEach(input => input.checked = false);
    };

    q('.ce-save').onclick = async () => {
      const data = {
        id: current?.Identificador || 0,
        entidad: q('.ce-entity').value,
        ruta: q('.ce-route').value.trim(),
        activo: q('.ce-enabled').checked,
        campos: chosen('.ce-fields'),
        metodos: chosen('.ce-methods')
      };

      if (!data.campos.length) {
        return status(
          'Selecciona al menos un campo.',
          true
        );
      }

      if (!data.metodos.length) {
        return status(
          'Selecciona al menos un método.',
          true
        );
      }

      const button = q('.ce-save');
      button.disabled = true;

      status('Guardando y comprobando persistencia…');

      try {
        const result = await call('save', data);

        await reload(result.id);

        if (
          !current ||
          String(current.Identificador) !== String(result.id)
        ) {
          throw new Error(
            'El endpoint no aparece en el listado después de guardarlo.'
          );
        }

        if (result.api_key) {
          showKey(result.api_key);
        }

        status('Endpoint guardado y verificado correctamente.');

      } catch (error) {
        status(error.message, true);

      } finally {
        button.disabled = false;
      }
    };

    q('.ce-rotate').onclick = async () => {
      if (
        !current ||
        !confirm('¿Invalidar la clave anterior?')
      ) {
        return;
      }

      try {
        const result = await call('rotate', {
          id: current.Identificador
        });

        showKey(result.api_key);
        status('Clave regenerada');

      } catch (error) {
        status(error.message, true);
      }
    };

    q('.ce-delete').onclick = async () => {
      if (
        !current ||
        !confirm('¿Eliminar este endpoint?')
      ) {
        return;
      }

      try {
        await call('delete', {
          id: current.Identificador
        });

        current = null;
        await reload();

        status('Endpoint eliminado');

      } catch (error) {
        status(error.message, true);
      }
    };

    q('.ce-copy').onclick = () => {
      navigator.clipboard.writeText(secret)
        .then(() => status('Clave copiada'))
        .catch(() =>
          status('No se pudo copiar', true)
        );
    };

    try {
      await reload();
    } catch (error) {
      status(error.message, true);
    }
  }

  window.CrimsonEndpoints = { open };

})();
