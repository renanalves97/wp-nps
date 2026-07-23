/**
 * AJAX submission logic for the NPS form.
 * Uses event delegation to support multiple [nps_form] instances on the same page.
 */
( function () {
	'use strict';

	function handleSubmit( event ) {
		var form = event.target;

		if ( ! form.classList || ! form.classList.contains( 'nps-form' ) ) {
			return;
		}

		event.preventDefault();

		var btn = form.querySelector( '.nps-btn' );
		var msgBox = form.querySelector( '.nps-message' );
		var scoreInput = form.querySelector( 'input[name="score"]:checked' );

		if ( ! scoreInput ) {
			window.alert( 'Please select a score from 0 to 10.' );
			return;
		}

		var score = parseInt( scoreInput.value, 10 );

		var originalLabel = btn.textContent;
		btn.disabled = true;
		btn.textContent = 'Sending...';
		msgBox.className = 'nps-message';
		msgBox.innerHTML = '';

		// new FormData(form) automatically includes all form fields
		// (name, email, score, reason).
		var formData = new FormData( form );
		formData.append( 'action', 'submit_nps_feedback' );
		formData.append( 'nonce', NPSFeedback.nonce );

		fetch( NPSFeedback.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( result ) {
				if ( result.success ) {
					msgBox.classList.add( 'success' );

					if ( score >= 8 ) {
						msgBox.innerHTML =
							'<strong>✓ Submitted successfully!</strong><br>' +
							'Thank you so much for your excellent rating!' +
							( NPSFeedback.googleReviewUrl
								? '<br><br>If you can, please also share your opinion on Google by clicking the button below:<br>' +
									'<a href="' + NPSFeedback.googleReviewUrl + '" target="_blank" rel="noopener noreferrer" class="google-review-btn">Rate us on Google</a>'
								: '' );
					} else {
						msgBox.innerHTML =
							'<strong>✓ Submitted successfully!</strong><br>Thank you for your feedback. Your opinion is essential for us to keep improving our services.';
					}

					form.reset();
				} else {
					msgBox.classList.add( 'error' );
					msgBox.innerHTML =
						result.data && result.data.message
							? result.data.message
							: 'An error occurred while submitting the form. Please try again.';
				}
			} )
			.catch( function () {
				msgBox.classList.add( 'error' );
				msgBox.innerHTML = 'Connection error. Please try again.';
			} )
			.finally( function () {
				btn.disabled = false;
				btn.textContent = originalLabel;
			} );
	}

	document.addEventListener( 'submit', handleSubmit, false );
} )();
