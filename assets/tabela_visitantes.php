<?php
include __DIR__ . "/db.php";

// Recebe filtros (caso venham via AJAX)
$filtro_nome = $_GET['nome'] ?? '';
$filtro_cpf = $_GET['cpf'] ?? '';
$filtro_secao = $_GET['secao'] ?? '';
$filtro_autorizado = $_GET['autorizado'] ?? '';
$filtro_data = $_GET['data'] ?? '';

// Monta a query
$sql_pendentes = "SELECT id, nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, data_hora 
                  FROM visitante 
                  WHERE data_saida IS NULL";

// Aplica filtros dinamicamente
if ($filtro_nome !== '') $sql_pendentes .= " AND nome LIKE '%" . $conn->real_escape_string($filtro_nome) . "%'";
if ($filtro_cpf !== '') $sql_pendentes .= " AND cpf LIKE '%" . $conn->real_escape_string($filtro_cpf) . "%'";
if ($filtro_secao !== '') $sql_pendentes .= " AND secao LIKE '%" . $conn->real_escape_string($filtro_secao) . "%'";
if ($filtro_autorizado !== '') $sql_pendentes .= " AND autorizado LIKE '%" . $conn->real_escape_string($filtro_autorizado) . "%'";
if ($filtro_data !== '') $sql_pendentes .= " AND DATE(data_hora) = '" . $conn->real_escape_string($filtro_data) . "'";

$sql_pendentes .= " ORDER BY data_hora DESC";

$res_pendentes = $conn->query($sql_pendentes);

if (!$res_pendentes || $res_pendentes->num_rows === 0): ?>
  <tr class="empty-row"><td colspan="12" style="text-align:center;">Nenhum visitante aguardando saída.</td></tr>
<?php else: ?>
  <?php while ($rowp = $res_pendentes->fetch_assoc()): ?>
    <tr>
      <td class="oculto"><?= htmlspecialchars($rowp['id']) ?></td>
      <td><?= htmlspecialchars($rowp['nome']) ?></td>
      <td><?= htmlspecialchars($rowp['cpf']) ?></td>
      <td><?= htmlspecialchars($rowp['secao']) ?></td>
      <td><?= htmlspecialchars($rowp['ramal']) ?></td>
      <td><?= htmlspecialchars($rowp['autorizado']) ?></td>
      <td><?= htmlspecialchars($rowp['carro_entrou']) ?></td>
      <td><?= htmlspecialchars($rowp['placa']) ?></td>
      <td><?= htmlspecialchars($rowp['modelo']) ?></td>
      <td><?= htmlspecialchars($rowp['data_hora']) ?></td>
      <td>
        <a href="list.php?saida_id=<?= $rowp['id'] ?>" style="color:#ff3c3c; font-weight:bold;">Registrar Saída</a>
      </td>
    </tr>
  <?php endwhile; ?>
<?php endif; ?>

<?php $conn->close(); ?>
