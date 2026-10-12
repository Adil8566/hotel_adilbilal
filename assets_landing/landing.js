document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({ duration: 700, easing: 'ease-out-cubic', once: true, offset: 70 });
    }

    var preloader = document.getElementById('preloader');
    if (preloader) {
        window.addEventListener('load', function () {
            setTimeout(function () { preloader.classList.add('hide'); }, 350);
        });
        setTimeout(function () { preloader.classList.add('hide'); }, 1800);
    }

    var nav = document.getElementById('mainNav');
    var scrollTopBtn = document.getElementById('scrollTop');
    var progressCircle = scrollTopBtn ? scrollTopBtn.querySelector('.progress') : null;
    var radius = 23;
    var circumference = 2 * Math.PI * radius;

    if (progressCircle) {
        progressCircle.style.strokeDasharray = circumference;
        progressCircle.style.strokeDashoffset = circumference;
    }

    function updateScrollState() {
        var scrollTop = window.scrollY || window.pageYOffset;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = docHeight > 0 ? Math.min(scrollTop / docHeight, 1) : 0;

        if (nav) {
            nav.classList.toggle('scrolled', scrollTop > 40);
        }
        if (progressCircle) {
            progressCircle.style.strokeDashoffset = circumference - (pct * circumference);
        }
        if (scrollTopBtn) {
            scrollTopBtn.classList.toggle('show', scrollTop > 400);
        }
    }

    window.addEventListener('scroll', updateScrollState, { passive: true });
    updateScrollState();

    if (scrollTopBtn) {
        scrollTopBtn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    document.querySelectorAll('#navMenu .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            var collapseEl = document.getElementById('navMenu');
            if (window.bootstrap && collapseEl) {
                var instance = bootstrap.Collapse.getInstance(collapseEl);
                if (instance) instance.hide();
            }
        });
    });

    var counters = document.querySelectorAll('.counter');
    if ('IntersectionObserver' in window) {
        var counted = new WeakSet();
        var counterObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting || counted.has(entry.target)) return;
                counted.add(entry.target);
                var el = entry.target;
                var target = parseInt(el.getAttribute('data-target'), 10) || 0;
                var start = null;
                var duration = 1200;

                function step(ts) {
                    if (!start) start = ts;
                    var progress = Math.min((ts - start) / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(target * eased).toLocaleString('id-ID');
                    if (progress < 1) requestAnimationFrame(step);
                    else el.textContent = target.toLocaleString('id-ID');
                }
                requestAnimationFrame(step);
            });
        }, { threshold: 0.45 });
        counters.forEach(function (el) { counterObserver.observe(el); });
    }

    var progressBars = document.querySelectorAll('.cause-progress-fill');
    progressBars.forEach(function (bar) {
        var value = bar.getAttribute('data-progress') || '0';
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.style.width = value + '%';
                    observer.unobserve(entry.target);
                });
            }, { threshold: 0.25 });
            observer.observe(bar);
        } else {
            bar.style.width = value + '%';
        }
    });

    var heroGlow = document.querySelector('.hero-glow');
    var heroSection = document.querySelector('.hero');
    if (heroGlow && heroSection && window.matchMedia('(pointer:fine)').matches) {
        heroSection.addEventListener('mousemove', function (e) {
            var rect = heroSection.getBoundingClientRect();
            var x = e.clientX - rect.left;
            var y = e.clientY - rect.top;
            heroGlow.style.transform = 'translate(' + (x - 240) * 0.12 + 'px,' + (y - 240) * 0.12 + 'px)';
        });
    }
});
