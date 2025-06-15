document.addEventListener('DOMContentLoaded', function() {
    initCardAnimation();
    initErrorMessageAnimation();
    setupIncidentStatusInteractions();
});

function initCardAnimation() {
    const card = document.querySelector('.incident-detail-card');
    if (!card) return;

    card.classList.add('card-entering');
    
    setTimeout(() => {
        card.classList.add('card-entered');
        card.classList.remove('card-entering');
    }, 100);
}

function initErrorMessageAnimation() {
    const errorMsg = document.querySelector('.message-container.error');
    if (!errorMsg) return;

    errorMsg.classList.add('error-entering');
    
    setTimeout(() => {
        errorMsg.classList.add('error-entered');
        errorMsg.classList.remove('error-entering');
        
        setTimeout(() => {
            errorMsg.classList.add('error-exiting');
            setTimeout(() => {
                errorMsg.classList.remove('error-exiting');
                errorMsg.style.display = 'none';
            }, 800);
        }, 5000);
    }, 200);
}

function setupIncidentStatusInteractions() {
    addStatusBadgeEventListeners();
    injectIncidentSpecificStyles();
}

function addStatusBadgeEventListeners() {
    const statusElements = document.querySelectorAll('.status-açık, .status-incelemede, .status-çözüldü, .severity-belirlenmedi');
    
    statusElements.forEach(badge => {
        badge.addEventListener('mouseenter', handleStatusMouseEnter);
        badge.addEventListener('mouseleave', handleStatusMouseLeave);
        badge.addEventListener('click', handleStatusClick);
    });
}

function handleStatusClick(event) {
    const badge = event.target;
    if (!badge.classList.contains('status-badge-pop-animation')) {
        badge.classList.add('status-badge-pop-animation');
        setTimeout(() => {
            badge.classList.remove('status-badge-pop-animation');
        }, 400);
    }
}

function handleStatusMouseEnter(event) {
    const badge = event.target;
    const statusKey = getStatusKey(badge);
    const message = getStatusMessage(statusKey);
    
    showStatusTooltip(badge, message);
}

function handleStatusMouseLeave() {
    hideStatusTooltip();
}

function getStatusKey(element) {
    if (element.classList.contains('status-açık')) return 'açık';
    if (element.classList.contains('status-incelemede')) return 'incelemede';
    if (element.classList.contains('status-çözüldü')) return 'çözüldü';
    if (element.classList.contains('severity-belirlenmedi')) return 'belirlenmedi';
    return element.textContent.trim().toLowerCase();
}

function getStatusMessage(statusKey) {
    const messages = {
        'açık': 'Durum: Açık - Bu olay henüz aktif olarak incelenmiyor veya çözülmedi.',
        'incelemede': 'Durum: İnceleniyor - Bu olay şu anda aktif olarak incelenmektedir.',
        'çözüldü': 'Durum: Çözüldü - Bu olay başarıyla çözülmüş ve kapatılmıştır.',
        'belirlenmedi': 'Önem Derecesi: Belirlenmedi - Bu olayın önem derecesi henüz atanmamıştır.'
    };
    
    return messages[statusKey] || 'Bu durum hakkında ek bilgi bulunmamaktadır.';
}

function showStatusTooltip(element, message) {
    const existingTooltip = document.querySelector('.incident-status-tooltip');
    if (existingTooltip) existingTooltip.remove();

    const tooltip = document.createElement('div');
    tooltip.className = 'incident-status-tooltip';
    tooltip.textContent = message;
    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();
    
    let top = rect.top - tooltipRect.height - 8;
    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    
    if (top < 0) top = rect.bottom + 8;
    if (left < 0) left = 5;
    else if (left + tooltipRect.width > window.innerWidth) left = window.innerWidth - tooltipRect.width - 5;
    
    tooltip.style.left = `${left}px`;
    tooltip.style.top = `${top}px`;
    
    setTimeout(() => tooltip.classList.add('visible'), 10);
}

function hideStatusTooltip() {
    const tooltip = document.querySelector('.incident-status-tooltip.visible');
    if (tooltip) {
        tooltip.classList.remove('visible');
        setTimeout(() => tooltip.remove(), 200);
    }
}

function injectIncidentSpecificStyles() {
    const styleId = 'incident-dynamic-styles';
    
    if (document.getElementById(styleId)) return;

    const css = `
        @keyframes statusBadgePopAnimation {
            0% { transform: scale(1); }
            50% { transform: scale(1.18) rotate(-4deg); }
            100% { transform: scale(1); }
        }
        
        .card-entering {
            opacity: 0;
            transform: translateY(50px) rotateX(-20deg) scale(0.9);
            transform-origin: center bottom;
        }
        
        .card-entered {
            opacity: 1;
            transform: translateY(0) rotateX(0deg) scale(1);
            transition: opacity 1.3s cubic-bezier(0.165, 0.84, 0.44, 1), 
                        transform 1.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }
        
        .error-entering {
            opacity: 0;
            transform: translateY(-30px) scale(0.8);
        }
        
        .error-entered {
            opacity: 1;
            transform: translateY(0) scale(1);
            transition: opacity 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275), 
                        transform 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .error-exiting {
            opacity: 0;
            transform: translateY(-30px) scale(0.8);
            transition: opacity 0.8s cubic-bezier(0.6, -0.28, 0.735, 0.045), 
                        transform 0.8s cubic-bezier(0.6, -0.28, 0.735, 0.045);
        }
        
        .status-badge-pop-animation {
            animation: statusBadgePopAnimation 0.4s cubic-bezier(.68,-0.55,.27,1.55);
        }
        
        .incident-status-tooltip {
            position: fixed;
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.4;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
            opacity: 0;
            transform: translateY(10px) scale(0.95);
            transition: opacity 0.2s, transform 0.2s;
            z-index: 1070;
            pointer-events: none;
            max-width: 280px;
            text-align: left;
        }
        
        .incident-status-tooltip.visible {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.id = styleId;
    styleElement.type = 'text/css';
    
    if (styleElement.styleSheet) {
        styleElement.styleSheet.cssText = css;
    } else {
        styleElement.appendChild(document.createTextNode(css));
    }
    
    document.head.appendChild(styleElement);
}