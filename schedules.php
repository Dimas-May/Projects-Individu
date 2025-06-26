<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Ambil data untuk dropdown (perlu di-fetch lagi jika ada operasi tambah/hapus)
$courses_dropdown = mysqli_query($conn, "SELECT id, kode_mk, nama_mk, jurusan FROM courses ORDER BY kode_mk");
$rooms_dropdown = mysqli_query($conn, "SELECT id, kode_ruangan, nama_ruangan FROM rooms ORDER BY kode_ruangan");

// Proses tambah jadwal
if (isset($_POST['tambah'])) {
    $course_id = (int)$_POST['course_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $room_id = (int)$_POST['room_id'];
    $hari = mysqli_real_escape_string($conn, $_POST['hari']);
    $jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun_akademik = mysqli_real_escape_string($conn, $_POST['tahun_akademik']);

    // Validasi input dasar
    if (empty($course_id) || empty($teacher_id) || empty($room_id) || empty($hari) || empty($jam_mulai) || empty($jam_selesai) || empty($semester) || empty($tahun_akademik)) {
        $error = "Semua field wajib diisi.";
    } elseif (strtotime($jam_selesai) <= strtotime($jam_mulai)) {
        $error = "Jam selesai harus setelah jam mulai.";
    } else {
        // --- VALIDASI TAMBAHAN: Cek apakah dosen yang dipilih memang mengajar mata kuliah tersebut ---
        $check_course_teacher_query = "SELECT COUNT(*) FROM course_teacher WHERE course_id = $course_id AND teacher_id = $teacher_id";
        $check_course_teacher_result = mysqli_query($conn, $check_course_teacher_query);
        $is_valid_combination = mysqli_fetch_row($check_course_teacher_result)[0];

        if ($is_valid_combination == 0) {
            $error = "Dosen yang dipilih tidak diizinkan atau tidak mengajar mata kuliah ini. Silakan pilih kombinasi yang sesuai.";
        } else {
            // Cek bentrok ruangan
            $check_room_query = "SELECT id FROM schedules WHERE room_id = $room_id AND hari = '$hari' AND (('$jam_mulai' BETWEEN jam_mulai AND jam_selesai) OR ('$jam_selesai' BETWEEN jam_mulai AND jam_selesai) OR (jam_mulai BETWEEN '$jam_mulai' AND '$jam_selesai'))";
            $check_room_result = mysqli_query($conn, $check_room_query);

            // Cek bentrok dosen
            $check_teacher_query = "SELECT id FROM schedules WHERE teacher_id = $teacher_id AND hari = '$hari' AND (('$jam_mulai' BETWEEN jam_mulai AND jam_selesai) OR ('$jam_selesai' BETWEEN jam_mulai AND jam_selesai) OR (jam_mulai BETWEEN '$jam_mulai' AND '$jam_selesai'))";
            $check_teacher_result = mysqli_query($conn, $check_teacher_query);

            if (mysqli_num_rows($check_room_result) > 0) {
                $error = "Ruangan sudah digunakan pada jam tersebut.";
            } elseif (mysqli_num_rows($check_teacher_result) > 0) {
                $error = "Dosen sudah mengajar pada jam tersebut.";
            } else {
                $insert_query = "INSERT INTO schedules (course_id, teacher_id, room_id, hari, jam_mulai, jam_selesai, semester, tahun_akademik) VALUES ($course_id, $teacher_id, $room_id, '$hari', '$jam_mulai', '$jam_selesai', '$semester', '$tahun_akademik')";

                if (mysqli_query($conn, $insert_query)) {
                    $message = "Jadwal berhasil ditambahkan.";
                } else {
                    $error = "Gagal menambahkan jadwal: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Hapus jadwal
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $delete_query = "DELETE FROM schedules WHERE id = $id";
        if (mysqli_query($conn, $delete_query)) {
            $message = "Jadwal berhasil dihapus.";
        } else {
            $error = "Gagal menghapus jadwal: " . mysqli_error($conn);
        }
    }
}

// Ambil semua jadwal dengan join tabel untuk tampilan
$query_schedules = "SELECT s.*, c.kode_mk, c.nama_mk, c.jurusan, t.kode_dosen, t.nama_dosen, r.kode_ruangan, r.nama_ruangan
          FROM schedules s
          JOIN courses c ON s.course_id = c.id
          JOIN teachers t ON s.teacher_id = t.id
          JOIN rooms r ON s.room_id = r.id
          ORDER BY FIELD(s.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), s.jam_mulai";
$schedules = mysqli_query($conn, $query_schedules);

// Re-fetch courses_dropdown untuk memastikan data terbaru di form tambah
mysqli_data_seek($courses_dropdown, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal</title>
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
        <h2><i class="fas fa-calendar-alt header-icon"></i> Kelola Jadwal</h2>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4 p-4 border rounded shadow-sm">
            <h3 class="mb-3">Tambah Jadwal Kuliah Baru</h3>
            <div class="mb-3">
                <label for="course_id" class="form-label">Mata Kuliah:</label>
                <select class="form-select" id="course_id" name="course_id" required>
                    <option value="">-- Pilih Mata Kuliah --</option>
                    <?php while ($row = mysqli_fetch_assoc($courses_dropdown)): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['kode_mk']) ?> - <?= htmlspecialchars($row['nama_mk']) ?> (<?= htmlspecialchars($row['jurusan']) ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="teacher_id" class="form-label">Dosen Pengampu:</label>
                <select class="form-select" id="teacher_id" name="teacher_id" required>
                    <option value="">-- Pilih Dosen --</option>
                    </select>
            </div>

            <div class="mb-3">
                <label for="room_id" class="form-label">Ruangan:</label>
                <select class="form-select" id="room_id" name="room_id" required>
                    <option value="">-- Pilih Ruangan --</option>
                    <?php mysqli_data_seek($rooms_dropdown, 0); // Reset pointer ?>
                    <?php while ($row = mysqli_fetch_assoc($rooms_dropdown)): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['kode_ruangan']) ?> - <?= htmlspecialchars($row['nama_ruangan']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="hari" class="form-label">Hari:</label>
                    <select class="form-select" id="hari" name="hari" required>
                        <option value="">-- Pilih Hari --</option>
                        <option value="Senin">Senin</option>
                        <option value="Selasa">Selasa</option>
                        <option value="Rabu">Rabu</option>
                        <option value="Kamis">Kamis</option>
                        <option value="Jumat">Jumat</option>
                        <option value="Sabtu">Sabtu</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="jam_mulai" class="form-label">Jam Mulai:</label>
                    <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" required>
                </div>
                <div class="col-md-4">
                    <label for="jam_selesai" class="form-label">Jam Selesai:</label>
                    <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="semester" class="form-label">Semester:</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="">-- Pilih Semester --</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="tahun_akademik" class="form-label">Tahun Akademik:</label>
                    <input type="text" class="form-control" id="tahun_akademik" name="tahun_akademik" placeholder="Contoh: 2023/2024" required>
                </div>
            </div>

            <button type="submit" name="tambah" class="btn btn-primary">Tambah Jadwal</button>
        </form>

        <h3 class="mb-3">Daftar Jadwal Kuliah</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Mata Kuliah (Jurusan)</th>
                        <th>Dosen</th>
                        <th>Ruangan</th>
                        <th>Hari</th>
                        <th>Jam</th>
                        <th>Semester</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php if (mysqli_num_rows($schedules) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($schedules)): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['kode_mk']) ?> - <?= htmlspecialchars($row['nama_mk']) ?> (<?= htmlspecialchars($row['jurusan']) ?>)</td>
                            <td><?= htmlspecialchars($row['kode_dosen']) ?> - <?= htmlspecialchars($row['nama_dosen']) ?></td>
                            <td><?= htmlspecialchars($row['kode_ruangan']) ?> - <?= htmlspecialchars($row['nama_ruangan']) ?></td>
                            <td><?= $row['hari'] ?></td>
                            <td><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                            <td><?= $row['semester'] ?></td>
                            <td><?= $row['tahun_akademik'] ?></td>
                            <td>
                                <a href="edit_schedule.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                                <a href="schedules.php?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">Tidak ada jadwal ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <br>
        <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const courseSelect = document.getElementById('course_id');
            const teacherSelect = document.getElementById('teacher_id');

            function loadTeachersByCourse() {
                const selectedCourseId = courseSelect.value;
                teacherSelect.innerHTML = '<option value="">-- Pilih Dosen --</option>'; // Reset opsi dosen

                if (selectedCourseId) {
                    fetch('get_teachers_by_course.php?course_id=' + selectedCourseId)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.length > 0) {
                                data.forEach(teacher => {
                                    const option = document.createElement('option');
                                    option.value = teacher.id;
                                    option.textContent = teacher.kode_dosen + ' - ' + teacher.nama_dosen;
                                    teacherSelect.appendChild(option);
                                });
                            } else {
                                const option = document.createElement('option');
                                option.value = "";
                                option.textContent = "Tidak ada dosen yang mengajar mata kuliah ini.";
                                teacherSelect.appendChild(option);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching teachers:', error);
                            const option = document.createElement('option');
                            option.value = "";
                            option.textContent = "Gagal memuat dosen.";
                            teacherSelect.appendChild(option);
                        });
                }
            }

            courseSelect.addEventListener('change', loadTeachersByCourse);
            if (courseSelect.value) {
                loadTeachersByCourse();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>