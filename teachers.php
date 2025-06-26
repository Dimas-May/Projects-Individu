<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Tambah data
if (isset($_POST['tambah'])) {
    $kode_dosen = mysqli_real_escape_string($conn, $_POST['kode_dosen']);
    $nama_dosen = mysqli_real_escape_string($conn, $_POST['nama_dosen']);
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);

    if (empty($kode_dosen) || empty($nama_dosen)) {
        $error = "Kode dan Nama Dosen harus diisi.";
    } else {
        $check_query = "SELECT id FROM teachers WHERE kode_dosen = '$kode_dosen'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kode Dosen sudah terdaftar.";
        } else {
            $insert_query = "INSERT INTO teachers (kode_dosen, nama_dosen, bidang) VALUES ('$kode_dosen', '$nama_dosen', '$bidang')";
            if (mysqli_query($conn, $insert_query)) {
                $message = "Dosen berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan dosen: " . mysqli_error($conn);
            }
        }
    }
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $check_schedule_query = "SELECT id FROM schedules WHERE teacher_id = $id";
        $check_schedule_result = mysqli_query($conn, $check_schedule_query);

        $check_course_teacher_query = "SELECT teacher_id FROM course_teacher WHERE teacher_id = $id";
        $check_course_teacher_result = mysqli_query($conn, $check_course_teacher_query);

        if (mysqli_num_rows($check_schedule_result) > 0) {
            $error = "Dosen tidak dapat dihapus karena sudah mengajar dalam jadwal.";
        } elseif (mysqli_num_rows($check_course_teacher_result) > 0) {
            $error = "Dosen tidak dapat dihapus karena sudah terdaftar sebagai pengajar mata kuliah tertentu. Hapus keterkaitan di `course_teacher` dulu.";
        } else {
            $delete_query = "DELETE FROM teachers WHERE id = $id";
            if (mysqli_query($conn, $delete_query)) {
                $message = "Dosen berhasil dihapus.";
            } else {
                $error = "Gagal menghapus dosen: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil data untuk ditampilkan, termasuk mata kuliah yang diajarkan
$result = mysqli_query($conn, "SELECT t.*,
    GROUP_CONCAT(DISTINCT c.nama_mk ORDER BY c.nama_mk SEPARATOR ', ') AS mengajar_mk
    FROM teachers t
    LEFT JOIN course_teacher ct ON t.id = ct.teacher_id
    LEFT JOIN courses c ON ct.course_id = c.id
    GROUP BY t.id
    ORDER BY t.kode_dosen");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen</title>
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
                Admin Area
            </span>
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-light btn-sm" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="page-header-crud">
        <h2><i class="fas fa-user-graduate header-icon"></i> Kelola Dosen</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4 p-4 border rounded shadow-sm">
            <h3 class="mb-3">Tambah Dosen Baru</h3>
            <div class="mb-3">
                <label for="kode_dosen" class="form-label">Kode Dosen:</label>
                <input type="text" class="form-control" id="kode_dosen" name="kode_dosen" placeholder="Contoh: D001" required>
            </div>
            <div class="mb-3">
                <label for="nama_dosen" class="form-label">Nama Dosen:</label>
                <input type="text" class="form-control" id="nama_dosen" name="nama_dosen" placeholder="Contoh: Dr. Budi Santoso" required>
            </div>
            <div class="mb-3">
                <label for="bidang" class="form-label">Bidang Keahlian:</label>
                <input type="text" class="form-control" id="bidang" name="bidang" placeholder="Contoh: Rekayasa Perangkat Lunak">
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah Dosen</button>
        </form>

        <h3 class="mb-3">Daftar Dosen</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode Dosen</th>
                        <th>Nama Dosen</th>
                        <th>Bidang</th>
                        <th>Mengajar MK</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['kode_dosen']) ?></td>
                            <td><?= htmlspecialchars($row['nama_dosen']) ?></td>
                            <td><?= htmlspecialchars($row['bidang']) ?></td>
                            <td><?= htmlspecialchars($row['mengajar_mk'] ?: 'Belum Ada') ?></td>
                            <td>
                                <a href="edit_teacher.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                                <a href="teachers.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini? Data yang sudah terpakai di jadwal atau terdaftar sebagai pengajar mata kuliah tidak bisa dihapus.')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Tidak ada data dosen.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <br>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>