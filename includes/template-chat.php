<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="mpia-chat-root" class="mpia-theme">
	<button id="mpia-toggle" title="<?php echo esc_attr__( 'Abrir chat', 'asistente-inteligente' ); ?>">
		<span aria-hidden="true">💬</span>
		<span id="mpia-badge" aria-hidden="true"></span>
	</button>

	<div
		id="mpia-chatbox"
		style="display:none;"
		role="dialog"
		aria-label="<?php echo esc_attr__( 'Asistente virtual', 'asistente-inteligente' ); ?>"
	>
		<div class="mpia-header">
			<div class="mpia-title">
				<img
					class="mpia-avatar"
					src="<?php echo esc_url( 'https://ui-avatars.com/api/?name=AI&background=ffffff&color=0b57d0' ); ?>"
					alt="<?php echo esc_attr__( 'Avatar del asistente virtual', 'asistente-inteligente' ); ?>"
					width="22"
					height="22"
				/>
				<strong><?php echo esc_html__( 'Asistente virtual', 'asistente-inteligente' ); ?></strong>
			</div>
			<button
				id="mpia-close"
				type="button"
				aria-label="<?php echo esc_attr__( 'Cerrar', 'asistente-inteligente' ); ?>"
			>
				<span aria-hidden="true">✖</span>
			</button>
		</div>

		<div id="mpia-messages" role="log" aria-live="polite"></div>

		<div
			id="mpia-chips"
			class="mpia-chips"
			aria-label="<?php echo esc_attr__( 'Sugerencias', 'asistente-inteligente' ); ?>"
		>
			<button class="mpia-chip" type="button"><?php echo esc_html__( '¿Qué puedes hacer?', 'asistente-inteligente' ); ?></button>
			<button class="mpia-chip" type="button"><?php echo esc_html__( 'Dame un ejemplo', 'asistente-inteligente' ); ?></button>
			<button class="mpia-chip" type="button"><?php echo esc_html__( 'Ayúdame con mi pedido', 'asistente-inteligente' ); ?></button>
		</div>

		<form id="mpia-form" class="mpia-input-wrap" autocomplete="off">
			<label for="mpia-input" class="screen-reader-text">
				<?php echo esc_html__( 'Escribe tu mensaje', 'asistente-inteligente' ); ?>
			</label>
			<input
				id="mpia-input"
				type="text"
				placeholder="<?php echo esc_attr__( 'Escribe tu mensaje...', 'asistente-inteligente' ); ?>"
			/>
			<button
				id="mpia-send"
				type="submit"
				title="<?php echo esc_attr__( 'Enviar', 'asistente-inteligente' ); ?>"
				aria-label="<?php echo esc_attr__( 'Enviar', 'asistente-inteligente' ); ?>"
			>
				<svg
					width="18"
					height="18"
					viewBox="0 0 24 24"
					aria-hidden="true"
					focusable="false"
					role="img"
				>
					<path d="M2 21l20-9L2 3v7l15 2-15 2v7z" />
				</svg>
			</button>
		</form>
	</div>
</div>
