import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['status'];

    async save(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const statusTarget = this.statusTarget;

        statusTarget.textContent = 'Salvando...';
        statusTarget.className = 'text-sm text-blue-500';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok) {
                statusTarget.textContent = data.message;
                statusTarget.className = 'text-sm text-green-600';
                
                // Clear message after 3 seconds
                setTimeout(() => {
                    statusTarget.textContent = '';
                }, 3000);
            } else {
                statusTarget.textContent = 'Erro: ' + (data.error || 'Falha ao salvar');
                statusTarget.className = 'text-sm text-red-600';
            }
        } catch (error) {
            console.error('Error:', error);
            statusTarget.textContent = 'Erro de conexão';
            statusTarget.className = 'text-sm text-red-600';
        }
    }
}
