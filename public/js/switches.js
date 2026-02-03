/**
 * Modern Switch/Toggle Functions
 * Provides interactive functionality for custom switches across the application
 */

/**
 * Handles toggle interaction for modern switches
 * @param {HTMLElement} slider - The switch slider element clicked
 */
function toggleSwitchContainer(slider) {
    const checkbox = slider.parentElement.querySelector('input[type="checkbox"]');
    const container = slider.closest('.switch-container');
    
    if (!checkbox || !container) {
        console.warn('Switch elements not found');
        return;
    }
    
    // Toggle checkbox
    checkbox.checked = !checkbox.checked;
    
    // Add animation class
    container.classList.add('toggling');
    
    // Remove animation class after animation completes
    setTimeout(() => {
        container.classList.remove('toggling');
    }, 200);
    
    // Dispatch change event for any listeners
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    
    // Handle specific functionality for different switch types
    handleSwitchSpecialFunctionality(checkbox);
}

/**
 * Handles special functionality for specific switches
 * @param {HTMLInputElement} checkbox - The checkbox input element
 */
function handleSwitchSpecialFunctionality(checkbox) {
    switch (checkbox.id) {
        case 'test_mode':
            // Email config test mode switch
            const testRecipientGroup = document.getElementById('test_recipient_group');
            if (testRecipientGroup) {
                testRecipientGroup.style.display = checkbox.checked ? 'block' : 'none';
            }
            break;
            
        case 'activo':
            // Status switches - could update status badges if needed
            updateStatusDisplay(checkbox);
            break;
    }
}

/**
 * Updates status display for status switches
 * @param {HTMLInputElement} checkbox - The status checkbox
 */
function updateStatusDisplay(checkbox) {
    // Find any status badges in the same container and update them
    const container = checkbox.closest('.switch-container');
    if (!container) return;
    
    const statusBadges = container.querySelectorAll('.badge');
    statusBadges.forEach(badge => {
        if (badge.textContent.includes('Activo') || badge.textContent.includes('Inactivo')) {
            if (checkbox.checked) {
                badge.className = 'badge bg-success';
                badge.textContent = 'Activo';
            } else {
                badge.className = 'badge bg-danger';
                badge.textContent = 'Inactivo';
            }
        }
    });
}

/**
 * Initialize switches when DOM is loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add keyboard support for switches
    const switches = document.querySelectorAll('.custom-switch input[type="checkbox"]');
    
    switches.forEach(switchInput => {
        switchInput.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                const slider = this.parentElement.querySelector('.custom-switch-slider');
                if (slider) {
                    toggleSwitchContainer(slider);
                }
            }
        });
    });
    
    // Initialize any switches that need special setup
    initializeSpecialSwitches();
});

/**
 * Initialize switches that need special setup on page load
 */
function initializeSpecialSwitches() {
    // Email config test mode
    const testModeCheckbox = document.getElementById('test_mode');
    if (testModeCheckbox) {
        handleSwitchSpecialFunctionality(testModeCheckbox);
    }
}