<?php
session_start(); //
include "koneksi.php"; // Menggunakan file koneksi database Anda

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') { // Memastikan hanya admin yang bisa mengakses
    header("Location: login.php"); //
    exit(); //
}

$message = '';
$error = '';

if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) { //
    header("Location: courses.php"); //
    exit(); //
}

$id = (int)$_GET['id']; //

$course_query = "SELECT * FROM courses WHERE id = $id"; //
$course_result = mysqli_query($conn, $course_query); //
if (mysqli_num_rows($course_result) != 1) { //
    header("Location: courses.php"); //
    exit(); //
}
$course = mysqli_fetch_assoc($course_result); //

// Proses update data
if (isset($_POST['update'])) { //
    $kode_mk = mysqli_real_escape_string($conn, $_POST['kode_mk']); //
    $nama_mk = mysqli_real_escape_string($conn, $_POST['nama_mk']); //
    $sks = (int)$_POST['sks']; //
    $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']); // Ambil Jurusan

    if (empty($kode_mk) || empty($nama_mk) || $sks < 1 || empty($jurusan)) { //
        $error = "Semua field harus diisi dan SKS minimal 1."; //
    } else {
        $check_query = "SELECT id FROM courses WHERE kode_mk = '$kode_mk' AND id != $id"; //
        $check_result = mysqli_query($conn, $check_query); //
        if (mysqli_num_rows($check_result) > 0) { //
            $error = "Kode Mata Kuliah sudah terdaftar."; //
        } else {
            // Tambahkan kolom jurusan ke UPDATE
            $update_query = "UPDATE courses SET kode_mk = '$kode_mk', nama_mk = '$nama_mk', sks = $sks, jurusan = '$jurusan' WHERE id = $id"; //
            if (mysqli_query($conn, $update_query)) { //
                $message = "Mata kuliah berhasil diperbarui."; //
                header("Location: courses.php?message=" . urlencode($message)); //
                exit(); //
            } else {
                $error = "Gagal memperbarui mata kuliah: " . mysqli_error($conn); //
            }
        }
    }
}

// Ambil daftar jurusan unik untuk dropdown (jika sudah ada data)
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
    <title>Edit Mata Kuliah</title>
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
        <h2><i class="fas fa-edit header-icon"></i> Edit Mata Kuliah</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="p-4 border rounded shadow-sm">
            <div class="mb-3">
                <label for="kode_mk" class="form-label">Kode Mata Kuliah:</label>
                <input type="text" class="form-control" id="kode_mk" name="kode_mk" value="<?= htmlspecialchars($course['kode_mk']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="nama_mk" class="form-label">Nama Mata Kuliah:</label>
                <input type="text" class="form-control" id="nama_mk" name="nama_mk" value="<?= htmlspecialchars($course['nama_mk']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="sks" class="form-label">SKS:</label>
                <input type="number" class="form-control" id="sks" name="sks" min="1" value="<?= $course['sks'] ?>" required>
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
                        $selected = ($course['jurusan'] == $j) ? 'selected' : '';
                        echo "<option value=\"".htmlspecialchars($j)."\" {$selected}>".htmlspecialchars($j)."</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" name="update" class="btn btn-primary">Update Mata Kuliah</button>
            <a href="courses.php" class="btn btn-secondary cancel-btn">Batal</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>