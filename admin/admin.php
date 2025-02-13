<?php
session_start();
require_once '../db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_email']) || !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle game deletion
if (isset($_POST['delete_game_id'])) {
    $game_id = intval($_POST['delete_game_id']);
    
    // First check if game is used in any tournaments
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM tournaments WHERE game_type IN (SELECT game_name FROM games WHERE id = ?)");
    $check_stmt->bind_param("i", $game_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $game_error = "Cannot delete game as it is being used in tournaments.";
    } else {
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        if ($stmt->execute()) {
            $game_success = "Game deleted successfully!";
        } else {
            $game_error = "Error deleting game: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle tournament deletion
if (isset($_POST['delete_tournament_id'])) {
    $tournament_id = intval($_POST['delete_tournament_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete registrations first
        $stmt = $conn->prepare("DELETE FROM registrations WHERE tournament_id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        
        // Then delete tournament
        $stmt = $conn->prepare("DELETE FROM tournaments WHERE id = ?");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        
        $conn->commit();
        $tournament_success = "Tournament deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $tournament_error = "Error deleting tournament: " . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_game'])) {
        $game_name = $_POST['game_name'];
        $number_of_players = intval($_POST['number_of_players']);
        $description = $_POST['description'];
        
        // Handle picture upload
        $picture = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
            $upload_dir = '../uploads/games/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $picture = $upload_dir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        }
        
        $stmt = $conn->prepare("INSERT INTO games (game_name, number_of_players, picture, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $game_name, $number_of_players, $picture, $description);
        
        if ($stmt->execute()) {
            $success_message = "Game added successfully!";
        } else {
            $error_message = "Error adding game: " . $conn->error;
        }
        $stmt->close();
    }
    
    if (isset($_POST['add_tournament'])) {
        $tournament_name = $_POST['tournament_name'];
        $game_id = intval($_POST['game_id']);
        $entry_fee = floatval($_POST['entry_fee']);
        $prize_money = floatval($_POST['prize_money']);
        $slots = intval($_POST['slots']);
        
        // Handle picture upload
        $picture = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
            $upload_dir = '../uploads/tournaments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $picture = $upload_dir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        }
        
        // Get game_name for the selected game_id
        $stmt = $conn->prepare("SELECT game_name FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
        $game_type = $game['game_name'];
        $stmt->close();
        
        $stmt = $conn->prepare("INSERT INTO tournaments (tournament_name, game_type, entry_fee, prize_money, slots, picture, status, tournament_date, registration_deadline, min_teams, max_teams) 
                               VALUES (?, ?, ?, ?, ?, ?, 'upcoming', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 4, 16)");
        $stmt->bind_param("ssddss", $tournament_name, $game_type, $entry_fee, $prize_money, $slots, $picture);
        
        if ($stmt->execute()) {
            $success_message = "Tournament added successfully!";
        } else {
            $error_message = "Error adding tournament: " . $conn->error;
        }
        $stmt->close();
    }

    if (isset($_POST['generate_fixtures'])) {
        $tournament_id = intval($_POST['tournament_id']);
        
        // Check if tournament exists and slots are filled
        $stmt = $conn->prepare("SELECT t.*, COUNT(r.id) as registered_teams 
                              FROM tournaments t 
                              LEFT JOIN registrations r ON t.id = r.tournament_id 
                              WHERE t.id = ? 
                              GROUP BY t.id");
        $stmt->bind_param("i", $tournament_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tournament = $result->fetch_assoc();
        
        if ($tournament && $tournament['registered_teams'] == $tournament['slots']) {
            // Get all registered teams
            $teams_query = "SELECT r.id as registration_id, t.id as team_id, t.team_name 
                          FROM registrations r 
                          JOIN teams t ON r.team_id = t.id 
                          WHERE r.tournament_id = ?";
            $stmt = $conn->prepare($teams_query);
            $stmt->bind_param("i", $tournament_id);
            $stmt->execute();
            $teams_result = $stmt->get_result();
            $teams = [];
            while ($team = $teams_result->fetch_assoc()) {
                $teams[] = $team;
            }
            
            // Shuffle teams randomly
            shuffle($teams);
            
            // Generate fixtures
            $conn->begin_transaction();
            try {
                // Delete existing fixtures
                $conn->query("DELETE FROM fixtures WHERE tournament_id = $tournament_id");
                
                // Create new fixtures
                $round = "Round 1";
                for ($i = 0; $i < count($teams); $i += 2) {
                    if (isset($teams[$i]) && isset($teams[$i + 1])) {
                        $team1_id = $teams[$i]['team_id'];
                        $team2_id = $teams[$i + 1]['team_id'];
                        
                        $stmt = $conn->prepare("INSERT INTO fixtures (tournament_id, team1_id, team2_id, round) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiis", $tournament_id, $team1_id, $team2_id, $round);
                        $stmt->execute();
                    }
                }
                
                $conn->commit();
                $success_message = "Fixtures generated successfully!";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error generating fixtures: " . $e->getMessage();
            }
        } else {
            $error_message = "Cannot generate fixtures. Tournament slots are not filled completely.";
        }
    }

    if (isset($_POST['edit_game'])) {
        $game_id = intval($_POST['game_id']);
        $game_name = $_POST['game_name'];
        $number_of_players = intval($_POST['number_of_players']);
        $description = $_POST['description'];
        
        // Handle picture upload
        $picture = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
            $upload_dir = '../uploads/games/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $picture = $upload_dir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        } else {
            $picture = $_POST['old_picture'];
        }
        
        $stmt = $conn->prepare("UPDATE games SET game_name = ?, number_of_players = ?, picture = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sissi", $game_name, $number_of_players, $picture, $description, $game_id);
        
        if ($stmt->execute()) {
            $success_message = "Game updated successfully!";
        } else {
            $error_message = "Error updating game: " . $conn->error;
        }
        $stmt->close();
    }

    if (isset($_POST['edit_tournament'])) {
        $tournament_id = intval($_POST['tournament_id']);
        $tournament_name = $_POST['tournament_name'];
        $game_id = intval($_POST['game_id']);
        $entry_fee = floatval($_POST['entry_fee']);
        $prize_money = floatval($_POST['prize_money']);
        $slots = intval($_POST['slots']);
        
        // Handle picture upload
        $picture = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
            $upload_dir = '../uploads/tournaments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $picture = $upload_dir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
        } else {
            $picture = $_POST['old_picture'];
        }
        
        // Get game_name for the selected game_id
        $stmt = $conn->prepare("SELECT game_name FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $game = $result->fetch_assoc();
        $game_type = $game['game_name'];
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE tournaments SET tournament_name = ?, game_type = ?, entry_fee = ?, prize_money = ?, slots = ?, picture = ? WHERE id = ?");
        $stmt->bind_param("ssddsssi", $tournament_name, $game_type, $entry_fee, $prize_money, $slots, $picture, $tournament_id);
        
        if ($stmt->execute()) {
            $success_message = "Tournament updated successfully!";
        } else {
            $error_message = "Error updating tournament: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch games for tournament form
$games_query = "SELECT id, game_name FROM games";
$games_result = $conn->query($games_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CracCloud</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/CSE370-Project/assets/css/admin.css">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <div class="nav-top">
            <h2><i class="fas fa-gamepad"></i> Admin Dashboard</h2>
            <div class="nav-buttons">
                <div class="system-buttons">
                    <a href="/CSE370-Project/index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Home</a>
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
                <div class="main-buttons">
                    <button onclick="showSection('addGame')" class="btn btn-primary"><i class="fas fa-plus"></i> Add Game</button>
                    <button onclick="showSection('addTournament')" class="btn btn-primary"><i class="fas fa-trophy"></i> Add Tournament</button>
                    <button onclick="showSection('generateFixtures')" class="btn btn-primary"><i class="fas fa-sitemap"></i> Generate Fixtures</button>
                    <button onclick="showSection('viewGames')" class="btn btn-primary"><i class="fas fa-list"></i> View Games</button>
                    <button onclick="showSection('viewTournaments')" class="btn btn-primary"><i class="fas fa-list"></i> View Tournaments</button>
                    <a href="users.php" class="btn btn-primary"><i class="fas fa-users"></i> Manage Users</a>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Game Form -->
        <div id="addGameSection" class="form-container" style="display: none;">
            <h3><i class="fas fa-gamepad"></i> Add New Game</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Game Name</label>
                    <input type="text" name="game_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Number of Players</label>
                    <input type="number" name="number_of_players" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label>Picture</label>
                    <input type="file" name="picture" class="form-control">
                </div>
                <button type="submit" name="add_game" class="btn btn-primary">Add Game</button>
            </form>
        </div>

        <!-- Add Tournament Form -->
        <div id="addTournamentSection" class="form-container" style="display: none;">
            <h3><i class="fas fa-trophy"></i> Add Tournament</h3>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="tournament_name">Tournament Name</label>
                    <input type="text" class="form-control" id="tournament_name" name="tournament_name" required>
                </div>
                <div class="form-group">
                    <label for="game_id">Select Game</label>
                    <select class="form-control" id="game_id" name="game_id" required>
                        <?php
                        $games_result = $conn->query("SELECT * FROM games");
                        while($game = $games_result->fetch_assoc()) {
                            echo "<option value='" . $game['id'] . "'>" . htmlspecialchars($game['game_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="entry_fee">Entry Fee</label>
                    <input type="number" class="form-control" id="entry_fee" name="entry_fee" required>
                </div>
                <div class="form-group">
                    <label for="prize_money">Prize Money</label>
                    <input type="number" class="form-control" id="prize_money" name="prize_money" required>
                </div>
                <div class="form-group">
                    <label for="slots">Number of Slots</label>
                    <input type="number" class="form-control" id="slots" name="slots" required>
                </div>
                <button type="submit" name="add_tournament" class="btn btn-primary">Add Tournament</button>
            </form>
        </div>

        <!-- Generate Fixtures Section -->
        <div id="generateFixturesSection" class="form-container" style="display: none;">
            <h3><i class="fas fa-sitemap"></i> Generate Tournament Fixtures</h3>
            <form method="POST" class="tournament-form">
                <div class="form-group">
                    <label>Select Tournament</label>
                    <select name="tournament_id" class="form-control" required>
                        <?php
                        $tournaments_query = "SELECT t.id, t.tournament_name, t.slots, COUNT(r.id) as registered_teams 
                                           FROM tournaments t 
                                           LEFT JOIN registrations r ON t.id = r.tournament_id 
                                           WHERE t.status != 'completed'
                                           GROUP BY t.id";
                        $tournaments_result = $conn->query($tournaments_query);
                        while ($tournament = $tournaments_result->fetch_assoc()) {
                            $disabled = ($tournament['registered_teams'] != $tournament['slots']) ? 'disabled' : '';
                            echo "<option value='" . $tournament['id'] . "' " . $disabled . ">" 
                                . htmlspecialchars($tournament['tournament_name']) 
                                . " (" . $tournament['registered_teams'] . "/" . $tournament['slots'] . " teams)"
                                . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" name="generate_fixtures" class="btn btn-primary">Generate Fixtures</button>
            </form>
        </div>

        <!-- View Games Section -->
        <div id="viewGamesSection" class="form-container" style="display: none;">
            <h3><i class="fas fa-list"></i> Games List</h3>
            <div id="editGameForm" style="display: none;" class="edit-form-container">
                <h4>Edit Game</h4>
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <input type="hidden" name="game_id" id="edit_game_id">
                    <input type="hidden" name="old_picture" id="edit_game_old_picture">
                    <div class="form-group">
                        <label>Game Name</label>
                        <input type="text" name="game_name" id="edit_game_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Number of Players</label>
                        <input type="number" name="number_of_players" id="edit_number_of_players" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Picture (Optional)</label>
                        <input type="file" name="picture" class="form-control">
                        <small class="form-text text-muted">Leave empty to keep current picture</small>
                    </div>
                    <button type="submit" name="edit_game" class="btn btn-primary">Update Game</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditForm('Game')">Cancel</button>
                </form>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Game Name</th>
                        <th>Players</th>
                        <th>Description</th>
                        <th>Picture</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $games_result = $conn->query("SELECT * FROM games ORDER BY id DESC");
                    while($game = $games_result->fetch_assoc()):
                    ?>
                    <tr data-game-id="<?php echo $game['id']; ?>">
                        <td><?php echo $game['id']; ?></td>
                        <td id="game_name_<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['game_name']); ?></td>
                        <td id="players_<?php echo $game['id']; ?>"><?php echo $game['number_of_players']; ?></td>
                        <td id="description_<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['description']); ?></td>
                        <td>
                            <?php if($game['picture']): ?>
                                <img src="<?php echo $game['picture']; ?>" alt="Game Picture" style="max-width: 100px;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-action" onclick="showEditForm('Game', <?php echo $game['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Are you sure you want to delete this game?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- View Tournaments Section -->
        <div id="viewTournamentsSection" class="form-container" style="display: none;">
            <h3><i class="fas fa-list"></i> Tournaments List</h3>
            <div id="editTournamentForm" style="display: none;" class="edit-form-container">
                <h4>Edit Tournament</h4>
                <form method="POST" enctype="multipart/form-data" class="edit-form">
                    <input type="hidden" name="tournament_id" id="edit_tournament_id">
                    <input type="hidden" name="old_picture" id="edit_tournament_old_picture">
                    <div class="form-group">
                        <label>Tournament Name</label>
                        <input type="text" name="tournament_name" id="edit_tournament_name" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Game Type</label>
                        <select name="game_id" id="edit_game_id" class="form-control">
                            <?php 
                            $games = $conn->query("SELECT id, game_name FROM games");
                            while($game = $games->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $game['id']; ?>"><?php echo htmlspecialchars($game['game_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Entry Fee</label>
                        <input type="number" name="entry_fee" id="edit_entry_fee" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Prize Money</label>
                        <input type="number" name="prize_money" id="edit_prize_money" class="form-control" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Available Slots</label>
                        <input type="number" name="slots" id="edit_slots" class="form-control" min="2">
                    </div>
                    <div class="form-group">
                        <label>Picture (Optional)</label>
                        <input type="file" name="picture" class="form-control">
                        <small class="form-text text-muted">Leave empty to keep current picture</small>
                    </div>
                    <button type="submit" name="edit_tournament" class="btn btn-primary">Update Tournament</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditForm('Tournament')">Cancel</button>
                </form>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Game</th>
                        <th>Entry Fee</th>
                        <th>Prize Money</th>
                        <th>Date</th>
                        <th>Slots</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tournaments_result = $conn->query("SELECT * FROM tournaments ORDER BY id DESC");
                    while($tournament = $tournaments_result->fetch_assoc()):
                    ?>
                    <tr data-tournament-id="<?php echo $tournament['id']; ?>">
                        <td><?php echo $tournament['id']; ?></td>
                        <td id="tournament_name_<?php echo $tournament['id']; ?>"><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                        <td><?php echo htmlspecialchars($tournament['game_type']); ?></td>
                        <td id="entry_fee_<?php echo $tournament['id']; ?>"><?php echo $tournament['entry_fee']; ?></td>
                        <td id="prize_money_<?php echo $tournament['id']; ?>"><?php echo $tournament['prize_money']; ?></td>
                        <td><?php echo $tournament['tournament_date']; ?></td>
                        <td id="slots_<?php echo $tournament['id']; ?>"><?php echo $tournament['slots']; ?></td>
                        <td><?php echo $tournament['status']; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-action" onclick="showEditForm('Tournament', <?php echo $tournament['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_tournament_id" value="<?php echo $tournament['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-action" onclick="return confirm('Are you sure you want to delete this tournament?')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Auto-remove alerts after animation
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.remove();
                }, 3500); // Remove after 3.5 seconds (matches CSS animation)
            });
        });

        function showSection(section) {
            // Hide all sections
            document.querySelectorAll('.form-container').forEach(el => el.style.display = 'none');
            
            // Show selected section
            document.getElementById(section + 'Section').style.display = 'block';
            
            // Hide any open edit forms
            document.querySelectorAll('.edit-form-container').forEach(el => el.style.display = 'none');
        }

        function showEditForm(type, id) {
            const formId = `edit${type}Form`;
            const form = document.getElementById(formId);
            const row = document.querySelector(`tr[data-${type.toLowerCase()}-id="${id}"]`);
            
            // Hide any other open edit forms
            document.querySelectorAll('.edit-form-container').forEach(el => {
                if (el.id !== formId) el.style.display = 'none';
            });
            
            // Remove any existing edit form rows
            document.querySelectorAll('.edit-form-row').forEach(el => el.remove());
            
            if (form.style.display === 'none' || !form.style.display) {
                // Create a new row for the edit form
                const editRow = document.createElement('tr');
                editRow.className = 'edit-form-row';
                const editCell = document.createElement('td');
                editCell.colSpan = row.cells.length;
                editCell.appendChild(form.cloneNode(true));
                editRow.appendChild(editCell);
                
                // Insert the edit form after the clicked row
                row.parentNode.insertBefore(editRow, row.nextSibling);
                
                // Show the form
                editRow.querySelector('.edit-form-container').style.display = 'block';
                
                // Set form values
                if (type === 'Game') {
                    editRow.querySelector('#edit_game_id').value = id;
                    editRow.querySelector('#edit_game_name').value = document.getElementById(`game_name_${id}`).textContent;
                    editRow.querySelector('#edit_number_of_players').value = document.getElementById(`players_${id}`).textContent;
                    editRow.querySelector('#edit_description').value = document.getElementById(`description_${id}`).textContent;
                } else if (type === 'Tournament') {
                    editRow.querySelector('#edit_tournament_id').value = id;
                    editRow.querySelector('#edit_tournament_name').value = document.getElementById(`tournament_name_${id}`).textContent;
                    editRow.querySelector('#edit_entry_fee').value = document.getElementById(`entry_fee_${id}`).textContent;
                    editRow.querySelector('#edit_prize_money').value = document.getElementById(`prize_money_${id}`).textContent;
                    editRow.querySelector('#edit_slots').value = document.getElementById(`slots_${id}`).textContent;
                    const gameId = document.getElementById(`game_id_${id}`).value;
                    editRow.querySelector('#edit_game_id').value = gameId;
                }
            } else {
                // Hide the form by removing the edit form row
                document.querySelector('.edit-form-row')?.remove();
            }
        }

        function closeEditForm(type) {
            document.querySelector('.edit-form-row')?.remove();
        }

        function confirmDelete(type, id) {
            if (confirm(`Are you sure you want to delete this ${type.toLowerCase()}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_${type.toLowerCase()}_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
