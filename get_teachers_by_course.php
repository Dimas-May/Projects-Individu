<?php
include "koneksi.php";

header('Content-Type: application/json');

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

$teachers = [];

if ($course_id > 0) {
    $query = "SELECT t.id, t.kode_dosen, t.nama_dosen
              FROM teachers t
              JOIN course_teacher ct ON t.id = ct.teacher_id
              WHERE ct.course_id = $course_id
              ORDER BY t.kode_dosen";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $teachers[] = $row;
        }
    }
}

echo json_encode($teachers);
?>