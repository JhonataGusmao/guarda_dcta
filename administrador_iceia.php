<?php
session_start();
require __DIR__ . '/icea_admin_config.php';
if (isset($_GET['sair'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: login_seg.php');
    exit;
}
if (empty($_SESSION['icea_admin_authenticated'])) {
    header('Location: login_seg.php');
    exit;
}
include __DIR__ . '/db.php';
$erro = '';
$aviso = '';
$resultado = $_GET['resultado'] ?? '';
$importacao = $_SESSION['icea_admin_flash'] ?? '';
unset($_SESSION['icea_admin_flash']);

function dataValidaIcea($valor) {
    $data = DateTime::createFromFormat('!Y-m-d', $valor);
    return $data && $data->format('Y-m-d') === $valor;
}

function dataPlanilhaIcea($valor) {
    $valor = trim($valor);
    foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y'] as $formato) {
        $data = DateTime::createFromFormat($formato, $valor);
        $erros = DateTime::getLastErrors();
        if ($data && ($erros === false || ($erros['warning_count'] === 0 && $erros['error_count'] === 0))) {
            return $data->format('Y-m-d');
        }
    }
    if (is_numeric($valor) && (float)$valor > 20000 && (float)$valor < 80000) {
        return gmdate('Y-m-d', (int)round(((float)$valor - 25569) * 86400));
    }
    return false;
}

function cabecalhoPlanilhaIcea($valor) {
    $valor = preg_replace('/^\xEF\xBB\xBF/', '', trim($valor));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($valor));
    return strtolower(preg_replace('/[^a-z0-9]/', '', $ascii === false ? $valor : $ascii));
}

function indicesPlanilhaIcea(array $cabecalhos) {
    $aliases = [
        'posto' => ['postograduacao', 'posto', 'graduacao', 'patente'],
        'nome' => ['nomedeguerra', 'nomeguerra', 'nome', 'nomecompleto', 'militar'],
        'cpf' => ['cpf', 'numerocpf'],
        'om' => ['omorigem', 'om', 'organizacaomilitar', 'unidade', 'origem'],
        'inicio' => ['datainicio', 'inicio', 'inicioacesso', 'periodoinicio', 'vigenciainicio'],
        'fim' => ['datafim', 'fim', 'fimacesso', 'periodofim', 'vigenciafim'],
        'periodo' => ['periodo', 'periododeacesso', 'periodoacesso', 'vigencia']
    ];
    $indices = [];
    foreach ($cabecalhos as $i => $cabecalho) {
        foreach ($aliases as $campo => $opcoes) {
            if (in_array($cabecalho, $opcoes, true) && !isset($indices[$campo])) {
                $indices[$campo] = $i;
            }
        }
    }
    return $indices;
}

function datasPeriodoPlanilhaIcea($valor) {
    preg_match_all('/(?:\d{4}-\d{1,2}-\d{1,2}|\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}|\d{5})/', trim($valor), $encontradas);
    if (count($encontradas[0]) < 2) return [false, false];
    return [dataPlanilhaIcea($encontradas[0][0]), dataPlanilhaIcea($encontradas[0][1])];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'reativar') {
        $pessoaId = filter_input(INPUT_POST, 'autorizado_id', FILTER_VALIDATE_INT);
        if (!$pessoaId) {
            $erro = 'Pessoa inválida.';
        } else {
            $stmt = $conn->prepare('UPDATE icea_autorizado SET excluido_em = NULL WHERE id = ? AND excluido_em IS NOT NULL');
            $stmt->bind_param('i', $pessoaId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                header('Location: administrador_iceia.php?resultado=reativado');
                exit;
            }
            $stmt->close();
            $erro = 'Não foi possível reativar a pessoa.';
        }
    } elseif ($acao === 'excluir') {
        $pessoaId = filter_input(INPUT_POST, 'autorizado_id', FILTER_VALIDATE_INT);
        if (!$pessoaId) {
            $erro = 'Pessoa inválida.';
        } else {
            $stmt = $conn->prepare('UPDATE icea_autorizado SET excluido_em = NOW() WHERE id = ? AND excluido_em IS NULL');
            $stmt->bind_param('i', $pessoaId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();
                header('Location: administrador_iceia.php?resultado=excluido');
                exit;
            }
            $stmt->close();
            $erro = 'Não foi possível excluir a pessoa.';
        }
    } elseif ($acao === 'importar_planilha') {
        $arquivo = $_FILES['planilha'] ?? null;
        if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name']) || strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION)) !== 'csv' || $arquivo['size'] > 3145728) {
            $erro = 'Selecione um arquivo CSV válido de até 3 MB.';
        } else {
            $handle = fopen($arquivo['tmp_name'], 'rb');
            if (!$handle) {
                $erro = 'Não foi possível ler a planilha enviada.';
            } else {
                $primeiraLinha = fgets($handle);
                $delimitador = substr_count($primeiraLinha ?: '', ';') >= substr_count($primeiraLinha ?: '', ',') ? ';' : ',';
                $cabecalhos = array_map('cabecalhoPlanilhaIcea', str_getcsv($primeiraLinha ?: '', $delimitador, '"', '\\'));
                $indices = indicesPlanilhaIcea($cabecalhos);
                $camposObrigatorios = ['posto', 'nome', 'cpf', 'om'];
                $faltamCampos = array_diff($camposObrigatorios, array_keys($indices));
                $temInicioFim = isset($indices['inicio'], $indices['fim']);
                $temPeriodo = isset($indices['periodo']);
                if ($faltamCampos || (!$temInicioFim && !$temPeriodo)) {
                    $erro = 'Não identifiquei todas as colunas. A planilha precisa ter Posto/Graduação, Nome de Guerra, CPF, OM de Origem e Período de Acesso (ou datas de início e fim).';
                } else {
                    $incluidos = 0;
                    $duplicados = 0;
                    $invalidos = 0;
                    try {
                        $conn->begin_transaction();
                        while (($linha = fgetcsv($handle, 0, $delimitador, '"', '\\')) !== false) {
                            if (count($linha) === 1 && trim($linha[0]) === '') continue;
                            $posto = trim($linha[$indices['posto']] ?? '');
                            $nome = trim($linha[$indices['nome']] ?? '');
                            $cpf = trim($linha[$indices['cpf']] ?? '');
                            $om = trim($linha[$indices['om']] ?? '');
                            if ($temInicioFim) {
                                $inicio = dataPlanilhaIcea($linha[$indices['inicio']] ?? '');
                                $fim = dataPlanilhaIcea($linha[$indices['fim']] ?? '');
                            } else {
                                [$inicio, $fim] = datasPeriodoPlanilhaIcea($linha[$indices['periodo']] ?? '');
                            }
                            if ($posto === '' || $nome === '' || $cpf === '' || $om === '' || !$inicio || !$fim || $fim < $inicio) {
                                $invalidos++;
                                continue;
                            }
                            $stmt = $conn->prepare('SELECT id FROM icea_autorizado WHERE cpf = ?');
                            $stmt->bind_param('s', $cpf);
                            $stmt->execute();
                            $existe = $stmt->get_result()->fetch_assoc();
                            $stmt->close();
                            if ($existe) {
                                $duplicados++;
                                continue;
                            }
                            $stmt = $conn->prepare('INSERT INTO icea_autorizado (posto_graduacao, nome_guerra, cpf, om_origem) VALUES (?, ?, ?, ?)');
                            $stmt->bind_param('ssss', $posto, $nome, $cpf, $om);
                            $stmt->execute();
                            $pessoaId = $conn->insert_id;
                            $stmt->close();
                            $stmt = $conn->prepare('INSERT INTO icea_periodo_acesso (autorizado_id, data_inicio, data_fim) VALUES (?, ?, ?)');
                            $stmt->bind_param('iss', $pessoaId, $inicio, $fim);
                            $stmt->execute();
                            $stmt->close();
                            $incluidos++;
                        }
                        $conn->commit();
                        $_SESSION['icea_admin_flash'] = "Importação concluída: $incluidos incluídos, $duplicados CPFs já cadastrados e $invalidos linhas inválidas ignoradas.";
                        header('Location: administrador_iceia.php');
                        exit;
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $erro = 'A importação falhou e nenhuma linha foi gravada. Confira o arquivo e tente novamente.';
                    }
                }
                fclose($handle);
            }
        }
    } else {
        $inicio = trim($_POST['data_inicio'] ?? '');
        $fim = trim($_POST['data_fim'] ?? '');
        if (!dataValidaIcea($inicio) || !dataValidaIcea($fim) || $fim < $inicio) {
            $erro = 'Informe um período válido. A data final deve ser igual ou posterior à inicial.';
        } elseif ($acao === 'cadastrar') {
            $posto = trim($_POST['posto_graduacao'] ?? '');
            $nome = trim($_POST['nome_guerra'] ?? '');
            $cpf = trim($_POST['cpf'] ?? '');
            $om = trim($_POST['om_origem'] ?? '');
            if ($posto === '' || $nome === '' || $cpf === '' || $om === '') {
                $erro = 'Preencha todos os dados da pessoa.';
            } else {
                $stmt = $conn->prepare('SELECT id FROM icea_autorizado WHERE cpf = ?');
                $stmt->bind_param('s', $cpf);
                $stmt->execute();
                $existe = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($existe) {
                    $erro = 'Esse CPF já está cadastrado. Use o formulário de novo período na linha da pessoa.';
                } else {
                    try {
                        $conn->begin_transaction();
                        $stmt = $conn->prepare('INSERT INTO icea_autorizado (posto_graduacao, nome_guerra, cpf, om_origem) VALUES (?, ?, ?, ?)');
                        $stmt->bind_param('ssss', $posto, $nome, $cpf, $om);
                        $stmt->execute();
                        $pessoaId = $conn->insert_id;
                        $stmt->close();
                        $stmt = $conn->prepare('INSERT INTO icea_periodo_acesso (autorizado_id, data_inicio, data_fim) VALUES (?, ?, ?)');
                        $stmt->bind_param('iss', $pessoaId, $inicio, $fim);
                        $stmt->execute();
                        $stmt->close();
                        $conn->commit();
                        $codigoResultado = $inicio > date('Y-m-d') ? 'cadastro_agendado' : 'cadastrado';
                        header('Location: administrador_iceia.php?resultado=' . $codigoResultado);
                        exit;
                    } catch (Throwable $e) {
                        $conn->rollback();
                        $erro = 'Não foi possível salvar o cadastro. Verifique se a migração do banco foi executada.';
                    }
                }
            }
        } elseif ($acao === 'renovar') {
            $pessoaId = filter_input(INPUT_POST, 'autorizado_id', FILTER_VALIDATE_INT);
            if (!$pessoaId) {
                $erro = 'Pessoa inválida.';
            } else {
                $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM icea_periodo_acesso WHERE autorizado_id = ? AND data_inicio <= ? AND data_fim >= ?');
                $stmt->bind_param('iss', $pessoaId, $fim, $inicio);
                $stmt->execute();
                $sobreposicao = (int)$stmt->get_result()->fetch_assoc()['total'] > 0;
                $stmt->close();
                if ($sobreposicao) {
                    $aviso = 'Este período se sobrepõe a outro já cadastrado para a pessoa. Ajuste as datas antes de salvar.';
                } else {
                    $stmt = $conn->prepare('INSERT INTO icea_periodo_acesso (autorizado_id, data_inicio, data_fim) VALUES (?, ?, ?)');
                    $stmt->bind_param('iss', $pessoaId, $inicio, $fim);
                    if ($stmt->execute()) {
                        $stmt->close();
                        $codigoResultado = $inicio > date('Y-m-d') ? 'periodo_agendado' : 'periodo_salvo';
                        header('Location: administrador_iceia.php?resultado=' . $codigoResultado);
                        exit;
                    }
                    $stmt->close();
                    $erro = 'Não foi possível registrar o novo período.';
                }
            }
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
if (dataValidaIcea($filtros['data'])) {
    $dataFiltro = $conn->real_escape_string($filtros['data']);
    $condicoes[] = "p.data_inicio <= '$dataFiltro' AND p.data_fim >= '$dataFiltro'";
}
$whereFiltros = $condicoes ? ' AND ' . implode(' AND ', $condicoes) : '';

$porPagina = 10;
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$sqlTotal = "SELECT COUNT(*) AS total FROM icea_autorizado a
             LEFT JOIN icea_periodo_acesso p ON p.id = (
                 SELECT p2.id FROM icea_periodo_acesso p2
                 WHERE p2.autorizado_id = a.id ORDER BY p2.id DESC LIMIT 1
             ) WHERE 1=1 $whereFiltros";
$totalRegistros = (int)($conn->query($sqlTotal)->fetch_assoc()['total'] ?? 0);
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
$paginaAtual = min($paginaAtual, $totalPaginas);
$offset = ($paginaAtual - 1) * $porPagina;

$sql = "SELECT a.id, a.posto_graduacao, a.nome_guerra, a.cpf, a.om_origem, a.excluido_em,
               p.data_inicio, p.data_fim,
               CASE WHEN a.excluido_em IS NOT NULL THEN 'Vencido'
                    WHEN p.id IS NULL THEN 'Vencido'
                    WHEN CURRENT_DATE < p.data_inicio THEN 'Agendado'
                    WHEN CURRENT_DATE <= p.data_fim THEN 'Autorizado'
                    ELSE 'Vencido' END AS situacao
        FROM icea_autorizado a
        LEFT JOIN icea_periodo_acesso p ON p.id = (
            SELECT p2.id FROM icea_periodo_acesso p2
            WHERE p2.autorizado_id = a.id
            ORDER BY p2.id DESC LIMIT 1
        )
        WHERE 1=1 $whereFiltros
        ORDER BY a.nome_guerra LIMIT $porPagina OFFSET $offset";
$pessoas = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administrador ICEA | DCTA</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #e0f7ff;
    background: radial-gradient(circle at top, #173e71, #0b1d3b);
}
#bgCanvas { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
.page {
    position: relative;
    z-index: 1;
    width: 100%;
    min-height: 100vh;
    padding: 24px 12px;
}
header img {
    display: block;
    max-width: 180px;
    margin: 0 auto 10px;
    filter: drop-shadow(0 0 10px rgba(0, 150, 255, 0.5));
}
h1 { margin: 8px 0 22px; text-align: center; }
.panel {
    width: 100%;
    margin-bottom: 22px;
    padding: 16px;
    border: 1px solid rgba(0, 150, 255, 0.2);
    border-radius: 10px;
    background: rgba(10, 25, 50, 0.6);
}
.panel h2 { margin: 0 0 14px; text-align: center; }
.fields { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
.fields label { display: flex; flex-direction: column; gap: 5px; }
.filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: center; gap: 10px; margin: 0 0 18px; padding: 14px; border: 1px solid rgba(0, 150, 255, 0.2); border-radius: 10px; background: rgba(10, 25, 50, 0.6); }
.filter-title { flex-basis: 100%; margin: 0 0 4px; text-align: center; color: #e0f7ff; }
.filter-form label { display: flex; flex-direction: column; gap: 5px; }
.filter-form a { padding: 9px 11px; color: #e0f7ff; font-weight: bold; text-decoration: none; }
input, button { padding: 9px 11px; border: 1px solid rgba(0, 150, 255, 0.35); border-radius: 7px; }
input { color: #fff; background: rgba(255, 255, 255, 0.07); }
button { color: white; background: linear-gradient(90deg, #00c6ff, #0072ff); font-weight: bold; cursor: pointer; }
.notice { margin: 0 auto 14px; padding: 10px; border-radius: 7px; text-align: center; background: rgba(0, 120, 80, 0.35); opacity: 1; transition: opacity 700ms ease; }
.error { background: rgba(150, 30, 30, 0.4); }
.period-modal {
    width: min(440px, calc(100% - 28px));
    padding: 22px;
    border: 1px solid rgba(0, 198, 255, 0.4);
    border-radius: 14px;
    color: #e0f7ff;
    background: #10294a;
    box-shadow: 0 0 30px rgba(0, 150, 255, 0.28);
}
.period-modal::backdrop { background: rgba(3, 10, 24, 0.72); backdrop-filter: blur(4px); }
.period-modal h2 { margin: 0 0 8px; text-align: center; }
.period-modal p { margin: 0 0 16px; text-align: center; }
.period-fields { display: grid; gap: 12px; }
.period-fields label { display: grid; gap: 5px; }
.period-fields input { width: 100%; }
.period-actions { display: flex; justify-content: center; gap: 10px; margin-top: 18px; }
.period-actions button[type="button"] { background: rgba(255, 255, 255, 0.12); }
.back-button {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 2;
    padding: 12px 18px;
    border: 1px solid rgba(0, 198, 255, 0.45);
    border-radius: 8px;
    background: rgba(10, 25, 50, 0.8);
    color: #e0f7ff;
    font-weight: bold;
    text-decoration: none;
}
.logout-button {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 2;
    padding: 12px 18px;
    border: 1px solid rgba(255, 120, 120, 0.5);
    border-radius: 8px;
    color: #fff;
    background: rgba(100, 25, 30, 0.8);
    font-weight: bold;
    text-decoration: none;
}
.reactivate-button { border: 2px solid #8ee6ae; color: #fff; background: #176b42; }
.reactivate-button:hover { background: #218653; }
.status-badge { display:inline-block; padding:5px 10px; border-radius:999px; font-weight:700; white-space:nowrap; }
.status-agendado { color:#fff1bd; background:#806315; }
.status-autorizado { color:#d9ffe8; background:#176b42; }
.status-vencido { color:#ffe0e0; background:#812d35; }
.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid rgba(0, 150, 255, 0.2);
    border-radius: 10px;
    background: rgba(10, 25, 50, 0.6);
    backdrop-filter: blur(15px);
}
table { width: 100%; min-width: 760px; border-collapse: collapse; }
th, td { padding: 12px 10px; border-bottom: 1px solid rgba(0, 150, 255, 0.2); text-align: center; }
th { background: rgba(0, 150, 255, 0.2); }
tbody tr:nth-child(even) { background: rgba(255, 255, 255, 0.03); }
.empty-row td { padding: 24px; color: rgba(224, 247, 255, 0.8); }
 .pagination { display:flex; justify-content:center; align-items:center; gap:12px; flex-wrap:wrap; padding:16px; }
 .pagination a, .pagination span, .pagination select { color:#e0f7ff; padding:8px 12px; border:1px solid rgba(0,150,255,.35); border-radius:7px; background:#10294a; text-decoration:none; }
 .import-form { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:12px; }
 .notice-warning { background:rgba(160,105,0,.35); border:1px solid rgba(255,200,50,.35); }
@media (max-width: 760px) {
    .table-wrap { overflow:visible; border:0; background:transparent; backdrop-filter:none; }
    table { min-width:0; }
    thead { display:none; }
    tbody, tr, td { display:block; width:100%; }
    tbody tr { margin-bottom:12px; padding:6px 12px; border:1px solid rgba(0,150,255,.2); border-radius:10px; background:rgba(10,25,50,.75)!important; }
    td { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; padding:9px 0; text-align:right; overflow-wrap:anywhere; }
    td::before { content:attr(data-label); flex:0 0 42%; color:#9edff5; font-weight:bold; text-align:left; }
    .empty-row td { display:block; text-align:center; }
    .empty-row td::before { content:none; }
    td form { margin-left:auto; }
    .fields label, .filter-form label { width:100%; }
    .fields input, .filter-form input { width:100%; }
    .filter-form { align-items:stretch; }
    .filter-form button, .filter-form a { text-align:center; }
    .back-button, .logout-button { position:absolute; top:12px; padding:9px 10px; font-size:12px; }
    .back-button { left:8px; } .logout-button { right:8px; }
    .page { padding:62px 8px 16px; }
}
@media (min-width:761px) and (max-width:1024px) {
    .page { padding:72px 14px 18px; }
    .fields { justify-content:flex-start; }
}
@media (max-width: 600px) {
    .back-button { top: 12px; left: 12px; padding: 10px 12px; font-size: 13px; }
    .logout-button { top: 12px; right: 12px; padding: 10px 12px; font-size: 13px; }
    .page { padding: 64px 8px 16px; }
}
</style>
</head>
<body>
<canvas id="bgCanvas"></canvas>
<a class="back-button" href="autorizados_icea.php">Ir para Autorizados ICEIA</a>
<a class="logout-button" href="administrador_iceia.php?sair=1">Sair</a>
<main class="page">
    <header><img src="assets/css/imagens/logotipodcta.png" alt="Logo do DCTA"></header>
    <h1>Administrador ICEA | DCTA</h1>
    <?php if ($erro !== ''): ?><div class="notice error"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if (in_array($resultado, ['cadastrado', 'periodo_salvo', 'excluido', 'reativado', 'cadastro_agendado', 'periodo_agendado'], true)): ?>
        <div class="notice" id="success-notice"><?= match ($resultado) { 'cadastrado' => 'Cadastro Realizado com sucesso!', 'cadastro_agendado' => 'Cadastro realizado. A autorização ficará disponível na data inicial.', 'periodo_agendado' => 'Período agendado e ficará disponível na data inicial.', 'excluido' => 'Pessoa excluída da lista de operação.', 'reativado' => 'Pessoa reativada.', default => 'Novo período registrado com sucesso!' } ?></div>
    <?php endif; ?>
    <?php if ($importacao !== ''): ?><div class="notice" id="success-notice"><?= htmlspecialchars($importacao, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($aviso !== ''): ?><div class="notice notice-warning"><?= htmlspecialchars($aviso, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <section class="panel">
        <h2>Cadastrar autorizado</h2>
        <form method="post" class="fields">
            <input type="hidden" name="acao" value="cadastrar">
            <label>Posto/Graduação<input name="posto_graduacao" maxlength="60" required></label>
            <label>Nome de Guerra<input name="nome_guerra" maxlength="100" required></label>
            <label>CPF<input name="cpf" maxlength="14" required></label>
            <label>OM de Origem<input name="om_origem" maxlength="100" required></label>
            <label>Início do acesso<input type="date" name="data_inicio" required></label>
            <label>Fim do acesso<input type="date" name="data_fim" required></label>
            <button type="submit" style="align-self:flex-end">Cadastrar</button>
        </form>
    </section>
    <section class="panel">
        <h2>Importar autorizados por planilha</h2>
        <p style="text-align:center"><a href="modelo_autorizados_icea.csv" download style="color:#8ee6ff">Baixar modelo de planilha</a></p>
        <form method="post" enctype="multipart/form-data" class="import-form">
            <input type="hidden" name="acao" value="importar_planilha">
            <input type="file" name="planilha" accept=".csv,text/csv" required>
            <button type="submit">Importar planilha</button>
        </form>
    </section>
    <form method="get" class="filter-form">
        <h2 class="filter-title">Pesquisa</h2>
        <label>Posto/Graduação<input name="posto" value="<?= htmlspecialchars($filtros['posto'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Nome de Guerra<input name="nome" value="<?= htmlspecialchars($filtros['nome'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>CPF<input name="cpf" value="<?= htmlspecialchars($filtros['cpf'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>OM de Origem<input name="om" value="<?= htmlspecialchars($filtros['om'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <label>Data dentro do período<input type="date" name="data" value="<?= htmlspecialchars($filtros['data'], ENT_QUOTES, 'UTF-8') ?>"></label>
        <button type="submit">Pesquisar</button>
        <a href="administrador_iceia.php">Limpar</a>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">Posto/Graduação</th>
                    <th scope="col">Nome de Guerra</th>
                    <th scope="col">CPF</th>
                    <th scope="col">OM de Origem</th>
                    <th scope="col">Período de Acesso</th>
                    <th scope="col">Status</th>
                    <th scope="col">Novo período</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pessoas || $pessoas->num_rows === 0): ?>
                    <tr class="empty-row"><td colspan="8">Nenhum autorizado cadastrado.</td></tr>
                <?php else: ?>
                    <?php while ($pessoa = $pessoas->fetch_assoc()): ?>
                        <tr>
                            <td data-label="Posto/Graduação"><?= htmlspecialchars($pessoa['posto_graduacao'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Nome de Guerra"><?= htmlspecialchars($pessoa['nome_guerra'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="CPF"><?= htmlspecialchars($pessoa['cpf'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="OM de Origem"><?= htmlspecialchars($pessoa['om_origem'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td data-label="Período de Acesso"><?= $pessoa['data_inicio'] ? date('d/m/Y', strtotime($pessoa['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($pessoa['data_fim'])) : '—' ?></td>
                            <td data-label="Status"><span class="status-badge status-<?= strtolower($pessoa['situacao']) ?>"><?= htmlspecialchars($pessoa['situacao'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td data-label="Novo período">
                                <?php if ($pessoa['excluido_em'] === null): ?>
                                    <button type="button" class="open-period-modal" data-id="<?= (int)$pessoa['id'] ?>" data-nome="<?= htmlspecialchars($pessoa['posto_graduacao'] . ' ' . $pessoa['nome_guerra'], ENT_QUOTES, 'UTF-8') ?>">Adicionar período</button>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td data-label="Ações">
                                <?php if ($pessoa['excluido_em'] === null): ?>
                                    <form method="post" onsubmit="return confirm('Deseja excluir esta pessoa da lista de operação? O histórico será preservado.');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="autorizado_id" value="<?= (int)$pessoa['id'] ?>">
                                        <button type="submit" style="background:#b83232">Excluir</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" onsubmit="return confirm('Deseja reativar esta pessoa?');">
                                        <input type="hidden" name="acao" value="reativar">
                                        <input type="hidden" name="autorizado_id" value="<?= (int)$pessoa['id'] ?>">
                                        <button type="submit" class="reactivate-button">Reativar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav class="pagination" aria-label="Paginação">
        <?php if ($paginaAtual > 1): ?><a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual - 1])) ?>">Anterior</a><?php endif; ?>
        <form method="get" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center">
            <?php foreach ($filtros as $chave => $valor): ?><input type="hidden" name="<?= htmlspecialchars($chave, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>"><?php endforeach; ?>
            <label for="pagina-atual">Página</label>
            <select id="pagina-atual" name="pagina" onchange="this.form.submit()" aria-label="Escolher página">
                <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?><option value="<?= $pagina ?>" <?= $pagina === $paginaAtual ? 'selected' : '' ?>><?= $pagina ?></option><?php endfor; ?>
            </select>
            <span>de <?= $totalPaginas ?> (<?= $totalRegistros ?> registros)</span>
        </form>
        <?php if ($paginaAtual < $totalPaginas): ?><a href="?<?= http_build_query(array_merge($filtros, ['pagina' => $paginaAtual + 1])) ?>">Próxima</a><?php endif; ?>
    </nav>
</main>
<dialog class="period-modal" id="period-modal">
    <h2>Novo período de acesso</h2>
    <p id="period-person-name"></p>
    <form method="post" id="period-form">
        <input type="hidden" name="acao" value="renovar">
        <input type="hidden" name="autorizado_id" id="period-authorized-id">
        <div class="period-fields">
            <label>Início do acesso<input type="date" name="data_inicio" required></label>
            <label>Fim do acesso<input type="date" name="data_fim" required></label>
        </div>
        <div class="period-actions">
            <button type="button" id="close-period-modal">Cancelar</button>
            <button type="submit">Salvar período</button>
        </div>
    </form>
</dialog>
<script>
const canvas = document.getElementById('bgCanvas');
const ctx = canvas.getContext('2d');
let particles = [];
let mouse = { x: null, y: null };
function resize() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    particles = Array.from({ length: 100 }, () => ({
        x: Math.random() * canvas.width, y: Math.random() * canvas.height,
        vx: Math.random() - 0.5, vy: Math.random() - 0.5
    }));
}
function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
        if (mouse.x !== null) {
            const dx = mouse.x - p.x, dy = mouse.y - p.y;
            if (Math.hypot(dx, dy) < 150) { p.x += dx * 0.02; p.y += dy * 0.02; }
        }
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
        ctx.beginPath(); ctx.arc(p.x, p.y, 2, 0, Math.PI * 2);
        ctx.fillStyle = '#00d4ff'; ctx.fill();
    });
    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            if (Math.hypot(particles[i].x - particles[j].x, particles[i].y - particles[j].y) < 120) {
                ctx.beginPath(); ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = 'rgba(0, 212, 255, 0.1)'; ctx.stroke();
            }
        }
    }
    requestAnimationFrame(draw);
}
resize(); draw();
window.addEventListener('resize', resize);
window.addEventListener('mousemove', e => { mouse.x = e.clientX; mouse.y = e.clientY; });
const successNotice = document.getElementById('success-notice');
if (successNotice) {
    window.setTimeout(() => {
        successNotice.style.opacity = '0';
        window.setTimeout(() => successNotice.remove(), 750);
    }, 3000);
}
const periodModal = document.getElementById('period-modal');
const periodForm = document.getElementById('period-form');
document.querySelectorAll('.open-period-modal').forEach(button => {
    button.addEventListener('click', () => {
        periodForm.reset();
        document.getElementById('period-authorized-id').value = button.dataset.id;
        document.getElementById('period-person-name').textContent = button.dataset.nome;
        periodModal.showModal();
    });
});
document.getElementById('close-period-modal').addEventListener('click', () => periodModal.close());
periodModal.addEventListener('click', event => {
    if (event.target === periodModal) periodModal.close();
});
</script>
<?php $conn->close(); ?>
</body>
</html>
