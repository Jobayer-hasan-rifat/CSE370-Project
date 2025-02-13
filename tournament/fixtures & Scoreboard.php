<?php
session_start();
require_once '../db.php';

// Function to get tournament matches
function getTournamentMatches($conn, $tournament_id) {
    $query = "SELECT 
        t.tournament_name,
        tr1.team_name as team1_name,
        tr2.team_name as team2_name,
        m.match_date,
        m.match_time,
        m.team1_score,
        m.team2_score,
        m.match_status,
        m.winner_team
    FROM matches m
    JOIN tournaments t ON m.tournament_id = t.id
    JOIN team_registrations tr1 ON m.team1_id = tr1.id
    JOIN team_registrations tr2 ON m.team2_id = tr2.id
    WHERE m.tournament_id = ?
    ORDER BY m.match_date ASC, m.match_time ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get all tournaments
$tournaments_query = "SELECT id, tournament_name FROM tournaments ORDER BY id DESC";
$tournaments = $conn->query($tournaments_query);

// Get matches for selected tournament
$selected_tournament = isset($_GET['tournament_id']) ? $_GET['tournament_id'] : null;
$matches = $selected_tournament ? getTournamentMatches($conn, $selected_tournament) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures & Scoreboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: white;
            padding-top: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .tournament-select {
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
        }
        .match-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .match-card:hover {
            transform: translateY(-5px);
        }
        .match-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .status-upcoming {
            background-color: #007bff;
        }
        .status-live {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }
        .status-completed {
            background-color: #6c757d;
        }
        .team-score {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0 10px;
        }
        .winner {
            color: #ffd700;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .nav-buttons {
            margin-bottom: 20px;
        }
        .nav-buttons a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-buttons">
            <a href="../index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="tournaments.php" class="btn btn-secondary">
                <i class="fas fa-trophy"></i> Tournaments
            </a>
        </div>

        <h1 class="text-center mb-4">
            <i class="fas fa-calendar-alt"></i> Fixtures & Scoreboard
        </h1>

        <div class="tournament-select">
            <form method="GET" class="form-inline justify-content-center">
                <div class="form-group mx-sm-3 mb-2">
                    <select name="tournament_id" class="form-control" onchange="this.form.submit()">
                        <option value="">Select Tournament</option>
                        <?php while ($tournament = $tournaments->fetch_assoc()): ?>
                            <option value="<?php echo $tournament['id']; ?>" 
                                    <?php echo $selected_tournament == $tournament['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </div>

        <?php if ($matches && $matches->num_rows > 0): ?>
            <div class="matches">
                <?php while ($match = $matches->fetch_assoc()): ?>
                    <div class="match-card">
                        <div class="match-status <?php 
                            echo $match['match_status'] == 'upcoming' ? 'status-upcoming' : 
                                 ($match['match_status'] == 'live' ? 'status-live' : 'status-completed'); 
                        ?>">
                            <?php echo ucfirst($match['match_status']); ?>
                        </div>
                        
                        <div class="row align-items-center">
                            <div class="col-md-4 text-md-right">
                                <span class="<?php echo $match['winner_team'] == $match['team1_name'] ? 'winner' : ''; ?>">
                                    <?php echo htmlspecialchars($match['team1_name']); ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-center">
                                <span class="team-score">
                                    <?php 
                                        echo $match['match_status'] != 'upcoming' 
                                            ? $match['team1_score'] . ' - ' . $match['team2_score']
                                            : 'VS';
                                    ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-md-left">
                                <span class="<?php echo $match['winner_team'] == $match['team2_name'] ? 'winner' : ''; ?>">
                                    <?php echo htmlspecialchars($match['team2_name']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small>
                                <i class="far fa-calendar"></i> 
                                <?php echo date('F j, Y', strtotime($match['match_date'])); ?>
                                <i class="far fa-clock ml-3"></i> 
                                <?php echo date('g:i A', strtotime($match['match_time'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php elseif ($selected_tournament): ?>
            <div class="alert alert-info text-center">
                No matches found for this tournament.
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">
                Please select a tournament to view fixtures and scores.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
