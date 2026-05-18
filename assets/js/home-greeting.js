(function () {
    var el = document.getElementById('home-greeting');
    if (!el) {
        return;
    }

    var hour = new Date().getHours();
    el.textContent = hour >= 17 ? 'Good Evening' : 'Good Morning';
})();
