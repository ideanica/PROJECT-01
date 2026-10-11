document.querySelectorAll('[data-password-monitor]').forEach((monitor) => {
    const input = monitor.closest('form')?.querySelector('input[name="password"]');
    if (!input) return;
    const confirmation = input.form.querySelector('input[name="password_confirmation"]');

    const rules = {
        length: (password) => Array.from(password).length >= 8,
        uppercase: (password) => /\p{Lu}/u.test(password),
        lowercase: (password) => /\p{Ll}/u.test(password),
        number: (password) => /\p{N}/u.test(password),
        symbol: (password) => /[\p{Z}\p{S}\p{P}]/u.test(password),
        confirmation: (password) => password.length > 0 && confirmation?.value === password,
    };

    const update = () => {
        const password = input.value;
        let fulfilled = 0;

        monitor.querySelectorAll('[data-password-rule]').forEach((item) => {
            const valid = rules[item.dataset.passwordRule](password);
            fulfilled += Number(valid);
            item.classList.toggle('is-met', valid);
            item.querySelector('[data-password-icon]').textContent = valid ? '✓' : '○';
            item.querySelector('[data-password-rule-status]').textContent = valid ? ': terpenuhi' : ': belum terpenuhi';
        });

        const total = Object.keys(rules).length;
        const complete = fulfilled === total;
        monitor.classList.toggle('is-complete', complete);
        monitor.querySelector('[data-password-progress]').value = fulfilled;
        const status = !password ? 'Belum diisi' : complete ? 'Password sudah sesuai' : 'Password belum sesuai';
        monitor.querySelector('[data-password-summary]').textContent = `${status}: ${fulfilled} dari ${total} syarat terpenuhi`;
    };

    input.addEventListener('input', update);
    input.addEventListener('change', update);
    confirmation?.addEventListener('input', update);
    confirmation?.addEventListener('change', update);
    window.addEventListener('pageshow', update);
    input.form.addEventListener('reset', () => window.setTimeout(update, 0));
    update();
});
