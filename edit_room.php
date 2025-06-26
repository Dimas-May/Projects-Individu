<?php
session_start(); //
include "koneksi.php"; //

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') { // Memastikan hanya admin yang bisa mengakses
    header("Location: login.php"); //
    exit(); //
}

$message = '';
$error = '';

if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) { //
    header("Location: rooms.php"); //
    exit(); //
}

$id = (int)$_GET['id']; //

$room_query = "SELECT * FROM rooms WHERE id = $id"; //
$room_result = mysqli_query($conn, $room_query); //
if (mysqli_num_rows($room_result) != 1) { //
    header("Location: rooms.php"); //
    exit(); //
}
$room = mysqli_fetch_assoc($room_result); //

// Proses update data
if (isset($_POST['update'])) { //
    $kode_ruangan = mysqli_real_escape_string($conn, $_POST['kode_ruangan']); //
    $nama_ruangan = mysqli_real_escape_string($conn, $_POST['nama_ruangan']); //
    $kapasitas = (int)$_POST['kapasitas']; //
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']); //

    if (empty($kode_ruangan) || empty($nama_ruangan) || $kapasitas < 1) { //
        $error = "Semua field harus diisi dan kapasitas minimal 1."; //
    } else {
        $check_query = "SELECT id FROM rooms WHERE kode_ruangan = '$kode_ruangan' AND id != $id"; //
        $check_result = mysqli_query($conn, $check_query); //
        if (mysqli_num_rows($check_result) > 0) { //
            $error = "Kode Ruangan sudah terdaftar."; //
        } else {
            $update_query = "UPDATE rooms SET kode_ruangan = '$kode_ruangan', nama_ruangan = '$nama_ruangan', kapasitas = $kapasitas, jenis = '$jenis' WHERE id = $id"; //
            if (mysqli_query($conn, $update_query)) { //
                $message = "Ruangan berhasil diperbarui."; //
                header("Location: rooms.php?message=" . urlencode($message)); //
                exit(); //
            } else {
                $error = "Gagal memperbarui ruangan: " . mysqli_error($conn); //
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
    <title>Edit Ruangan</title>
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
        <h2><i class="fas fa-tools header-icon"></i> Edit Ruangan</h2>
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
                <label for="kode_ruangan" class="form-label">Kode Ruangan:</label>
                <input type="text" class="form-control" id="kode_ruangan" name="kode_ruangan" value="<?= htmlspecialchars($room['kode_ruangan']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="kapasitas" class="form-label">Kapasitas:</label>
                <input type="number" class="form-control" id="kapasitas" name="kapasitas" min="1" value="<?= $room['kapasitas'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="jenis" class="form-label">Jenis Ruangan:</label>
                <select class="form-select" id="jenis" name="jenis" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="Teori" <?= $room['jenis'] == 'Teori' ? 'selected' : '' ?>>Teori</option>
                    <option value="Lab" <?= $room['jenis'] == 'Lab' ? 'selected' : '' ?>>Laboratorium</option>
                    <option value="Khusus" <?= $room['jenis'] == 'Khusus' ? 'selected' : '' ?>>Khusus</option>
                </select>
            </div>
            <button type="submit" name="update" class="btn btn-primary">Update Ruangan</button>
            <a href="rooms.php" class="btn btn-secondary cancel-btn">Batal</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>