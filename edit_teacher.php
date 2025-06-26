<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
    header("Location: teachers.php");
    exit();
}

$id = (int)$_GET['id'];

$teacher_query = "SELECT * FROM teachers WHERE id = $id";
$teacher_result = mysqli_query($conn, $teacher_query);
if (mysqli_num_rows($teacher_result) != 1) {
    header("Location: teachers.php");
    exit();
}
$teacher = mysqli_fetch_assoc($teacher_result);

// Proses update data
if (isset($_POST['update'])) {
    $kode_dosen = mysqli_real_escape_string($conn, $_POST['kode_dosen']);
    $nama_dosen = mysqli_real_escape_string($conn, $_POST['nama_dosen']);
    $bidang = mysqli_real_escape_string($conn, $_POST['bidang']);

    if (empty($kode_dosen) || empty($nama_dosen)) {
        $error = "Kode dan Nama Dosen harus diisi.";
    } else {
        $check_query = "SELECT id FROM teachers WHERE kode_dosen = '$kode_dosen' AND id != $id";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = "Kode Dosen sudah terdaftar.";
        } else {
            $update_query = "UPDATE teachers SET kode_dosen = '$kode_dosen', nama_dosen = '$nama_dosen', bidang = '$bidang' WHERE id = $id";
            if (mysqli_query($conn, $update_query)) {
                $message = "Dosen berhasil diperbarui.";
                header("Location: teachers.php?message=" . urlencode($message));
                exit();
            } else {
                $error = "Gagal memperbarui dosen: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="crud-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="dashboard.php">
                <img src="images/Logo Universitas XYZ.png" alt="Logo Universitas XYZ" width="35" height="35" class="d-inline-block align-text-top me-2">
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
        <h2><i class="fas fa-user-edit header-icon"></i> Edit Dosen</h2>
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
                <label for="kode_dosen" class="form-label">Kode Dosen:</label>
                <input type="text" class="form-control" id="kode_dosen" name="kode_dosen" value="<?= htmlspecialchars($teacher['kode_dosen']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="nama_dosen" class="form-label">Nama Dosen:</label>
                <input type="text" class="form-control" id="nama_dosen" name="nama_dosen" value="<?= htmlspecialchars($teacher['nama_dosen']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="bidang" class="form-label">Bidang Keahlian:</label>
                <input type="text" class="form-control" id="bidang" name="bidang" value="<?= htmlspecialchars($teacher['bidang']) ?>">
            </div>
            <button type="submit" name="update" class="btn btn-primary">Update Dosen</button>
            <a href="teachers.php" class="btn btn-secondary cancel-btn">Batal</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>