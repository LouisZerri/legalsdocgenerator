import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content', 'result', 'loading', 'actions']
    static values = {
        documentId: Number
    }

    connect() {
        this.checkStatus();
    }

    async checkStatus() {
        try {
            const response = await fetch('/ai/status');
            const data = await response.json();
            
            if (!data.available) {
                this.element.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800 text-sm">
                        <strong>IA indisponible</strong> - Le service n'est pas accessible.
                    </div>
                `;
            }
        } catch (e) {
            console.error('Erreur vérification IA:', e);
        }
    }

    async improve(event) {
        const tone = event.params.tone || 'formel';
        await this.callAi('improve', { tone });
    }

    async reformulate(event) {
        const style = event.params.style || 'formel';
        await this.callAi('reformulate', { style });
    }

    async summarize() {
        await this.callAi('summarize', {}, 'summary');
    }

    async check() {
        await this.callAi('check', {}, 'analysis');
    }

    async callAi(action, params = {}, resultKey = 'content') {
        this.showLoading();

        try {
            const response = await fetch(`/ai/document/${this.documentIdValue}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(params),
            });

            const data = await response.json();

            if (data.success) {
                this.showResult(data[resultKey], resultKey === 'content');
            } else {
                this.showError(data.error || 'Une erreur est survenue');
            }
        } catch (e) {
            this.showError('Erreur de connexion au service IA');
        }
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.remove('hidden');
        }
        if (this.hasResultTarget) {
            this.resultTarget.classList.add('hidden');
        }
        if (this.hasActionsTarget) {
            this.actionsTarget.classList.add('hidden');
        }
    }

    showResult(content, canApply = false) {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
        if (this.hasResultTarget) {
            this.resultTarget.classList.remove('hidden');
            this.resultTarget.querySelector('[data-ai-content]').textContent = content;
        }
        if (this.hasActionsTarget && canApply) {
            this.actionsTarget.classList.remove('hidden');
            this.actionsTarget.dataset.content = content;
        }
    }

    showError(message) {
        if (this.hasLoadingTarget) {
            this.loadingTarget.classList.add('hidden');
        }
        if (this.hasResultTarget) {
            this.resultTarget.classList.remove('hidden');
            this.resultTarget.querySelector('[data-ai-content]').innerHTML = `
                <span class="text-red-600">${message}</span>
            `;
        }
    }

    async applyChanges() {
        if (!this.hasActionsTarget) return;

        const content = this.actionsTarget.dataset.content;

        try {
            const response = await fetch(`/ai/document/${this.documentIdValue}/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ content }),
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Erreur lors de l\'application');
            }
        } catch (e) {
            alert('Erreur de connexion');
        }
    }

    cancelChanges() {
        if (this.hasResultTarget) {
            this.resultTarget.classList.add('hidden');
        }
        if (this.hasActionsTarget) {
            this.actionsTarget.classList.add('hidden');
        }
    }
}