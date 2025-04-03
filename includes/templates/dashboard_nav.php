<div class="header">
    <div class="container">
        <nav>
            <ul>
                <li class="logo-text"><a href="/index.php">Share</a></li>
                <li class="profile-icon"><i class="fa-solid fa-user"></i></li>
            </ul>
            <div class="profile-popup">
                <h3>Hi, <?php output_firstname(); ?></h3>
                <form action="/includes/auth/logout.inc.php" method="post">
                    <button class="btn1">Logout</button>
                </form>
            </div>
        </nav>
    </div>
</div>