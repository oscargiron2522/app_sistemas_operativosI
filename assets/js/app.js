document.addEventListener('DOMContentLoaded', () => {
    const $ = (s, root=document) => root.querySelector(s);
    const $$ = (s, root=document) => [...root.querySelectorAll(s)];
    const toast = (message) => {
        const box = $('#toast');
        if (!box) return;
        box.textContent = message;
        box.classList.add('show');
        clearTimeout(window.linuxCoreToastTimer);
        window.linuxCoreToastTimer = setTimeout(() => box.classList.remove('show'), 2800);
    };

    const sidebar = $('#sidebar');
    const menuToggle = $('#menuToggle');
    if (sidebar && menuToggle) menuToggle.addEventListener('click', () => {
        if (matchMedia('(max-width: 760px)').matches) sidebar.classList.toggle('open');
        else sidebar.classList.toggle('collapsed');
    });

    $$('.tab').forEach(tab => tab.addEventListener('click', () => {
        const container = tab.closest('.panel');
        $$('.tab', container).forEach(t => t.classList.toggle('active', t === tab));
        $$('.tab-content, .banker-content', container).forEach(content => {
            content.classList.toggle('hidden', content.id !== tab.dataset.tab);
        });
    }));

    $$('[data-toast]').forEach(btn => btn.addEventListener('click', () => toast(btn.dataset.toast)));

    const search = $('#processSearch');
    if (search) search.addEventListener('input', () => {
        const rows = $$('#processTable tbody tr');
        let visible = 0;
        rows.forEach(row => {
            const show = row.textContent.toLowerCase().includes(search.value.toLowerCase());
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const count = $('#visibleCount');
        if (count) count.textContent = visible ? `1 a ${visible}` : '0';
    });
    const refresh = $('#refreshProcesses');
    if (refresh) refresh.addEventListener('click', () => toast('Listado de demostración actualizado.'));

    $$('.pagination button').forEach(btn => btn.addEventListener('click', () => {
        const group = btn.parentElement;
        $$('.pagination button', group).forEach(b => b.classList.remove('selected'));
        if (/^\d+$/.test(btn.textContent.trim())) btn.classList.add('selected');
        toast('Paginación visual de demostración.');
    }));

    const modal = $('#userModal');
    $$('[data-modal]').forEach(btn => btn.addEventListener('click', () => modal?.classList.remove('hidden')));
    $$('[data-close-modal]').forEach(btn => btn.addEventListener('click', () => modal?.classList.add('hidden')));
    if (modal) modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });
    const userForm = $('#newUserForm');
    if (userForm) userForm.addEventListener('submit', e => {
        e.preventDefault();
        modal.classList.add('hidden');
        userForm.reset();
        toast('Formulario validado. La creación real requiere conexión con Linux.');
    });

    const processTable = $('#scheduleTable');
    const gantt = $('#gantt');
    const updateGantt = () => {
        if (!processTable || !gantt) return;
        const rows = $$('tbody tr', processTable);
        const colors = ['g1','g2','g3','g4','g5','g6'];
        gantt.innerHTML = '';
        let total = 0;
        rows.forEach((row, i) => {
            const cells = $$('td', row);
            const burst = Math.max(1, parseInt(cells[2]?.textContent || '1', 10));
            total += burst;
            const seg = document.createElement('div');
            seg.className = `gantt-segment ${colors[i % colors.length]}`;
            seg.style.flex = burst;
            seg.textContent = cells[0]?.textContent || `P${i+1}`;
            gantt.appendChild(seg);
        });
        const ticks = $('.gantt-ticks');
        if (ticks) ticks.innerHTML = `<span>0</span><span>${Math.round(total*.25)}</span><span>${Math.round(total*.5)}</span><span>${Math.round(total*.75)}</span><span>${total}</span>`;
    };
    $$('.remove-process').forEach(btn => btn.addEventListener('click', () => {
        btn.closest('tr').remove(); updateGantt(); toast('Proceso eliminado de la simulación.');
    }));
    const addProcess = $('#addProcess');
    if (addProcess && processTable) addProcess.addEventListener('click', () => {
        const body = $('tbody', processTable);
        const n = body.rows.length + 1;
        const row = body.insertRow();
        row.innerHTML = `<td>P${n}</td><td>${n-1}</td><td>${Math.floor(Math.random()*6)+2}</td><td>1</td><td><button class="icon-btn danger remove-process"><i class="fa-solid fa-trash"></i></button></td>`;
        $('.remove-process', row).addEventListener('click', e => { e.currentTarget.closest('tr').remove(); updateGantt(); });
        updateGantt(); toast(`Proceso P${n} agregado a la simulación.`);
    });
    const clearProcesses = $('#clearProcesses');
    if (clearProcesses && processTable) clearProcesses.addEventListener('click', () => {
        $('tbody', processTable).innerHTML = '';
        updateGantt(); toast('Se limpiaron los procesos de la simulación.');
    });

    const generateMatrices = $('#generateMatrices');
    if (generateMatrices) generateMatrices.addEventListener('click', () => toast('Tablas de ejemplo listas. La generación dinámica se puede implementar en la siguiente etapa.'));
    const detect = $('#detectDeadlock');
    if (detect) detect.addEventListener('click', () => {
        const result = $('#deadlockResult');
        result.classList.remove('danger-box'); result.classList.add('safe-box');
        result.innerHTML = '<i class="fa-solid fa-circle-check"></i><div><strong>Estado seguro</strong><p>No se detectó interbloqueo en esta simulación.</p></div>';
        toast('Detección de demostración completada.');
    });

    const terminalForm = $('#terminalForm');
    if (terminalForm) terminalForm.addEventListener('submit', e => {
        e.preventDefault();
        const input = $('#terminalCommand');
        const output = $('#terminalOutput');
        const command = input.value.trim();
        if (!command) return;
        const safe = {
            help: 'Comandos simulados: help, ls, whoami, date, free -h, df -h, uptime, clear',
            ls: 'Desktop  Documentos  Descargas  proyectos',
            whoami: 'admin',
            date: new Date().toLocaleString('es-GT'),
            'free -h': 'Memoria: 3.8 GiB total, 1.4 GiB usada, 1.3 GiB libre, 1.1 GiB caché',
            'df -h': '/dev/sda2  46G  31G  15G  67% /',
            uptime: 'Tiempo activo simulado: 3 días, 4 horas',
            clear: '__CLEAR__'
        };
        if (command === 'clear') output.innerHTML = '';
        else {
            const line = document.createElement('div');
            line.innerHTML = `<span class="terminal-prompt">admin@linuxcore:~$</span> ${escapeHtml(command)}<br>${escapeHtml(safe[command] || 'Comando no disponible en la terminal de demostración. Escribe help.')}`;
            output.appendChild(line);
        }
        input.value = '';
        output.scrollTop = output.scrollHeight;
    });
    $$('.command-chip').forEach(chip => chip.addEventListener('click', () => {
        const input = $('#terminalCommand');
        if (input) { input.value = chip.dataset.command; input.focus(); }
    }));

    $$('[data-report]').forEach(btn => btn.addEventListener('click', () => {
        toast(`Reporte de ${btn.dataset.report}: listo para imprimir desde el navegador.`);
        setTimeout(() => window.print(), 450);
    }));

    function escapeHtml(value) {
        return value.replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }

    const makeLineChart = (canvasId, values, fill=true) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return;
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(20, 119, 245, .28)');
        gradient.addColorStop(1, 'rgba(20, 119, 245, .025)');
        new Chart(ctx, {
            type:'line',
            data:{ labels:['60s','55s','50s','45s','40s','35s','30s','25s','20s','15s','10s','5s','0s'], datasets:[{data:values,borderColor:'#0875f7',backgroundColor:gradient,borderWidth:3,fill,tension:0,pointRadius:0}]},
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{color:'#edf2f7'},ticks:{color:'#607493',maxRotation:0}},y:{min:0,max:100,ticks:{stepSize:20,color:'#607493',callback:v=>v+'%'},grid:{color:'#edf2f7'}}}}
        });
    };
    makeLineChart('dashboardCpuChart',[32,41,42,53,44,46,55,47,51,63,49,57,54]);
    makeLineChart('cpuLiveChart',[24,37,35,45,40,58,46,50,80,64,79,58,64,44]);
    const doughnut = (id, values, colors, centerText=false) => {
        const canvas = document.getElementById(id);
        if (!canvas || !window.Chart) return;
        new Chart(canvas.getContext('2d'), {type:'doughnut',data:{labels:['Usada','Caché','Libre'],datasets:[{data:values,backgroundColor:colors,borderWidth:0,hoverOffset:4}]},options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{display:false}}}});
    };
    doughnut('memoryChart',[37,29,34],['#0875f7','#69b3fa','#0bb35b']);
    doughnut('diskChart',[67,33],['#0875f7','#a7bacb']);
});
