<header>
    <div class="logo">Online Voting System</div>
    <div class="menu-toggle" id="mobile-menu">&#9776;</div>
    <nav>
        <a href="firstpage.html">Home</a>
        <a href="login.php">Login</a>
        <a href="about.php">About Us</a>
        <a href="help.php">Help</a>
    </nav>
</header>

<style>
/* Header Styling */
header {
    width: 100%;
    background: rgba(11, 92, 183, 0.9);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    position: fixed;
    top: 0;
    z-index: 1000;
    flex-wrap: wrap;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.logo {
    font-size: 24px;
    font-weight: bold;
    color: rgb(24, 189, 9);
    background-color: white;
    padding: 10px 20px;
    border-radius: 200px;
}

nav {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

nav a {
    padding: 10px 20px;
    background: white;
    color: black;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.3s;
    text-align: center;
}

nav a:hover {
    background: #4f0cc3;
    color: white;
}

/* Hamburger menu for mobile */
.menu-toggle {
    display: none;
    font-size: 28px;
    cursor: pointer;
}

/* Push page content below fixed header */
body { padding-top: 80px; }

/* Responsive */
@media screen and (max-width: 800px) {
    nav {
        display: none;
        width: 100%;
        flex-direction: column;
        gap: 5px;
        margin-top: 10px;
    }
    nav.active {
        display: flex;
    }
    .menu-toggle {
        display: block;
    }
    header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
// Toggle mobile menu
const menuToggle = document.getElementById('mobile-menu');
const nav = document.querySelector('header nav');

menuToggle.addEventListener('click', () => {
    nav.classList.toggle('active');
});
</script>
