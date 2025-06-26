<?php
session_start(); //
include "koneksi.php"; // Menggunakan file koneksi database Anda

if (!isset($_SESSION['username'])) { //
    header("Location: login.php"); //
    exit(); //
}

$role = $_SESSION['role'] ?? 'guest'; // Ambil peran, default 'guest' jika tidak diset

// Logika untuk mengambil data statistik (hanya untuk admin)
$total_mk = 0;
$total_dosen = 0;
$total_ruangan = 0;
$total_jadwal = 0;
$total_slots = 0;

if ($role === 'admin') {
    try {
        $stmt_mk = mysqli_query($conn, "SELECT COUNT(*) FROM courses"); //
        $total_mk = mysqli_fetch_row($stmt_mk)[0]; //

        $stmt_dosen = mysqli_query($conn, "SELECT COUNT(*) FROM teachers"); //
        $total_dosen = mysqli_fetch_row($stmt_dosen)[0]; //

        $stmt_ruangan = mysqli_query($conn, "SELECT COUNT(*) FROM rooms"); //
        $total_ruangan = mysqli_fetch_row($stmt_ruangan)[0]; //

        $stmt_jadwal = mysqli_query($conn, "SELECT COUNT(*) FROM schedules"); //
        $total_jadwal = mysqli_fetch_row($stmt_jadwal)[0]; //

        $stmt_slots = mysqli_query($conn, "SELECT COUNT(*) FROM time_slots"); //
        $total_slots = mysqli_fetch_row($stmt_slots)[0]; //

    } catch(Exception $e) {
        // Bisa log error, atau tampilkan pesan jika diperlukan
        // echo '<div class="alert alert-danger" role="alert">Gagal mengambil data statistik: ' . $e->getMessage() . '</div>';
    }
}

// Untuk mahasiswa, ambil info nama dan jurusan
$nama_mahasiswa = '';
$jurusan_mahasiswa = '';
if ($role === 'mahasiswa') {
    $nama_mahasiswa = $_SESSION['nama_mahasiswa'];
    $jurusan_mahasiswa = $_SESSION['jurusan_mahasiswa'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?= ($role === 'admin') ? 'Admin' : 'Mahasiswa' ?> - Sistem Informasi Pengelolaan Jadwal Kuliah Universitas XYZ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <img src="sc/Logo Universitas XYZ.png" alt="Logo Universitas XYZ" width="35" height="35" class="d-inline-block align-text-top me-2">
                Sistem Informasi Pengelolaan Jadwal Kuliah
            </a>
            <span class="navbar-text ms-auto me-3 text-white">
                <?= ($role === 'admin') ? 'Dashboard Admin' : 'Selamat Datang, ' . htmlspecialchars($nama_mahasiswa) . ' (' . htmlspecialchars($jurusan_mahasiswa) . ')' ?>
            </span>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container py-5 dashboard-main-content">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <?php if ($role === 'admin'): ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 dashboard-grid-area">
                        <div class="col">
                            <a href="courses.php" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-book-open icon-v2"></i>
                                    <h5>Kelola Mata Kuliah</h5>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="teachers.php" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-user-graduate icon-v2"></i>
                                    <h5>Kelola Dosen</h5>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="rooms.php" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-building icon-v2"></i>
                                    <h5>Kelola Ruangan</h5>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="schedules.php" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-calendar-alt icon-v2"></i>
                                    <h5>Kelola Jadwal</h5>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a href="view_schedule.php" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-search icon-v2"></i>
                                    <h5>Lihat Jadwal Umum</h5>
                                </div>
                            </a>
                        </div>
                <?php elseif ($role === 'mahasiswa'): ?>
                    <h2 class="text-center mb-4">Akses Cepat</h2>
                    <div class="row row-cols-1 row-cols-sm-2 g-4 dashboard-grid-area">
                        <div class="col">
                            <a href="view_schedule.php?jurusan=<?= urlencode($jurusan_mahasiswa) ?>" class="text-decoration-none">
                                <div class="dashboard-card-v2">
                                    <i class="fas fa-calendar-check icon-v2"></i>
                                    <h5>Lihat Jadwal Kuliah Saya</h5>
                                </div>
                            </a>
                        </div>
                        </div>
                <?php else: ?>
                    <p class="text-center alert alert-danger">Akses ditolak. Silakan login.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>