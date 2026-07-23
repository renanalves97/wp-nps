/**
 * Lógica de submissão AJAX do formulário NPS.
 * Usa delegação de eventos para suportar múltiplas instâncias de [formulario_nps] na mesma página.
 */
( function () {
	'use strict';

	function handleSubmit( event ) {
		var form = event.target;

		if ( ! form.classList || ! form.classList.contains( 'sentir-nps-form' ) ) {
			return;
		}

		event.preventDefault();

		var btn = form.querySelector( '.sentir-nps-btn' );
		var msgBox = form.querySelector( '.sentir-nps-message' );
		var notaInput = form.querySelector( 'input[name="nota"]:checked' );

		if ( ! notaInput ) {
			window.alert( 'Por favor, selecione uma nota de 0 a 10.' );
			return;
		}

		var nota = parseInt( notaInput.value, 10 );

		var originalLabel = btn.textContent;
		btn.disabled = true;
		btn.textContent = 'A enviar...';
		msgBox.className = 'sentir-nps-message';
		msgBox.innerHTML = '';

		// new FormData(form) inclui automaticamente todos os campos do formulário
		// (nome, email, nota, motivo).
		var formData = new FormData( form );
		formData.append( 'action', 'enviar_nps_wp' );
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

					if ( nota >= 8 ) {
						msgBox.innerHTML =
							'<strong>✓ Submetido com sucesso!</strong><br>' +
							'Muito obrigado pela sua excelente avaliação!<br><br>' +
							'Se puder, partilhe também a sua opinião no Google clicando no botão abaixo:<br>' +
							'<a href="' + NPSFeedback.googleReviewUrl + '" target="_blank" rel="noopener noreferrer" class="google-review-btn">Avaliar no Google</a>';
					} else {
						msgBox.innerHTML =
							'<strong>✓ Submetido com sucesso!</strong><br>Agradecemos o seu feedback. A sua opinião é essencial para continuarmos a melhorar os nossos serviços.';
					}

					form.reset();
				} else {
					msgBox.classList.add( 'error' );
					msgBox.innerHTML =
						result.data && result.data.message
							? result.data.message
							: 'Ocorreu um erro ao submeter o formulário. Por favor, tente novamente.';
				}
			} )
			.catch( function () {
				msgBox.classList.add( 'error' );
				msgBox.innerHTML = 'Erro de ligação. Por favor, tente novamente.';
			} )
			.finally( function () {
				btn.disabled = false;
				btn.textContent = originalLabel;
			} );
	}

	document.addEventListener( 'submit', handleSubmit, false );
} )();
