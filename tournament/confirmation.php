<?php
session_start();
$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "cse370-project";    

$conn = mysqli_connect($servername, $username, $password, $dbname);


// avoid warning: Trying to access array offset on value of type null
if (isset($registration_info) && is_array($registration_info)) {
    $tournament_id = $registration_info['tournament_id'] ?? null;
    $team_name = $registration_info['team_name'] ?? null;
    $manager_name = $registration_info['manager_name'] ?? null;
} else {
    $tournament_id = null;
    $team_name = null;
    $manager_name = null;
}
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$registration_info = $_SESSION['registration_info'] ?? null;
$payment_info = $_SESSION['payment_info'] ?? null;

// if payment method is card or mobile, insert payment info into payments table
if ($payment_info['method'] === 'card' || $payment_info['method'] === 'mobile') {
    
    $sql = "INSERT INTO payments (tournament_id, team_name, manager_name, amount, payment_status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $payment_status = 'Paid'; 
    $amount = 100; 
    $stmt->bind_param("sssis", $registration_info['tournament_id'], $registration_info['team_name'], $registration_info['manager_name'], $amount, $payment_status);
    $stmt->execute();
    $stmt->close();
}

$payment_status = ($payment_info['method'] === 'cash') ? 'Due Payment' : 'Due';
$registration_sql = "INSERT INTO registrations (tournament_id, team_name, manager_name) VALUES (?, ?, ?)";
$registration_stmt = $conn->prepare($registration_sql);
$registration_stmt->bind_param("sss", $registration_info['tournament_id'], $registration_info['team_name'], $registration_info['manager_name']);
$registration_stmt->execute();
$registration_stmt->close();

mysqli_close($conn);

$amount = 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        .nav-links {
            text-align: center;
            margin-top: 20px;
        }
        .nav-links a {
            margin: 0 10px;
            text-decoration: none;
            color: #53A9A8;
        }
        .back-button {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #53A9A8;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }
        .back-button:hover {
            background-color: #12525c;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Registration Successful</h2>
    <p>Congratulations! Your team has been successfully registered.</p>
    <p><strong>Team Name:</strong> <?php echo htmlspecialchars($registration_info['team_name'] ?? 'N/A'); ?></p>
    <p><strong>Tournament ID:</strong> <?php echo htmlspecialchars($registration_info['tournament_id'] ?? 'N/A'); ?></p>
    <p><strong>Manager Name:</strong> <?php echo htmlspecialchars($registration_info['manager_name'] ?? 'N/A'); ?></p>
    <p><strong>Amount:</strong> $<?php echo htmlspecialchars($amount); ?></p>
    <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($payment_status); ?></p>
    
    <div class="nav-links">
        <a href="../tournament.php">Tournaments</a>
        <a href="../tournament/teams.php">Teams</a>
    </div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="../index.php" class="back-button">Go Back to Homepage</a>
    </div>
</div>

</body>
</html>
<?php

