export function initMenu() {
  const menuToggle = document.getElementById('menu-toggle');
    const menuPanel = document.getElementById('menu-panel');

    function setMenu(open) {
        menuPanel?.classList.toggle('hidden', !open);
        menuPanel?.classList.toggle('flex', open);
        menuToggle?.setAttribute('aria-expanded', String(open));
    }

    menuToggle?.addEventListener('click', () => setMenu(menuPanel.classList.contains('hidden')));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && setMenu(false));
}
