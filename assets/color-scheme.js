(function () {
	const media = window.matchMedia('(prefers-color-scheme: dark)');

	function updateColorScheme() {
		let mode;
		try {
			mode = window.localStorage.getItem('toolpad-mode');
		} catch (error) {
			console.warn('Unable to read the Study IELTS color mode:', error);
		}
		const dark = mode === 'dark' || (mode !== 'light' && media.matches);
		document.documentElement.setAttribute('data-studyieltsvn-color-scheme', dark ? 'dark' : 'light');
	}

	updateColorScheme();
	media.addEventListener('change', updateColorScheme);
	window.addEventListener('storage', function (event) {
		if (event.key === 'toolpad-mode' || event.key === null) {
			updateColorScheme();
		}
	});
}());
