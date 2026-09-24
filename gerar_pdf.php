<?php
include __DIR__ . "/db.php";
require __DIR__ . "/vendor/autoload.php";

use Dompdf\Dompdf;

// =====================
// VALIDAÇÃO
// =====================
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

if (!$data_inicio || !$data_fim) {
    exit("Informe data inicial e final.");
}

// =====================
// FORMATO MYSQL
// =====================
$inicio = date("Y-m-d H:i:s", strtotime($data_inicio));
$fim = date("Y-m-d H:i:s", strtotime($data_fim));

// =====================
// LOGO (CAMINHO ABSOLUTO SIMPLES)
// =====================
$logoPath = __DIR__ . "assets/css/imagens/logotipodcta.png";

// =====================
// CONSULTA
// =====================
$sql = "SELECT nome, cpf, secao, autorizado, placa, modelo, data_hora, data_saida
        FROM visitante
        WHERE data_hora BETWEEN '$inicio' AND '$fim'
        ORDER BY data_hora ASC";

$result = $conn->query($sql);

// =====================
// HTML
// =====================
$html = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>
body {
    font-family: Arial;
    font-size: 12px;
}

.header {
    text-align: center;
    margin-bottom: 20px;
}

.logo {
    width: 120px;
    margin-bottom: 10px;
}

h2 {
    margin: 0;
}

.periodo {
    margin-top: 5px;
    font-size: 11px;
    color: #000000;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th {
    background: #0073ff;
    color: #fff;
    padding: 6px;
}

td {
    border: 1px solid #000000;
    padding: 6px;
    text-align: center;
}

.pendente {
    color: red;
    font-weight: bold;
}
</style>

</head>

<body>

<div class="header">';

if (file_exists($logoPath)) {
    $html .= '<img class="logo" src="' . $logoPath . '">';
}

$html .= '
    <h2>RELATÓRIO DE VISITANTES</h2>
    <div class="periodo">
        Período: ' . $inicio . ' até ' . $fim . '
    </div>
</div>

<table>
<thead>
<tr>
    <th>Nome</th>
    <th>CPF</th>
    <th>Seção</th>
    <th>Autorizado</th>
    <th>Placa</th>
    <th>Modelo</th>
    <th>Entrada</th>
    <th>Saída</th>
</tr>
</thead>
<tbody>
';

// =====================
// DADOS
// =====================
if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $saida = $row['data_saida'] ? $row['data_saida'] : '<span class="pendente">Pendente</span>';

        $html .= "
        <tr>
            <td>{$row['nome']}</td>
            <td>{$row['cpf']}</td>
            <td>{$row['secao']}</td>
            <td>{$row['autorizado']}</td>
            <td>{$row['placa']}</td>
            <td>{$row['modelo']}</td>
            <td>{$row['data_hora']}</td>
            <td>{$saida}</td>
        </tr>";
    }

} else {
    $html .= "<tr><td colspan='8'>Nenhum registro encontrado</td></tr>";
}

$html .= '
</tbody>
</table>

</body>
</html>
';

// =====================
// PDF
// =====================
$dompdf = new Dompdf();

// 🔥 IMPORTANTE: permite carregar imagens locais
$options = $dompdf->getOptions();
$options->set(['isRemoteEnabled' => true]);
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream("relatorio_visitantes.pdf", ["Attachment" => false]);
exit;