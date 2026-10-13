const landingPage = document.querySelector('.op-page');

if (landingPage) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    const revealTargets = [
        { element: landingPage.querySelector('.op-about-card'), delay: 0 },
        ...[...landingPage.querySelectorAll('.op-stats article')].map((element, index) => ({
            element,
            delay: index * 85,
        })),
        { element: landingPage.querySelector('.op-cta'), delay: 0 },
        { element: landingPage.querySelector('.op-mobile-tracking-wrap'), delay: 0 },
    ].filter(({ element }) => element);

    if (!reducedMotion.matches && 'IntersectionObserver' in window) {
        revealTargets.forEach(({ element }) => {
            element.style.opacity = '0';
            element.style.transform = 'translate3d(0, 34px, 0)';
            element.style.transition = 'opacity 680ms ease, transform 680ms cubic-bezier(.22, 1, .36, 1)';
            element.style.willChange = 'opacity, transform';
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                const target = revealTargets.find(({ element }) => element === entry.target);
                const delay = target?.delay ?? 0;

                entry.target.style.transitionDelay = `${delay}ms`;
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translate3d(0, 0, 0)';
                observer.unobserve(entry.target);

                window.setTimeout(() => {
                    entry.target.style.willChange = 'auto';
                    entry.target.style.transitionDelay = '0ms';
                }, 760 + delay);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px',
        });

        revealTargets.forEach(({ element }) => observer.observe(element));
    }
}
