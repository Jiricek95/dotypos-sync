class CustomAlert {
    constructor() {
        this.modal = null;
        this.styleElement = null;
        this.dialogCount = 0; // Počet aktivních dialogů
    }

    // Přidání CSS do stránky
    injectStyles() {
        if (!document.getElementById('custom-alert-styles')) {
            this.styleElement = document.createElement('style');
            this.styleElement.id = 'custom-alert-styles';
            this.styleElement.innerHTML = `
                .custom-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 1000;
                }
                .custom-modal-content {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                    width: 300px;
                    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
                }
                .custom-modal-text {
                    margin-bottom: 20px;
                    font-size: 16px;
                }
                .custom-modal-buttons {
                    display: flex;
                    justify-content: center;
                    gap: 10px;
                }
                .custom-modal-btn {
                    padding: 10px 20px;
                    border: none;
                    cursor: pointer;
                    border-radius: 4px;
                    font-size: 14px;
                }
                    /*
                .custom-modal-confirm {
                    background-color: #28a745;
                    color: white;
                }
                .custom-modal-cancel {
                    background-color: #dc3545;
                    color: white;
                }
                    */
            `;
            document.head.appendChild(this.styleElement);
        }
    }

    // Vytvoření modálního HTML
    createModal() {
        if (!this.modal) {
            this.modal = document.createElement('div');
            this.modal.classList.add('custom-modal');
            this.modal.style.display = 'none';

            this.modal.innerHTML = `
                <div class="custom-modal-content">
                    <p class="custom-modal-text"></p>
                    <div class="custom-modal-buttons">
                        <button class="button button-primary custom-modal-confirm">OK</button>
                        <button class="button custom-modal-btn button custom-modal-cancel">Zrušit</button>
                    </div>
                </div>
            `;

            document.body.appendChild(this.modal);
            this.textElement = this.modal.querySelector('.custom-modal-text');
            this.confirmButton = this.modal.querySelector('.custom-modal-confirm');
            this.cancelButton = this.modal.querySelector('.custom-modal-cancel');

            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) this.close();
            });
        }
    }

    // Zobrazení alertu (jednoduché oznámení)
    showAlert(message, callback = null) {
        this.injectStyles();
        this.createModal();
        this.dialogCount++;

        this.textElement.textContent = message;
        this.confirmButton.textContent = 'OK';
        this.cancelButton.style.display = 'none';

        this.confirmButton.onclick = () => {
            this.close();
            if (callback) callback();
        };

        this.modal.style.display = 'flex';
    }

    // Zobrazení potvrzovacího dialogu (Ano/Ne)
    showConfirm(message, onConfirm, onCancel = null) {
        this.injectStyles();
        this.createModal();
        this.dialogCount++;

        this.textElement.textContent = message;
        this.confirmButton.textContent = 'Ano';
        this.cancelButton.textContent = 'Ne';
        this.cancelButton.style.display = 'inline-block';

        this.confirmButton.onclick = () => {
            this.close();
            if (onConfirm) onConfirm();
        };

        this.cancelButton.onclick = () => {
            this.close();
            if (onCancel) onCancel(); // Volání funkce při kliknutí na Ne
        };

        this.modal.style.display = 'flex';
    }

    // Zavření dialogu
    close() {
        if (this.modal) {
            this.modal.style.display = 'none';
            this.dialogCount--;

            // Pokud už nejsou žádná otevřená okna, odstraníme CSS
            if (this.dialogCount === 0 && this.styleElement) {
                document.head.removeChild(this.styleElement);
                this.styleElement = null;
            }
        }
    }
}

// Vytvoření instance pro použití
const customAlert = new CustomAlert();