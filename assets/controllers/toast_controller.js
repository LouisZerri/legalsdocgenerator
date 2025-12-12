import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Afficher avec animation
        setTimeout(() => {
            this.element.classList.remove('translate-x-full', 'opacity-0');
        }, 100);

        // Auto-fermer après 4 secondes
        setTimeout(() => {
            this.close();
        }, 4000);
    }

    close() {
        this.element.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            this.element.remove();
        }, 300);
    }
}