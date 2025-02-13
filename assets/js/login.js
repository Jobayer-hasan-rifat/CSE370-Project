var x = document.getElementById("login");
var y = document.getElementById("Sign-Up");
var z = document.getElementById("btn");

function Login() {
    x.style.left = "50px";
    y.style.left = "450px";
    z.style.left = "0";
}

function SignUp() {
    x.style.left = "-400px";
    y.style.left = "50px";
    z.style.left = "110px";
}

function handleLogin() {
    // Your existing login logic
    Login();
    // After successful login:
    sessionStorage.setItem('loggedIn', 'true'); // Store login state
    // Redirect to home or dashboard
    window.location.href = 'home.php';
}
