<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cafe POS - Secure Login</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            <img src="assets/img/logo.png" alt="Cafe Logo" onerror="this.src='https://via.placeholder.com/90'">
            
            <h2>CAFE POS</h2>
            <div class="subtitle">POS and Inventory System</div>

            <form id="loginForm">
                <div id="message" style="margin-bottom: 15px; font-size: 14px;"></div>

                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="login-btn" id="btnLogin">LOGIN TO SYSTEM</button>
            </form>

            <div class="login-note">AUTHORIZED ACCESS ONLY</div>
            <div class="forgot-password">Forgot Password?</div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = document.getElementById('btnLogin');
            const msg = document.getElementById('message');

            btn.disabled = true;
            btn.innerText = "VERIFYING...";

            fetch('api/auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    msg.innerHTML = `<span style="color: #ffcf88;">Access Granted! Redirecting...</span>`;
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                } else {
                    msg.innerHTML = `<span style="color: #ff6b6b;">${data.message}</span>`;
                    btn.disabled = false;
                    btn.innerText = "LOGIN TO SYSTEM";
                }
            })
            .catch(err => {
                msg.innerHTML = `<span style="color: #ff6b6b;">Server Error. Try again.</span>`;
                btn.disabled = false;
                btn.innerText = "LOGIN TO SYSTEM";
            });
        });
    </script>
</body>
</html>