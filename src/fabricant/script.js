// Fonction de bascule sidebar
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('collapsed');

  // Sauvegarde l'état dans localStorage
  const isCollapsed = sidebar.classList.contains('collapsed');
  localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebar');

  // Restaure l'état sauvegardé
  const savedState = localStorage.getItem('sidebarCollapsed');
  if (savedState === 'true') {
    sidebar.classList.add('collapsed');
  } else {
    sidebar.classList.remove('collapsed');
  }

  // Ferme tous les menus déroulants si clic à l'extérieur
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-content').forEach(content => {
        content.style.display = 'none';
      });
      document.querySelectorAll('.arrow i').forEach(icon => {
        icon.classList.remove('rotate');
      });
    }
  });

  // Gère tous les menus déroulants indépendamment
  document.querySelectorAll('.dropdown').forEach(dropdown => {
    const toggle = dropdown.querySelector('.dropdown-toggle');
    const content = dropdown.querySelector('.dropdown-content');
    const arrow = dropdown.querySelector('.arrow i');

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();

      // Fermer les autres menus
      document.querySelectorAll('.dropdown-content').forEach(dc => {
        if (dc !== content) dc.style.display = 'none';
      });
      document.querySelectorAll('.arrow i').forEach(ai => {
        if (ai !== arrow) ai.classList.remove('rotate');
      });

      // Bascule pour celui sélectionné
      const isOpen = content.style.display === 'block';
      content.style.display = isOpen ? 'none' : 'block';
      arrow.classList.toggle('rotate');
    });
  });

  // Met en surbrillance l'élément actif
  const currentURL = window.location.href;
  document.querySelectorAll('.sidebar a').forEach(link => {
    if (link.href === currentURL) {
      link.classList.add('active');
    }
  });
});
