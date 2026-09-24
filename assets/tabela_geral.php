<?php
include __DIR__ . "/db.php";

// Recebe filtros (vindos via AJAX)
$filtro_nome = $_GET['nome'] ?? '';
$filtro_cpf = $_GET['cpf'] ?? '';
$filtro_secao = $_GET['secao'] ?? '';
$filtro_autorizado = $_GET['autorizado'] ?? '';
$filtro_data = $_GET['data'] ?? '';
$filtro_carro = $_GET['carro'] ?? '';
$filtro_placa = $_GET['placa'] ?? '';
$filtro_modelo = $_GET['modelo'] ?? '';

// Monta a query base
$sql = "SELECT id, nome, cpf, secao, ramal, autorizado, carro_entrou, placa, modelo, data_hora, data_saida
        FROM visitante
        WHERE 1=1";

// Aplica filtros dinamicamente
if ($filtro_nome !== '') $sql .= " AND nome LIKE '%" . $conn->real_escape_string($filtro_nome) . "%'";
if ($filtro_cpf !== '') $sql .= " AND cpf LIKE '%" . $conn->real_escape_string($filtro_cpf) . "%'";
if ($filtro_secao !== '') $sql .= " AND secao LIKE '%" . $conn->real_escape_string($filtro_secao) . "%'";
if ($filtro_autorizado !== '') $sql .= " AND autorizado LIKE '%" . $conn->real_escape_string($filtro_autorizado) . "%'";
if ($filtro_data !== '') $sql .= " AND DATE(data_hora) = '" . $conn->real_escape_string($filtro_data) . "'";
if ($filtro_carro === 'sim') $sql .= " AND carro_entrou = 'sim'";
elseif ($filtro_carro === 'nao') $sql .= " AND (carro_entrou IS NULL OR carro_entrou = 'nao')";
if ($filtro_placa !== '') $sql .= " AND placa LIKE '%" . $conn->real_escape_string($filtro_placa) . "%'";
if ($filtro_modelo !== '') $sql .= " AND modelo LIKE '%" . $conn->real_escape_string($filtro_modelo) . "%'";

$sql .= " ORDER BY data_hora DESC";

$result = $conn->query($sql);

if (!$result || $result->num_rows === 0): ?>
<tr class="empty-row">
  <td colspan="12" style="text-align:center;">Nenhum registro encontrado.</td>
</tr>
<?php else: ?>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
  <td class="oculto"><?= htmlspecialchars($row['id']) ?></td>
  <td><?= htmlspecialchars($row['nome']) ?></td>
  <td><?= htmlspecialchars($row['cpf']) ?></td>
  <td><?= htmlspecialchars($row['secao']) ?></td>
  <td><?= htmlspecialchars($row['ramal']) ?></td>
  <td><?= htmlspecialchars($row['autorizado']) ?></td>
  <td><?= htmlspecialchars($row['carro_entrou']) ?></td>
  <td><?= htmlspecialchars($row['placa']) ?></td>
  <td><?= htmlspecialchars($row['modelo']) ?></td>
  <td><?= htmlspecialchars($row['data_hora']) ?></td>
  <td><?= $row['data_saida'] ? htmlspecialchars($row['data_saida']) : '<em>Pendente</em>' ?></td>
  <td>
    <?php if (empty($row['data_saida'])): ?>
      <a href="list.php?saida_id=<?= $row['id'] ?>" style="color:#ff3c3c; font-weight:bold;">Registrar Saída</a>
    <?php else: ?>
      ✔
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
<?php endif; ?>

<?php $conn->close(); ?>
