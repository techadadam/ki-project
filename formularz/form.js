(() => {
    const form = document.querySelector('#investment-form');

    if (!form) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('.form-status');
    const tokenInput = form.querySelector('input[name="csrf_token"]');

    async function initForm() {
        try {
            const response = await fetch('/formularz/init.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store'
            });

            const data = await response.json();

            if (!response.ok || !data.ok || !data.csrf_token) {
                throw new Error('Nie udało się zainicjować formularza.');
            }

            tokenInput.value = data.csrf_token;
            return true;
        } catch (error) {
            console.error(error);
            status.textContent = 'Formularz jest chwilowo niedostępny. Odśwież stronę.';
            status.className = 'form-status error';
            button.disabled = true;
            return false;
        }
    }

    initForm();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!tokenInput.value) {
            const ready = await initForm();

            if (!ready) {
                return;
            }
        }

        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = 'WYSYŁANIE...';

        status.textContent = '';
        status.className = 'form-status';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            let data;

            try {
                data = await response.json();
            } catch {
                throw new Error('Serwer zwrócił nieprawidłową odpowiedź.');
            }

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Nie udało się wysłać formularza.');
            }

            status.textContent = data.message;
            status.className = 'form-status success';

            form.reset();
            tokenInput.value = '';

            // Nowy token na wypadek kolejnego zapytania.
            await initForm();
        } catch (error) {
            status.textContent = error.message || 'Nie udało się wysłać formularza.';
            status.className = 'form-status error';
        } finally {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'WYŚLIJ ZAPYTANIE';
        }
    });
})();
