<?php
function highlight($texto, $filtro) {
    if ($filtro === '' || $texto === null) {
        return htmlspecialchars($texto ?? '');
    }
    $pattern = '/' . preg_quote($filtro, '/') . '/i';
    $replacement = '<span style="background:yellow; color:black; font-weight:bold;">$0</span>';
    return preg_replace($pattern, $replacement, htmlspecialchars($texto ?? ''));
}

include __DIR__ . "/db.php";



// Registrar saída
if (isset($_GET['saida_id'])) {
    $id = (int)$_GET['saida_id'];
    $stmt = $conn->prepare("UPDATE visitante SET data_saida = NOW() WHERE id = ? AND data_saida IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: list.php");
    exit;
}

// Filtros
$filtro_nome = $_GET['nome'] ?? '';
$filtro_cpf = $_GET['cpf'] ?? '';
$filtro_secao = $_GET['secao'] ?? '';
$filtro_autorizado = $_GET['autorizado'] ?? '';
$filtro_data = $_GET['data'] ?? '';
$filtro_carro = $_GET['carro'] ?? '';
$filtro_placa = $_GET['placa'] ?? '';
$filtro_modelo = $_GET['modelo'] ?? '';

// Paginação
$registros_por_pagina = 10; // quantidade de linhas por página
$pagina_atual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Consulta principal
$sql = "SELECT id, nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, data_hora, data_saida
        FROM visitante
        WHERE 1=1";

if ($filtro_nome !== '') $sql .= " AND nome LIKE '%" . $conn->real_escape_string($filtro_nome) . "%'";
if ($filtro_cpf !== '') $sql .= " AND cpf LIKE '%" . $conn->real_escape_string($filtro_cpf) . "%'";
if ($filtro_secao !== '') $sql .= " AND secao LIKE '%" . $conn->real_escape_string($filtro_secao) . "%'";
if ($filtro_autorizado !== '') $sql .= " AND autorizado LIKE '%" . $conn->real_escape_string($filtro_autorizado) . "%'";
if ($filtro_data !== '') $sql .= " AND DATE(data_hora) = '" . $conn->real_escape_string($filtro_data) . "'";
if ($filtro_carro === 'sim') $sql .= " AND carro_entrou = 'sim'";
elseif ($filtro_carro === 'nao') $sql .= " AND (carro_entrou IS NULL OR carro_entrou = 'nao')";
if ($filtro_placa !== '') $sql .= " AND placa LIKE '%" . $conn->real_escape_string($filtro_placa) . "%'";
if ($filtro_modelo !== '') $sql .= " AND modelo LIKE '%" . $conn->real_escape_string($filtro_modelo) . "%'";


$sql .= " LIMIT $registros_por_pagina OFFSET $offset";


$result = $conn->query($sql);
if ($result === false) {
    echo "<div style='color:red;'>Erro na consulta: " . htmlspecialchars($conn->error) . "</div>";
    $result = (object)['num_rows' => 0];
}

// Conta total de registros para paginação
$sql_total = "SELECT COUNT(*) AS total FROM visitante WHERE 1=1";
if ($filtro_nome !== '') $sql_total .= " AND nome LIKE '%" . $conn->real_escape_string($filtro_nome) . "%'";
if ($filtro_cpf !== '') $sql_total .= " AND cpf LIKE '%" . $conn->real_escape_string($filtro_cpf) . "%'";
if ($filtro_secao !== '') $sql_total .= " AND secao LIKE '%" . $conn->real_escape_string($filtro_secao) . "%'";
if ($filtro_autorizado !== '') $sql_total .= " AND autorizado LIKE '%" . $conn->real_escape_string($filtro_autorizado) . "%'";
if ($filtro_data !== '') $sql_total .= " AND DATE(data_hora) = '" . $conn->real_escape_string($filtro_data) . "'";
if ($filtro_carro === 'sim') $sql_total .= " AND carro_entrou = 'sim'";
elseif ($filtro_carro === 'nao') $sql_total .= " AND (carro_entrou IS NULL OR carro_entrou = 'nao')";
if ($filtro_placa !== '') $sql_total .= " AND placa LIKE '%" . $conn->real_escape_string($filtro_placa) . "%'";
if ($filtro_modelo !== '') $sql_total .= " AND modelo LIKE '%" . $conn->real_escape_string($filtro_modelo) . "%'";

$total_result = $conn->query($sql_total);
$total_registros = $total_result->fetch_assoc()['total'] ?? 0;
$total_paginas = ceil($total_registros / $registros_por_pagina);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Lista de Visitantes</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" type="text/css" href="../assets/css/template.css" />
<style>

/* FUNDO PADRÃO */
body {
    margin: 0;
    font-family: 'Segoe UI', Arial;
    overflow-x: hidden;
    background: radial-gradient(circle at top, #173e71, #0b1d3b);
    color: #e0f7ff;
    padding: 20px;
}

/* CANVAS */
#bgCanvas {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 0;
    pointer-events: none;
}

/* CONTEÚDO */
.wrap, h2 + table, form {
    position: relative;
    z-index: 1;
}

/* LOGO */
header img {
    max-width: 200px;
    display: block;
    margin: 0 auto 10px;
    filter: drop-shadow(0 0 10px rgba(0,150,255,0.5));
}

/* TÍTULOS */
h1, h2 {
    text-align: center;
    color: #e0f7ff;
}

/* FORM */
form {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

form input, form button {
    padding: 8px;
    border-radius: 8px;
    border: 1px solid rgba(0,150,255,0.3);
    background: rgba(255,255,255,0.05);
    color: #fff;
}

form button {
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    cursor: pointer;
    font-weight: bold;
}

/* TABELA */
table {
    width: 100%;
    border-collapse: collapse;
    backdrop-filter: blur(15px);
    background: rgba(10, 25, 50, 0.6);
    border-radius: 10px;
    overflow: hidden;
}

th, td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid rgba(0,150,255,0.2);
}

th {
    background: rgba(0,150,255,0.2);
}

/* LINHAS */
tr:nth-child(even) td {
    background: rgba(255,255,255,0.03);
}

/* STATUS */
.pendente {
    color: #ff4c4c;
    font-weight: bold;
}

.saida {
    color: #00ffae;
    font-weight: bold;
}

/* LINKS */
a {
    color: #00c6ff;
    font-weight: bold;
    text-decoration: none;
}

a:hover {
    text-shadow: 0 0 8px #00c6ff;
}

/* PAGINAÇÃO */
a[style] {
    background: rgba(255,255,255,0.05) !important;
    color: #fff !important;
}

/* OCULTO */
.oculto {
    display: none;
}

/* HIGHLIGHT */
span {
    background: #00c6ff;
    color: #000;
    padding: 2px 4px;
    border-radius: 3px;
}

</style>


</head>
<body>

<canvas id="bgCanvas"></canvas>

<header>
    <img src="assets/css/imagens/logotipodcta.png">
</header>
<h1>Registros de Visitas</h1>

<form method="get" style="margin-bottom:20px; display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
  <div><label for="nome">Nome:</label><input type="text" name="nome" id="nome" value="<?= htmlspecialchars($filtro_nome) ?>"></div>
  <div><label for="cpf">CPF:</label><input type="text" name="cpf" id="cpf" value="<?= htmlspecialchars($filtro_cpf) ?>"></div>
  <div><label for="secao">Seção:</label><input type="text" name="secao" id="secao" value="<?= htmlspecialchars($filtro_secao) ?>"></div>
  <div><label for="autorizado">Autorizado:</label><input type="text" name="autorizado" id="autorizado" value="<?= htmlspecialchars($filtro_autorizado) ?>"></div>
  <div><label for="data">Data:</label><input type="date" name="data" id="data" value="<?= htmlspecialchars($filtro_data) ?>"></div>
  <div style="align-self:flex-end;"><button type="submit" style="padding:6px 12px; background:#fff; color:#111; border:none; border-radius:5px; cursor:pointer;">Buscar</button></div>
</form>

<!-- Botão de imprimir PDF -->
<form method="get" action="gerar_pdf.php" target="_blank" style="margin:20px 0; display:flex; gap:10px; flex-wrap:wrap; justify-content:center;">
    <div>
        <label for="data_inicio">Data e hora inicial:</label>
        <input type="datetime-local" name="data_inicio" id="data_inicio" required>
    </div>
    <div>
        <label for="data_fim">Data e hora final:</label>
        <input type="datetime-local" name="data_fim" id="data_fim" required>
    </div>
    <div style="align-self:flex-end;">
        <button type="submit" style="padding:6px 12px; background:#fff; color:#111; border:none; border-radius:5px; cursor:pointer;">
            Imprimir PDF
        </button>
    </div>
</form>



<div class="wrap">
<table>
<thead>
<tr>
  <th class="oculto">ID</th>
  <th>Nome</th>
  <th>CPF</th>
  <th>Seção</th>
  <th>Ramal</th>
  <th>Autorizado por</th>
  <th>Carro entrou?</th>
  <th>Placa</th>
  <th>Modelo</th>
  <th>Entrada</th>
  <th>Saída</th>
  <th>Ação</th>
</tr>
</thead>
<tbody id="tabela_geral">
<?php if ($result->num_rows === 0): ?>
<tr class="empty-row">
  <td colspan="12">Nenhum registro encontrado.</td>
</tr>
<?php else: ?>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
  <td class="oculto"><?= (int)$row['id'] ?></td>
  <td><?= highlight($row['nome'], $filtro_nome) ?></td>
  <td class="small"><?= highlight($row['cpf'], $filtro_cpf) ?></td>
  <td><?= highlight($row['secao'], $filtro_secao) ?></td>
  <td><?= htmlspecialchars($row['ramal'] ?? '') ?></td>
  <td><?= highlight($row['autorizado'], $filtro_autorizado) ?></td>
  <td><?= htmlspecialchars($row['carro_entrou'] ?? '') ?></td>
  <td><?= highlight($row['placa'] ?? '', $filtro_placa) ?></td>
  <td><?= highlight($row['modelo'] ?? '', $filtro_modelo) ?></td>
  <td class="small"><?= htmlspecialchars($row['data_hora'] ?? '') ?></td>
  <td class="small"><?= !empty($row['data_saida']) ? htmlspecialchars($row['data_saida']) : '<em>Pendente</em>' ?></td>
  <td><?php if (empty($row['data_saida'])): ?><a href="?saida_id=<?= $row['id'] ?>" style="color: #ff0000;; font-weight:bold;">Registrar Saída</a><?php else: ?>✔<?php endif; ?></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Paginação -->
<?php if ($total_paginas > 1): ?>
<div style="text-align:center; margin-top:20px;">
  <?php 
  // GERA TODOS OS PARÂMETROS ATUALMENTE USADOS
  $params = $_GET;
  unset($params['pagina']); // Remove página atual para gerar links
  
  $base_url = '?' . http_build_query($params);
  
  $inicio = max(1, $pagina_atual - 2);
  $fim = min($total_paginas, $pagina_atual + 2);
  ?>
  
  <?php if ($pagina_atual > 1): ?>
    <a href="<?= $base_url ?>&pagina=1" 
       style="display:inline-block; margin:0 4px; padding:6px 10px; border-radius:5px; 
              background:#FFFFFF; color:#1C1F26; border:1px solid #A0A6AF; text-decoration:none;">
       « 1
    </a>
  <?php endif; ?>
  
  <?php for ($i = $inicio; $i <= $fim; $i++): ?>
    <a href="<?= $base_url ?>&pagina=<?= $i ?>" 
       style="display:inline-block; margin:0 4px; padding:6px 10px; border-radius:5px; 
              background:<?= $i == $pagina_atual ? '#4B5D63' : '#FFFFFF' ?>; 
              color:<?= $i == $pagina_atual ? '#F2E3B3' : '#1C1F26' ?>; 
              border:1px solid #A0A6AF; text-decoration:none; font-weight:<?= $i == $pagina_atual ? 'bold' : 'normal' ?>;">
       <?= $i ?>
    </a>
  <?php endfor; ?>
  
  <?php if ($pagina_atual < $total_paginas): ?>
    <a href="<?= $base_url ?>&pagina=<?= $total_paginas ?>" 
       style="display:inline-block; margin:0 4px; padding:6px 10px; border-radius:5px; 
              background:#FFFFFF; color:#1C1F26; border:1px solid #A0A6AF; text-decoration:none;">
       <?= $total_paginas ?> »
    </a>
  <?php endif; ?>
  
</div>
<?php endif; ?>

<h2 style="margin-top:40px; text-align:center;">Visitantes ainda no local</h2>
<table>
<thead>
<tr>
  <th class="oculto">ID</th>
  <th>Nome</th>
  <th>CPF</th>
  <th>Seção</th>
  <th>Ramal</th>
  <th>Autorizado por</th>
  <th>Carro entrou?</th>
  <th>Placa</th>
  <th>Modelo</th>
  <th>Entrada</th>
  <th>Ação</th>
</tr>
</thead>
<tbody id="tabela-dados">
<?php
$sql_pendentes = "SELECT id, nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, data_hora 
                  FROM visitante WHERE data_saida IS NULL ORDER BY data_hora DESC";
$res_pendentes = $conn->query($sql_pendentes);

if (!$res_pendentes || $res_pendentes->num_rows === 0): ?>
<tr class="empty-row">
  <td colspan="10">Nenhum visitante aguardando saída.</td>
</tr>
<?php else: ?>
<?php while ($rowp = $res_pendentes->fetch_assoc()): ?>
<tr>
  <td class="oculto"><?= $rowp['id'] ?></td>
  <td><?= htmlspecialchars($rowp['nome']) ?></td>
  <td><?= htmlspecialchars($rowp['cpf']) ?></td>
  <td><?= htmlspecialchars($rowp['secao']) ?></td>
  <td><?= htmlspecialchars($rowp['ramal']) ?></td>
  <td><?= htmlspecialchars($rowp['autorizado']) ?></td>
  <td><?= htmlspecialchars($rowp['carro_entrou']) ?></td>
  <td><?= htmlspecialchars($rowp['placa']) ?></td>
  <td><?= htmlspecialchars($rowp['modelo']) ?></td>
  <td><?= htmlspecialchars($rowp['data_hora']) ?></td>
  <td><a href="?saida_id=<?= $rowp['id'] ?>" style="color: #ff0000; font-weight:bold;">Registrar Saída</a></td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</tbody>
</table>

<script>
const canvas = document.getElementById("bgCanvas");
const ctx = canvas.getContext("2d");

let mouse = { x: null, y: null };

function resize(){
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
}
resize();

window.addEventListener("resize", resize);

window.addEventListener("mousemove", e=>{
    mouse.x = e.x;
    mouse.y = e.y;
});

let particles = [];

for(let i=0;i<100;i++){
    particles.push({
        x: Math.random()*canvas.width,
        y: Math.random()*canvas.height,
        vx: (Math.random()-0.5),
        vy: (Math.random()-0.5)
    });
}

function draw(){
    ctx.clearRect(0,0,canvas.width,canvas.height);

    particles.forEach(p=>{
        let dx = mouse.x - p.x;
        let dy = mouse.y - p.y;
        let dist = Math.sqrt(dx*dx + dy*dy);

        if(dist < 150){
            p.x += dx * 0.02;
            p.y += dy * 0.02;
        }

        p.x += p.vx;
        p.y += p.vy;

        ctx.beginPath();
        ctx.arc(p.x,p.y,2,0,Math.PI*2);
        ctx.fillStyle = "#00d4ff";
        ctx.fill();

        if(p.x<0||p.x>canvas.width) p.vx*=-1;
        if(p.y<0||p.y>canvas.height) p.vy*=-1;
    });

    for(let i=0;i<particles.length;i++){
        for(let j=i+1;j<particles.length;j++){
            let dx = particles[i].x - particles[j].x;
            let dy = particles[i].y - particles[j].y;
            let dist = Math.sqrt(dx*dx + dy*dy);

            if(dist < 120){
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = "rgba(0,212,255,0.1)";
                ctx.stroke();
            }
        }
    }

    requestAnimationFrame(draw);
}

draw();
</script>



<script>
function getParamsComPagina() {
  return window.location.search.replace('?', '');
}

function atualizarTabela() {
  const params = getParamsComPagina();

  fetch('list.php?' + params + '&ajax=pendentes')
    .then(res => res.text())
    .then(html => {
      const temp = document.createElement('div');
      temp.innerHTML = html;
      const novoTbody = temp.querySelector('#tabela-dados');
      if (novoTbody) {
        document.getElementById('tabela-dados').innerHTML = novoTbody.innerHTML;
      }
    });
}
function atualizarTabelaGeral() {
  const params = getParamsComPagina();

  fetch('list.php?' + params + '&ajax=geral')
    .then(res => res.text())
    .then(html => {
      const temp = document.createElement('div');
      temp.innerHTML = html;
      const novoTbody = temp.querySelector('#tabela_geral');
      if (novoTbody) {
        document.getElementById('tabela_geral').innerHTML = novoTbody.innerHTML;
      }
    });
}

setInterval(atualizarTabela, 1000);
setInterval(atualizarTabelaGeral, 1000);
</script>

