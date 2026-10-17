<?php
$processes = [
[1,'root','systemd','0.0','0.1','S'],[234,'root','kthreadd','0.0','0.0','S'],[456,'jordy','firefox','12.3','4.1','R'],
[789,'jordy','code','5.2','2.8','S'],[1023,'root','apache2','1.1','1.5','S'],[1900,'mysql','mysqld','0.6','3.2','S'],
[2011,'jordy','terminal','0.2','0.4','S'],[2044,'root','cron','0.0','0.1','S']
];
?>
<div class="toolbar panel"><label class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input id="processSearch" placeholder="Buscar proceso..."></label><button class="btn btn-primary" id="refreshProcesses"><i class="fa-solid fa-arrows-rotate"></i> Actualizar</button></div>
<div class="panel table-panel">
<div class="table-scroll"><table id="processTable"><thead><tr><th>PID</th><th>Usuario</th><th>Nombre</th><th>CPU %</th><th>Memoria %</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($processes as $p): ?><tr><td><?= $p[0] ?></td><td><?= e($p[1]) ?></td><td><?= e($p[2]) ?></td><td><?= e($p[3]) ?></td><td><?= e($p[4]) ?></td><td><span class="status-pill <?= $p[5]==='R'?'status-running':'' ?>"><?= e($p[5]) ?></span></td><td class="actions"><button class="icon-btn" title="Detalles" data-toast="Vista de detalles de <?= e($p[2]) ?>"><i class="fa-solid fa-magnifying-glass"></i></button><button class="icon-btn danger" title="Finalizar" data-toast="Acción de demostración: no se finalizó ningún proceso"><i class="fa-solid fa-trash"></i></button></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="table-footer"><span>Mostrando <b id="visibleCount">1 a 8</b> de 142 procesos</span><div class="pagination"><button>‹</button><button class="selected">1</button><button>2</button><button>3</button><button>4</button><button>5</button><button>›</button></div></div>
</div>
