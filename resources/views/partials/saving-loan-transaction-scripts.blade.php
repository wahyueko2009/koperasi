<script>
    (() => {
        const modalRoot = document.getElementById('transaction-modal-root');
        const modalContent = document.getElementById('transaction-modal-content');
        const modalTitle = document.getElementById('transaction-modal-title');
        const closeButton = document.getElementById('transaction-modal-close');

        if (!modalRoot || !modalContent || !modalTitle || !closeButton) {
            return;
        }

        const templates = new Map();
        document.querySelectorAll('[data-transaction-template]').forEach((template) => {
            templates.set(template.dataset.transactionTemplate, template);
        });

        const closeModal = () => {
            modalRoot.classList.add('hidden');
            modalRoot.classList.remove('flex');
            modalContent.innerHTML = '';
            document.body.classList.remove('overflow-hidden');
        };

        const openModal = (type) => {
            const template = templates.get(type);
            if (!template) {
                return;
            }

            const contentNode = template.content.firstElementChild.cloneNode(true);
            modalTitle.textContent = contentNode.dataset.title || 'Form Transaksi';
            modalContent.innerHTML = '';
            modalContent.appendChild(contentNode.firstElementChild);
            modalRoot.classList.remove('hidden');
            modalRoot.classList.add('flex');
            document.body.classList.add('overflow-hidden');

            modalContent.querySelectorAll('[data-close-transaction-modal]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });
        };

        document.querySelectorAll('[data-open-transaction-modal]').forEach((button) => {
            button.addEventListener('click', () => openModal(button.dataset.openTransactionModal));
        });

        closeButton.addEventListener('click', closeModal);

        modalRoot.addEventListener('click', (event) => {
            if (event.target === modalRoot) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modalRoot.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
