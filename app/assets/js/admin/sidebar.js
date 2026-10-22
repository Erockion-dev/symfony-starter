export function initSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  function setSidebar(open) {
      sidebar?.classList.toggle('-translate-x-full', !open);
      overlay?.classList.toggle('hidden', !open);
  }

  document.getElementById('sidebar-toggle')?.addEventListener('click', () => setSidebar(true));
  overlay?.addEventListener('click', () => setSidebar(false));
  document.getElementById('sidebar-close')?.addEventListener('click', () => setSidebar(false));
  document.addEventListener('keydown', (e) => e.key === 'Escape' && setSidebar(false));

  const dropdowns = document.querySelectorAll('[data-dropdown]');

  document.addEventListener('click', (e) => {
      dropdowns.forEach((d) => {
          if (!d.contains(e.target)) d.removeAttribute('open');
      });
  });

  document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') dropdowns.forEach((d) => d.removeAttribute('open'));
  });
}
