<?php
session_start();
include 'dbconnect.php'; // Include fișierul pentru conectarea la baza de date

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register_new'])) {
        $username = $_POST['username_new'] ?? '';
        $password = $_POST['password_user_new'] ?? '';

        // Verificăm dacă credentialele introduse sunt cele ale administratorului
        if ($username === 'Andrei' && $password === '123123123') {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = true; // Setăm o variabilă pentru a identifica utilizatorul ca fiind admin

            // Redirecționăm către pagina calendarului
            header('Location: calendar.php');
            exit;
        } else {
            // Dacă nu este contul de admin, adăugăm utilizatorul în baza de date
            $conn = getDB();

            // Validăm dacă username-ul există deja în baza de date
            $sql_check = "SELECT * FROM users WHERE email = ?";
            $stmt_check = mysqli_prepare($conn, $sql_check);
            mysqli_stmt_bind_param($stmt_check, 's', $username);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);

            if (mysqli_num_rows($result_check) > 0) {
                echo 'Acest username este deja înregistrat!';
            } else {
                // Inserăm noul utilizator
                $sql_insert = "INSERT INTO users (email, password, is_admin) VALUES (?, ?, 0)";
                $stmt_insert = mysqli_prepare($conn, $sql_insert);
                mysqli_stmt_bind_param($stmt_insert, 'ss', $username, $password);

                if (mysqli_stmt_execute($stmt_insert)) {
                    echo 'Contul a fost creat cu succes!';

                    // Redirecționăm către pagina de logare
                    header('Location: login.php');
                    exit;
                } else {
                    echo 'Eroare la crearea contului: ' . mysqli_error($conn);
                }
            }
            mysqli_close($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <h2>Register here</h2>
</head>
<body>
<form action="register.php" method="POST">
    <fieldset>
        <div class="username">
            <label for="username_new">Username</label>
            <input type="text" id="username_new" name="username_new" minlength="3" maxlength="20" required>
        </div>

        <div class="password">
            <label for="password_new">Password</label>
            <input type="password" id="password_new" name="password_user_new" minlength="8" required>
        </div>

        <button type="submit" name="register_new">Submit</button>
    </fieldset>
</form>
<fieldset><a href="login.php">Log In</a></fieldset>
</body>
</html>
