/**
 * Tab panels: [data-view-tabs] with [data-view-tab] buttons and [data-view-panel] panels.
 */
(function () {
    function initTabs(root) {
        const tabs = root.querySelectorAll('[data-view-tab]');
        const panels = root.querySelectorAll('[data-view-panel]');
        if (!tabs.length || !panels.length) {
            return;
        }

        const param = root.dataset.viewTabsParam || 'view';
        const defaultTab = root.dataset.viewTabsDefault || tabs[0].dataset.viewTab;

        function tabFromUrl() {
            const value = new URLSearchParams(window.location.search).get(param);
            if (value && root.querySelector('[data-view-tab="' + value + '"]')) {
                return value;
            }
            return defaultTab;
        }

        function setUrl(tab) {
            const url = new URL(window.location.href);
            if (tab === defaultTab) {
                url.searchParams.delete(param);
            } else {
                url.searchParams.set(param, tab);
            }
            history.replaceState(null, '', url.pathname + url.search + url.hash);
        }

        function activate(tab) {
            tabs.forEach(function (btn) {
                const on = btn.dataset.viewTab === tab;
                btn.classList.toggle('view-tab--active', on);
                btn.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(function (panel) {
                panel.hidden = panel.dataset.viewPanel !== tab;
            });
            setUrl(tab);
        }

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.dataset.viewTab);
            });
        });

        activate(tabFromUrl());
    }

    document.querySelectorAll('[data-view-tabs]').forEach(initTabs);
})();
