<?php
session_start(); // Memulai sesi PHP
include "koneksi.php"; // Mengimpor file koneksi database

// Cek apakah form login telah disubmit
if (isset($_POST['submit'])) {
    $username = $_POST['username']; //
    // Menggunakan MD5 untuk mengenkripsi password (PERHATIAN: MD5 tidak direkomendasikan untuk password sungguhan karena keamanannya rendah. Gunakan password_hash() untuk keamanan yang lebih baik.)
    $password = md5($_POST['password']); //

    // Coba login sebagai admin
    $query_admin = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result_admin = mysqli_query($conn, $query_admin); //

    if (mysqli_num_rows($result_admin) == 1) { //
        $_SESSION['username'] = $username; // Set sesi username
        $_SESSION['role'] = 'admin'; // Menetapkan peran sebagai 'admin'
        header("Location: dashboard.php"); // Redirect ke dashboard umum
        exit(); // Penting: Menghentikan eksekusi skrip setelah redirect
    } else {
        // Coba login sebagai mahasiswa
        $query_mahasiswa = "SELECT * FROM mahasiswa WHERE nim='$username' AND password='$password'";
        $result_mahasiswa = mysqli_query($conn, $query_mahasiswa);

        if (mysqli_num_rows($result_mahasiswa) == 1) {
            $mahasiswa_data = mysqli_fetch_assoc($result_mahasiswa);
            $_SESSION['username'] = $username; // NIM sebagai username sesi
            $_SESSION['nama_mahasiswa'] = $mahasiswa_data['nama'];
            $_SESSION['jurusan_mahasiswa'] = $mahasiswa_data['jurusan'];
            $_SESSION['role'] = 'mahasiswa'; // Menetapkan peran sebagai 'mahasiswa'
            header("Location: dashboard.php"); // Redirect ke dashboard umum
            exit();
        } else {
            $error = "Username/NIM atau Password salah!"; // Pesan error jika login gagal
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-left-panel">
        <div class="container login">
            <img src="sc/Logo Universitas XYZ.png" class="login-logo">
            <p class="university-tagline">XYZ Cendikia</p>

            <?php
            // Menampilkan pesan error jika ada
            if (isset($error)) {
                echo "<p class='error'>$error</p>";
            }
            ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username/NIM" required><br>
                <input type="password" name="password" placeholder="Password" required><br>
                <input type="submit" name="submit" value="Login">
            </form>
        </div>
    </div>

    <div class="login-right-panel">
        <img src="sc/Orang Salaman.png" class="right-panel-image">

        <div class="sliding-text-overlay">
            <div id="quote-text" class="sliding-text"></div>
        </div>
    </div>

    <script>
        // Array berisi kutipan-kutipan inspiratif
        const quotes = [
            "“Education is not the filling of a pail, but the lighting of a fire.”\n— William Butler Yeats",
            "“Great minds are shaped by great teachers.”",
            "“Learning is a journey that never ends.”",
            "“The future belongs to those who prepare for it today.”\n— Malcolm X",
            "“Empowering minds to shape the world.”",
            "“University is where curiosity meets opportunity.”",
            "“The best investment is in the tools of one’s own education.”\n— Benjamin Franklin"
        ];

        let currentQuoteIndex = 0;
        const quoteElement = document.getElementById('quote-text');

        // Fungsi untuk menampilkan kutipan berikutnya
        function showNextQuote() {
            quoteElement.style.opacity = 0; // Mulai dengan memudarkan teks saat ini

            setTimeout(() => {
                // Mengganti baris baru '\n' dengan <br> untuk HTML
                quoteElement.innerHTML = quotes[currentQuoteIndex].replace(/\n/g, '<br>');
                quoteElement.style.opacity = 1; // Munculkan teks baru

                currentQuoteIndex = (currentQuoteIndex + 1) % quotes.length; // Pindah ke kutipan berikutnya
            }, 1000); // Waktu yang sama dengan durasi transisi CSS
        }

        // Tampilkan kutipan pertama kali saat halaman dimuat
        document.addEventListener('DOMContentLoaded', showNextQuote);

        // Atur interval untuk mengubah kutipan setiap beberapa detik
        setInterval(showNextQuote, 7000); // Ganti kutipan setiap 7 detik
    </script>
</body>
</html>