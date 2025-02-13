<?php
session_start();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CracCloud</title>
    <link rel="stylesheet" href="/CSE370-Project/assets/css/index_style.css">
    <link rel="stylesheet" href="/CSE370-Project/assets/css/globe.css">
    <link rel="shortcut icon" type="x-icon" href="/CSE370-Project/assets/images/CracCloud.jpg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">
    <style>
        body {
            background-image: url('/CSE370-Project/assets/images/omen.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        nav {
            background-color: rgba(0, 0, 0, 0.8);
            padding: 15px 20px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .banner {
            position: relative;
            padding: 100px 50px;
            color: white;
            text-align: center;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .banner h2 {
            font-size: 3rem;
            margin-bottom: 20px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .cta-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-top: 20px;
        }

        .cta-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
        }
        
        #team-photos {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            margin: 40px 0;
            padding: 0 20px;
        }
        
        figure {
            text-align: center;
            margin: 0;
            width: 250px;
        }
        
        figure img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease;
        }
        
        figure img:hover {
            transform: scale(1.05);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        figcaption {
            color: white;
            padding: 10px;
        }
        
        .member-name {
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .social-links a {
            color: white;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .social-links a:hover {
            transform: scale(1.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            nav ul {
                justify-content: center;
            }
            
            figure {
                width: 200px;
            }
            
            figure img {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="/CSE370-Project/index.php" class="logo"><b>Crac <br>Cloud</b></a>
        <ul class="nav">
            <li><a href="/CSE370-Project/admin/admin_login.php">Admin</a></li>
            <li class="active"><a href="/CSE370-Project/index.php">Home</a></li>
            <li><a href="/CSE370-Project/Tournament/games.php">Games</a></li>
            <li><a href="/CSE370-Project/tournament/tournaments.php">Tournaments</a></li>
            <li><a href="/CSE370-Project/Tournament/teams.php">Teams</a></li>
            <li><a href="/CSE370-Project/news.php">News</a></li>
            <li><a href="#about-us">About Us</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li>
                    <div class='profile'>
                        <a href='/CSE370-Project/user/profile.php'><?php echo htmlspecialchars($_SESSION['user_name']); ?></a>
                        <a href='/CSE370-Project/user/logout.php'>Logout</a>
                    </div>
                </li>
            <?php elseif (isset($_SESSION['admin'])): ?>
                <li>
                    <div class='profile'>
                        <p>Welcome, Admin</p>
                        <a href='/CSE370-Project/admin/admin.php'>Dashboard</a>
                        <a href='/CSE370-Project/auth/logout.php'>Logout</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="/CSE370-Project/user/LogIn.php">Log In/Sign Up</a></li>
            <?php endif; ?>
        </ul>
        <div class="action"></div>
        <div class="toggleMenu"></div>
    </header>

    <div class="banner">
        <div class="content">
            <h2>Dive into our<br>realm of Gaming!</h2>
            <p class="tournament-text">Do you have what it takes to touch glory? Are you eager to prove yourself in the biggest of stages? Then look no further as you can put yourself to test against the top competitors right here right now! Click on the link below!</p>
            <a href="/CSE370-Project/tournament/tournaments.php" class="cta-button">Join Now</a>
        </div>
        <img src="/CSE370-Project/assets/images/spacewars.jpg" alt="Space Wars">
    </div>

    <section id="about-us">
        <h2>About Us</h2>
        <p>Cloud9 welcoming you guys for a lovable gaming experience. Come and join us to have a taste of your favourite game in the most intriguing way possible! Get to know who 'walks' this website.

Feel free to give any feedback or queries you have at <a href="craccloud@gmail.com"</a>.</p>
        
    <div id="team-photos">
        <figure>
            <img src="/CSE370-Project/assets/images/mahejabin.jpg" alt="Mahejabin Yesmin">
            <figcaption>
                <div class="member-name">Mahejabin Yesmin</div>
                <div class="social-links">
                    <a href="https://facebook.com/mahejabin" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="https://instagram.com/mahejabin" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </figcaption>
        </figure>
        <figure>
            <img src="/CSE370-Project/assets/images/sawpno.jpg" alt="Samuzzal Sawpno">
            <figcaption>
                <div class="member-name">Samuzzal Sawpno</div>
                <div class="social-links">
                    <a href="https://facebook.com/sawpno" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="https://instagram.com/sawpno" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </figcaption>
        </figure>
        <figure>
            <img src="/CSE370-Project/assets/images/jobayer.jpg" alt="MD Jobayer Hasan">
            <figcaption>
                <div class="member-name">MD Jobayer Hasan</div>
                <div class="social-links">
                    <a href="https://facebook.com/jobayer" target="_blank"><i class="fab fa-facebook"></i></a>
                    <a href="https://instagram.com/jobayer" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </figcaption>
        </figure>
    </div>
    </section>
    
    <div class="toggleMenu">
        <i class="fa fa-bars"></i>
    </div>
    <script>
        const toggleMenu = document.querySelector('.toggleMenu');
        const nav = document.querySelector('.nav');

        toggleMenu.addEventListener('click', () => {
            toggleMenu.classList.toggle('active');
            nav.classList.toggle('active');
        });
    </script>

    <script src="/CSE370-Project/assets/js/script.js"></script>
    <script src="/CSE370-Project/assets/js/in_script.js"></script>

    <footer>
        <p> 2024 CracCloud. All rights reserved.</p>
    </footer>
</body>
</html>
