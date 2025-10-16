<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
check_csrf();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (login($email, $senha)) {
        if (tem_permissao('dashboard.ver')) {
            header('Location: /dashboard');
        } else {
            header('Location: /menu');
        }
        exit;
    } else {
        $error = 'E-mail ou senha inválidos.';
    }
}

render_header("Login - LicitAções", [
    'bodyClass' => 'page-centered',
    'showHeader' => false
]);
?>

<div class="login-container">
  <img src="/img/logo.png" alt="Logo LicitAções" class="login-logo">

  <div class="card login-card">
    <?php if ($error): ?>
      <p class="chip error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <label>E-mail</label>
    <input type="email" name="email" required placeholder="voce@sapucaiadosul.rs.gov.br">
    <label>Senha</label>
    <input type="password" name="senha" required>
    <button class="btn primary login" type="submit">Acessar</button>
  </form>
    <p class="muted">Usuário inicial: usuario@sapucaiadosul.rs.gov.br / senha: Licita@123 (altere após login)</p>
  </div>

  <div class="login-footer">
    <a href="#sobre-popup" class="link-discreto">Sobre o Sistema</a>
  </div>
</div>

<div id="sobre-popup" class="popup-overlay">
  <div class="popup-card card popup-small">
    <a href="#" class="popup-close">&times;</a>
    <h2>Sobre o LicitAções</h2>
    <p>Este é um exemplo simples de sistema para controle de licitações.</p>
    <ul>
      <li>Dashboard com KPIs e gráficos.</li>
      <li>Lista de licitações com filtros e edição.</li>
      <li>Cadastros auxiliares.</li>
    </ul>
  </div>
</div>

<?php render_footer(); ?>