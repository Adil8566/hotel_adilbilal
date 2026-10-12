
document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.getElementById("sidebarToggle");
  const sidebar = document.getElementById("hotelSidebar");
  const overlay = document.getElementById("sidebarOverlay");
  function closeSidebar(){
    if(sidebar) sidebar.classList.remove("show");
    if(overlay) overlay.classList.remove("show");
  }
  if(toggle) toggle.addEventListener("click", function(){
    sidebar.classList.toggle("show");
    overlay.classList.toggle("show");
  });
  if(overlay) overlay.addEventListener("click", closeSidebar);
  document.querySelectorAll("#hotelSidebar a").forEach(a => {
    a.addEventListener("click", function(){ if(window.innerWidth < 992) closeSidebar(); });
  });
});
