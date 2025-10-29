<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div id="mpia-chat-root" class="mpia-theme">
  <button id="mpia-toggle" title="<?php esc_attr_e('Abrir chat','mi-plugin-ia'); ?>">
    💬
    <span id="mpia-badge" aria-hidden="true"></span>
  </button>

  <div id="mpia-chatbox" style="display:none;" role="dialog" aria-label="<?php esc_attr_e('Asistente virtual','mi-plugin-ia'); ?>">
    <div class="mpia-header">
      <div class="mpia-title">
        <img class="mpia-avatar" src="https://ui-avatars.com/api/?name=AI&background=ffffff&color=0b57d0" alt="" />
        <strong><?php esc_html_e('Asistente virtual','mi-plugin-ia'); ?></strong>
      </div>
      <button id="mpia-close" aria-label="<?php esc_attr_e('Cerrar','mi-plugin-ia'); ?>">✖</button>
    </div>

    <div id="mpia-messages" role="log" aria-live="polite"></div>

    <div id="mpia-chips" class="mpia-chips" aria-label="<?php esc_attr_e('Sugerencias','mi-plugin-ia'); ?>">
      <button class="mpia-chip">¿Qué puedes hacer?</button>
      <button class="mpia-chip">Dame un ejemplo</button>
      <button class="mpia-chip">Ayúdame con mi pedido</button>
    </div>

    <form id="mpia-form" class="mpia-input-wrap" autocomplete="off">
    <input id="mpia-input" type="text" placeholder="<?php esc_attr_e('Escribe tu mensaje...','mi-plugin-ia'); ?>" />
    <button id="mpia-send" type="submit" title="<?php esc_attr_e('Enviar','mi-plugin-ia'); ?>" aria-label="<?php esc_attr_e('Enviar','mi-plugin-ia'); ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 21l20-9L2 3v7l15 2-15 2v7z"/></svg>
    </button>
    </form>

  </div>
</div>
