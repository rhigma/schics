(function () {
    const MAX_PX = 14;
    const MIN_PX = 9;
    const STEP   = 0.5;

    function fit(ta) {
        let size = MAX_PX;
        ta.style.fontSize = size + 'px';
        let i = 0;
        while (ta.scrollHeight > ta.clientHeight + 1 && size > MIN_PX && i < 60) {
            size -= STEP;
            ta.style.fontSize = size + 'px';
            i++;
        }
        ta.classList.toggle('is-overfull', ta.scrollHeight > ta.clientHeight + 1);
    }

    function fitAll() {
        document.querySelectorAll('textarea[data-shrink]').forEach(fit);
    }

    function init() {
        document.querySelectorAll('textarea[data-shrink]').forEach(ta => {
            fit(ta);
            ta.addEventListener('input', () => fit(ta));
        });
        let raf;
        window.addEventListener('resize', () => {
            cancelAnimationFrame(raf);
            raf = requestAnimationFrame(fitAll);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
