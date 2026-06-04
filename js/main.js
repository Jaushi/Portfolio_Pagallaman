document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('interactive-modal');
  const modalBackdrop = document.getElementById('modal-backdrop');
  const modalClose = document.getElementById('modal-close');
  const modalTitle = document.getElementById('modal-title');
  const modalContent = document.getElementById('modal-content');
  const contactBtn = document.getElementById('contact-btn');

  if (!modal || !modalBackdrop || !modalClose) return;

  function openModal(title, contentHtml) {
    modalTitle.textContent = title;
    modalContent.innerHTML = contentHtml;
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    // Attach form handler if present
    attachFormHandler();
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  modalBackdrop.addEventListener('click', closeModal);
  modalClose.addEventListener('click', closeModal);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
      closeModal();
    }
  });

  // Show project details when clicking a project card
  document.querySelectorAll('.project-card').forEach((card) => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', (e) => {
      // Prevent opening when clicking links inside the card
      if (e.target.tagName.toLowerCase() === 'a') return;
      const titleEl = card.querySelector('h3');
      const paragraphs = Array.from(card.querySelectorAll('p'));
      const lists = Array.from(card.querySelectorAll('ul'));
      const title = titleEl ? titleEl.textContent.trim() : 'Project Details';
      let contentHtml = '';
      paragraphs.forEach(p => { contentHtml += `<p>${p.innerHTML}</p>`; });
      lists.forEach(ul => { contentHtml += `<div>${ul.outerHTML}</div>`; });
      openModal(title, contentHtml);
    });
  });

  // Contact button opens a form in the modal
  if (contactBtn) {
    contactBtn.addEventListener('click', () => {
      const formHtml = `
        <form id="contact-form" novalidate>
          <div class="form-row">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required minlength="2" />
            <div class="field-error" data-for="name"></div>
          </div>

          <div class="form-row">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required />
            <div class="field-error" data-for="email"></div>
          </div>

          <div class="form-row">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" required minlength="5"></textarea>
            <div class="field-error" data-for="message"></div>
          </div>

          <div class="form-actions">
            <button type="submit" class="cta-button">Send Message</button>
            <button type="button" id="form-cancel" class="cta-button secondary">Cancel</button>
          </div>
          <div id="form-status" aria-live="polite"></div>
        </form>
      `;

      openModal('Contact Me', formHtml);
    });
  }

  // Attach form submit handler if form exists in modal
  function attachFormHandler() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    const status = document.getElementById('form-status');
    const cancelBtn = document.getElementById('form-cancel');

    cancelBtn && cancelBtn.addEventListener('click', () => closeModal());

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      clearErrors(form);
      const data = new FormData(form);
      const name = data.get('name') ? String(data.get('name')).trim() : '';
      const email = data.get('email') ? String(data.get('email')).trim() : '';
      const message = data.get('message') ? String(data.get('message')).trim() : '';

      let hasError = false;
      if (name.length < 2) {
        showError('name', 'Please enter your name (2+ characters)');
        hasError = true;
      }
      if (!validateEmail(email)) {
        showError('email', 'Please enter a valid email');
        hasError = true;
      }
      if (message.length < 5) {
        showError('message', 'Please enter a message (5+ characters)');
        hasError = true;
      }

      if (hasError) return;

      // Send to PHP backend
      status.textContent = 'Sending message...';
      fetch('server/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ name, email, message })
      })
        .then(async (res) => {
          const data = await res.json().catch(() => null);
          if (res.ok && data && data.ok) return data;
          // Validation errors
          if (res.status === 400 && data && data.errors) {
            Object.entries(data.errors).forEach(([k, v]) => showError(k, v));
            status.textContent = 'Please fix the fields highlighted below.';
            throw new Error('validation');
          }
          throw new Error('network');
        })
        .then(() => {
          status.textContent = 'Message sent — thank you! I will respond soon.';
          form.reset();
          setTimeout(closeModal, 1500);
        })
        .catch((err) => {
          if (err.message === 'validation') return;
          status.textContent = 'Failed to send — please try again later.';
          console.error('Contact form error', err);
        });
    });

    // Real-time validation
    ['name','email','message'].forEach((field) => {
      const el = form.querySelector(`[name="${field}"]`);
      if (!el) return;
      el.addEventListener('input', () => {
        const errorBox = form.querySelector(`.field-error[data-for="${field}"]`);
        if (!errorBox) return;
        if (field === 'email') {
          if (el.value && validateEmail(el.value)) errorBox.textContent = '';
        } else {
          if (el.value && el.value.trim().length >= (field === 'name' ? 2 : 5)) errorBox.textContent = '';
        }
      });
    });
  }

  function showError(fieldName, message) {
    const err = modalContent.querySelector(`.field-error[data-for="${fieldName}"]`);
    if (err) err.textContent = message;
  }

  function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach(el => el.textContent = '');
    const status = document.getElementById('form-status');
    if (status) status.textContent = '';
  }

  function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
});
