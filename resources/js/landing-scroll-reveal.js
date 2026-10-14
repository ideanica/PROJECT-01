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
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                const target = revealTargets.find(({ element }) => element === entry.target);
                const delay = target?.delay ?? 0;

                // Keep content visible if observation is delayed, and preserve
                // the transforms used by the landing layout and touch targets.
                if (!reducedMotion.matches && typeof entry.target.animate === 'function') {
                    entry.target.animate([{ opacity: 0.35 }, { opacity: 1 }], {
                        duration: 680,
                        delay,
                        easing: 'ease-out',
                    });
                }
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.01,
        });

        revealTargets.forEach(({ element }) => observer.observe(element));
    }
}
