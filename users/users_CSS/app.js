// Woke Coliving User App Interactions
document.addEventListener('DOMContentLoaded', () => {

    document.body.classList.add('js-active');
    
    // 1. Button Ripple Effect (Material-like Interaction)
    const buttons = document.querySelectorAll('.btn-custom, .btn-accent');
    buttons.forEach(btn => {
        btn.addEventListener('mousedown', function (e) {
            if (window.getComputedStyle(this).position === 'static') {
                this.style.position = 'relative';
            }
            this.style.overflow = 'hidden';

            let ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple-anim 600ms linear';
            ripple.style.backgroundColor = 'rgba(255, 255, 255, 0.4)';
            ripple.style.pointerEvents = 'none';
            
            this.appendChild(ripple);

            let rect = btn.getBoundingClientRect();
            let diameter = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${diameter}px`;
            let x = e.clientX - rect.left - diameter / 2;
            let y = e.clientY - rect.top - diameter / 2;

            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;

            setTimeout(() => { ripple.remove(); }, 600);
        });
    });

    // 2. Scroll Animation Observer (Slide elements up on scroll)
    const observerOptions = { root: null, rootMargin: '0px', threshold: 0.05 };
    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slide-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.anim-trigger').forEach(el => observer.observe(el));

    // 3. Form Input Interactive Focus Tracking
    const formControls = document.querySelectorAll('.form-control, .form-select');
    formControls.forEach(control => {
        if(control.parentElement && !control.parentElement.classList.contains('input-wrapper')) {
            control.parentElement.classList.add('input-wrapper');
        }
        
        control.addEventListener('focus', () => control.parentElement.classList.add('focused'));
        control.addEventListener('blur', () => control.parentElement.classList.remove('focused'));
    });
});