(async () => {
	'use strict';

	document.documentElement.classList.add('js');
	const themeUrl = '/wp-content/themes/andalan-mutu-energi/';
	const icon = (name, className = '') => `<svg class="icon icon-${name} ${className}" aria-hidden="true"><use href="#icon-${name}"/></svg>`;
	const artwork = (item, className, priority = false) => `<img class="${className}" src="${themeUrl}${item.image}" alt="" loading="${priority ? 'eager' : 'lazy'}" decoding="async"${priority ? ' fetchpriority="high"' : ''}>`;
	const chevron = () => icon('chevron-right');
	const menuItems = (location, data, arrows = false) => Object.entries(data.menus[location]?.items || {})
		.map(([label, url]) => `<li class="menu-item"><a href="${url}">${arrows ? icon('arrow-right') : ''}${label}</a></li>`).join('');

	const loadScript = (source) => new Promise((resolve, reject) => {
		const script = document.createElement('script');
		script.src = source;
		script.onload = resolve;
		script.onerror = reject;
		document.body.appendChild(script);
	});

	try {
		const response = await fetch(`${themeUrl}demo-content.json`);
		if (!response.ok) throw new Error(`Demo data returned ${response.status}`);
		const data = await response.json();
		const { heroes, promos, shows, settings, brand } = data;
		document.querySelector('#brand-logo').src = `${themeUrl}${brand.logo}`;

		const sectionLinks = (item) => `<div class="section-links">${item.primary[0] ? `<a class="text-link" href="${item.primary[1]}">${item.primary[0]}${chevron()}</a>` : ''}${item.secondary[0] ? `<a class="text-link" href="${item.secondary[1]}">${item.secondary[0]}${chevron()}</a>` : ''}</div>`;
		const heroMarkup = heroes.map((hero, index) => `<section class="hero-section hero-section-${index + 1} section-${hero.theme}" aria-labelledby="hero-title-${index + 1}">${artwork(hero, 'hero-artwork', index === 0)}<div class="hero-content"><${index === 0 ? 'h1' : 'h2'} id="hero-title-${index + 1}" class="hero-title">${hero.title}</${index === 0 ? 'h1' : 'h2'}><p class="hero-subtitle">${hero.subtitle}</p>${hero.availability ? `<p class="availability">${hero.availability}</p>` : ''}${sectionLinks(hero)}</div></section>`).join('');

		const promoMarkup = promos.map((promo, index) => `<section class="promo-card section-${promo.theme}" aria-labelledby="promo-title-${index + 1}">${artwork(promo, 'promo-artwork')}<div class="promo-content"><h2 id="promo-title-${index + 1}" class="promo-title">${promo.title}</h2><p class="promo-subtitle">${promo.subtitle}${promo.footnote ? `<sup>${promo.footnote}</sup>` : ''}</p>${promo.availability ? `<p class="availability">${promo.availability}</p>` : ''}${sectionLinks(promo)}</div></section>`).join('');

		const slides = shows.map((show, index) => `<li id="gallery-slide-${index + 1}" class="gallery-card" data-gallery-card data-gallery-title="${show.title}" role="group" aria-roledescription="slide" aria-label="${index + 1} dari ${shows.length}"><a class="gallery-card-link" href="${show.url}" aria-label="${show.action}: ${show.title}">${artwork(show, 'gallery-artwork')}<div class="gallery-card-content"><div class="card-brand" aria-hidden="true">${icon('bolt')}<span>${brand.name}</span></div><h3 class="show-title">${show.title}</h3><span class="card-action">${icon('arrow-right')}${show.action}</span><p class="gallery-meta"><span class="gallery-genre">${show.genre}</span>${show.description}</p></div></a></li>`).join('');
		const dots = shows.map((show, index) => `<button class="gallery-dot${index === 0 ? ' is-active' : ''}" type="button" data-gallery-dot aria-controls="gallery-slide-${index + 1}" aria-label="Buka slide ${index + 1}: ${show.title}"${index === 0 ? ' aria-current="true"' : ''}></button>`).join('');
		const gallery = `<section id="informasi" class="entertainment-section" aria-label="Galeri informasi"><h2 class="entertainment-heading">${settings.ame_gallery_heading}</h2><div class="gallery" data-gallery><div class="gallery-viewport" data-gallery-viewport tabindex="0" role="region" aria-roledescription="carousel" aria-label="Slide informasi"><ul class="gallery-track">${slides}</ul></div><button class="gallery-arrow gallery-prev" type="button" data-gallery-previous aria-label="Slide sebelumnya" disabled>${icon('chevron-left')}</button><button class="gallery-arrow gallery-next" type="button" data-gallery-next aria-label="Slide berikutnya">${icon('chevron-right')}</button><div class="gallery-pagination" aria-label="Pilih slide">${dots}</div><p class="screen-reader-text" aria-live="polite" aria-atomic="true" data-gallery-status></p></div></section>`;
		document.querySelector('#primary').innerHTML = `${heroMarkup}<div id="layanan" class="promo-grid">${promoMarkup}</div>${gallery}`;

		const primaryMarkup = menuItems('primary', data);
		document.querySelectorAll('[data-menu]').forEach((menu) => { menu.innerHTML = primaryMarkup; });
		document.querySelector('#quick-links').innerHTML = `<h2>${settings.ame_quick_links_heading}</h2><ul>${menuItems('quick_links', data, true)}</ul>`;
		document.querySelector('#contact-panel-inner').innerHTML = `<h2>${settings.ame_contact_title}</h2><p>${settings.ame_contact_message}</p><ul>${menuItems('contact_links', data)}</ul>`;
		document.querySelectorAll('#nav-search-field, [data-nav-trigger="search"]').forEach((field) => {
			if (field.matches('input')) field.placeholder = settings.ame_search_placeholder;
			else field.setAttribute('aria-label', settings.ame_search_placeholder);
		});

		const footerColumns = [
			['footer_company', 'footer_services'],
			['footer_guides', 'footer_support'],
			['footer_contact'],
			['footer_scope', 'footer_sectors', 'footer_management', 'footer_legal_info'],
			['footer_news', 'footer_social'],
		];
		let footerMarkup = '';
		footerColumns.forEach((column) => {
			footerMarkup += '<div class="footer-column">';
			column.forEach((location) => {
				const menu = data.menus[location];
				if (!menu) return;
				footerMarkup += `<section class="footer-group"><h2 class="footer-heading"><button class="footer-heading-button" type="button" aria-expanded="true" aria-controls="footer-${location}"><span>${menu.name}</span>${icon('plus')}</button></h2><ul id="footer-${location}" class="footer-links">${menuItems(location, data)}</ul></section>`;
			});
			footerMarkup += '</div>';
		});
		document.querySelector('#footer-columns').innerHTML = footerMarkup;
		document.querySelector('#footer-notes').innerHTML = settings.ame_footer_notes.split(/\r?\n/).filter(Boolean).map((note) => `<p>${note}</p>`).join('');
		document.querySelector('#footer-contact').innerHTML = `<p>${settings.ame_contact_info}</p>`;
		document.querySelector('#footer-copyright').textContent = settings.ame_copyright.replace('[year]', new Date().getFullYear());
		document.querySelector('#footer-legal-links').innerHTML = menuItems('legal', data);
		const locale = document.querySelector('#footer-locale');
		locale.textContent = settings.ame_locale_label;
		locale.href = settings.ame_locale_url;

		await loadScript(`${themeUrl}assets/js/navigation.js`);
		await loadScript(`${themeUrl}assets/js/gallery.js`);
	} catch (error) {
		console.error(error);
		document.querySelector('#primary').innerHTML = '<section class="content-shell setup-notice"><h1>Data pratinjau tidak dapat dimuat.</h1><p>Periksa demo-content.json lalu muat ulang halaman.</p></section>';
	}
})();
