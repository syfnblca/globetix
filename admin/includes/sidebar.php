<!-- ===== Sidebar ===== -->
<style>
/* --- Sidebar Container --- */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 240px;
    background: #1e272e;
    color: white;
    overflow-y: auto;
    transition: width 0.3s ease;
    z-index: 1000;
}

/* --- Sidebar collapsed --- */
.sidebar.collapsed {
    width: 70px;
}

/* --- Sidebar Header --- */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #141a1f;
    font-weight: bold;
    font-size: 18px;
}

.sidebar-header span {
    white-space: nowrap;
}

/* --- Toggle button --- */
.toggle-btn {
    background: none;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
}

/* --- Sidebar menu --- */
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar ul li {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    color: white;
    text-decoration: none;
    padding: 14px 20px;
    transition: background 0.2s;
}

.sidebar ul li a:hover {
    background: #2f3640;
}

/* --- Ikon menu (gunakan emoji atau icon library) --- */
.sidebar ul li a i {
    width: 25px;
    text-align: center;
    margin-right: 10px;
}

/* --- Saat collapsed --- */
.sidebar.collapsed .sidebar-header span,
.sidebar.collapsed ul li a span {
    display: none;
}

.sidebar.collapsed ul li a i {
    margin-right: 0;
    font-size: 20px;
}

/* --- Konten utama bergeser --- */
.main-content {
    margin-left: 240px;
    transition: margin-left 0.3s ease;
    padding: 20px;
}
.sidebar.collapsed ~ .main-content {
    margin-left: 70px;
}
</style>

<script>
document.getElementById("toggle-btn").addEventListener("click", function() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("collapsed");
});
</script>


<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="logo">
        <button id="toggleSidebar" class="toggle-btn">☰</button><br>
        <img src="assets/logo.png" alt="Logo"><br>
        Globetix Admin
    </div>
    <ul>
        <li><a href="index_admin.php">Dashboard</a></li>
        <li><a href="bus.php">Kelola Jadwal</a></li> 
        <li><a href="pemesanan.php">Pemesanan</a></li>
        <li><a href="refund.php">Refund</a></li>
        <li><a href="logout.php" class="logout">Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
