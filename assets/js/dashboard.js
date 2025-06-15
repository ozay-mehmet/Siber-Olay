document.addEventListener('DOMContentLoaded', function() {
  setupDashboard();

  function setupDashboard() {
    animateElements();
    addInteractions();
    startLiveUpdates();
    injectStyles();
  }

  function animateElements() {
    const cards = document.querySelectorAll('.summary-card');
    cards.forEach(function(card, i) {
      card.style.opacity = 0;
      card.style.transform = 'scale(0.85) rotateY(10deg)';
      setTimeout(function() {
        card.style.transition = 'all 0.7s cubic-bezier(.68,-0.55,.27,1.55)';
        card.style.opacity = 1;
        card.style.transform = 'scale(1) rotateY(0)';
        card.style.boxShadow = '0 8px 32px rgba(74,108,247,0.18)';
      }, 120 * i);
    });

    const rows = document.getElementsByClassName('table-row');
    for (let i = 0; i < rows.length; i++) {
      rows[i].style.opacity = 0;
      rows[i].style.transform = 'translateX(40px) scale(0.97)';
      setTimeout((function(row) {
        return function() {
          row.style.transition = 'all 0.6s cubic-bezier(.68,-0.55,.27,1.55)';
          row.style.opacity = 1;
          row.style.transform = 'translateX(0) scale(1)';
        };
      })(rows[i]), 200 + (70 * i));
    }

    const actionBtns = document.getElementsByClassName('quick-action-btn');
    Array.from(actionBtns).forEach(function(btn) {
      btn.addEventListener('mouseover', function() {
        this.style.animation = 'btnGlow 1.2s infinite alternate';
      });
      btn.addEventListener('mouseout', function() {
        this.style.animation = '';
      });
    });
  }

  function addInteractions() {
    const cards = document.getElementsByClassName('summary-card');
    for (const card of cards) {
      card.addEventListener('mousedown', function() {
        this.style.transform = 'scale(0.96) rotateZ(-2deg)';
      });
      card.addEventListener('mouseup', function() {
        this.style.transform = '';
      });
      card.addEventListener('mouseleave', function() {
        this.style.transform = '';
      });
      card.addEventListener('mouseenter', function() {
        this.style.boxShadow = '0 12px 40px 0 rgba(74,108,247,0.22)';
      });
      card.addEventListener('mouseleave', function() {
        this.style.boxShadow = '';
      });
    }

    const badges = document.querySelectorAll('.status-badge');
    badges.forEach(function(badge) {
      badge.addEventListener('mouseenter', showStatusTooltip);
      badge.addEventListener('mouseleave', removeTooltip);
      badge.addEventListener('click', function() {
        badge.classList.add('badge-pop');
        setTimeout(() => badge.classList.remove('badge-pop'), 400);
      });
    });
  }

  function startLiveUpdates() {
    setInterval(function() {
      updateCounters();
    }, 30000);
  }

  function updateCounters() {
    const counters = document.getElementsByClassName('count');
    Array.from(counters).forEach(function(counter) {
      const target = +counter.textContent;
      const current = +(counter.dataset.current || 0);
      if (target !== current) {
        animateValue(counter, current, target, 1200);
        counter.dataset.current = target;
      }
    });
  }

  function animateValue(el, start, end, duration) {
    let startTime;
    function update(timestamp) {
      if (!startTime) startTime = timestamp;
      const progress = Math.min((timestamp - startTime) / duration, 1);
      el.textContent = Math.floor(progress * (end - start) + start);
      if (progress < 1) {
        window.requestAnimationFrame(update);
      } else {
        el.textContent = end;
      }
    }
    window.requestAnimationFrame(update);
  }

  // olay durumları 
  function showStatusTooltip(e) {
    const badge = e.target;
    const status = badge.textContent.trim().toLowerCase();
    let message = '';
    const statusMessages = {
      'açık': 'Henüz çözülmemiş olay',
      'incelemede': 'Aktif olarak incelenen olay',
      'çözüldü': 'Çözüme ulaştırılmış olay'
    };
    message = statusMessages[status] || 'Olay durumu';
    const tooltip = document.createElement('div');
    tooltip.className = 'status-tooltip';
    tooltip.textContent = message;
    const rect = badge.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2) + 'px';
    tooltip.style.top = (rect.top - 38) + 'px';
    document.body.appendChild(tooltip);
    setTimeout(() => tooltip.classList.add('visible'), 10);
  }

  function removeTooltip() {
    const tooltip = document.querySelector('.status-tooltip');
    if (tooltip) {
      tooltip.classList.remove('visible');
      setTimeout(() => tooltip.remove(), 200);
    }
  }

  function injectStyles() {
    // css stilleri
    const css = `
      @keyframes btnGlow {
        0% { box-shadow: 0 0 0 0 #48dbfb44, 0 2px 10px #4a6cf7; }
        100% { box-shadow: 0 0 18px 8px #48dbfb44, 0 2px 18px #4a6cf7; }
      }
      @keyframes badgePop {
        0% { transform: scale(1);}
        50% { transform: scale(1.15) rotate(-5deg);}
        100% { transform: scale(1);}
      }
      .badge-pop {
        animation: badgePop 0.4s cubic-bezier(.68,-0.55,.27,1.55);
      }
      .status-tooltip {
        position: fixed;
        background: linear-gradient(90deg, #4a6cf7 60%, #48dbfb 100%);
        color: white;
        padding: 7px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 4px 18px rgba(74,108,247,0.18);
        transform: translateX(-50%) scale(0.95);
        opacity: 0;
        transition: all 0.22s cubic-bezier(.68,-0.55,.27,1.55);
        z-index: 100;
        pointer-events: none;
        letter-spacing: 0.2px;
      }
      .status-tooltip.visible {
        opacity: 1;
        transform: translateX(-50%) scale(1.05);
      }
      .status-tooltip:after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -7px;
        border: 7px solid transparent;
        border-top-color: #4a6cf7;
      }
      .summary-card {
        will-change: transform, box-shadow;
      }
      .table-row {
        will-change: transform, box-shadow;
      }
    `;
    const style = document.createElement('style');
    style.type = 'text/css';
    style.appendChild(document.createTextNode(css));
    document.head.appendChild(style);
  }
});