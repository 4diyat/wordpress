(() => {
	'use strict';

	document.querySelectorAll('[data-gallery]').forEach((gallery) => {
		const viewport = gallery.querySelector('[data-gallery-viewport]');
		const cards = Array.from(gallery.querySelectorAll('[data-gallery-card]'));
		const previous = gallery.querySelector('[data-gallery-previous]');
		const next = gallery.querySelector('[data-gallery-next]');
		const dots = Array.from(gallery.querySelectorAll('[data-gallery-dot]'));
		const status = gallery.querySelector('[data-gallery-status]');
		const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
		if (!viewport || !cards.length) return;

		let activeIndex = 0;
		let scrollFrame = 0;
		let announceTimer = 0;

		const targetScrollLeft = (index) => {
			const card = cards[index];
			return Math.max(0, card.offsetLeft - (viewport.clientWidth - card.offsetWidth) / 2);
		};

		const announce = () => {
			if (!status) return;
			const title = cards[activeIndex].dataset.galleryTitle || `Slide ${activeIndex + 1}`;
			status.textContent = `${title}, slide ${activeIndex + 1} dari ${cards.length}`;
		};

		const updateControls = (index, shouldAnnounce = false) => {
			activeIndex = Math.max(0, Math.min(index, cards.length - 1));
			if (previous) previous.disabled = activeIndex === 0;
			if (next) next.disabled = activeIndex === cards.length - 1;

			dots.forEach((dot, dotIndex) => {
				const isActive = dotIndex === activeIndex;
				dot.classList.toggle('is-active', isActive);
				if (isActive) dot.setAttribute('aria-current', 'true');
				else dot.removeAttribute('aria-current');
			});

			cards.forEach((card, cardIndex) => {
				if (cardIndex === activeIndex) card.setAttribute('aria-current', 'true');
				else card.removeAttribute('aria-current');
			});

			if (shouldAnnounce) {
				window.clearTimeout(announceTimer);
				announceTimer = window.setTimeout(announce, 180);
			}
		};

		const goTo = (index, smooth = true) => {
			const boundedIndex = Math.max(0, Math.min(index, cards.length - 1));
			viewport.scrollTo({
				left: targetScrollLeft(boundedIndex),
				behavior: smooth && !reducedMotion.matches ? 'smooth' : 'auto',
			});
			updateControls(boundedIndex, true);
		};

		previous?.addEventListener('click', () => goTo(activeIndex - 1));
		next?.addEventListener('click', () => goTo(activeIndex + 1));
		dots.forEach((dot, index) => dot.addEventListener('click', () => goTo(index)));

		viewport.addEventListener('keydown', (event) => {
			if (event.key === 'ArrowRight') {
				event.preventDefault();
				goTo(activeIndex + 1);
			} else if (event.key === 'ArrowLeft') {
				event.preventDefault();
				goTo(activeIndex - 1);
			} else if (event.key === 'Home') {
				event.preventDefault();
				goTo(0);
			} else if (event.key === 'End') {
				event.preventDefault();
				goTo(cards.length - 1);
			}
		});

		viewport.addEventListener(
			'scroll',
			() => {
				if (scrollFrame) return;
				scrollFrame = window.requestAnimationFrame(() => {
					const viewportCenter = viewport.scrollLeft + viewport.clientWidth / 2;
					let closestIndex = 0;
					let closestDistance = Number.POSITIVE_INFINITY;
					cards.forEach((card, index) => {
						const cardCenter = card.offsetLeft + card.offsetWidth / 2;
						const distance = Math.abs(viewportCenter - cardCenter);
						if (distance < closestDistance) {
							closestDistance = distance;
							closestIndex = index;
						}
					});
					updateControls(closestIndex, true);
					scrollFrame = 0;
				});
			},
			{ passive: true }
		);

		let resizeTimer = 0;
		window.addEventListener('resize', () => {
			window.clearTimeout(resizeTimer);
			resizeTimer = window.setTimeout(() => goTo(activeIndex, false), 100);
		});

		updateControls(0);
	});
})();
