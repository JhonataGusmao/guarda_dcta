<?php
include __DIR__ . "/db.php";

// =====================
// REGISTRAR SAÍDA
// =====================
if (isset($_GET['saida_id'])) {
    $id = (int)$_GET['saida_id'];

    $conn->query("
        UPDATE visitante 
        SET data_saida = NOW() 
        WHERE id = $id AND data_saida IS NULL
    ");
}

// =====================
// FILTRO
// =====================
$filtro = $_GET['busca'] ?? '';

// =====================
// CONSULTA
// =====================
$sql = "SELECT id, nome, cpf, placa, modelo, data_hora, data_saida
        FROM visitante
        WHERE 1=1";

if ($filtro !== '') {
    $esc = $conn->real_escape_string($filtro);
    $sql .= " AND (nome LIKE '%$esc%' OR cpf LIKE '%$esc%' OR placa LIKE '%$esc%')";
}

$sql .= " ORDER BY data_hora DESC";

$result = $conn->query($sql);

// =====================
// AJAX
// =====================
if (isset($_GET['ajax'])) {

    if ($result->num_rows === 0) {
        echo '<tr><td colspan="8">Nenhum visitante encontrado.</td></tr>';
        exit;
    }

    while ($row = $result->fetch_assoc()) {

        echo "<tr>";
        echo "<td class='oculto'>{$row['id']}</td>";
        echo "<td>".htmlspecialchars($row['nome'])."</td>";
        echo "<td>".htmlspecialchars($row['cpf'])."</td>";
        echo "<td>".htmlspecialchars($row['placa'])."</td>";
        echo "<td>".htmlspecialchars($row['modelo'])."</td>";
        echo "<td>".htmlspecialchars($row['data_hora'])."</td>";

        echo "<td>";
        if (empty($row['data_saida'])) {
            echo "<span class='pendente'>Pendente</span>";
        } else {
            echo "<span class='saida'>".htmlspecialchars($row['data_saida'])."</span>";
        }
        echo "</td>";

        echo "<td>";
        if (empty($row['data_saida'])) {
            echo "<a class='botao' href='?saida_id={$row['id']}&busca=".urlencode($filtro)."'>Registrar</a>";
        } else {
            echo "✔️";
        }
        echo "</td>";

        echo "</tr>";
    }

    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Saída de Visitantes - DCTA</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Arial;
    background: radial-gradient(circle at top, #173e71, #0b1d3b);
    color: #e0f7ff;
    overflow-x: hidden;
}

/* CANVAS FUNDO */
#bgCanvas {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 0;
}

.wrap {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;
}

.card {
    backdrop-filter: blur(20px);
    background: rgba(10, 25, 50, 0.6);
    border-radius: 15px;
    padding: 25px;
    border: 1px solid rgba(0,150,255,0.2);
}

h1 {
    text-align: center;
}

/* LOGO */
header {
    text-align: center;
    padding-top: 20px;
}

header img {
    max-width: 200px;
    filter: drop-shadow(0 0 10px rgba(0,150,255,0.6));
}

/* BUSCA */
.search {
    text-align: center;
    margin-bottom: 20px;
}

.search input {
    padding: 10px;
    width: 60%;
    border-radius: 8px;
    border: 1px solid rgba(0,150,255,0.3);
    background: rgba(255,255,255,0.05);
    color: #fff;
}

.search button {
    padding: 10px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    color: #fff;
    cursor: pointer;
}

/* TABELA */
table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    text-align: center;
    border-bottom: 1px solid rgba(0,150,255,0.2);
}

th {
    background: rgba(0,150,255,0.2);
}

.pendente { color: #ff4c4c; font-weight: bold; }
.saida { color: #ffffff; }

a.botao {
    padding: 6px 10px;
    background: #0072ff;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
}

.oculto { display: none; }
</style>
</head>

<body>

<!-- 🔥 FUNDO ANIMADO RESTAURADO -->
<canvas id="bgCanvas"></canvas>

<!-- LOGO RESTAURADA -->
<header>
    <img src="assets/css/imagens/logotipodcta.png">
</header>

<div class="wrap">
<div class="card">

<h1>Saída de Visitantes</h1>

<form class="search" method="get">
    <input type="text" name="busca" placeholder="Nome, CPF ou placa"
           value="<?= htmlspecialchars($filtro) ?>">
    <button type="submit">Buscar</button>
</form>

<table>
<thead>
<tr>
    <th class="oculto">ID</th>
    <th>Nome</th>
    <th>CPF</th>
    <th>Placa</th>
    <th>Modelo</th>
    <th>Entrada</th>
    <th>Saída</th>
    <th>Ação</th>
</tr>
</thead>

<tbody id="tabela-saida">
<?php
if ($result->num_rows === 0) {
    echo "<tr><td colspan='8'>Nenhum visitante encontrado</td></tr>";
} else {
    while ($row = $result->fetch_assoc()) {
        ?>
        <tr>
            <td class="oculto"><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nome']) ?></td>
            <td><?= htmlspecialchars($row['cpf']) ?></td>
            <td><?= htmlspecialchars($row['placa']) ?></td>
            <td><?= htmlspecialchars($row['modelo']) ?></td>
            <td><?= htmlspecialchars($row['data_hora']) ?></td>

            <td>
                <?php if (empty($row['data_saida'])): ?>
                    <span class="pendente">Pendente</span>
                <?php else: ?>
                    <span class="saida"><?= htmlspecialchars($row['data_saida']) ?></span>
                <?php endif; ?>
            </td>

            <td>
                <?php if (empty($row['data_saida'])): ?>
                    <a class="botao"
                       href="?saida_id=<?= $row['id'] ?>&busca=<?= urlencode($filtro) ?>">
                       Registrar
                    </a>
                <?php else: ?>
                    ✔️
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
?>
</tbody>
</table>

</div>
</div>

<!-- 🔥 FUNDO ANIMADO (PARTÍCULAS + MOUSE) -->
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

for(let i=0;i<120;i++){
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

<!-- 🔥 AJAX UPDATE -->
<script>
function atualizarSaida() {
    const params = new URLSearchParams(window.location.search);

    fetch('saida.php?' + params.toString() + '&ajax=1')
        .then(r => r.text())
        .then(html => {
            document.getElementById('tabela-saida').innerHTML = html;
        });
}

setInterval(atualizarSaida, 2000);
</script>

</body>
</html>