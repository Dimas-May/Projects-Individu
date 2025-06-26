<?php
session_start(); //
include "koneksi.php"; //

if (!isset($_SESSION['username'])) { //
    header("Location: login.php"); //
    exit(); //
}

$role = $_SESSION['role'] ?? 'guest';

$semester_filter = isset($_GET['semester']) ? mysqli_real_escape_string($conn, $_GET['semester']) : ''; //
$tahun_filter = isset($_GET['tahun']) ? mysqli_real_escape_string($conn, $_GET['tahun']) : ''; //
$hari_filter = isset($_GET['hari']) ? mysqli_real_escape_string($conn, $_GET['hari']) : ''; //
$jurusan_filter = ''; // Akan diisi otomatis jika role mahasiswa, atau dari GET jika admin

$where_clauses = []; //

if ($role === 'mahasiswa') {
    $jurusan_filter = $_SESSION['jurusan_mahasiswa'];
    $where_clauses[] = "c.jurusan = '$jurusan_filter'"; // Filter utama berdasarkan jurusan mahasiswa
} elseif ($role === 'admin') {
    $jurusan_filter = isset($_GET['jurusan']) ? mysqli_real_escape_string($conn, $_GET['jurusan']) : '';
    if (!empty($jurusan_filter)) {
        $where_clauses[] = "c.jurusan = '$jurusan_filter'";
    }
}

if (!empty($semester_filter)) { //
    $where_clauses[] = "s.semester = '$semester_filter'"; //
}
if (!empty($tahun_filter)) { //
    $where_clauses[] = "s.tahun_akademik = '$tahun_filter'"; //
}
if (!empty($hari_filter)) { //
    $where_clauses[] = "s.hari = '$hari_filter'"; //
}

$where_sql = ''; //
if (!empty($where_clauses)) { //
    $where_sql = "WHERE " . implode(" AND ", $where_clauses); //
}

$order_by_sql = "ORDER BY FIELD(s.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), s.jam_mulai"; //

$query_schedules = "SELECT s.*, c.kode_mk, c.nama_mk, c.sks, c.jurusan, t.kode_dosen, t.nama_dosen, t.bidang, r.kode_ruangan, r.nama_ruangan
          FROM schedules s
          JOIN courses c ON s.course_id = c.id
          JOIN teachers t ON s.teacher_id = t.id
          JOIN rooms r ON s.room_id = r.id
          $where_sql
          $order_by_sql"; //
$schedules_result = mysqli_query($conn, $query_schedules); //

// Ambil daftar semester dan tahun akademik unik untuk filter
$semesters_years_query = mysqli_query($conn, "SELECT DISTINCT semester, tahun_akademik FROM schedules ORDER BY tahun_akademik DESC, semester DESC"); //
$unique_semesters_years = []; //
while($row = mysqli_fetch_assoc($semesters_years_query)) { //
    $unique_semesters_years[] = $row; //
}

// Ambil daftar jurusan unik untuk filter (hanya jika role admin)
$unique_jurusan_options = [];
if ($role === 'admin') {
    $jurusan_query = mysqli_query($conn, "SELECT DISTINCT jurusan FROM courses WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan ASC");
    while($row = mysqli_fetch_assoc($jurusan_query)) {
        $unique_jurusan_options[] = $row['jurusan'];
    }
}

$page_title_suffix = "";
if ($role === 'mahasiswa') {
    $page_title_suffix = "Saya (" . htmlspecialchars($_SESSION['jurusan_mahasiswa']) . ")";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Jadwal Perkuliahan <?= $page_title_suffix ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="crud-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <img src="sc/Logo Universitas XYZ.png" alt="Logo Universitas XYZ" width="35" height="35" class="d-inline-block align-text-top me-2">
                Sistem Informasi Pengelolaan Jadwal Kuliah
            </a>
            <span class="navbar-text ms-auto me-3 text-white">
                <?= ($role === 'admin') ? 'Admin Area' : 'Selamat Datang, ' . htmlspecialchars($_SESSION['nama_mahasiswa']) ?>
            </span>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="page-header-crud">
        <h2><i class="fas fa-eye header-icon"></i> Lihat Jadwal Perkuliahan <?= $page_title_suffix ?></h2>
    </div>

    <div class="container">
        <form method="GET" class="filter-form mb-4 p-4 border rounded shadow-sm">
            <?php if ($role === 'admin'): ?>
            <div class="col-md-auto mb-2">
                <label for="jurusan_filter" class="form-label me-2">Jurusan:</label>
                <select class="form-select" id="jurusan_filter" name="jurusan">
                    <option value="">-- Semua Jurusan --</option>
                    <?php foreach ($unique_jurusan_options as $jurusan_name): ?>
                        <option value="<?= htmlspecialchars($jurusan_name) ?>" <?= ($jurusan_filter == $jurusan_name) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jurusan_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-auto mb-2">
                <label for="semester_filter" class="form-label me-2">Semester:</label>
                <select class="form-select" id="semester_filter" name="semester">
                    <option value="">-- Semua Semester --</option>
                    <option value="Ganjil" <?= ($semester_filter == 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
                    <option value="Genap" <?= ($semester_filter == 'Genap') ? 'selected' : '' ?>>Genap</option>
                </select>
            </div>

            <div class="col-md-auto mb-2">
                <label for="tahun_filter" class="form-label me-2">Tahun Akademik:</label>
                <select class="form-select" id="tahun_filter" name="tahun">
                    <option value="">-- Semua Tahun --</option>
                    <?php
                    $displayed_years = [];
                    foreach ($unique_semesters_years as $sy_row) {
                        if (!in_array($sy_row['tahun_akademik'], $displayed_years)) {
                            echo "<option value='{$sy_row['tahun_akademik']}' " . ($tahun_filter == $sy_row['tahun_akademik'] ? 'selected' : '') . ">{$sy_row['tahun_akademik']}</option>";
                            $displayed_years[] = $sy_row['tahun_akademik'];
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-auto mb-2">
                <label for="hari_filter" class="form-label me-2">Hari:</label>
                <select class="form-select" id="hari_filter" name="hari">
                    <option value="">-- Semua Hari --</option>
                    <option value="Senin" <?= ($hari_filter == 'Senin') ? 'selected' : '' ?>>Senin</option>
                    <option value="Selasa" <?= ($hari_filter == 'Selasa') ? 'selected' : '' ?>>Selasa</option>
                    <option value="Rabu" <?= ($hari_filter == 'Rabu') ? 'selected' : '' ?>>Rabu</option>
                    <option value="Kamis" <?= ($hari_filter == 'Kamis') ? 'selected' : '' ?>>Kamis</option>
                    <option value="Jumat" <?= ($hari_filter == 'Jumat') ? 'selected' : '' ?>>Jumat</option>
                    <option value="Sabtu" <?= ($hari_filter == 'Sabtu') ? 'selected' : '' ?>>Sabtu</option>
                    <option value="Minggu" <?= ($hari_filter == 'Minggu') ? 'selected' : '' ?>>Minggu</option>
                </select>
            </div>

            <div class="col-md-auto mb-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="view_schedule.php<?= ($role === 'mahasiswa') ? '?jurusan=' . urlencode($jurusan_filter) : '' ?>" class="btn btn-secondary">Reset Filter</a>
            </div>
        </form>

        <?php if (mysqli_num_rows($schedules_result) > 0): ?>
            <div class="table-responsive">
                <table class="schedule-table table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Mata Kuliah (SKS)</th>
                            <th>Dosen Pengampu</th>
                            <th>Ruangan</th>
                            <th>Semester</th>
                            <th>Tahun Akademik</th>
                            <?php if ($role === 'admin'): ?>
                                <th>Jurusan MK</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = mysqli_fetch_assoc($schedules_result)) {
                        ?>
                        <tr>
                            <td><?= $row['hari'] ?></td>
                            <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($row['kode_mk']) ?> - <?= htmlspecialchars($row['nama_mk']) ?> (<?= $row['sks'] ?> SKS)</td>
                            <td><?= htmlspecialchars($row['nama_dosen']) ?> (<?= htmlspecialchars($row['bidang']) ?>)</td>
                            <td><?= htmlspecialchars($row['kode_ruangan']) ?> - <?= htmlspecialchars($row['nama_ruangan']) ?></td>
                            <td><?= $row['semester'] ?></td>
                            <td><?= $row['tahun_akademik'] ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td><?= htmlspecialchars($row['jurusan']) ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center alert alert-info">Tidak ada jadwal ditemukan untuk filter yang dipilih.</p>
        <?php endif; ?>

        <br>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>