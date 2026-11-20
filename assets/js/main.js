const initializePage = () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    if (sidebar && toggle) {
        toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', event => {
            if (window.innerWidth < 992 && sidebar.classList.contains('open')
                && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    document.querySelectorAll('[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    const pageSize = document.querySelector('[data-page-size]');
    if (pageSize) {
        pageSize.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', pageSize.value);
            params.delete('page');
            window.location.search = params.toString();
        });
    }

    const mailDialog = document.querySelector('[data-mail-dialog]');
    const mailForm = document.querySelector('[data-mail-form]');
    if (mailDialog && mailForm) {
        const status = mailForm.querySelector('[data-mail-status]');
        const submit = mailForm.querySelector('[data-mail-submit]');
        let lastMailTrigger = null;

        const closeMailDialog = () => {
            mailDialog.hidden = true;
            document.body.classList.remove('mail-dialog-open');
            if (lastMailTrigger && lastMailTrigger.isConnected) lastMailTrigger.focus();
        };
        document.addEventListener('click', event => {
            if (!(event.target instanceof Element)) return;
            const openButton = event.target.closest('[data-mail-open]');
            if (openButton) {
                lastMailTrigger = openButton;
                mailDialog.hidden = false;
                document.body.classList.add('mail-dialog-open');
                mailForm.querySelector('[name="to"]').focus();
                return;
            }
            if (event.target.closest('[data-mail-close]') || event.target === mailDialog) {
                closeMailDialog();
            }
        });
        document.addEventListener('keydown', event => {
            if (mailDialog.hidden) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                closeMailDialog();
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = Array.from(mailDialog.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled])'
            )).filter(element => !element.hidden);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
        mailForm.addEventListener('submit', async event => {
            event.preventDefault();
            let sent = false;
            status.hidden = true;
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Sending…';
            try {
                const response = await fetch(mailForm.action, {
                    method: 'POST',
                    body: new FormData(mailForm),
                    headers: { Accept: 'application/json' }
                });
                let result;
                try {
                    result = await response.json();
                } catch {
                    throw new Error('The server could not process the email request. Please try again.');
                }
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'The email could not be sent. Please try again.');
                }
                status.className = 'mail-status alert alert-success';
                status.textContent = result.message;
                status.hidden = false;
                sent = true;
                submit.innerHTML = '<i class="bi bi-check2"></i> Sent';
                window.setTimeout(() => {
                    closeMailDialog();
                    mailForm.reset();
                    submit.disabled = false;
                    submit.innerHTML = '<i class="bi bi-send"></i> Send E-mail';
                    status.hidden = true;
                }, 1400);
            } catch (error) {
                status.className = 'mail-status alert alert-danger';
                status.textContent = error instanceof TypeError
                    ? 'Could not connect to the application server. Check your connection and try again.'
                    : error instanceof Error ? error.message : 'The email could not be sent. Please try again.';
                status.hidden = false;
            } finally {
                if (!sent) {
                    submit.disabled = false;
                    submit.innerHTML = '<i class="bi bi-send"></i> Send E-mail';
                }
            }
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage, { once: true });
} else {
    initializePage();
}
