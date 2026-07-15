import { logError } from './lib';

const createAlert = function (level, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${level}`;
    // messages are CMS-authored (tag-limited via heading_striptags) or
    // hash-verified by Craft, never raw user input
    alert.innerHTML = message;

    return alert;
};

const createErrorList = function (errors) {
    const messages = Object.values(errors ?? {}).flat();
    if (!messages.length) {
        return null;
    }

    const list = document.createElement('ul');
    list.className = 'field-errors';

    messages.forEach((message) => {
        const item = document.createElement('li');
        item.textContent = message;
        list.appendChild(item);
    });

    return list;
};

const initAjaxForm = function (form) {
    const messagesEl = form.querySelector('.js-form-messages');
    const submitButton = form.querySelector('[type="submit"]');
    const successMessage = form.dataset.successMessage;
    const failMessage = form.dataset.failMessage;

    // the actionInput() hidden field shadows form.action, so read the attribute
    const url = form.getAttribute('action');

    let sending = false;

    const showMessages = function (...nodes) {
        messagesEl.replaceChildren(...nodes.filter(Boolean));
        messagesEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (sending) {
            return;
        }

        sending = true;
        submitButton.disabled = true;
        messagesEl.replaceChildren();

        try {
            const response = await fetch(url, {
                method: 'POST',
                // Craft switches to a JSON response based on Accept alone
                headers: {
                    Accept: 'application/json',
                },
                body: new FormData(form),
            });
            const data = await response.json();

            if (response.ok) {
                showMessages(createAlert('success', successMessage || data.message));
                form.dispatchEvent(new CustomEvent('ajax-form-success', { bubbles: true }));
                form.reset();
            } else {
                showMessages(
                    createAlert('danger', failMessage),
                    createErrorList(data.errors),
                );
            }
        } catch (err) {
            logError(err);
            showMessages(createAlert('danger', failMessage));
        } finally {
            sending = false;
            submitButton.disabled = false;
        }
    });
};

export const initAjaxForms = function () {
    document.querySelectorAll('form[data-ajax-form]').forEach(initAjaxForm);
};
