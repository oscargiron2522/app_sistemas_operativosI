<section class="planning-top">
<article class="panel configuration">
<h2>Configuración</h2>
<label>Algoritmo<select id="algorithm"><option>FCFS (First Come First Served)</option><option>SJF (Shortest Job First)</option><option>Round Robin (RR)</option><option>Prioridad</option></select></label>
<label>Quantum (solo RR)<input type="number" id="quantum" value="2" min="1"></label>
<div class="button-row"><button class="btn btn-primary" id="addProcess"><i class="fa-solid fa-plus"></i> Agregar proceso</button><button class="btn btn-muted" id="clearProcesses">Limpiar</button></div>
</article>
<article class="panel"><h2>Procesos</h2><div class="table-scroll"><table id="scheduleTable"><thead><tr><th>PID</th><th>Tiempo Llegada</th><th>Ráfaga CPU</th><th>Prioridad</th><th>Acciones</th></tr></thead><tbody>
<tr><td>P1</td><td>0</td><td>5</td><td>1</td><td><button class="icon-btn danger remove-process"><i class="fa-solid fa-trash"></i></button></td></tr>
<tr><td>P2</td><td>1</td><td>3</td><td>1</td><td><button class="icon-btn danger remove-process"><i class="fa-solid fa-trash"></i></button></td></tr>
<tr><td>P3</td><td>2</td><td>8</td><td>1</td><td><button class="icon-btn danger remove-process"><i class="fa-solid fa-trash"></i></button></td></tr>
<tr><td>P4</td><td>3</td><td>6</td><td>1</td><td><button class="icon-btn danger remove-process"><i class="fa-solid fa-trash"></i></button></td></tr>
</tbody></table></div></article>
</section>
<article class="panel gantt-panel"><h2>Diagrama de Gantt</h2><div class="gantt" id="gantt"><div class="gantt-segment g1" style="flex:5">P1</div><div class="gantt-segment g2" style="flex:3">P2</div><div class="gantt-segment g3" style="flex:8">P3</div><div class="gantt-segment g4" style="flex:6">P4</div></div><div class="gantt-ticks"><span>0</span><span>5</span><span>8</span><span>16</span><span>22</span></div></article>
<section class="metrics-grid"><article class="metric-card"><p>Tiempo de espera promedio</p><strong>8.25</strong></article><article class="metric-card"><p>Tiempo de retorno promedio</p><strong>13.25</strong></article><article class="metric-card"><p>Utilización de CPU</p><strong>86 %</strong></article></section>
