(() => {
	'use strict';

	if (!window.wp?.customize) return;

	const defaults = {
		ame_color_primary: '#0b2d25',
		ame_color_accent: '#b91f32',
		ame_color_accent_dark: '#ff7b87',
		ame_color_text: '#10251f',
		ame_color_muted: '#64736c',
		ame_color_background: '#f1f5f3',
	};
	const variables = {
		ame_color_primary: '--ame-graphite',
		ame_color_accent: '--ame-blue',
		ame_color_accent_dark: '--ame-blue-dark',
		ame_color_text: '--ame-text',
		ame_color_muted: '--ame-muted',
		ame_color_background: '--ame-gray',
	};

	const darkVariant = (value) => {
		let hex = String(value || defaults.ame_color_primary).replace('#', '');
		if (hex.length === 3) hex = hex.split('').map((part) => part + part).join('');
		if (!/^[0-9a-f]{6}$/i.test(hex)) return '#071d18';
		const channels = [0, 2, 4].map((start) => Math.round(parseInt(hex.slice(start, start + 2), 16) * 0.65));
		return `#${channels.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`;
	};

	Object.entries(variables).forEach(([setting, variable]) => {
		window.wp.customize(setting, (settingValue) => {
			settingValue.bind((value) => {
				const color = value || defaults[setting];
				document.documentElement.style.setProperty(variable, color);
				if (setting === 'ame_color_primary') {
					document.documentElement.style.setProperty('--ame-black', darkVariant(color));
				}
			});
		});
	});
})();
