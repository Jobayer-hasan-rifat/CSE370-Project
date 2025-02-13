<?php
session_start();
require_once '../db.php';

// Fetch all upcoming tournaments
$query = "SELECT t.*, g.game_name 
          FROM tournaments t 
          LEFT JOIN games g ON t.game_type = g.game_name 
          WHERE t.status = 'upcoming' 
          ORDER BY t.tournament_date ASC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournaments</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('../assets/images/all_background.jpg');
            background-size: cover;
            background-attachment: fixed;
            color: white;
        }

        .home-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: rgb(3, 118, 133);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }

        .home-button:hover {
            background-color: rgb(2, 98, 113);
            color: white;
        }

        .container {
            margin-top: 60px;
            padding: 20px;
            background-color: rgba(1, 1, 1, 0.7);
            border-radius: 10px;
            color: rgb(3, 118, 133);
            shadow: rgb(103, 218, 133);
        }

        .tournament-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .tournament-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .tournament-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }
        .tournament-details {
            padding: 15px;
        }
        .register-btn {
            width: 100%;
            border-radius: 0 0 8px 8px;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .card {
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            width: 18rem;
            color: white;
        }

        .card-title, .card-text {
            color: white;
        }

        .btn-primary {
            background-color: rgb(3, 118, 133);
            border: none;
        }

        .btn-primary:hover {
            background-color: rgb(2, 98, 113);
        }

        .btn-rules {
            background-color: rgb(53, 168, 183);
            border: none;
            margin-left: 10px;
            color: white;
        }

        .btn-rules:hover {
            background-color: rgb(33, 148, 163);
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5em;
        }

       
        .features-card {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            width: 18rem;
            margin-top: 20px;
        }

        .features-card h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .register-button {
            background-color: rgb(53, 168, 183);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
        }

        .register-button:hover {
            background-color: rgb(33, 148, 163);
        }
    </style>
</head>
<body>

<a href="../index.php" class="home-button">Home</a>

<div class="container">
    <h1><b>Available Tournaments</b></h1>

    <div class="card-container">
        <?php while($tournament = $result->fetch_assoc()): ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h5>
                    <p class="card-text">Entry Fee: <?php echo htmlspecialchars($tournament['entry_fee']); ?> TK</p>
                    <p class="card-text">Game: <?php echo htmlspecialchars($tournament['game_type']); ?></p>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="registration.php?tournament_id=<?php echo $tournament['id']; ?>" 
                           class="btn btn-primary">Register Now</a>
                    <?php else: ?>
                        <a href="../user/LogIn.php?redirect=tournament_registration&tournament_id=<?php echo $tournament['id']; ?>" 
                           class="btn btn-primary">Login to Register</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
       
        <div class="features-card">
            <h2>Fixtures & Scoreboard</h2>
            <p>Check out the latest fixtures and live scoreboard updates for ongoing tournaments!</p>
            <a href="fixtures & Scoreboard.php" class="register-button">View Now</a>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php

mysqli_close($conn);
?>
