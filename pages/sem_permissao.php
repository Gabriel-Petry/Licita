<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

render_header("Acesso Limitado");
?>

<div class="container">
  <div class="card">
    <h1>Acesso Limitado</h1>
    <p>Seus privilégios de acesso são limitados.</p>
    <p>Por favor, caso necessário entrar em contato com o administrador para liberação dos demais acessos.</p>
  </div>
</div>

<?php
render_footer();
?>