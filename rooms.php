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
    $kode_ruangan = mysqli_real_escape_string($conn, $_POST['kode_ruangan']);
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']);
    $kapasitas = (int)$_POST['kapasitas'];
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);

    if (empty($kode_ruangan) || empty($nama_ruangan) || $kapasitas < 1) {
        $error = "Semua field harus diisi dan kapasitas minimal 1.";
    } else {
        $check_query = "SELECT id FROM rooms WHERE kode_ruangan = '$kode_ruangan'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kode Ruangan sudah terdaftar.";
        } else {
            $insert_query = "INSERT INTO rooms (kode_ruangan, nama_ruangan, kapasitas, jenis) VALUES ('$kode_ruangan', '$nama_ruangan', $kapasitas, '$jenis')";
            if (mysqli_query($conn, $insert_query)) {
                $message = "Ruangan berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan ruangan: " . mysqli_error($conn);
            }
        }
    }
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $check_schedule_query = "SELECT id FROM schedules WHERE room_id = $id";
        $check_schedule_result = mysqli_query($conn, $check_schedule_query);
        if (mysqli_num_rows($check_schedule_result) > 0) {
            $error = "Ruangan tidak dapat dihapus karena sudah digunakan dalam jadwal.";
        } else {
            $delete_query = "DELETE FROM rooms WHERE id = $id";
            if (mysqli_query($conn, $delete_query)) {
                $message = "Ruangan berhasil dihapus.";
            } else {
                $error = "Gagal menghapus ruangan: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil data untuk ditampilkan
$result = mysqli_query($conn, "SELECT * FROM rooms ORDER BY kode_ruangan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ruangan</title>
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
        <h2><i class="fas fa-building header-icon"></i> Kelola Ruangan</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4 p-4 border rounded shadow-sm">
            <h3 class="mb-3">Tambah Ruangan Baru</h3>
            <div class="mb-3">
                <label for="kode_ruangan" class="form-label">Kode Ruangan:</label>
                <input type="text" class="form-control" id="kode_ruangan" name="kode_ruangan" placeholder="Contoh: R101" required>
            </div>
            <div class="mb-3">
                <label for="nama_ruangan" class="form-label">Nama Ruangan:</label>
                <input type="text" class="form-control" id="nama_ruangan" name="nama_ruangan" placeholder="Contoh: Ruang Teori 101" required>
            </div>
            <div class="mb-3">
                <label for="kapasitas" class="form-label">Kapasitas:</label>
                <input type="number" class="form-control" id="kapasitas" name="kapasitas" placeholder="Contoh: 50" min="1" required>
            </div>
            <div class="mb-3">
                <label for="jenis" class="form-label">Jenis Ruangan:</label>
                <select class="form-select" id="jenis" name="jenis" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Teori">Teori</option>
                    <option value="Lab">Laboratorium</option>
                    <option value="Khusus">Khusus</option>
                </select>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah Ruangan</button>
        </form>

        <h3 class="mb-3">Daftar Ruangan</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Ruangan</th>
                        <th>Kapasitas</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['kode_ruangan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                            <td><?= $row['kapasitas'] ?></td>
                            <td><?= htmlspecialchars($row['jenis']) ?></td>
                            <td>
                                <a href="edit_room.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                                <a href="rooms.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini? Data yang sudah terpakai di jadwal tidak bisa dihapus.')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Tidak ada data ruangan.</td></tr>
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