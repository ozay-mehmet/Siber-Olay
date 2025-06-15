document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const header = document.querySelector('.main-header');

    const mobileProfileToggle = document.querySelector('.mobile-dropdown-toggle');
    const mobileProfileMenu = document.querySelector('.mobile-dropdown-menu');

    if (mobileProfileToggle && mobileProfileMenu) {
        mobileProfileToggle.addEventListener('click', function(event) {
            event.preventDefault(); 
            this.classList.toggle('active'); 
            mobileProfileMenu.classList.toggle('open'); 
        });

        document.addEventListener('click', function(event) {
            if (mobileProfileToggle && mobileProfileMenu) { 
                const isClickInsideToggle = mobileProfileToggle.contains(event.target);
                const isClickInsideMenu = mobileProfileMenu.contains(event.target);

                if (!isClickInsideToggle && !isClickInsideMenu) {
                    if (mobileProfileMenu.classList.contains('open')) {
                        mobileProfileMenu.classList.remove('open');
                        mobileProfileToggle.classList.remove('active');
                    }
                }
            }
        });
    }

    if (header) {
        header.style.opacity = '0';
        header.style.transform = 'translateY(-20px)';

        setTimeout(() => {
            header.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
            header.style.opacity = '1';
            header.style.transform = 'translateY(0)';
        }, 100);

        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 10);
        });
    }

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        });
    }

    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const menu = toggle.nextElementSibling;
            closeAllDropdowns();
            menu.classList.toggle('open');
        });
    });

    document.querySelectorAll('.mobile-dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const parent = toggle.closest('.mobile-dropdown');
            const menu = parent.querySelector('.mobile-dropdown-menu');
            const arrow = toggle.querySelector('.mobile-dropdown-arrow');

            closeAllMobileDropdowns(menu);

            menu.classList.toggle('open');
            arrow?.classList.toggle('rotate');
        });
    });

    document.addEventListener('click', () => {
        closeAllDropdowns();
        closeAllMobileDropdowns();
        if (window.innerWidth <= 992) {
            hamburger?.classList.remove('active');
            navLinks?.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    window.addEventListener('resize', () => {
        closeAllDropdowns();
        closeAllMobileDropdowns();
        if (window.innerWidth > 992) {
            hamburger?.classList.remove('active');
            navLinks?.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('open'));
    }

    function closeAllMobileDropdowns(exception = null) {
        document.querySelectorAll('.mobile-dropdown-menu').forEach(menu => {
            if (menu !== exception) menu.classList.remove('open');
        });
        document.querySelectorAll('.mobile-dropdown-arrow').forEach(arrow => arrow.classList.remove('rotate'));
    }
});
