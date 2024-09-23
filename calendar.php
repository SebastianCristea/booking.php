<?php
// Pornim sesiunea
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}

include 'dbconnect.php'; // Include fișierul pentru conectarea la baza de date

// Gestionăm logout-ul
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: register.php');
    exit;
}

// Obținem datele utilizatorului
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false;

// Funcție pentru ștergerea rezervării
function deleteReservation($reservation_id, $user_id, $is_admin) {
    $conn = getDB();

    if ($is_admin) {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reservation_id, $user_id);
    }

    $success = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $success;
}

// Ștergere rezervare
if (isset($_POST['delete_reservation'])) {
    $reservation_id = $_POST['reservation_id'];

    if (!(deleteReservation($reservation_id, $user_id, $is_admin))) {
        // echo "<p>Rezervarea a fost ștearsă cu succes.</p>";
        //} else {
        echo "<p>Eroare la ștergerea rezervării. Vă rugăm să încercați din nou.</p>";
    }
}

// Funcție pentru adăugarea unei rezervări
function addReservation($user_id, $selectedDate, $slot_time, $username) {
    $conn = getDB();

    // Verificăm dacă utilizatorul are deja o rezervare în acea zi
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $stmt->close();
        $conn->close();
        return "Ai deja o rezervare în această zi. Nu poți face o altă rezervare.";
    }

    // Verificăm dacă intervalul de timp este deja ocupat
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE date = ? AND slot_time = ?");
    $stmt->bind_param("ss", $selectedDate, $slot_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $stmt->close();
        $conn->close();
        return "Intervalul orar selectat nu mai este disponibil. Vă rugăm să alegeți un alt interval.";
    }

    // Verificăm dacă toate intervalele sunt ocupate în acea zi
    $stmt = $conn->prepare("SELECT COUNT(*) as total_reservations FROM bookings WHERE date = ?");
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total_reservations'] >= 8) {
        $stmt->close();
        $conn->close();
        return "Nu mai sunt locuri libere, selectează o altă zi.";
    }

    // Inserăm rezervarea
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, date, slot_time, name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $selectedDate, $slot_time, $username);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return "Rezervarea pentru data $selectedDate la ora $slot_time a fost înregistrată cu succes.";
    } else {
        $stmt->close();
        $conn->close();
        return "Eroare la înregistrarea rezervării. Vă rugăm să încercați din nou.";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar 2024-2025</title>
    <style>
        .container {

            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px 20px;
            max-width: 860px;
        }

        .square {
            width: 100%;
            height: 180px;
            background-color: lightblue;
            border: 2px solid #000;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        table {
            width: 90%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid black;
            padding: 2px;
            text-align: center;
            min-width: 21px;  /* Dimensiune minimă pentru celulele tabelului */
            min-height: 21px; /* Dimensiune minimă pentru celulele tabelului */
        }

        h2 {
            font-size: 15px;
            margin: 0;
            text-align: center;
        }

        button {
            width: 100%;
            padding: 0px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            cursor: pointer;
        }

        .year-select {
            display: flex;
            flex-direction: column;
            padding-top: 10px;
            margin-bottom: 20px;
            max-width: 100px;
            margin-left: 50px;
        }

        .submit {
            width: 100%;
            padding: 5px; /* Spațiu interior pentru buton */
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            cursor: pointer; /* Cursor pointer la trecerea mouse-ului */
        }

        .all_containers {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
        }

        .rezervari {
            max-width: 900px;
        }

        .logout_rezerva {
            width: 90%;
            height: 30px;
        }
    </style>
</head>
<body>

<?php if (!$is_admin): ?>
<h2>Vă rugăm să selectați data când doriți să faceți rezervarea</h2>

<div class="year-select">
    <form id="yearForm" method="POST">

        <label for="year">Selectați anul: </label>
        <select name="year" id="year">
            <option value="2024" <?php if (isset($_POST['year']) && $_POST['year'] == '2024') echo 'selected'; ?>>2024</option>
            <option value="2025" <?php if (isset($_POST['year']) && $_POST['year'] == '2025') echo 'selected'; ?>>2025</option>
        </select>

        <button class="submit" type="submit">Selectează</button>
    </form>
</div>
<div class="all_containers">
    <div class="container">
        <?php
        $selectedYear = isset($_POST['year']) ? $_POST['year'] : 2024;

        function generateCalendar($year) {
            $months = [
                1 => 'Ianuarie', 2 => 'Februarie', 3 => 'Martie', 4 => 'Aprilie',
                5 => 'Mai', 6 => 'Iunie', 7 => 'Iulie', 8 => 'August',
                9 => 'Septembrie', 10 => 'Octombrie', 11 => 'Noiembrie', 12 => 'Decembrie'
            ];

            $daysInMonth = [
                1 => 31, 2 => ($year % 4 == 0 && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28,
                3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31,
                8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31
            ];

            foreach ($months as $monthNumber => $monthName) {
                echo "<div class='square'>";
                echo "<h2>$monthName $year</h2>";
                echo "<table>";
                echo "<tr><th>Lu</th><th>Ma</th><th>Mi</th><th>Jo</th><th>Vi</th><th>Sâ</th><th>Du</th></tr>";

                $firstDayOfMonth = date('N', strtotime("$year-$monthNumber-01"));
                echo "<tr>";
                for ($i = 1; $i < $firstDayOfMonth; $i++) {
                    echo "<td></td>";
                }

                for ($day = 1; $day <= $daysInMonth[$monthNumber]; $day++) {
                    $dateValue = sprintf('%04d-%02d-%02d', $year, $monthNumber, $day);
                    echo "<td><form method='POST'>
                        <button type='submit' name='selected_date' value='$dateValue'>$day</button>
                        <input type='hidden' name='year' value='$year'>
                      </form></td>";

                    if ((($day + $firstDayOfMonth - 1) % 7 == 0) && ($day != $daysInMonth[$monthNumber])) {
                        echo "</tr><tr>";
                    }
                }

                $remainingCells = 7 - (($day + $firstDayOfMonth - 2) % 7) ;
                if ($remainingCells < 7) {
                    for ($i = 0; $i < $remainingCells; $i++) {
                        echo "<td></td>";
                    }
                }

                echo "</tr>";
                echo "</table>";
                echo "</div>";
            }
        }

        generateCalendar($selectedYear);
        ?>

    </div>
    <div class="rezervari">
        <?php
        // Afișăm lista de intervale disponibile dacă a fost selectată o dată
        if (isset($_POST['selected_date'])) {
            $selectedDate = $_POST['selected_date'];
            $conn = getDB();

            $stmt = $conn->prepare("SELECT * FROM bookings WHERE user_id = ? AND date = ?");
            $stmt->bind_param("is", $user_id, $selectedDate);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<p>Ați făcut deja o rezervare pentru această zi. Vă rugăm să alegeți o altă zi.</p>";
                } else {
                $stmt = $conn->prepare("SELECT slot_time FROM bookings WHERE date = ?");
                $stmt->bind_param("s", $selectedDate);
                $stmt->execute();
                $result = $stmt->get_result();

                $occupied_slots = [];
                while ($row = $result->fetch_assoc()) {
                    $occupied_slots[] = $row['slot_time'];
                }

                $all_slots = [
                    '09:00:00', '10:00:00', '11:00:00', '12:00:00',
                    '13:00:00', '14:00:00', '15:00:00', '16:00:00'
                ];
                $available_slots = array_diff($all_slots, $occupied_slots);

                if (empty($available_slots)) {
                    echo "<p>Nu mai sunt intervale orare disponibile în această zi. Selectează o altă zi.</p>";
                } else {
                    echo "Data selectată: $selectedDate";
                    echo '<form method="POST">';
                    echo '<input type="hidden" name="selected_date" value="' . htmlspecialchars($selectedDate) . '">';
                    echo '<label for="slot_time">Selectați intervalul orar:</label>';
                    echo '<select name="slot_time" id="slot_time" required>';
                    foreach ($available_slots as $slot) {
                        echo "<option value='$slot'>$slot</option>";
                    }
                    echo '</select><br><br>';
                    echo '<button type="submit" name="make_reservation" class="logout_rezerva">Rezervă</button>';
                    echo '</form>';
                }
            }

            $conn->close();
        }


        ?>

        <?php endif;
        // Adăugare rezervare nouă
        if (isset($_POST['make_reservation'])) {
            $selectedDate = $_POST['selected_date'];
            $slot_time = $_POST['slot_time'];
            $message = addReservation($user_id, $selectedDate, $slot_time, $username);
            echo "<p>$message</p>";
        }

        ?>

        <!-- Afișăm rezervările curente -->
        <h2>Rezervările <?php echo $is_admin ? 'tuturor utilizatorilor' : 'mele'; ?></h2>
        <table>
            <tr>
                <th>Data</th>
                <th>Ora</th>
                <?php if ($is_admin) echo '<th>Client</th>'; ?>
                <th>Acțiuni</th>
            </tr>
            <?php
            $conn = getDB();

            if ($is_admin) {
                $stmt = $conn->prepare("SELECT bookings.id, bookings.date, bookings.slot_time, users.email AS client_name FROM bookings JOIN users ON bookings.user_id = users.id");
            } else {
                $stmt = $conn->prepare("SELECT id, date, slot_time FROM bookings WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['date']) . "</td>
                        <td>" . htmlspecialchars($row['slot_time']) . "</td>";
                if ($is_admin) {
                    echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                }
                echo "<td>
                        <form method='POST'>
                            <input type='hidden' name='reservation_id' value='" . $row['id'] . "'>
                            <button type='submit' name='delete_reservation'>Anulează</button>
                        </form>
                      </td>
                      </tr>";
            }

            $conn->close();
            ?>
        </table>
        <form action="" method="post">
            <button type="submit" name="logout" class="logout_rezerva">Log Out</button>
        </form>

    </div>
<h2>asdasd</h2>
</div>
</body>
</html>