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
    $kode_mk = mysqli_real_escape_string($conn, $_POST['kode_mk']);
    $nama_mk = mysqli_real_escape_string($conn, $_POST['nama_mk']);
    $sks = (int)$_POST['sks'];
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);

    if (empty($kode_mk) || empty($nama_mk) || $sks < 1 || empty($jurusan)) {
        $error = "Semua field harus diisi dan SKS minimal 1.";
    } else {
        $check_query = "SELECT id FROM courses WHERE kode_mk = '$kode_mk'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kode Mata Kuliah sudah terdaftar.";
        } else {
            $insert_query = "INSERT INTO courses (kode_mk, nama_mk, sks, jurusan) VALUES ('$kode_mk', '$nama_mk', $sks, '$jurusan')";
            if (mysqli_query($conn, $insert_query)) {
                $message = "Mata kuliah berhasil ditambahkan.";
            } else {
                $error = "Gagal menambahkan mata kuliah: " . mysqli_error($conn);
            }
        }
    }
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $check_schedule_query = "SELECT id FROM schedules WHERE course_id = $id";
        $check_schedule_result = mysqli_query($conn, $check_schedule_query);

        $check_course_teacher_query = "SELECT course_id FROM course_teacher WHERE course_id = $id";
        $check_course_teacher_result = mysqli_query($conn, $check_course_teacher_query);

        if (mysqli_num_rows($check_schedule_result) > 0) {
            $error = "Mata kuliah tidak dapat dihapus karena sudah digunakan dalam jadwal.";
        } elseif (mysqli_num_rows($check_course_teacher_result) > 0) {
            $error = "Mata kuliah tidak dapat dihapus karena sudah terdaftar memiliki pengajar. Hapus hubungan pengajar terlebih dahulu.";
        } else {
            $delete_query = "DELETE FROM courses WHERE id = $id";
            if (mysqli_query($conn, $delete_query)) {
                $message = "Mata kuliah berhasil dihapus.";
            } else {
                $error = "Gagal menghapus mata kuliah: " . mysqli_error($conn);
            }
        }
    }
}

// Ambil data untuk ditampilkan, termasuk dosen pengajar
$result = mysqli_query($conn, "SELECT c.*,
    GROUP_CONCAT(DISTINCT t.nama_dosen ORDER BY t.nama_dosen SEPARATOR ', ') AS pengajar_dosen
    FROM courses c
    LEFT JOIN course_teacher ct ON c.id = ct.course_id
    LEFT JOIN teachers t ON ct.teacher_id = t.id
    GROUP BY c.id
    ORDER BY c.kode_mk");

// Ambil daftar jurusan unik untuk dropdown
$jurusan_options = mysqli_query($conn, "SELECT DISTINCT jurusan FROM courses WHERE jurusan IS NOT NULL AND jurusan != '' ORDER BY jurusan ASC");
$existing_jurusan = [];
while ($row = mysqli_fetch_assoc($jurusan_options)) {
    $existing_jurusan[] = $row['jurusan'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah</title>
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
        <h2><i class="fas fa-book-open header-icon"></i> Kelola Mata Kuliah</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4 p-4 border rounded shadow-sm">
            <h3 class="mb-3">Tambah Mata Kuliah Baru</h3>
            <div class="mb-3">
                <label for="kode_mk" class="form-label">Kode Mata Kuliah:</label>
                <input type="text" class="form-control" id="kode_mk" name="kode_mk" placeholder="Contoh: MK001" required>
            </div>
            <div class="mb-3">
                <label for="nama_mk" class="form-label">Nama Mata Kuliah:</label>
                <input type="text" class="form-control" id="nama_mk" name="nama_mk" placeholder="Contoh: Pemrograman Web Lanjut" required>
            </div>
            <div class="mb-3">
                <label for="sks" class="form-label">SKS:</label>
                <input type="number" class="form-control" id="sks" name="sks" placeholder="Contoh: 3" min="1" required>
            </div>
            <div class="mb-3">
                <label for="jurusan" class="form-label">Jurusan:</label>
                <select class="form-select" id="jurusan" name="jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <?php
                    $all_possible_jurusan = ['Sistem Informasi', 'Bahasa Inggris', 'Teknik Informatika', 'Akuntansi', 'Manajemen', 'Hukum', 'Kedokteran'];
                    $display_jurusan = array_unique(array_merge($all_possible_jurusan, $existing_jurusan));
                    sort($display_jurusan);

                    foreach ($display_jurusan as $j) {
                        echo "<option value=\"".htmlspecialchars($j)."\">".htmlspecialchars($j)."</option>";
                    }
                    ?>
                </select>
                <small class="form-text text-muted">Pilih jurusan mata kuliah.</small>
            </div>
            <button type="submit" name="tambah" class="btn btn-primary">Tambah Mata Kuliah</button>
        </form>

        <h3 class="mb-3">Daftar Mata Kuliah</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Kode MK</th>
                        <th>Nama Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Jurusan</th>
                        <th>Pengajar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['kode_mk']) ?></td>
                            <td><?= htmlspecialchars($row['nama_mk']) ?></td>
                            <td><?= $row['sks'] ?></td>
                            <td><?= htmlspecialchars($row['jurusan']) ?></td>
                            <td><?= htmlspecialchars($row['pengajar_dosen'] ?: 'Belum Ada') ?></td>
                            <td>
                                <a href="edit_course.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                                <a href="courses.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini? Data yang sudah terpakai di jadwal atau memiliki pengajar tidak bisa dihapus.')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center">Tidak ada data mata kuliah.</td></tr>
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