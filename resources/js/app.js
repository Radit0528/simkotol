import Alpine from 'alpinejs';

/**
 * [FRONTEND] SIMKOTOL Global Alpine Application & Toast Store
 * Handles client-side interactivity, dynamic alerts, and micro-interactions.
 */
Alpine.store('toasts', {
    items: [],
    add(message, type = 'info', duration = 4000) {
        const id = Date.now() + Math.random().toString(36).substring(2, 9);
        this.items.push({ id, message, type });
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }
    },
    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
});

window.Alpine = Alpine;
Alpine.start();

// Helper globally callable from vanilla JS or Alpine components
window.toast = (message, type = 'info', duration = 4000) => {
    Alpine.store('toasts').add(message, type, duration);
};
