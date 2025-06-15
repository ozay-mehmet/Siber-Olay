/**
 * @param {HTMLElement} input 
 * @param {boolean} isValid 
 * @param {string} message 
 * @param {Object} options 
 * @param {boolean} options.showIcon
 * @param {boolean} options.animate 
 */

document.addEventListener('DOMContentLoaded', function() {
    addValidationStyles();

    // Hata mesajlarını göster
    const errorBox = document.querySelector('.error-box');
    if (errorBox) {
        errorBox.classList.add('show');
    }

    // Başarı mesajlarını göster
    const successBox = document.querySelector('.success-box');
    if (successBox) {
        successBox.classList.add('show');
    }
});

const showValidationFeedback = (input, isValid, message = '', options = {}) => {
    const defaults = {
        showIcon: true,
        animate: true,
        iconSize: 20,
        successIcon: '✓',
        errorIcon: '⚠'
    };
    
    const config = { ...defaults, ...options };
    const group = input.closest('.input-group');
    
    if (!group) return;
    
    let errorEl = group.querySelector('.error-feedback');
    if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'error-feedback';
        group.appendChild(errorEl);
    }
    
    input.classList.remove('is-valid', 'is-invalid');
    group.classList.remove('valid', 'invalid');
    
    const stateClass = isValid ? 'is-valid' : 'is-invalid';
    const groupStateClass = isValid ? 'valid' : 'invalid';
    
    void input.offsetWidth;
    
    input.classList.add(stateClass);
    group.classList.add(groupStateClass);
    
    errorEl.textContent = isValid ? '' : message;
    errorEl.style.maxHeight = isValid ? '0' : `${errorEl.scrollHeight}px`;
    
    if (config.showIcon) {
        let icon = group.querySelector('.validation-icon');
        if (!icon) {
            icon = document.createElement('span');
            icon.className = 'validation-icon';
            icon.style.position = 'absolute';
            icon.style.right = '15px';
            icon.style.top = '50%';
            icon.style.transform = 'translateY(-50%)';
            icon.style.fontSize = `${config.iconSize}px`;
            icon.style.transition = 'all 0.3s ease';
            group.style.position = 'relative';
            group.appendChild(icon);
        }
        
        icon.textContent = isValid ? config.successIcon : config.errorIcon;
        icon.style.color = isValid ? '#4CAF50' : '#F44336';
        
        if (config.animate) {
            icon.style.animation = 'none';
            void icon.offsetWidth;
            icon.style.animation = 'bounceIn 0.5s ease';
        }
    }
    
    if (config.animate && !isValid) {
        input.style.animation = 'none';
        void input.offsetWidth;
        input.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97)';
    }
    
    if (config.animate && isValid) {
        input.style.animation = 'none';
        void input.offsetWidth;
        input.style.animation = 'pulse 0.5s ease';
    }
};

const addValidationStyles = () => {
    const style = document.createElement('style');
    // keyframe'ler
    style.textContent = `
        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }
        
        @keyframes bounceIn {
            0% { transform: translateY(-20px) translateY(-50%); opacity: 0; }
            50% { transform: translateY(10px) translateY(-50%); }
            100% { transform: translateY(0) translateY(-50%); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }
        
        .error-feedback {
            color: #F44336;
            font-size: 0.85rem;
            margin-top: 8px;
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            max-height: 0;
        }
        
        .invalid .error-feedback {
            opacity: 1;
        }
        
        .input-group {
            transition: all 0.3s ease;
        }
        
        .input-group.invalid input {
            border-color: #F44336 !important;
            background-color: rgba(244, 67, 54, 0.05) !important;
        }
        
        .input-group.valid input {
            border-color: #4CAF50 !important;
            background-color: rgba(76, 175, 80, 0.05) !important;
        }
    `;
    document.head.appendChild(style);
};

document.addEventListener('DOMContentLoaded', addValidationStyles);