
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,user-scalable=0" />

<title>DCTA - Controle de Visitantes</title>

<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Arial;
    overflow: hidden;
    background: radial-gradient(circle at top, #173e71, #0b1d3b);
}

#bgCanvas {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 0;
}

.container {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
}

.logo img {
    max-width: 180px;
    margin-bottom: 15px;
    filter: drop-shadow(0 0 10px rgba(0,150,255,0.5));
}

.form-card {
    backdrop-filter: blur(20px);
    background: rgba(10, 25, 50, 0.6);
    border-radius: 15px;
    padding: 30px;
    width: 400px;
    box-shadow: 0 0 40px rgba(0,150,255,0.2);
    border: 1px solid rgba(0,150,255,0.2);
    color: #e0f7ff;
}

.form-card h2 {
    text-align: center;
    margin-bottom: 20px;
}

input, select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    border: 1px solid rgba(0,150,255,0.3);
    background: rgba(255,255,255,0.05);
    color: #fff;
}

select option {
    color: #000;
    background: #fff;
}

button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(90deg, #00c6ff, #0072ff);
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.icea-button {
    position: fixed;
    top: 24px;
    left: 24px;
    z-index: 2;
    display: inline-block;
    width: auto;
    margin: 0;
    padding: 14px 22px;
    border: 1px solid rgba(0, 198, 255, 0.45);
    border-radius: 8px;
    background: rgba(10, 25, 50, 0.75);
    color: #e0f7ff;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    color: #e0f7ff;
    text-decoration: none;
    box-shadow: 0 0 18px rgba(0, 150, 255, 0.18);
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

@media (max-width: 600px) {
    .icea-button {
        top: 12px;
        left: 12px;
        padding: 11px 15px;
        font-size: 14px;
    }
}

.opcoes {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<canvas id="bgCanvas"></canvas>

<div class="container">

<div class="logo">
    <img src="assets/css/imagens/logotipodcta.png">
</div>

<a href="autorizados_icea.php" class="icea-button">Autorizados - ICEA</a>

<div class="form-card">

<h2>Cadastro de Visitantes</h2>

<form action="assets/process.php" method="POST" class="cadastro">

<label>Nome</label>
<input type="text" name="nome" required>

<label>CPF</label>
<input type="text" id="cpf" name="cpf" maxlength="14" required>

<label>Seção</label>
<input list="lista-secoes" name="secao" required>

<datalist id="lista-secoes">
<?php
$secoes = [
"AAJ","ACI","ACS","ACS FOTO","AIE-E","AJU","AJUR","AO-DOF","ARI","ASAA",
"ASC","ASEGVOO","ASSAE","CADM","CGC","CGI","CGOV","CGOV- GSGO",
"CGOV- GSGO-GCC","CGOV-GSGI","CGOV-GSGO","CGOV-GSGO-GCE",
"CGOV-GSGO-GCO","CGOV-GSGP","CGOV-SEC","COG","CSG","DAE","DAA","DCA",
"DCE","DCT","DDO","DESP","DGP","DIE-SDSG","DIP","DIR","DIREÇÃO","DOC",
"DOP","DP-SDPP-SPPM","DPI","DRH","DRH-SDAP-SMOB-SJ","ECGP",
"EPEP / GRADUADO MASTER","GAB-DCS","GAB","GCC","GAC-PAC",
"ID-ACI","ID-GOV","NCTI","SAD","SAL","SAU / SSA / SSAL","SCCO",
"SCC","SCE","SCI","SCI / SDM","SCI / SIN","SCP-HB","SCPL","SDA",
"SDA - CGOV","SDCM","SDEG","SDIN","SDM / SSIE","SDPC","SDPE",
"SDPM","SDSP","SDST","SDT","SEC CH","SECDA","SECDIR","SECDT",
"SECGAB","SECGAB // SRH","SECSDT","SECVD","SGD","SIM","SIN",
"SINST","SPI","SPT","SRH","SRP","SSA","SSAL","SSARQ","SSCI",
"SSCO","SSCP / SSPM","SSCT","SSPC","SSPF","SSPM","SSPROT",
"SSRAF","STA","STE","STI","VDCTA"
];

foreach ($secoes as $secao) {
    echo "<option value='$secao'>";
}
?>
</datalist>

<label>Ramal</label>
<input type="text" name="ramal" maxlength="4" required>

<label>Autorizado por</label>
<input list="lista-autorizados" name="autorizado" required>

<datalist id="lista-autorizados">
<?php
$autorizados = [
"Ten Brig BELLINTANI","Brig BREVIGLIERI","Brig BENITEZ","Brig SANTOPIETRO", 

"Cel MENCARINI","Cel CURSINO","Cel AHRENS","Cel ARTEMIO", "Cel GEORGE","Cel ANGELO","Cel MARCHETTI",
"Cel DE SA","Cel DENER","Cel DOMENICO", "Cel ROGERIO","Cel FABRICIO","Cel ANDRADE","Cel MOREIRA","Cel LESSA",
"Cel PETRACCONE", "Cel NILTON","Cel THAIS","Cel ALISSON","Cel CANTALUPPI","Cel P. ALVES","Cel BRENO",
"Cel BENITIZ", "Cel ZEDNIK","Cel MAURICIO","Cel DONEDA","Cel LUNA", "Cel AV CASTILHO","Cel COM PERIM FILHO", 
"Cel R/1 D.CORRÊA","Cel R/1 BARBOSA","Cel R/1 ANDRE CESAR","Cel R/1 MARCIO", "Cel R/1 ARAGÃO","Cel R/1 LUCCA",
"Cel R/1 GIOVANELLI","Cel R/1 MARCO WILLIAN", "Cel R/1 JUNZO","Cel R/1 KABZAS","Cel R/1 DINIZ","Cel R/1 GISLER", 
"Cel R/1 GILBERTO","Cel R/1 AFONSO","Cel R/1 BOTTURE","Cel R/1 SILVERIO", "Cel R/1 TOPINI","Cel R/1 SALVIATTO",
"Cel R/1 ESPÓSITO","Cel R/1 SORACLI",

"Ten Cel JULIO","Ten Cel JOSÉ MÁRCIO","Ten Cel MOREIRA","Ten Cel RENATO","Ten Cel TIAGO", "Ten Cel AV PRATESI",
"Ten Cel ENG GUIMARÃES","Ten Cel AV FONTES","Ten Cel AV VALNECK", "Ten Cel ENG MATTOS BRITO JUNIOR","Ten Cel INF BARBOSA",
 "Ten Cel R/1 FERNANDO","Ten Cel R/1 MAIA","Ten Cel R/1 AVANIR","Ten Cel R/1 ANTONIO PARENTE", 

"Maj CAPUCHINHO","Maj GUERREIRO","Maj SAKAJIRI","Maj LUCHINI","Maj BATISTELLI", "Maj LEANDRO","Maj OLIVEIRA","Maj ROCHA",
"Maj ODAGUIRI","Maj MÂNICA", "Maj AV CAPUCHINHO","Maj INT GONÇALVES", "Maj R/1 MARCOS LUIZ","Maj R/1 AMÉRICO",

"Cap PAIVA","Cap LUIZ CARLOS","Cap BRUNO","Cap AFFONSO","Cap JARDIM", "Cap INT PLEFFKEN","Cap INT VIEIRA","Cap INT SALVADOR",
"Cap ENG TRINDADE", "Cap QOEA COSTA FILHO","Cap QOEA SANTI","Cap QOEA MICHETTI","Cap QOEA PARADA", "Cap R/1 JOSENILDO",
"Cap R/1 TEREZINHA","Cap R/1 VLADMIR","Cap R/1 REINALALDO", "Cap R/1 MARCOS LOPES","Cap R/1 BUENO","Cap R/1 BARROS",
"Cap R/1 CRESPO", "Cap R/1 CAZOLARI","Cap R/1 GILVAN", 

"Ten RUAN","Ten FELÍCIO","Ten PRISCILLA","Ten LUANNY","Ten BILONIA","Ten GOTZ", "Ten KEILA ALVES","Ten RIBEIRO",
"Ten DE JESUS","Ten JOYCE","Ten EFRAIM","Ten LIMA", "Ten GABRIEL","Ten IANNI","Ten PATRÍCIA YANEZ","Ten RAFAELA FAUSTINO",
"Ten FRAGIOLLI", "Ten BRUNELLI","Ten PRISCILA SCARPARO","Ten ELEN","Ten CAROLINA REDLICH", "Ten BÁRBARA","Ten TAYSSA BRASILEIRO",
"Ten MARALYZA","Ten R/1 JOEL", "Ten CAMILA TRAVASSOS","Ten SATOSHI","Ten MARCELLY","Ten ALESSANDRA BORGES", "Ten DANIEL RANNA",
"Ten SILVERIO","Ten LANDIM","Ten MYRIELLE","Ten ROMEU", "Ten REGILENE","Ten PRUDENTE","Ten JÉSSICA REZENDE","Ten MARCELA",
"Ten ANALÍCIA", "Ten KEILA","Ten LÍGIA","Ten JOSÉ LUCAS","Ten WALTER","Ten TAUANA", "Ten GABRIELA AZEVEDO","Ten ANA NASCIMENTO",
"Ten AUGUSTO", " Ten CATIANA FARIA"," Ten MILITÃO"," Ten ROCHA"," Ten ROCHA QAO", " Ten PRADO"," Ten PRIANTE"," Ten FRANCO",
" Ten MACHADO HOMEM", " Ten BARBOSA"," Ten PROENÇA",

"SO MAGALHÃES","SO ADRIANA MEDEIROS","SO DANIEL","SO ANTONIO","SO ANDRADE", 
"SO GERALDO","SO BRUNO","SO ROBSON","SO MÁRCIO","SO BRANDÃO","SO L CAMPOS", "SO FELIX","SO SÉRGIO LUIZ","SO ELIOENAI","SO WILKERSON",
"SO ELEUTERIO", "SO TROGLIO","SO MENEZES","SO JONAS","SO PARREIRA","SO SOFIA","SO SHEILA", "SO BREGINSKI","SO BINO","SO FABIANO",
"SO ELIAS","SO CÁDIMO","SO C. TEIXEIRA", "SO MARTINO","SO LOBO","SO ESPADIM","SO CLEI","SO SOARES","SO ANDEILTON", "SO ESTRELA",
"SO ROCHA","SO CONDE RIBEIRO", "SO R/1 TARGA","SO R/1 JAILTON","SO R/1 BRUNO","SO R/1 NEHEMIAS", "SO R/1 HENRIQUE","SO R/1 LEITE",
"SO R/1 BATISTA","SO R/1 SCHLUCKEBIER", "SO R/1 MÁRCIO RICARDO","SO R/1 LUIZ","SO R/1 FRANCKLIM","SO R/1 LINARES",

"SGT RUBIM","SGT QUINTELA","SGT BEMFICA","SGT MOISES","SGT FONTENELE","SGT ADEMIR", "SGT TREVISAN","SGT BRASIL","SGT GISELE",
" SGT PEREIRA","SGT REANTO", "SGT FERNANDO","SGT BRUM","SGT AMADOR","SGT DOLFINI","SGT BRAZOLIN","SGT THALIS","SGT VILLA NOVA",
 "SGT NATHÁLIA DIAS", "SGT MIQUEIAS", "SGT BARBOSA","SGT ELAINE","SGT DJAN",
 
 "Cb LEANDRO","Cb ALEX","Cb MELO","Cb HIPOLITO", "Cb LACERDA","Cb CAIO", "Cb CORRÁ","Cb V. MOREIRA","Cb MESQUITA","Cb LEITE",
 "Cb DE MOURA", "Cb CHUVES","Cb MATIAS","Cb ELIAS", "Cb ACERBI","Cb CAMPOS","Cb MOURA", "Cb ALEXANDRE","Cb TRINDADE","Cb MANUEL",
 "Cb HUDSON","Cb LAWSON","Cb L. SILVA", "Cb CURSINO","Cb GONZALEZ","Cb BARRETO","Cb JUEX","Cb GUILHERME","Cb KELLEN", 
 "Cb RICHARD COSTA","Cb PRADO","Cb MATHEUS FERRAZ","Cb ALBUQUERQUE", "Cb RAMON","Cb ANDERSON","Cb BAZANINI","Cb ROBERTO",
 "Cb S. SOUZA", "Cb GOIOZO","Cb LUAN LEMES","Cb VICTOR","Cb TEIXEIRA","Cb HARLEY DAVIDSON", "Cb WILLIAM","Cb MELO","Cb CLAUDINEI",
 "Cb JONE CRUZ","Cb VINICIUS SANTOS", "Cb BERNARDO","S1 SANTOS VILELA","S1 RIAN VALE","S1 CELIO NETO", "S1 ENZO","S1 ERIK","S1 GUSMÃO",
 "S1 NASCIMENTO","S1 SILVÉRIO","S1 RODRIGO", "S1 JOAO AUGUSTO", "S2 CAIO SOUZA","S2 MARCONDES","S2 BRITO","S2 S.BERNARDO","S2 MIRLEY",
  "S2 HUGO MAGALHÃES","S2 ANDRÉ","S2 ANJOS","S2 DE ANDRADE", "S2 VIDAL","S2 DE SILVEIRA","S2 PEDRO","S2 CORDEIRO","S2 JOÃO PEDRO", 
  "S2 G. ROSAS","S2 REAICHE","S2 DE CORRÊA","S2 G. HENRIQUE","S2 SILVA FARIA", "S2 PAULO","S2 AMARO","S2 LUCAS SILVA","S2 CASSIANO",
  "S2 CAUÊ","S2 FARIA", "S2 ADLER","S2 JOÃO GABRIEL",
  
  
  "CV OSÓRIO","CV LUIZA","CV ADRIANA","CV ANA ZÉLIA","CV RAMALHO","CV BOLIS", "CV CARLOS","CV DARIO","CV TOMASSONI","CV EVALDO",
  "CV HUMBERTO","CV HEBER", "CV DONIZETTI","CV LÍGIA","CV LUIZ","CV MÁRCIO","CV MIGUEL","CV MILTON MOTA", "CV EDVALDO","CV FÁBIO",
  "CV GERALDO","CV ERNESTO","CV MARIO","CV PAULO", "CV RENATO","CV RENATO MUSSI","CV RITA","CV RODSON","CV SANDRA CRISTINA", 
  "CV STELA","CV TIAGO GABRIEL","CV VALDIR","CV MARCELO GUIDO","CV RUI", "CV JOÃO LUIZ", "CV JAIR","CV TITO","CV SANDRA",
  "CV FRANCISCO ASSIS", "CV AMANDA","CV ROBERTO","CV DILMAR","CV GILCINARA","CV GRAZIANE", "CV ROBLES","CV ACÁCIO","CV JOSUÉ",
  "CV PAULO TARSO","CV RODOLFO LUIZ", "CV DANIELA","CV BULIZANI","CV MARCIO","CV ANA LUCIA","CV RODOLFO CESAR", "CV FERNANDA",
  "CV KATIA XIMENE","CV SHIRLEY","CV NEY VENEZIANI","CV PAULA", "CV FRANCISCA","CV CARLOS ALBERTO","CV ALEXANDRE","CV CLAUDIA",
  "CV TALITA", "CV DEBORA","CV SUELI","CV SERGIO BUENO","CV VALDIR PEREIRA","CV PATRICIA", "CV LAÍS","CV MAGDA","CV HEGLAS",
  "CV CLAYTON"
];

foreach ($autorizados as $nome) {
    echo "<option value='$nome'>";
}
?>
</datalist>

<label>Carro vai entrar?</label>

<div class="opcoes">
<input type="radio" id="carro-sim" name="carro" value="sim" required>
<label for="carro-sim">Sim</label>

<input type="radio" id="carro-nao" name="carro" value="nao" required>
<label for="carro-nao">Não</label>
</div>

<div id="dados-carro" style="display:none;">
<label>Placa</label>
<input type="text" id="placa" name="placa">

<label>Modelo</label>
<input type="text" id="modelo" name="modelo">
</div>

<button type="submit">Cadastrar</button>

</form>

</div>
</div>

<script>

// CPF
document.getElementById("cpf").addEventListener("input", function(){
let v = this.value.replace(/\D/g,"");
v = v.replace(/(\d{3})(\d)/,"$1.$2");
v = v.replace(/(\d{3})(\d)/,"$1.$2");
v = v.replace(/(\d{3})(\d{1,2})$/,"$1-$2");
this.value = v;
});

// MAIÚSCULO
document.querySelectorAll("input").forEach(i=>{
i.addEventListener("input",()=>i.value=i.value.toUpperCase());
});

// CARRO
const sim = document.getElementById("carro-sim");
const nao = document.getElementById("carro-nao");

const box = document.getElementById("dados-carro");
const placa = document.getElementById("placa");
const modelo = document.getElementById("modelo");

sim.addEventListener("change", () => {
    box.style.display = "block";
    placa.setAttribute("required", "true");
    modelo.setAttribute("required", "true");
});

nao.addEventListener("change", () => {
    box.style.display = "none";
    placa.removeAttribute("required");
    modelo.removeAttribute("required");
});

// CANVAS
const canvas = document.getElementById("bgCanvas");
const ctx = canvas.getContext("2d");

let mouse = { x: null, y: null };

function resize(){
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
}
resize();

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
window.addEventListener("resize", resize);

</script>

</body>
</html>
