<?php
session_start();
include 'dbconnect.php'; // Include fișierul pentru conectarea la baza de date

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit'])) {
        $username_set = $_POST['username_set'] ?? '';
        $password_set = $_POST['password_user_set'] ?? '';

        // Verificăm dacă datele introduse corespund contului de administrator
        if ($username_set === 'Andrei' && $password_set === '123123123') {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username_set;
            $_SESSION['is_admin'] = true;
            $_SESSION['user_id'] = 0; // Setăm un ID arbitrar pentru admin

            // Redirecționăm către pagina calendarului
            header('Location: calendar.php');
            exit;
        } else {
            // Verificăm dacă datele introduse există în baza de date
            $conn = getDB();
            $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $username_set, $password_set);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            // Verificăm parola pentru utilizatorii obișnuiți (fără criptare)
            if ($user) {
                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username_set;
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['user_id'] = $user['id']; // Stocăm `user_id` în sesiune pentru rezervări

                // Redirecționăm către pagina calendarului
                header('Location: calendar.php');
                exit;
            } else {
                echo 'Username sau parola incorectă!';
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
    <title>Login</title>
</head>
<body>
<h2>Login here</h2>
<form method="POST">
    <fieldset>
        <div class="username">
            <label for="username">Username</label>
            <input type="text" id="username" name="username_set" minlength="3" maxlength="20" required>
        </div>

        <div class="password">
            <label for="password">Password</label>
            <input type="password" id="password" name="password_user_set" minlength="8" required>
        </div>

        <button type="submit" name="submit">Submit</button>
    </fieldset>
</form>
<fieldset><a href="register.php">Register page</a></fieldset>
</body>
</html>
