// Fonction de bascule sidebar
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('collapsed');
  
  // Sauvegarde l'état dans localStorage
  const isCollapsed = sidebar.classList.contains('collapsed');
  localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Initialisation au chargement
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  
  // Restaure l'état sauvegardé
  const savedState = localStorage.getItem('sidebarCollapsed');
  if (savedState === 'true') {
    sidebar.classList.add('collapsed');
  } else if (savedState === 'false') {
    sidebar.classList.remove('collapsed');
  }
  
  // Ferme le menu déroulant si clic à l'extérieur
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-toggle') && !e.target.closest('.dropdown-content')) {
      const dropdownContent = document.querySelector('.dropdown-content');
      const arrowIcon = document.querySelector('.arrow i');
      if (dropdownContent) dropdownContent.style.display = 'none';
      if (arrowIcon) arrowIcon.classList.remove('rotate');
    }
  });
  
  // Met en surbrillance l'élément actif
  const currentURL = window.location.href;
  document.querySelectorAll('.sidebar a').forEach(link => {
    if (link.href === currentURL) {
      link.classList.add('active');
    }
  });
});

// Gestion du menu déroulant
document.querySelectorAll('.dropdown-toggle').forEach(dropdownToggle => {
  dropdownToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    
    const dropdown = dropdownToggle.parentElement;
    const dropdownContent = dropdown.querySelector('.dropdown-content');
    const arrowIcon = dropdownToggle.querySelector('.arrow i');
    
    const isOpen = dropdownContent.style.display === 'block';
    dropdownContent.style.display = isOpen ? 'none' : 'block';
    arrowIcon.classList.toggle('rotate');
  });
});
