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
    header("Location: schedules.php"); //
    exit(); //
}

$id = (int)$_GET['id']; //

$schedule_query = "SELECT s.*, c.kode_mk, c.nama_mk, t.kode_dosen, t.nama_dosen, r.kode_ruangan, r.nama_ruangan
     FROM schedules s
     JOIN courses c ON s.course_id = c.id
     JOIN teachers t ON s.teacher_id = t.id
     JOIN rooms r ON s.room_id = r.id
     WHERE s.id = $id"; //
$schedule_result = mysqli_query($conn, $schedule_query); //

if (mysqli_num_rows($schedule_result) != 1) { //
    header("Location: schedules.php"); //
    exit(); //
}
$schedule = mysqli_fetch_assoc($schedule_result); //

// Ambil data untuk dropdown
$courses_dropdown = mysqli_query($conn, "SELECT id, kode_mk, nama_mk FROM courses ORDER BY kode_mk"); //
$teachers_dropdown = mysqli_query($conn, "SELECT id, kode_dosen, nama_dosen FROM teachers ORDER BY kode_dosen"); //
$rooms_dropdown = mysqli_query($conn, "SELECT id, kode_ruangan, nama_ruangan FROM rooms ORDER BY kode_ruangan"); //

// Proses update data
if (isset($_POST['update'])) { //
    $course_id = (int)$_POST['course_id']; //
    $teacher_id = (int)$_POST['teacher_id']; //
    $room_id = (int)$_POST['room_id']; //
    $hari = mysqli_real_escape_string($conn, $_POST['hari']); //
    $jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']); //
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']); //
    $semester = mysqli_real_escape_string($conn, $_POST['semester']); //
    $tahun_akademik = mysqli_real_escape_string($conn, $_POST['tahun_akademik']); //

    // Validasi input dasar
    if (empty($course_id) || empty($teacher_id) || empty($room_id) || empty($hari) || empty($jam_mulai) || empty($jam_selesai) || empty($semester) || empty($tahun_akademik)) { //
        $error = "Semua field wajib diisi."; //
    } elseif (strtotime($jam_selesai) <= strtotime($jam_mulai)) { //
        $error = "Jam selesai harus setelah jam mulai."; //
    } else {
        // Cek bentrok ruangan (kecuali jadwal ini sendiri)
        $check_room_query = "SELECT id FROM schedules WHERE room_id = $room_id AND hari = '$hari' AND id != $id AND (('$jam_mulai' BETWEEN jam_mulai AND jam_selesai) OR ('$jam_selesai' BETWEEN jam_mulai AND jam_selesai) OR (jam_mulai BETWEEN '$jam_mulai' AND '$jam_selesai'))"; //
        $check_room_result = mysqli_query($conn, $check_room_query); //

        // Cek bentrok dosen (kecuali jadwal ini sendiri)
        $check_teacher_query = "SELECT id FROM schedules WHERE teacher_id = $teacher_id AND hari = '$hari' AND id != $id AND (('$jam_mulai' BETWEEN jam_mulai AND jam_selesai) OR ('$jam_selesai' BETWEEN jam_mulai AND jam_selesai) OR (jam_mulai BETWEEN '$jam_mulai' AND '$jam_selesai'))"; //
        $check_teacher_result = mysqli_query($conn, $check_teacher_query); //

        if (mysqli_num_rows($check_room_result) > 0) { //
            $error = "Ruangan sudah digunakan pada jam tersebut."; //
        } elseif (mysqli_num_rows($check_teacher_result) > 0) { //
            $error = "Dosen sudah mengajar pada jam tersebut."; //
        } else {
            $update_query = "UPDATE schedules SET
                     course_id = $course_id,
                     teacher_id = $teacher_id,
                     room_id = $room_id,
                     hari = '$hari',
                     jam_mulai = '$jam_mulai',
                     jam_selesai = '$jam_selesai',
                     semester = '$semester',
                     tahun_akademik = '$tahun_akademik'
                     WHERE id = $id"; //

            if (mysqli_query($conn, $update_query)) { //
                $message = "Jadwal berhasil diperbarui."; //
                header("Location: schedules.php?message=" . urlencode($message)); //
                exit(); //
            } else {
                $error = "Gagal memperbarui jadwal: " . mysqli_error($conn); //
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
    <title>Edit Jadwal</title>
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
        <h2><i class="fas fa-edit header-icon"></i> Edit Jadwal</h2>
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
                <label for="course_id" class="form-label">Mata Kuliah:</label>
                <select class="form-select" id="course_id" name="course_id" required>
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php mysqli_data_seek($courses_dropdown, 0); // Reset pointer ?>
                    <?php while ($row = mysqli_fetch_assoc($courses_dropdown)): ?>
                        <option value="<?= $row['id'] ?>" <?= $row['id'] == $schedule['course_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['kode_mk']) ?> - <?= htmlspecialchars($row['nama_mk']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="teacher_id" class="form-label">Dosen Pengampu:</label>
                <select class="form-select" id="teacher_id" name="teacher_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    <?php mysqli_data_seek($teachers_dropdown, 0); // Reset pointer ?>
                    <?php while ($row = mysqli_fetch_assoc($teachers_dropdown)): ?>
                        <option value="<?= $row['id'] ?>" <?= $row['id'] == $schedule['teacher_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['kode_dosen']) ?> - <?= htmlspecialchars($row['nama_dosen']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="room_id" class="form-label">Ruangan:</label>
                <select class="form-select" id="room_id" name="room_id" required>
                    <option value="">-- Pilih Ruangan --</option>
                    <?php mysqli_data_seek($rooms_dropdown, 0); // Reset pointer ?>
                    <?php while ($row = mysqli_fetch_assoc($rooms_dropdown)): ?>
                        <option value="<?= $row['id'] ?>" <?= $row['id'] == $schedule['room_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['kode_ruangan']) ?> - <?= htmlspecialchars($row['nama_ruangan']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="hari" class="form-label">Hari:</label>
                    <select class="form-select" id="hari" name="hari" required>
                        <option value="">-- Pilih Hari --</option>
                        <option value="Senin" <?= $schedule['hari'] == 'Senin' ? 'selected' : '' ?>>Senin</option>
                        <option value="Selasa" <?= $schedule['hari'] == 'Selasa' ? 'selected' : '' ?>>Selasa</option>
                        <option value="Rabu" <?= $schedule['hari'] == 'Rabu' ? 'selected' : '' ?>>Rabu</option>
                        <option value="Kamis" <?= $schedule['hari'] == 'Kamis' ? 'selected' : '' ?>>Kamis</option>
                        <option value="Jumat" <?= $schedule['hari'] == 'Jumat' ? 'selected' : '' ?>>Jumat</option>
                        <option value="Sabtu" <?= $schedule['hari'] == 'Sabtu' ? 'selected' : '' ?>>Sabtu</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="jam_mulai" class="form-label">Jam Mulai:</label>
                    <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" value="<?= substr($schedule['jam_mulai'], 0, 5) ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="jam_selesai" class="form-label">Jam Selesai:</label>
                    <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" value="<?= substr($schedule['jam_selesai'], 0, 5) ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="semester" class="form-label">Semester:</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="">-- Pilih Semester --</option>
                        <option value="Ganjil" <?= $schedule['semester'] == 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                        <option value="Genap" <?= $schedule['semester'] == 'Genap' ? 'selected' : '' ?>>Genap</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="tahun_akademik" class="form-label">Tahun Akademik:</label>
                    <input type="text" class="form-control" id="tahun_akademik" name="tahun_akademik" placeholder="e.g. 2023/2024" value="<?= htmlspecialchars($schedule['tahun_akademik']) ?>" required>
                </div>
            </div>

            <button type="submit" name="update" class="btn btn-primary">Update Jadwal</button>
            <a href="schedules.php" class="btn btn-secondary cancel-btn">Batal</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>