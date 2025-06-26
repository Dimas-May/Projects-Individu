<?php
session_start(); //
include "koneksi.php"; //

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') { // Memastikan hanya admin yang bisa mengakses
    header("Location: login.php"); //
    exit(); //
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //
    $start_time = mysqli_real_escape_string($conn, trim($_POST['start_time'])); //
    $end_time = mysqli_real_escape_string($conn, trim($_POST['end_time'])); //

    if (empty($start_time) || empty($end_time)) { //
        $error = "Waktu mulai dan selesai wajib diisi."; //
    } elseif (strtotime($start_time) >= strtotime($end_time)) { //
        $error = "Waktu mulai harus lebih awal dari waktu selesai."; //
    } else {
        $check_query = "SELECT id FROM time_slots WHERE (start_time = '$start_time' AND end_time = '$end_time')"; //
        $check_result = mysqli_query($conn, $check_query); //
        if (mysqli_num_rows($check_result) > 0) { //
            $error = "Slot waktu ini sudah ada."; //
        } else {
            $insert_query = "INSERT INTO time_slots (start_time, end_time) VALUES ('$start_time', '$end_time')"; //
            if (mysqli_query($conn, $insert_query)) { //
                $message = "Berhasil menambahkan slot waktu."; //
            } else {
                $error = "Gagal menambahkan slot waktu: " . mysqli_error($conn); //
            }
        }
    }
}

if (isset($_GET['delete'])) { //
    $id = (int)$_GET['delete']; //
    if ($id > 0) { //
        $delete_query = "DELETE FROM time_slots WHERE id = $id"; //
        if (mysqli_query($conn, $delete_query)) { //
            $message = "Slot waktu berhasil dihapus."; //
        } else {
            $error = "Gagal menghapus slot waktu: " . mysqli_error($conn); //
        }
    }
}

$result = mysqli_query($conn, "SELECT * FROM time_slots ORDER BY start_time"); //
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Slot Waktu</title>
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
        <h2><i class="fas fa-clock header-icon"></i> Kelola Slot Waktu</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="post" class="form-inline mb-4 p-4 border rounded shadow-sm">
            <h3 class="mb-3 w-100 text-center">Tambah Slot Waktu Baru</h3>
            <div class="col-md-auto mb-3">
                <label for="start_time" class="form-label me-2">Mulai:</label>
                <input type="time" class="form-control" id="start_time" name="start_time" required />
            </div>
            <div class="col-md-auto mb-3">
                <label for="end_time" class="form-label me-2">Selesai:</label>
                <input type="time" class="form-control" id="end_time" name="end_time" required />
            </div>
            <div class="col-md-auto mb-3">
                <button type="submit" class="btn btn-primary">Tambah</button>
            </div>
        </form>

        <h3 class="mb-3">Daftar Slot Waktu</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0):
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)):
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= substr($row['start_time'], 0, 5) ?></td>
                        <td><?= substr($row['end_time'], 0, 5) ?></td>
                        <td>
                            <a href="time_slots.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus slot waktu ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php
                        endwhile;
                    else:
                    ?>
                    <tr><td colspan="4" class="text-center">Tidak ada data slot waktu.</td></tr>
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