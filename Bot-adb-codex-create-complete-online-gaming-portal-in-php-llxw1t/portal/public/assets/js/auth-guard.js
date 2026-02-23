async function fetchCurrentProfile() {
  try {
    const res = await fetch('/api/account/profile');
    const data = await res.json();
    return data?.data || null;
  } catch (_) {
    return null;
  }
}

function setAdminVisibility(profile) {
  const roleId = Number(profile?.role_id || 0);
  const isStaff = roleId === 3;
  document.querySelectorAll('.admin-only').forEach((el) => {
    el.style.display = isStaff ? '' : 'none';
  });
  return isStaff;
}

(async function initAuthGuard() {
  const profile = await fetchCurrentProfile();
  const isStaff = setAdminVisibility(profile);
  if (document.body.dataset.page === 'admin' && !isStaff) {
    const root = document.getElementById('admin-root');
    const denied = document.getElementById('admin-denied');
    if (root) root.style.display = 'none';
    if (denied) denied.style.display = 'block';
  }
})();
