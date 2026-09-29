<?php
session_start();
require __DIR__ . '/icea_admin_config.php';

if (!empty($_SESSION['icea_admin_authenticated'])) {
    header('Location: administrador_iceia.php');
    exit;
}

$erro = '';
$loginSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (hash_equals(ICEA_ADMIN_USER, $usuario) && password_verify($senha, ICEA_ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['icea_admin_authenticated'] = true;
        $loginSuccess = true;
    }
    $erro = 'Usuário ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Administrador ICEA | DCTA</title>
<style>
* { box-sizing: border-box; }
body { position: relative; isolation: isolate; margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 18px; font-family: 'Segoe UI', Arial, sans-serif; color: #e0f7ff; background: radial-gradient(circle at top, #173e71, #0b1d3b); overflow: hidden; }
#screen-sweep { position: fixed; z-index: 0; top: -18%; bottom: -18%; left: -48%; width: 38vw; min-width: 180px; transform: skewX(-20deg); opacity: 0; pointer-events: none; background: linear-gradient(90deg, transparent, rgba(0,198,255,.10) 28%, rgba(99,230,255,.68) 50%, rgba(0,198,255,.14) 72%, transparent); filter: blur(12px); animation: screen-sweep 1700ms ease-in-out 180ms both; }
.login-card { position: relative; z-index: 1; width: min(420px, 100%); padding: 28px; border: 1px solid rgba(0, 198, 255, 0.3); border-radius: 15px; background: rgba(10, 25, 50, 0.82); box-shadow: 0 0 35px rgba(0, 150, 255, 0.2); animation: login-reveal 750ms ease-out both; }
.login-card.is-exiting { animation: login-exit 650ms cubic-bezier(.55,.05,.8,.4) both; }
.login-success { margin: 16px 0 0; color: #8ee6ae; text-align: center; }
body.login-success #screen-sweep { animation: none; opacity: 0; }
body.login-success .login-card form { pointer-events: none; }
.login-card img { display: block; width: 150px; max-width: 100%; margin: 0 auto 14px; }
h1 { margin: 0 0 20px; text-align: center; font-size: 22px; }
label { display: block; margin: 12px 0 6px; }
input { width: 100%; padding: 11px; border: 1px solid rgba(0, 150, 255, 0.4); border-radius: 8px; color: #fff; background: rgba(255, 255, 255, 0.07); }
button { width: 100%; margin-top: 18px; padding: 12px; border: 0; border-radius: 8px; color: #fff; background: linear-gradient(90deg, #00c6ff, #0072ff); font-weight: bold; cursor: pointer; }
.error { margin-top: 14px; color: #ffb3b3; text-align: center; }
@keyframes login-reveal {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes screen-sweep { 0% { left: -48%; opacity: 0; } 12% { opacity: .9; } 72% { opacity: .55; } 100% { left: 120%; opacity: 0; } }
@keyframes login-exit { 0% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); } 100% { opacity: 0; transform: translateY(-38px) scale(.88); filter: blur(10px); } }
@media (prefers-reduced-motion: reduce) {
    #screen-sweep, .login-card, .login-card.is-exiting { animation: none; }
}
</style>
</head>
<body class="<?= $loginSuccess ? 'login-success' : '' ?>">
<div id="screen-sweep" aria-hidden="true"></div>
<main class="login-card<?= $loginSuccess ? ' is-exiting' : '' ?>">
    <img src="assets/css/imagens/logotipodcta.png" alt="Logo do DCTA">
    <h1>Administrador Guarda DCTA</h1>
    <form method="post" autocomplete="on">
        <label for="usuario">Usuário</label>
        <input id="usuario" name="usuario" autocomplete="username" required autofocus>
        <label for="senha">Senha</label>
        <input id="senha" name="senha" type="password" autocomplete="current-password" required>
        <button type="submit">Entrar</button>
    </form>
    <?php if ($erro !== ''): ?><div class="error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($loginSuccess): ?><p class="login-success">Acesso autorizado. Redirecionando…</p><?php endif; ?>
</main>
<?php if ($loginSuccess): ?>
<script>window.setTimeout(() => window.location.replace('administrador_iceia.php'), 700);</script>
<?php endif; ?>
</body>
</html>
