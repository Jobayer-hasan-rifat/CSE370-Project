<?php
$servername = "localhost"; 
$username = "root";        
$password = "";            
$dbname = "cse370-project";  
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$query = "SELECT * FROM teams"; 
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Teams</title>
    <link rel="stylesheet" href="Teams_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        header {
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 36px;
            color: #53A9A8;
            text-align: center;
            margin-bottom: 20px;
        }
        .team-dropdown {
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            text-align: center;
        }
        .team-list {
            margin: 20px auto;
            width: 80%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .team-item {
            margin-bottom: 15px;
            padding: 10px;
            border-bottom: 1px solid #ccc;
        }
        .team-item:last-child {
            border-bottom: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ccc;
        }
        th {
            background-color: #53A9A8;
            color: white;
        }
        .tournament-btn {
            display: inline-block;
            background-color: rgba(53, 147, 152, 0.5);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            margin: 20px auto;
            text-align: center;
        }
        .tournament-btn:hover {
            background-color: rgba(53, 147, 152, 0.8);
        }
    </style>
</head>
<body>
    <header>
        <h1>Team Information</h1>
    </header>

    <h2>Registered Teams</h2>
    <div class="team-dropdown">
        <select id="team-select" onchange="showTeamInfo()">
            <option value="">Select a team</option>
            
            <?php while ($team = mysqli_fetch_assoc($result)): ?>
                <option value="<?php echo htmlspecialchars($team['team_name']); ?>"><?php echo htmlspecialchars($team['team_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div id="team-info" class="team-list" style="display: none;">
        <h3>Team Details</h3>
        <table>
            <tr>
                <th>Team Name</th>
                <th>Members</th>
                <th>Game</th>
                <th>Status</th>
                <th>Wins</th>
                <th>Losses</th>
            </tr>
            <tr id="team-details">
    
            </tr>
        </table>
    </div>

    <a href="tournament.php" class="tournament-btn">View Tournaments</a>

    <script>
        function showTeamInfo() {
            const teamSelect = document.getElementById('team-select');
            const teamInfo = document.getElementById('team-info');
            const teamDetails = document.getElementById('team-details');

            const selectedTeam = teamSelect.value;

            if (selectedTeam) {
  
                const mockData = {
                    "Team A": { members: "Player 1, Player 2", game: "EA FC24", status: "Active", wins: 5, losses: 2 },
                    "Team B": { members: "Player 3, Player 4", game: "Valorant", status: "Active", wins: 3, losses: 4 },
                };

                const team = mockData[selectedTeam];

                if (team) {
                    teamDetails.innerHTML = `
                        <td>${selectedTeam}</td>
                        <td>${team.members}</td>
                        <td>${team.game}</td>
                        <td>${team.status}</td>
                        <td>${team.wins}</td>
                        <td>${team.losses}</td>
                    `;
                    teamInfo.style.display = 'block';
                }
            } else {
                teamInfo.style.display = 'none';
            }
        }
    </script>
</body>
</html>
