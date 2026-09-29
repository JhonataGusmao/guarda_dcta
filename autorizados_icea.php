<?php
session_start();
include __DIR__ . '/db.php';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alternar_acesso') {
    $autorizadoId = filter_input(INPUT_POST, 'autorizado_id', FILTER_VALIDATE_INT);
    if ($autorizadoId) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare(
                'SELECT p.id AS periodo_id, CONCAT(a.posto_graduacao, " ", a.nome_guerra) AS nome
                 FROM icea_autorizado a
                 JOIN icea_periodo_acesso p ON p.autorizado_id = a.id
                 WHERE a.id = ? AND a.excluido_em IS NULL AND CURRENT_DATE BETWEEN p.data_inicio AND p.data_fim
                 ORDER BY p.data_inicio DESC, p.id DESC LIMIT 1 FOR UPDATE'
            );
            $stmt->bind_param('i', $autorizadoId);
            $stmt->execute();
            $periodo = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$periodo) {
                throw new RuntimeException('O período de acesso não está válido hoje.');
            }

            $stmt = $conn->prepare(
                'SELECT id FROM visitante
                 WHERE icea_autorizado_id = ? AND data_saida IS NULL
                 ORDER BY data_hora DESC LIMIT 1 FOR UPDATE'
            );
            $stmt->bind_param('i', $autorizadoId);
            $stmt->execute();
            $aberto = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($aberto) {
                $stmt = $conn->prepare('UPDATE visitante SET data_saida = NOW() WHERE id = ? AND data_saida IS NULL');
                $stmt->bind_param('i', $aberto['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO visitante
                     (nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, icea_autorizado_id, icea_periodo_id)
                     SELECT CONCAT(a.posto_graduacao, ' ', a.nome_guerra), a.cpf, 'LABSIM-ICEIA', '(39) 459323-9301',
                            'LABSIM-ICEIA', 'nao', NULL, NULL, a.id, ?
                     FROM icea_autorizado a WHERE a.id = ?"
                );
                $stmt->bind_param('ii', $periodo['periodo_id'], $autorizadoId);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            $_SESSION['icea_operador_flash'] = ($aberto ? 'Saída' : 'Entrada') . ' registrada para ' . $periodo['nome'] . '.';
            $params = $_GET;
            unset($params['fragment']);
            header('Location: autorizados_icea.php' . ($params ? '?' . http_build_query($params) : ''));
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $mensagem = $e instanceof RuntimeException ? $e->getMessage() : 'Não foi possível registrar a entrada ou saída.';
        }
    }
}

$filtros = [
    'posto' => trim($_GET['posto'] ?? ''),
    'nome' => trim($_GET['nome'] ?? ''),
    'cpf' => trim($_GET['cpf'] ?? ''),
    'om' => trim($_GET['om'] ?? ''),
    'data' => trim($_GET['data'] ?? '')
];
$condicoes = [];
foreach (['posto' => 'a.posto_graduacao', 'nome' => 'a.nome_guerra', 'cpf' => 'a.cpf', 'om' => 'a.om_origem'] as $chave => $coluna) {
    if ($filtros[$chave] !== '') {
        $valor = $conn->real_escape_string($filtros[$chave]);
        $condicoes[] = "$coluna LIKE '%$valor%'";
    }
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['data'])) {
    $dataFiltro = $conn->real_escape_string($filtros['data']);
    $condicoes[] = "p.data_inicio <= '$dataFiltro' AND p.data_fim >= '$dataFiltro'";
}
$whereFiltros = $condicoes ? ' AND ' . implode(' AND ', $condicoes) : '';

$porPagina = 10;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$sqlTotal = "SELECT COUNT(*) AS total FROM icea_autorizado a
             JOIN icea_periodo_acesso p ON p.autorizado_id = a.id
             WHERE a.excluido_em IS NULL AND CURRENT_DATE BETWEEN p.data_inicio AND p.data_fim
             $whereFiltros AND p.id = (SELECT p2.id FROM icea_periodo_acesso p2
                 WHERE p2.autorizado_id = a.id AND CURRENT_DATE BETWEEN p2.data_inicio AND p2.data_fim
                 ORDER BY p2.data_inicio DESC, p2.id DESC LIMIT 1)";
$totalRegistros = (int)($conn->query($sqlTotal)->fetch_assoc()['total'] ?? 0);
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
$paginaAtual = min($paginaAtual, $totalPaginas);
$offset = ($paginaAtual - 1) * $porPagina;
$sql = "SELECT a.id, a.posto_graduacao, a.nome_guerra, a.cpf, a.om_origem,
               p.data_inicio, p.data_fim,
               EXISTS(SELECT 1 FROM visitante v
                      WHERE v.icea_autorizado_id = a.id AND v.data_saida IS NULL) AS dentro
        FROM icea_autorizado a
        JOIN icea_periodo_acesso p ON p.autorizado_id = a.id
        WHERE a.excluido_em IS NULL
          AND CURRENT_DATE BETWEEN p.data_inicio AND p.data_fim
          $whereFiltros
          AND p.id = (SELECT p2.id FROM icea_periodo_acesso p2
                      WHERE p2.autorizado_id = a.id
                        AND CURRENT_DATE BETWEEN p2.data_inicio AND p2.data_fim
                      ORDER BY p2.data_inicio DESC, p2.id DESC LIMIT 1)
        ORDER BY a.nome_guerra LIMIT $porPagina OFFSET $offset";
$autorizados = $conn->query($sql);
$mensagemOperador = $_SESSION['icea_operador_flash'] ?? '';
unset($_SESSION['icea_operador_flash']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Autorizados - ICEA | DCTA</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    overflow-x: hidden;
    overflow-y: auto;
    background: radial-gradient(circle at top, #173e71, #0b1d3b);
}

#bgCanvas {
    position: fixed;
    inset: 0;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    padding: 24px 12px;
}

.logo img {
    max-width: 180px;
    display: block;
    margin: 0 auto 10px;
    filter: drop-shadow(0 0 10px rgba(0, 150, 255, 0.5));
}

h1 {
    margin: 8px 0 22px;
    color: #e0f7ff;
    text-align: center;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: center;
    gap: 10px;
    width: 100%;
    margin: 0 0 18px;
    padding: 14px;
    border: 1px solid rgba(0, 150, 255, 0.2);
    border-radius: 10px;
    background: rgba(10, 25, 50, 0.6);
}

.filter-form label { display: flex; flex-direction: column; gap: 5px; color: #e0f7ff; }
.filter-form input { min-width: 150px; padding: 9px 11px; border: 1px solid rgba(0, 150, 255, 0.35); border-radius: 7px; color: #fff; background: rgba(255, 255, 255, 0.07); }
.filter-form button, .filter-form a { padding: 9px 13px; border: 0; border-radius: 7px; color: #fff; background: linear-gradient(90deg, #00c6ff, #0072ff); font-weight: bold; text-decoration: none; cursor: pointer; }
.filter-form a { background: rgba(255, 255, 255, 0.12); }

.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(0, 150, 255, 0.2);
    border-radius: 10px;
    background: rgba(10, 25, 50, 0.6);
    backdrop-filter: blur(15px);
}

table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
    color: #e0f7ff;
}

th, td {
    padding: 12px 10px;
    border-bottom: 1px solid rgba(0, 150, 255, 0.2);
    text-align: center;
}

th {
    background: rgba(0, 150, 255, 0.2);
}

tbody tr:nth-child(even) {
    background: rgba(255, 255, 255, 0.03);
}

.empty-row td {
    padding: 24px;
    color: rgba(224, 247, 255, 0.8);
}
.pagination { display:flex; justify-content:center; align-items:center; gap:12px; flex-wrap:wrap; padding:16px; color:#e0f7ff; }
.pagination a, .pagination span, .pagination select { color:#e0f7ff; padding:8px 12px; border:1px solid rgba(0,150,255,.35); border-radius:7px; background:#10294a; text-decoration:none; }
.notice-success { width:min(720px,100%); margin:0 0 16px; padding:12px; border:1px solid rgba(80,230,150,.5); border-radius:8px; color:#eafff1; background:rgba(0,120,65,.38); text-align:center; opacity:1; transition:opacity .7s ease; }

.notice {
    width: min(720px, 100%);
    margin: 0 0 16px;
    padding: 10px 14px;
    border: 1px solid rgba(255, 100, 100, 0.45);
    border-radius: 8px;
    background: rgba(100, 20, 20, 0.35);
}

.action-button {
    padding: 9px 14px;
    border: 0;
    border-radius: 7px;
    color: #fff;
    font-weight: bold;
    white-space: nowrap;
}

.action-entry { background: #168747; }
.action-exit { background: #c93636; }

.icea-button {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 2;
    display: inline-block;
    padding: 14px 22px;
    border: 1px solid rgba(0, 198, 255, 0.45);
    border-radius: 8px;
    background: rgba(10, 25, 50, 0.75);
    color: #e0f7ff;
    font-size: 16px;
    font-weight: bold;
    text-decoration: none;
    box-shadow: 0 0 14px rgba(0, 150, 255, 0.2);
    animation: icea-pulse 2.4s ease-in-out infinite;
}

.icea-button:hover {
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    box-shadow: 0 0 24px rgba(0, 150, 255, 0.4);
    animation-play-state: paused;
}

@keyframes icea-pulse {
    0%, 100% {
        box-shadow: 0 0 14px rgba(0, 150, 255, 0.2);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 22px rgba(0, 198, 255, 0.42);
        transform: scale(1.025);
    }
}

@media (max-width: 760px) {
    .table-wrap { overflow:visible; border:0; background:transparent; backdrop-filter:none; }
    table { min-width:0; }
    thead { display:none; }
    tbody, tbody tr, tbody td { display:block; width:100%; }
    tbody tr { margin-bottom:12px; padding:6px 12px; border:1px solid rgba(0,150,255,.2); border-radius:10px; background:rgba(10,25,50,.75)!important; }
    tbody td { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:9px 0; text-align:right; overflow-wrap:anywhere; }
    tbody td::before { content:attr(data-label); flex:0 0 42%; color:#9edff5; font-weight:bold; text-align:left; }
    .empty-row td { display:block; text-align:center; } .empty-row td::before { content:none; }
    .action-button { white-space:normal; }
    .icea-button { top:12px; left:12px; padding:10px 12px; font-size:13px; }
    .container { padding:64px 8px 16px; }
    .filter-form label, .filter-form input { width:100%; }
}
@media (min-width:761px) and (max-width:1024px) { .container { padding:72px 14px 18px; } }
@media (max-width: 600px) {
    .icea-button {
        top: 12px;
        left: 12px;
        padding: 11px 15px;
        font-size: 14px;
    }
}
</style>
</head>
<body>
<canvas id="bgCanvas"></canvas>

<div class="container">
    <div class="logo">
        <img src="assets/css/imagens/logotipodcta.png" alt="Logo do DCTA">
    </div>
    <h1>Autorizados ICEA</h1>
    <?php if ($mensagemOperador !== ''): ?><div class="notice-success" id="operator-confirmation" role="status" aria-live="polite"><?= htmlspecialchars($mensagemOperador, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($mensagem !== ''): ?>
        <div class="notice"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="get" class="filter-form">
        <label>Posto/Graduação<input name="posto" value="<?= htmlspecialchars($filtros['posto'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Nome de Guerra<input name="nome" value="<?= htmlspecialchars($filtros['nome'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>CPF<input name="cpf" value="<?= htmlspecialchars($filtros['cpf'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>OM de Origem<input name="om" value="<?= htmlspecialchars($filtros['om'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Data do acesso<input type="date" name="data" value="<?= htmlspecialchars($filtros['data'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <button type="submit">Pesquisar</button>
        <a href="autorizados_icea.php">Limpar</a>
    </form>
    <div class="table-wrap">
        <table id="operator-table">
            <thead>
                <tr>
                    <th scope="col">Posto/Graduação</th>
                    <th scope="col">Nome de Guerra</th>
                    <th scope="col">CPF</th>
                    <th scope="col">OM de Origem</th>
                    <th scope="col">Período de Acesso</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$autorizados || $autorizados->num_rows === 0): ?>
                    <tr class="empty-row"><td colspan="6">Nenhum autorizado com período válido hoje.</td></tr>
                <?php else: ?>
                    <?php while ($pessoa = $autorizados->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Posto/Graduação"><?= htmlspecialchars($pessoa['posto_graduacao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Nome de Guerra"><?= htmlspecialchars($pessoa['nome_guerra'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="CPF"><?= htmlspecialchars($pessoa['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="OM de Origem"><?= htmlspecialchars($pessoa['om_origem'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Período de Acesso"><?= date('d/m/Y', strtotime($pessoa['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($pessoa['data_fim'])) ?></td>
                            <td data-label="Ações">
                                <form method="post">
                                    <input type="hidden" name="acao" value="alternar_acesso">
                                    <input type="hidden" name="autorizado_id" value="<?= (int)$pessoa['id'] ?>">
                                    <?php if ((int)$pessoa['dentro'] === 1): ?>
                                        <button class="action-button action-exit" type="submit">Registrar saída</button>
                                    <?php else: ?>
                                        <button class="action-button action-entry" type="submit">Registrar entrada</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="pagination" id="operator-pagination" aria-label="Paginação">
        <?php if ($paginaAtual > 1): ?><a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual - 1])) ?>">Anterior</a><?php endif; ?>
        <form method="get" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center">
            <?php foreach ($filtros as $chave => $valor): ?><input type="hidden" name="<?= htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?>
            <label for="pagina-atual">Página</label>
            <select id="pagina-atual" name="pagina" onchange="this.form.submit()" aria-label="Escolher página">
                <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?><option value="<?= $pagina ?>" <?= $pagina === $paginaAtual ? 'selected' : '' ?>><?= $pagina ?></option><?php endfor; ?>
            </select>
            <span>de <?= $totalPaginas ?> (<?= $totalRegistros ?> autorizados)</span>
        </form>
        <?php if ($paginaAtual < $totalPaginas): ?><a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual + 1])) ?>">Próxima</a><?php endif; ?>
    </nav>
</div>

<a href="index.php" class="icea-button">Cadastro de visitantes DCTA</a>

<script>
const canvas = document.getElementById('bgCanvas');
const confirmation = document.getElementById('operator-confirmation');
if (confirmation) window.setTimeout(() => { confirmation.style.opacity = '0'; window.setTimeout(() => confirmation.remove(), 750); }, 4000);
const autoRefreshUrl = new URL(window.location.href);
window.setInterval(async () => {
    try {
        const response = await fetch(autoRefreshUrl, { cache: 'no-store', headers: { 'X-Requested-With': 'fetch' } });
        if (!response.ok) return;
        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const currentBody = document.querySelector('#operator-table tbody');
        const freshBody = parsed.querySelector('#operator-table tbody');
        const currentPagination = document.getElementById('operator-pagination');
        const freshPagination = parsed.getElementById('operator-pagination');
        if (currentBody && freshBody) currentBody.replaceWith(freshBody);
        if (currentPagination && freshPagination) currentPagination.replaceWith(freshPagination);
    } catch (error) { /* mantém os dados visíveis até a próxima atualização */ }
}, 15000);
const ctx = canvas.getContext('2d');
let mouse = { x: null, y: null };
let particles = [];

function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}

function createParticles() {
    particles = Array.from({ length: 100 }, () => ({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        vx: Math.random() - 0.5,
        vy: Math.random() - 0.5
    }));
}

function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    particles.forEach(p => {
        if (mouse.x !== null) {
            const dx = mouse.x - p.x;
            const dy = mouse.y - p.y;
            if (Math.sqrt(dx * dx + dy * dy) < 150) {
                p.x += dx * 0.02;
                p.y += dy * 0.02;
            }
        }

        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;

        ctx.beginPath();
        ctx.arc(p.x, p.y, 2, 0, Math.PI * 2);
        ctx.fillStyle = '#00d4ff';
        ctx.fill();
    });

    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx = particles[i].x - particles[j].x;
            const dy = particles[i].y - particles[j].y;
            if (Math.sqrt(dx * dx + dy * dy) < 120) {
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = 'rgba(0, 212, 255, 0.1)';
                ctx.stroke();
            }
        }
    }

    requestAnimationFrame(draw);
}

resize();
createParticles();
draw();
window.addEventListener('resize', () => {
    resize();
    createParticles();
});
window.addEventListener('mousemove', event => {
    mouse.x = event.clientX;
    mouse.y = event.clientY;
});
</script>
<?php $conn->close(); ?>
</body>
</html>
