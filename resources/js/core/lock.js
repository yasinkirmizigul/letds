export const Lock = {
    on() {
        document.body.classList.add('app-loading');
        document.body.setAttribute('data-app-loading', '1');
    },
    off() {
        document.body.classList.remove('app-loading');
        document.body.removeAttribute('data-app-loading');
    }
};
