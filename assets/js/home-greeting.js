(function () {
    var el = document.getElementById('home-greeting');
    if (!el) {
        return;
    }

    var hour = new Date().getHours();
    var greeting;

    if (hour >= 5 && hour < 12) {
        greeting = 'Good morning';
    } else if (hour >= 12 && hour < 17) {
        greeting = 'Good afternoon';
    } else if (hour >= 17 && hour < 22) {
        greeting = 'Good evening';
    } else {
        greeting = 'Good night';
    }

    el.textContent = greeting;
})();
