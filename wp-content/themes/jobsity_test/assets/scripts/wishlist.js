/* global JobsityWishlist */
(function () {
	'use strict';

	function $(selector, root) {
		return (root || document).querySelector(selector);
	}

	function setButtonState(btn, inWishlist) {
		btn.dataset.inWishlist = inWishlist ? '1' : '0';
		btn.textContent = inWishlist ? JobsityWishlist.strings.remove : JobsityWishlist.strings.add;
		btn.setAttribute('aria-pressed', inWishlist ? 'true' : 'false');
	}

	function toggleWishlist(btn) {
		if (!JobsityWishlist || !JobsityWishlist.ajaxUrl) {
			return;
		}

		if (!JobsityWishlist.isLoggedIn) {
			var msg = JobsityWishlist.strings.loginRequired || 'Please log in to use the wishlist.';
			window.alert(msg);

			var redirect = window.location.href;
			var loginUrl = JobsityWishlist.loginUrl || '';
			if (loginUrl) {
				window.location.href = loginUrl + (loginUrl.indexOf('?') === -1 ? '?' : '&') + 'redirect_to=' + encodeURIComponent(redirect);
			}
			return;
		}

		var movieId = parseInt(btn.dataset.movieId || '0', 10);
		if (!movieId) {
			return;
		}

		btn.disabled = true;

		var body = new URLSearchParams();
		body.append('action', 'jobsity_toggle_wishlist');
		body.append('nonce', JobsityWishlist.nonce || '');
		body.append('movie_id', String(movieId));

		window.fetch(JobsityWishlist.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (!json || !json.success || !json.data) {
					throw new Error('Bad response');
				}
				setButtonState(btn, !!json.data.in_wishlist);
			})
			.catch(function () {
				window.alert(JobsityWishlist.strings.tryAgain || 'Something went wrong. Please try again.');
			})
			.finally(function () {
				btn.disabled = false;
			});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('.js-wishlist-toggle') : null;
		if (!btn) {
			return;
		}
		e.preventDefault();
		toggleWishlist(btn);
	});

	// Initialize any server-rendered buttons.
	document.addEventListener('DOMContentLoaded', function () {
		var btns = document.querySelectorAll('.js-wishlist-toggle[data-in-wishlist]');
		for (var i = 0; i < btns.length; i++) {
			var btn = btns[i];
			setButtonState(btn, btn.dataset.inWishlist === '1');
		}
	});
})();

