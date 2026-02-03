/**
 * AUTHORIZATION EFFECTS - Sistema de efectos espectaculares
 * Efectos visuales y sonoros para celebrar autorizaciones exitosas
 */

class AuthorizationEffects {
    constructor() {
        this.isEffectsEnabled = true;
        this.soundEnabled = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.hapticEnabled = 'vibrate' in navigator;
    }

    /**
     * Efecto principal cuando se autoriza algo
     */
    celebrate(message = '¡Autorizado exitosamente!', type = 'authorization') {
        if (!this.isEffectsEnabled) return;

        // Secuencia de efectos en orden
        this.showSuccessNotification(message, type);
        this.createConfetti();
        this.addSuccessPulse();
        this.playSuccessSound();
        this.triggerHapticFeedback();
        
        // Efectos adicionales para autorización completa
        if (type === 'complete') {
            setTimeout(() => this.createFireworks(), 500);
            setTimeout(() => this.showCompletionModal(message), 800);
        }
    }

    /**
     * Notificación deslizante moderna
     */
    showSuccessNotification(message, type) {
        // Remover notificación existente si la hay
        const existing = document.querySelector('.success-notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = 'success-notification show d-flex align-items-center';
        
        const icons = {
            authorization: 'fas fa-check-circle',
            revision: 'fas fa-eye',
            complete: 'fas fa-trophy',
            special: 'fas fa-star'
        };

        notification.innerHTML = `
            <div class="notification-icon">
                <i class="${icons[type] || icons.authorization}"></i>
            </div>
            <div class="notification-content">
                <div class="notification-title">¡Excelente!</div>
                <div class="notification-message">${message}</div>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remover después de la animación
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3500);
    }

    /**
     * Efecto de confetti colorido
     */
    createConfetti() {
        const container = document.createElement('div');
        container.className = 'confetti-container';
        document.body.appendChild(container);

        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        const confettiCount = 50;

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.animationDelay = Math.random() * 3 + 's';
            confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
            container.appendChild(confetti);
        }

        // Limpiar después de la animación
        setTimeout(() => {
            if (container.parentNode) {
                container.remove();
            }
        }, 5000);
    }

    /**
     * Efecto de fuegos artificiales para celebraciones completas
     */
    createFireworks() {
        const container = document.createElement('div');
        container.className = 'fireworks-container';
        document.body.appendChild(container);

        const fireworkColors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        
        // Crear múltiples explosiones
        for (let explosion = 0; explosion < 3; explosion++) {
            setTimeout(() => {
                this.createFireworkExplosion(container, fireworkColors);
            }, explosion * 400);
        }

        // Limpiar después de todas las explosiones
        setTimeout(() => {
            if (container.parentNode) {
                container.remove();
            }
        }, 3000);
    }

    /**
     * Crear una explosión de fuegos artificiales
     */
    createFireworkExplosion(container, colors) {
        const centerX = Math.random() * window.innerWidth;
        const centerY = window.innerHeight * 0.3 + Math.random() * window.innerHeight * 0.4;
        
        const particleCount = 20;
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'firework';
            particle.style.left = centerX + 'px';
            particle.style.top = centerY + 'px';
            particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            
            // Calcular dirección aleatoria
            const angle = (i / particleCount) * 2 * Math.PI;
            const distance = 50 + Math.random() * 100;
            const dx = Math.cos(angle) * distance;
            const dy = Math.sin(angle) * distance;
            
            particle.style.setProperty('--dx', dx + 'px');
            particle.style.setProperty('--dy', dy + 'px');
            
            container.appendChild(particle);
        }
    }

    /**
     * Pulso de éxito en el elemento autorizado
     */
    addSuccessPulse(element = null) {
        if (!element) {
            element = document.body;
        }
        
        element.classList.add('success-pulse');
        
        setTimeout(() => {
            element.classList.remove('success-pulse');
        }, 1500);
    }

    /**
     * Sonido de éxito (usando Web Audio API)
     */
    playSuccessSound() {
        if (!this.soundEnabled || !window.AudioContext && !window.webkitAudioContext) return;

        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const audioCtx = new AudioContext();
            
            // Crear un sonido de campanilla de éxito
            this.playChime(audioCtx, 523.25, 0.1); // C5
            setTimeout(() => this.playChime(audioCtx, 659.25, 0.1), 100); // E5
            setTimeout(() => this.playChime(audioCtx, 783.99, 0.15), 200); // G5
            
        } catch (error) {
            console.log('Audio no disponible:', error);
        }
    }

    /**
     * Reproducir nota musical
     */
    playChime(audioCtx, frequency, duration) {
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.frequency.value = frequency;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, audioCtx.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + duration);
        gainNode.gain.linearRampToValueAtTime(0, audioCtx.currentTime + duration + 0.01);
        
        oscillator.start(audioCtx.currentTime);
        oscillator.stop(audioCtx.currentTime + duration + 0.01);
    }

    /**
     * Vibración háptica
     */
    triggerHapticFeedback() {
        if (!this.hapticEnabled) return;
        
        // Patrón de vibración para éxito
        navigator.vibrate([100, 50, 100]);
    }

    /**
     * Modal de celebración para autorización completa
     */
    showCompletionModal(message) {
        const modal = document.createElement('div');
        modal.className = 'modal fade success-modal';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title w-100">¡Autorización Completada!</h5>
                    </div>
                    <div class="modal-body">
                        <div class="success-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h4 class="mb-3">¡Felicitaciones!</h4>
                        <p class="mb-4">${message}</p>
                        <div class="authorization-progress">
                            <div class="authorization-progress-bar" style="width: 100%;"></div>
                        </div>
                        <p class="text-muted small">La requisición ha sido procesada exitosamente</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                            <i class="fas fa-check me-2"></i>Entendido
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        // Mostrar modal usando Bootstrap
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Remover del DOM cuando se cierre
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }

    /**
     * Efectos de botón durante la autorización
     */
    animateButton(button, state = 'authorizing') {
        if (!button) return;
        
        button.classList.remove('authorizing', 'authorized');
        button.classList.add('btn-authorize');
        
        if (state === 'authorizing') {
            button.classList.add('authorizing');
            button.disabled = true;
            
            const originalText = button.innerHTML;
            button.innerHTML = `
                <i class="fas fa-spinner fa-spin me-2"></i>
                Autorizando...
            `;
            
            // Guardar el texto original para restaurarlo
            button.dataset.originalText = originalText;
        } else if (state === 'authorized') {
            button.classList.add('authorized');
            button.innerHTML = `
                <i class="fas fa-check me-2"></i>
                ¡Autorizado!
            `;
            
            setTimeout(() => {
                button.disabled = false;
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                }
                button.classList.remove('authorized', 'authorizing');
            }, 2000);
        }
    }

    /**
     * Animar card/fila durante autorización
     */
    animateCard(cardElement, state = 'authorizing') {
        if (!cardElement) return;
        
        cardElement.classList.remove('authorizing', 'authorized', 'pending');
        cardElement.classList.add('authorization-card');
        
        if (state === 'authorizing') {
            cardElement.classList.add('authorizing');
        } else if (state === 'authorized') {
            cardElement.classList.add('authorized');
        } else if (state === 'pending') {
            cardElement.classList.add('pending');
        }
    }

    /**
     * Mostrar barra de progreso de autorización
     */
    showProgress(container, progress = 0) {
        let progressBar = container.querySelector('.authorization-progress');
        
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.className = 'authorization-progress';
            progressBar.innerHTML = '<div class="authorization-progress-bar"></div>';
            container.appendChild(progressBar);
        }
        
        const bar = progressBar.querySelector('.authorization-progress-bar');
        bar.style.width = progress + '%';
    }

    /**
     * Deshabilitar todos los efectos (para usuarios con preferencias de movimiento reducido)
     */
    disableEffects() {
        this.isEffectsEnabled = false;
        this.soundEnabled = false;
    }

    /**
     * Habilitar efectos
     */
    enableEffects() {
        this.isEffectsEnabled = true;
        this.soundEnabled = true;
    }
}

// Crear instancia global
window.AuthEffects = new AuthorizationEffects();

// Auto-deshabilitar efectos si el usuario prefiere movimiento reducido
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.AuthEffects.disableEffects();
}

// Funciones de conveniencia para uso global
window.celebrateAuthorization = (message, type) => {
    window.AuthEffects.celebrate(message, type);
};

window.animateAuthButton = (button, state) => {
    window.AuthEffects.animateButton(button, state);
};

window.animateAuthCard = (card, state) => {
    window.AuthEffects.animateCard(card, state);
};

console.log('🎉 Authorization Effects System loaded!');