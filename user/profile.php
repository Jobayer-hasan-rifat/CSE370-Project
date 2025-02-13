<?php
session_start();
require_once '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: LogIn.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// If user data not found, redirect to login
if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_picture'])) {
        // Delete the physical file if it exists
        if (!empty($user['picture']) && file_exists($user['picture'])) {
            unlink($user['picture']);
        }
        
        // Update database to remove picture reference
        $stmt = $conn->prepare("UPDATE users SET picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Profile picture deleted successfully!";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Error deleting profile picture: " . $conn->error;
        }
        $stmt->close();
    } else {
        $name = $_POST['name'];
        $number = $_POST['number'];
        $email = $_POST['email'];
        $team_name = $_POST['team_name'];
        
        // Handle picture upload
        $picture_sql = "";
        $types = "ssss";
        $params = array($name, $number, $email, $team_name);
        
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] === 0) {
            // Delete old picture if it exists
            if (!empty($user['picture']) && file_exists($user['picture'])) {
                unlink($user['picture']);
            }
            
            $upload_dir = '../uploads/users/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $picture = $upload_dir . basename($_FILES['picture']['name']);
            move_uploaded_file($_FILES['picture']['tmp_name'], $picture);
            $picture_sql = ", picture = ?";
            $types .= "s";
            $params[] = $picture;
        }
        
        // Add user_id to params
        $types .= "i";
        $params[] = $user_id;
        
        $stmt = $conn->prepare("UPDATE users SET name = ?, number = ?, email = ?, team_name = ?" . $picture_sql . " WHERE id = ?");
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-image: url('../assets/images/all_background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px;
        }
        .profile-container {
            background-color: rgba(42, 42, 42, 0.9);
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            color: white;
        }
        .form-control {
            background-color: rgba(51, 51, 51, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .form-control:focus {
            background-color: rgba(64, 64, 64, 0.9);
            color: white;
            border-color: #2193b0;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: #2193b0;
            border: none;
        }
        .btn-primary:hover {
            background-color: #1c7a8e;
        }
        .picture-controls {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="text-center mb-4">
                <?php if (isset($user['picture']) && $user['picture']): ?>
                    <img src="<?php echo htmlspecialchars($user['picture']); ?>" alt="Profile Picture" class="profile-picture">
                    <div class="picture-controls">
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="delete_picture" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete your profile picture?')">
                                <i class="fas fa-trash"></i> Delete Picture
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <img src="../assets/images/default-profile.jpg" alt="Default Profile Picture" class="profile-picture">
                <?php endif; ?>
                <h2>User Profile</h2>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="number" class="form-control" value="<?php echo isset($user['number']) ? htmlspecialchars($user['number']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Team Name (Optional)</label>
                    <input type="text" name="team_name" class="form-control" value="<?php echo isset($user['team_name']) ? htmlspecialchars($user['team_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Profile Picture (Optional)</label>
                    <input type="file" name="picture" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Profile</button>
            </form>
            <div class="text-center mt-3">
                <a href="../index.php" class="btn btn-secondary mr-2">Back to Home</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
