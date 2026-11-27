// Homepage promotion cover: every slide gets the height of the tallest one, so the slideshow (and the page below it)
// doesn't jump when the promotions' texts from the database differ in length.
// Bootstrap hides the inactive slides (display: none), so each one is briefly laid out off-screen to measure it.
document.addEventListener('DOMContentLoaded', function () {
    var cover = document.getElementById('promoCover');
    if (!cover) return;

    var items = cover.querySelectorAll('.carousel-item');
    if (items.length < 2) return;

    var frame = null;

    function equalize() {
        var slides = cover.querySelectorAll('.home-cover-slide');
        var tallest = 0;

        // Measure the natural heights, without the min-height set last time
        slides.forEach(function (slide) { slide.style.minHeight = ''; });

        items.forEach(function (item) {
            var hidden = getComputedStyle(item).display === 'none';

            if (hidden) {
                item.style.cssText = 'display: block; position: absolute; top: 0; left: 0; width: 100%; visibility: hidden;';
            }

            tallest = Math.max(tallest, item.querySelector('.home-cover-slide').offsetHeight);

            if (hidden) item.style.cssText = '';
        });

        slides.forEach(function (slide) { slide.style.minHeight = tallest + 'px'; });
    }

    // At most once per frame while the window is being resized
    function schedule() {
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(equalize);
    }

    equalize();
    window.addEventListener('resize', schedule);
    window.addEventListener('load', schedule); // images are in
    if (document.fonts) document.fonts.ready.then(schedule); // text wraps differently once the web font is in

    // The dots under the slideshow are outside the carousel, so Bootstrap doesn't mark the active one.
    // Move "active" to the dot of the slide that is coming in.
    var dots = document.querySelectorAll('[data-bs-target="#promoCover"][data-bs-slide-to]');
    var dotsWrap = document.querySelector('.home-cover-dots');

    cover.addEventListener('slide.bs.carousel', function (event) {
        // Going back (prev, or an earlier dot): the line flows to the left instead (CSS)
        if (dotsWrap) dotsWrap.classList.toggle('is-reverse', event.direction === 'right');

        dots.forEach(function (dot, index) {
            var active = index === event.to;
            dot.classList.toggle('active', active);
            if (active) {
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.removeAttribute('aria-current');
            }
        });
    });
});
