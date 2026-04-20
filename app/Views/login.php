<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Prevention Control System</title>
    <link rel="stylesheet" href="<?= base_url('public/css/login.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <header class="topbar">
        <div class="brand">
            <div class="brand-logo"></div>
            <div class="brand-text">
                <span>Prevention</span>
                <span>Control</span>
                <span>System</span>
            </div>
        </div>

        <div class="user-box">
            <i class="fa-regular fa-circle-user"></i>
            <span>Usuario</span>
        </div>
    </header>

    <main class="login-page">
        <section class="login-card">
            <h1>Login</h1>

            <form action="<?= base_url('login/auth') ?>" method="post" class="login-form">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="submit-btn">Submit</button>

                <p class="login-note">
                    El nombre de tu perfil de Canva no se compartirá. Nunca envíes contraseñas.
                </p>
            </form>
        </section>
    </main>

</body>
</html>