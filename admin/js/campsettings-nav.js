document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('.camp-section');
    const navLinks = document.querySelectorAll('.camp-sidebar a');

    if (!sections.length || !navLinks.length) return;

    function showSection(targetId) {
        sections.forEach(function (s) {
            s.classList.toggle('active', s.id === targetId);
        });
        navLinks.forEach(function (link) {
            link.classList.toggle('active', link.getAttribute('href') === '#' + targetId);
        });
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            showSection(targetId);
            history.replaceState(null, '', '#' + targetId);
        });
    });

    var hash = location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        showSection(hash);
    } else {
        showSection(sections[0].id);
    }
});
