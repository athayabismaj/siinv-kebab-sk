export function initFlashAlertPosition() {
    const root = document.documentElement;
    if (root.dataset.flashAlertPositionBound === 'true') return;

    root.dataset.flashAlertPositionBound = 'true';

    const moveGlobalFlashAlerts = () => {
        const main = document.querySelector('[data-app-main]');
        if (!main) return;

        const alert = Array.from(main.children)
            .find((element) => element.dataset.flashAlerts === 'global');
        if (!alert) return;

        const pageRoot = Array.from(main.children)
            .find((element) => element !== alert);
        if (!pageRoot) return;

        const rootClass = typeof pageRoot.className === 'string' ? pageRoot.className : '';
        const isPageWrapper = rootClass.includes('space-y')
            || rootClass.includes('-page')
            || pageRoot.dataset.pageRoot === 'true';
        const header = pageRoot.querySelector('[data-page-header]')
            || (isPageWrapper ? Array.from(pageRoot.children)[0] : pageRoot);

        header?.insertAdjacentElement('afterend', alert);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', moveGlobalFlashAlerts, { once: true });
        return;
    }

    moveGlobalFlashAlerts();
}
