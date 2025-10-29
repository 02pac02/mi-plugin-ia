(function ($) {
  'use strict';

  // Utilidades
  const el = (id) => document.getElementById(id);
  const toggle = el('mpia-toggle');
  const badge = el('mpia-badge');
  const chatbox = el('mpia-chatbox');
  const form = el('mpia-form');
  const input = el('mpia-input');
  const sendBtn = el('mpia-send');
  const log = el('mpia-messages');
  const chips = el('mpia-chips');
  const closeBtn = el('mpia-close');

  let openedOnce = false;
  let unread = 0;

  // Formatear hora
  const fmtTime = () => {
    const d = new Date();
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  // Crear burbuja de mensaje
  const bubble = (text, cls, withMeta = true) => {
    const wrap = document.createElement('div');
    wrap.className = 'mpia-bubble ' + cls;

    const p = document.createElement('div');
    p.textContent = text;
    wrap.appendChild(p);

    if (withMeta) {
      const meta = document.createElement('span');
      meta.className = 'mpia-meta';
      meta.textContent = fmtTime();
      wrap.appendChild(meta);
    }

    log.appendChild(wrap);
    log.scrollTop = log.scrollHeight;
    return wrap;
  };

  // Burbuja de "escribiendo..."
  const typingBubble = () => {
    const wrap = document.createElement('div');
    wrap.className = 'mpia-bubble mpia-bot mpia-typing';
    const dots = document.createElement('div');
    dots.className = 'mpia-dots';
    dots.innerHTML = '<span></span><span></span><span></span>';
    wrap.appendChild(dots);
    log.appendChild(wrap);
    log.scrollTop = log.scrollHeight;
    return wrap;
  };

  // Mostrar saludo solo la primera vez
  const showGreetingOnce = () => {
    try {
      const key = MPIA.firstOpenKey || 'mpia_first_open';
      if (!sessionStorage.getItem(key)) {
        bubble(MPIA.greeting || 'Hola, ¿cómo puedo ayudarte?', 'mpia-bot');
        sessionStorage.setItem(key, '1');
      }
    } catch (e) {
      console.warn('MPIA storage error:', e);
    }
  };

  // Actualizar badge de mensajes no leídos
  const updateBadge = () => {
    if (!badge) return;
    badge.style.display =
      unread > 0 && chatbox.style.display === 'none' ? 'block' : 'none';
  };

  // Abrir y cerrar chat
  const openChat = () => {
    chatbox.style.display = 'flex';
    toggle.style.display = 'none';
    if (!openedOnce) {
      showGreetingOnce();
      openedOnce = true;
    }
    unread = 0;
    updateBadge();
    input.focus();
  };

  const closeChat = () => {
    chatbox.style.display = 'none';
    toggle.style.display = 'inline-block';
  };

  if (toggle) toggle.addEventListener('click', openChat);
  if (closeBtn) closeBtn.addEventListener('click', closeChat);

  // Estado del botón enviar
  const updateSendState = () => {
    sendBtn.disabled = !input.value.trim();
  };
  input.addEventListener('input', updateSendState);
  updateSendState();

  // Chips: insertar texto en el input
  if (chips) {
    chips.addEventListener('click', (e) => {
      if (e.target.classList.contains('mpia-chip')) {
        input.value = e.target.textContent.trim();
        updateSendState();
        input.focus();
      }
    });
  }

  // Enviar con Enter
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!sendBtn.disabled) form.dispatchEvent(new Event('submit'));
    }
  });

  // Envío del mensaje
  if (form)
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (sendBtn.classList.contains('is-loading')) return;

      const text = input.value.trim();
      if (!text) return;

      // Bloqueo UI mientras se envía
      sendBtn.classList.add('is-loading');
      sendBtn.disabled = true;
      input.disabled = true;

      bubble(text, 'mpia-user');
      input.value = '';
      const typing = typingBubble();

      try {
        const res = await fetch(MPIA.restUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': MPIA.nonce,
          },
          body: JSON.stringify({ message: text }),
        });

        const data = await res.json();
        typing.remove();

        if (!res.ok || data.error) {
          bubble(
            '⚠️ ' +
              (data.error || res.statusText || 'Error desconocido del servidor'),
            'mpia-bot'
          );
          if (chatbox.style.display === 'none') {
            unread++;
            updateBadge();
          }
          return;
        }

        bubble(data.reply || 'Sin respuesta', 'mpia-bot');
        if (chatbox.style.display === 'none') {
          unread++;
          updateBadge();
        }
      } catch (err) {
        typing.remove();
        console.error('Chat error:', err);
        bubble('❌ Error de red. Inténtalo de nuevo.', 'mpia-bot');
        if (chatbox.style.display === 'none') {
          unread++;
          updateBadge();
        }
      } finally {
        // Restaurar UI
        sendBtn.classList.remove('is-loading');
        sendBtn.disabled = false;
        input.disabled = false;
        updateSendState();
        input.focus();
      }
    });
})(jQuery);
