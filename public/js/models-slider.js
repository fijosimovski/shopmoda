// Homepage "All models" slider: arrows and a timer move it one card at a time.
// How many cards are visible is decided by the Bootstrap column classes in the markup.
document.addEventListener('DOMContentLoaded', function () {
    var slider = document.getElementById('modelsSlider');
    if (!slider) return;

    var prevBtn = document.querySelector('[data-models-prev]');
    var nextBtn = document.querySelector('[data-models-next]');
    var delay = parseInt(slider.dataset.interval, 10) || 4000;
    var timer = null;

    function step() {
        var card = slider.firstElementChild;
        return card ? card.getBoundingClientRect().width : 0;
    }

    function atEnd() {
        return slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 5;
    }

    function atStart() {
        return slider.scrollLeft <= 5;
    }

    // Hide the arrows when every card already fits on screen
    function toggleArrows() {
        var hidden = slider.scrollWidth <= slider.clientWidth + 5;
        [prevBtn, nextBtn].forEach(function (btn) {
            if (btn) btn.classList.toggle('d-none', hidden);
        });
    }

    function next() {
        if (atEnd()) {
            slider.scrollTo({ left: 0 });
        } else {
            slider.scrollBy({ left: step() });
        }
    }

    function prev() {
        if (atStart()) {
            slider.scrollTo({ left: slider.scrollWidth });
        } else {
            slider.scrollBy({ left: -step() });
        }
    }

    function start() {
        stop();
        timer = setInterval(next, delay);
    }

    function stop() {
        if (timer) clearInterval(timer);
        timer = null;
    }

    if (nextBtn) nextBtn.addEventListener('click', function () { next(); start(); });
    if (prevBtn) prevBtn.addEventListener('click', function () { prev(); start(); });

    // Pause while the user is hovering or swiping
    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', start);
    slider.addEventListener('touchstart', stop, { passive: true });
    slider.addEventListener('touchend', start);

    window.addEventListener('resize', toggleArrows);
    toggleArrows();
    start();
});
