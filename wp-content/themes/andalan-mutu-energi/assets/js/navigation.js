(() => {
	'use strict';

	document.documentElement.classList.add('js');

	const header = document.querySelector('.site-header');
	const scrim = document.querySelector('[data-nav-scrim]');
	const triggers = Array.from(document.querySelectorAll('[data-nav-trigger]'));
	const panels = Array.from(document.querySelectorAll('[data-nav-panel]'));
	let activePanel = null;
	let activeTrigger = null;

	const closePanels = (restoreFocus = false) => {
		const triggerToFocus = activeTrigger;
		panels.forEach((panel) => panel.classList.remove('is-open'));
		triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
		if (scrim) scrim.classList.remove('is-visible');
		document.body.classList.remove('nav-locked');
		activePanel = null;
		activeTrigger = null;
		if (restoreFocus) triggerToFocus?.focus();
	};

	const openPanel = (trigger) => {
		const panelName = trigger.dataset.navTrigger;
		const panel = panels.find((item) => item.dataset.navPanel === panelName);
		if (!panel) return;

		if (activePanel === panel) {
			closePanels();
			trigger.focus();
			return;
		}

		closePanels();
		activePanel = panel;
		activeTrigger = trigger;
		panel.classList.add('is-open');
		trigger.setAttribute('aria-expanded', 'true');
		if (scrim) scrim.classList.add('is-visible');

		if (panelName === 'menu') {
			document.body.classList.add('nav-locked');
		}

		if (panelName === 'search') {
			window.setTimeout(() => {
				panel.querySelector('input[type="search"]')?.focus();
			}, 180);
		}
	};

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', () => openPanel(trigger));
	});

	scrim?.addEventListener('click', () => closePanels(true));

	document.addEventListener('keydown', (event) => {
		if (event.key === 'Escape' && activePanel) {
			const triggerToFocus = activeTrigger;
			closePanels();
			triggerToFocus?.focus();
		}

		if (event.key === 'Tab' && activePanel?.dataset.navPanel === 'menu' && header) {
			const focusable = Array.from(
				header.querySelectorAll('a[href], button:not([disabled]), input:not([disabled])')
			).filter((element) => element.offsetParent !== null);
			if (!focusable.length) return;
			const first = focusable[0];
			const last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});

	const desktopQuery = window.matchMedia('(min-width: 901px)');
	const handleDesktopChange = (event) => {
		if (event.matches && activePanel?.dataset.navPanel === 'menu') closePanels();
	};
	desktopQuery.addEventListener?.('change', handleDesktopChange);

	// Swap unavailable third-party artwork for the bundled local equivalent.
	document.querySelectorAll('img[data-fallback]').forEach((image) => {
		const useFallback = () => {
			const fallback = image.dataset.fallback;
			if (!fallback) return;
			delete image.dataset.fallback;
			image.src = fallback;
		};
		image.addEventListener('error', useFallback, { once: true });
		if (image.complete && image.naturalWidth === 0) useFallback();
	});

	// Footer groups behave as accordions only at the mobile breakpoint.
	const footerButtons = Array.from(document.querySelectorAll('.footer-heading-button'));
	const mobileFooter = window.matchMedia('(max-width: 733px)');

	const syncFooterButtons = () => {
		footerButtons.forEach((button) => {
			const group = button.closest('.footer-group');
			button.tabIndex = mobileFooter.matches ? 0 : -1;
			button.setAttribute(
				'aria-expanded',
				String(!mobileFooter.matches || group?.classList.contains('is-open'))
			);
		});
	};

	footerButtons.forEach((button) => {
		button.addEventListener('click', () => {
			if (!mobileFooter.matches) return;
			const group = button.closest('.footer-group');
			if (!group) return;
			group.classList.toggle('is-open');
			button.setAttribute('aria-expanded', String(group.classList.contains('is-open')));
		});
	});

	mobileFooter.addEventListener?.('change', syncFooterButtons);
	syncFooterButtons();

	document.querySelectorAll('[data-current-year]').forEach((element) => {
		element.textContent = String(new Date().getFullYear());
	});

	// Reveal a compact back-to-top control only after the visitor has scrolled.
	const backToTop = document.querySelector('[data-back-to-top]');
	if (backToTop) {
		let scrollFrame = 0;
		const syncBackToTop = () => {
			backToTop.hidden = window.scrollY < 600;
			scrollFrame = 0;
		};
		window.addEventListener('scroll', () => {
			if (scrollFrame) return;
			scrollFrame = window.requestAnimationFrame(syncBackToTop);
		}, { passive: true });
		backToTop.addEventListener('click', () => {
			const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			document.querySelector('.nav-brand')?.focus({ preventScroll: true });
			window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
		});
		syncBackToTop();
	}
})();
