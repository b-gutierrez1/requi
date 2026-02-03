// Debug Script - Add this to the create.php form to trace the exact issue
// Add this script to the bottom of create.php to debug form submissions

console.log("🔍 DEBUG: Form submission tracker loaded");

// Override the form submission to log exact data
const originalFormSubmit = document.querySelector('form').submit;

document.querySelector('form').addEventListener('submit', function(e) {
    console.log("🚀 FORM SUBMISSION DEBUG");
    console.log("=".repeat(50));
    
    const formData = new FormData(this);
    
    console.log("📋 All form data:");
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    console.log("\n🎯 Distribution specific data:");
    const distributionData = {};
    
    for (let [key, value] of formData.entries()) {
        if (key.includes('distribucion')) {
            console.log(`${key}: ${value}`);
            
            // Parse distribution index and field
            const match = key.match(/^distribucion\[(\d+)\]\[(.+)\]$/);
            if (match) {
                const index = match[1];
                const field = match[2];
                
                if (!distributionData[index]) {
                    distributionData[index] = {};
                }
                distributionData[index][field] = value;
            }
        }
    }
    
    console.log("\n📊 Parsed distribution data:");
    console.table(distributionData);
    
    console.log("\n🕵️ Hidden field analysis:");
    const hiddenFields = document.querySelectorAll('input[type="hidden"][name*="cuenta_contable_id"]');
    hiddenFields.forEach((field, index) => {
        const wrapper = field.closest('.cuenta-contable-wrapper') || field.closest('tr');
        const displayField = wrapper ? wrapper.querySelector('input[name*="cuenta_contable_display"]') : null;
        
        console.log(`Hidden field ${index}:`);
        console.log(`  Name: ${field.name}`);
        console.log(`  Value: "${field.value}"`);
        console.log(`  Display field value: "${displayField ? displayField.value : 'N/A'}"`);
        console.log(`  Is empty: ${field.value === '' || field.value === null || field.value === undefined}`);
    });
    
    console.log("\n⚠️ Issues detected:");
    let issuesFound = 0;
    
    Object.keys(distributionData).forEach(index => {
        const dist = distributionData[index];
        
        if (dist.centro_costo_id && (!dist.cuenta_contable_id || dist.cuenta_contable_id === '')) {
            console.log(`❌ Distribution ${index}: Has center cost but missing account ID`);
            issuesFound++;
        }
        
        if (dist.cuenta_contable_id === '1') {
            console.log(`⚠️ Distribution ${index}: Using default account ID = 1 (Fondo de Caja Chica)`);
        }
    });
    
    if (issuesFound === 0) {
        console.log("✅ No obvious issues detected");
    }
    
    console.log("=".repeat(50));
    
    // Allow form to continue submitting
});

// Debug autocomplete selection
function debugCuentaSelection() {
    const hiddenFields = document.querySelectorAll('input[type="hidden"][name*="cuenta_contable_id"]');
    
    hiddenFields.forEach((field, index) => {
        const originalSetValue = function(value) {
            console.log(`🎯 Account ${index} selection: "${value}"`);
            field.value = value;
        };
        
        // Monitor value changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                    console.log(`🔄 Account ${index} value changed to: "${field.value}"`);
                }
            });
        });
        
        observer.observe(field, { attributes: true });
        
        // Monitor input events
        field.addEventListener('input', function() {
            console.log(`📝 Account ${index} input event: "${this.value}"`);
        });
        
        field.addEventListener('change', function() {
            console.log(`🔄 Account ${index} change event: "${this.value}"`);
        });
    });
}

// Initialize debugging when page loads
document.addEventListener('DOMContentLoaded', function() {
    debugCuentaSelection();
    console.log("🔍 Account selection debugging initialized");
});

// Debug the autocomplete function if it exists
if (typeof seleccionarCuentaDesdePortal !== 'undefined') {
    const originalFunction = seleccionarCuentaDesdePortal;
    window.seleccionarCuentaDesdePortal = function(id, label) {
        console.log(`🎯 seleccionarCuentaDesdePortal called with ID: ${id}, Label: ${label}`);
        return originalFunction(id, label);
    };
}

if (typeof seleccionarCuenta !== 'undefined') {
    const originalFunction = seleccionarCuenta;
    window.seleccionarCuenta = function(item, id, label) {
        console.log(`🎯 seleccionarCuenta called with ID: ${id}, Label: ${label}`);
        return originalFunction(item, id, label);
    };
}