document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const formCard = document.querySelector('.form-card');
    const inputs = document.querySelectorAll('input, textarea, select');
    const submitBtn = document.querySelector('button[type="submit"]');

    if (formCard) {
        formCard.style.opacity = '0';
        formCard.style.transform = 'translateY(60px) scale(0.97)';
        
        setTimeout(() => {
            formCard.style.transition = 'all 0.9s ease-out';
            formCard.style.opacity = '1';
            formCard.style.transform = 'translateY(0) scale(1)';
        }, 100);
    }

    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#4361ee';
            this.style.boxShadow = '0 0 0 4px rgba(67, 97, 238, 0.13)';
            this.style.backgroundColor = 'white';
        });

        input.addEventListener('blur', function() {
            this.style.borderColor = '#e2e8f0';
            this.style.boxShadow = 'none';
            this.style.backgroundColor = '#f8fafc';
        });
    });

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                shakeForm();
                highlightInvalidFields();
                e.preventDefault();
                return;
            }

            e.preventDefault();
            showLoadingState();
            
            setTimeout(() => {
                form.submit();
            }, 1300);
        });
    }

    function shakeForm() {
        form.style.transition = 'none';
        
        const keyframes = [
            { transform: 'translateX(-12px)' },
            { transform: 'translateX(12px)' },
            { transform: 'translateX(-8px)' },
            { transform: 'translateX(8px)' },
            { transform: 'translateX(-4px)' },
            { transform: 'translateX(4px)' },
            { transform: 'translateX(0)' }
        ];
        
        form.animate(keyframes, {
            duration: 700,
            easing: 'cubic-bezier(0.25, 0.1, 0.25, 1)'
        });
    }

    function highlightInvalidFields() {
        document.querySelectorAll(':invalid').forEach(el => {
            let count = 0;
            const interval = setInterval(() => {
                el.style.backgroundColor = count % 2 === 0 ? 'rgba(239, 35, 60, 0.13)' : '';
                el.style.borderColor = count % 2 === 0 ? '#f72585' : '#e2e8f0';
                count++;
                if (count > 5) {
                    clearInterval(interval);
                    el.style.backgroundColor = '';
                    el.style.borderColor = '#e2e8f0';
                }
            }, 150);
        });
    }

    function showLoadingState() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="submit-spinner">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="10" r="8" stroke="#f3f3f3" stroke-width="3"></circle>
                    <circle cx="10" cy="10" r="8" stroke="#4361ee" stroke-width="3" stroke-linecap="round" stroke-dasharray="50 100"></circle>
                </svg>
            </span>
            Gönderiliyor...
        `;
        
        const spinner = submitBtn.querySelector('.submit-spinner circle:nth-child(2)');
        let rotation = 0;
        const spinInterval = setInterval(() => {
            rotation += 30;
            spinner.style.transform = `rotate(${rotation}deg)`;
        }, 100);
        
        formCard.style.transition = 'all 0.5s ease-out';
        formCard.style.transform = 'translateY(-18px) scale(0.98)';
    }

    const textarea = document.getElementById('description');
    if (textarea) {
        const charCounter = document.createElement('div');
        charCounter.className = 'char-counter';
        charCounter.textContent = '0/1000';
        textarea.parentNode.appendChild(charCounter);

        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCounter.textContent = `${count}/1000`;
            
            if (count > 950) {
                charCounter.style.color = '#f72585';
            } else if (count > 800) {
                charCounter.style.color = '#f8961e';
            } else {
                charCounter.style.color = '#6c757d';
            }
        });
    }

    const message = document.querySelector('.message-container');
    if (message) {
        message.style.opacity = '0';
        message.style.transform = 'translateY(-24px) scale(0.98)';
        
        setTimeout(() => {
            message.style.transition = 'all 0.7s cubic-bezier(0.68, -0.55, 0.27, 1.55)';
            message.style.opacity = '1';
            message.style.transform = 'translateY(0) scale(1)';
            
            setTimeout(() => {
                message.style.transition = 'all 0.6s ease-in';
                message.style.opacity = '0';
                message.style.transform = 'translateY(-24px) scale(0.97)';
                
                setTimeout(() => {
                    message.remove();
                }, 600);
            }, 5000);
        }, 100);
    }
});