(() => {
  'use strict';

  const PRODUCTS_URL = '../iav2/productos.txt';
  const COLORS_URL = '../iav2/colores.json';
  const STATIC_LOGOS = 'https://static.jocarsa.com/logos/';
  const mount = document.getElementById('product-identity-pages');

  if (!mount) return;

  const state = { loaded: false, products: [], identities: {} };

  function basename(path) {
    return String(path || '').replace(/\\/g, '/').replace(/\/$/, '').split('/').pop() || '';
  }

  function productName(path) {
    const slug = basename(path);
    const clean = slug.replace(/^jocarsa[-_]/i, '').replace(/^jcoarsa[-_]/i, '');
    return `jocarsa | ${clean.replace(/[-_]+/g, ' ')}`.toLowerCase();
  }

  function parseProducts(text) {
    const result = [];
    let category = 'Sin categoría';
    const seen = new Set();

    String(text || '').split(/\r?\n/).forEach((raw) => {
      if (!raw.trim() || raw.trimStart().startsWith('#')) return;
      const isProduct = /^\s+/.test(raw);
      if (!isProduct) {
        category = raw.trim();
        return;
      }
      const path = raw.trim();
      if (!path || seen.has(path)) return;
      seen.add(path);
      result.push({ path, category, name: productName(path), slug: basename(path) });
    });
    return result;
  }

  function normalizeIdentity(value) {
    return basename(value)
      .replace(/\.[^.]+$/, '')
      .toLowerCase()
      .replace(/^jocarsa[-_ ]/i, '')
      .replace(/^jcoarsa[-_ ]/i, '')
      .replace(/[^a-z0-9]+/g, ' ')
      .trim();
  }

  function findStoredValue(section, product, data) {
    const values = data && typeof data[section] === 'object' && data[section] ? data[section] : {};
    if (Object.prototype.hasOwnProperty.call(values, product.path)) return String(values[product.path] || '').trim();

    const target = normalizeIdentity(product.path);
    for (const [key, value] of Object.entries(values)) {
      if (normalizeIdentity(key) === target) return String(value || '').trim();
    }
    return '';
  }

  function cssColorInfo(color) {
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = 1;
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    if (!ctx) return { rgb: '', hex: '', foreground: '#111111' };
    ctx.clearRect(0, 0, 1, 1);
    ctx.fillStyle = '#000000';
    ctx.fillStyle = color || '#000000';
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
    const hex = `#${[r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase()}`;
    const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    return {
      rgb: `RGB ${r} · ${g} · ${b}`,
      hex,
      foreground: luminance > 0.58 ? '#111111' : '#FFFFFF'
    };
  }

  function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[char]));
  }

  function logoUrl(color) {
    const safe = /^[a-z]+$/i.test(color) ? color.toLowerCase() : 'black';
    return `${STATIC_LOGOS}${encodeURIComponent(safe)}.svg`;
  }

  function identityFor(product, data) {
    const color = findStoredValue('colors', product, data).toLowerCase();
    return {
      color: /^[a-z]+$/i.test(color) ? color : 'black',
      slogan: findStoredValue('slogans', product, data),
      pricing: findStoredValue('pricing_policies', product, data)
    };
  }

  function productIntroPage(productCount, categories, pageNumber) {
    const categoryCount = new Set(categories).size;
    return `
      <section class="page product-system-intro soft-page" data-generated-product-page>
        <header class="page-header">
          <span class="chapter">13 · Identidad de producto</span>
          <span class="page-no">${String(pageNumber).padStart(2, '0')}</span>
        </header>
        <div class="product-system-intro__grid">
          <div>
            <p class="eyebrow">Sistema cromático de productos</p>
            <h2>Una familia.<br>Una voz.<br>Un color para cada producto.</h2>
            <p class="product-system-lead">JOCARSA mantiene una identidad común, pero cada producto tiene su propio color corporativo. No es decoración: sirve para reconocerlo rápido y mantener una familia grande de aplicaciones ordenada.</p>
          </div>
          <div class="product-system-rules">
            <article><b>${productCount}</b><span>productos leídos de <code>iav2/productos.txt</code></span></article>
            <article><b>${categoryCount}</b><span>familias de producto</span></article>
            <article><b>1</b><span>color CSS3 exclusivo por producto, leído de <code>iav2/colores.json</code></span></article>
            <article><b>J</b><span>nombre de producto compuesto con <code>../jocarsa.ttf</code></span></article>
          </div>
        </div>
        <div class="product-system-note">
          <strong>Regla práctica:</strong> cuando estamos hablando de JOCARSA como marca, mandan índigo y naranja. Cuando estamos dentro de un producto, manda su color. Así sabemos siempre dónde estamos sin dejar de saber de qué familia venimos.
        </div>
      </section>`;
  }

  function productPage(product, identity, pageNumber) {
    const color = identity.color;
    const info = cssColorInfo(color);
    const slogan = identity.slogan || 'Un producto JOCARSA. Una herramienta concreta para resolver una necesidad concreta.';
    const safeName = escapeHTML(product.name);
    const safeCategory = escapeHTML(product.category);
    const safeColor = escapeHTML(color);
    const safeSlogan = escapeHTML(slogan);
    const safePath = escapeHTML(product.path);

    return `
      <section class="page product-identity-page" data-generated-product-page style="--product-color:${safeColor};--product-fg:${info.foreground}">
        <header class="page-header">
          <span class="chapter">13 · Identidad de producto · ${safeCategory}</span>
          <span class="page-no">${String(pageNumber).padStart(2, '0')}</span>
        </header>

        <div class="product-identity-layout">
          <div class="product-color-field">
            <div class="product-color-heading">
              <span>Color corporativo de producto</span>
              <strong>${safeColor}</strong>
            </div>
            <div class="product-color-values">
              <span>${info.hex}</span>
              <span>${info.rgb}</span>
            </div>
            <div class="product-color-word">${safeColor}</div>
          </div>

          <div class="product-identity-content">
            <div class="product-logo-card">
              <img src="${logoUrl(color)}" alt="Símbolo de ${safeName} en ${safeColor}" width="210" height="210">
            </div>
            <p class="product-category">${safeCategory}</p>
            <h2 class="product-jocarsa-name">${safeName}</h2>
            <p class="product-slogan">${safeSlogan}</p>
            <div class="product-identity-meta">
              <div><span>Codename</span><strong>${safeColor}</strong></div>
              
            </div>
          </div>
        </div>

        <div class="product-design-rules">
          <article><b>01</b><span>El color del producto es el acento principal en pantallas, documentación y piezas comerciales propias.</span></article>
          <article><b>02</b><span>El nombre del producto se compone con JOCARSA. El contenido que hay que leer, con Ubuntu.</span></article>
          <article><b>03</b><span>Usad el logo cromático oficial. No inventéis variaciones del color ni efectos nuevos.</span></article>
          <article><b>04</b><span>Que cada producto tenga personalidad no significa romper la familia: estructura, tono y criterios siguen siendo JOCARSA.</span></article>
        </div>
      </section>`;
  }

  function render(products, data) {
    const startPage = 16;
    const chunks = [productIntroPage(products.length, products.map(p => p.category), startPage)];
    products.forEach((product, index) => {
      const identity = identityFor(product, data);
      state.identities[product.path] = identity;
      chunks.push(productPage(product, identity, startPage + index + 1));
    });
    mount.innerHTML = chunks.join('');

    const finalNo = document.querySelector('[data-final-page-no]');
    if (finalNo) finalNo.textContent = String(startPage + products.length + 1).padStart(2, '0');

    document.documentElement.style.setProperty('--product-pages', String(products.length));
    document.body.classList.add('product-data-ready');
    state.loaded = true;

    document.dispatchEvent(new CustomEvent('jocarsa:products-rendered', {
      detail: { count: products.length }
    }));
  }

  function renderError(error) {
    mount.innerHTML = `
      <section class="page soft-page product-load-error" data-generated-product-page>
        <header class="page-header">
          <span class="chapter">13 · Identidad de producto</span><span class="page-no">16</span>
        </header>
        <div>
          <p class="eyebrow">Datos de producto</p>
          <h2>No se han podido cargar todavía.</h2>
          <p>Este manual espera encontrar <code>${PRODUCTS_URL}</code> y <code>${COLORS_URL}</code>. Servido desde <code>/corporativo/</code> en jocarsa.com, esas rutas apuntan directamente a <code>/iav2/</code>.</p>
          <p class="small-error">${escapeHTML(error && error.message ? error.message : String(error))}</p>
        </div>
      </section>`;
    document.body.classList.add('product-data-error');
  }

  async function boot() {
    try {
      const [productsResponse, colorsResponse] = await Promise.all([
        fetch(PRODUCTS_URL, { cache: 'no-store' }),
        fetch(COLORS_URL, { cache: 'no-store' })
      ]);
      if (!productsResponse.ok) throw new Error(`productos.txt: HTTP ${productsResponse.status}`);
      if (!colorsResponse.ok) throw new Error(`colores.json: HTTP ${colorsResponse.status}`);
      const [productsText, colorData] = await Promise.all([
        productsResponse.text(),
        colorsResponse.json()
      ]);
      state.products = parseProducts(productsText);
      render(state.products, colorData);
    } catch (error) {
      console.error('JOCARSA manual: no se pudieron cargar los datos de productos.', error);
      renderError(error);
    }
  }

  boot();
})();
