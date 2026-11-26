const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const script = fs.readFileSync(path.join(__dirname, '../assets/color-scheme.js'), 'utf8');

function setup(mode, systemDark) {
	const listeners = {};
	const media = {
		matches: systemDark,
		addEventListener: (_, listener) => { listeners.change = listener; },
	};
	const attributes = {};
	const storage = { mode };
	const window = {
		matchMedia: () => media,
		localStorage: { getItem: () => storage.mode },
		addEventListener: (_, listener) => { listeners.storage = listener; },
	};
	vm.runInNewContext(script, {
		window,
		document: { documentElement: { setAttribute: (key, value) => { attributes[key] = value; } } },
		console,
	});
	return { attributes, listeners, media, storage };
}

test('follows device preference when no explicit mode is saved', () => {
	const app = setup(null, true);
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'dark');
	app.media.matches = false;
	app.listeners.change();
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'light');
});

test('saved mode takes precedence and updates across tabs', () => {
	const app = setup('light', true);
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'light');
	app.media.matches = false;
	app.listeners.change();
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'light');
	app.storage.mode = 'dark';
	app.listeners.storage({ key: 'toolpad-mode' });
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'dark');
	app.storage.mode = 'system';
	app.listeners.storage({ key: 'toolpad-mode' });
	assert.equal(app.attributes['data-studyieltsvn-color-scheme'], 'light');
});

test('light preset button text has sufficient contrast', () => {
	const preset = JSON.parse(fs.readFileSync(path.join(__dirname, '../config/buddyboss-preset.json'), 'utf8'));
	function luminance(hex) {
		const channels = hex.slice(1).match(/../g).map((part) => parseInt(part, 16) / 255);
		const linear = channels.map((value) => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
		return 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2];
	}
	for (const type of ['primary', 'secondary']) {
		for (const state of ['regular', 'hover']) {
			const background = luminance(preset[`${type}_button_background`][state]);
			const text = luminance(preset[`${type}_button_text_color`][state]);
			const ratio = (Math.max(background, text) + 0.05) / (Math.min(background, text) + 0.05);
			assert.ok(ratio >= 4.5, `${type} ${state}: ${ratio.toFixed(2)}:1`);
		}
	}
});
