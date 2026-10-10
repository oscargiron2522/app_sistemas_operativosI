import sqlite3

# ==========================================
# MÓDULO M3: PLANIFICACIÓN DE CPU
# ==========================================

class Proceso:
    def __init__(self, pid, llegada, rafaga, prioridad=0):
        self.pid = pid
        self.llegada = llegada
        self.rafaga = rafaga
        self.prioridad = prioridad
        self.tiempo_restante = rafaga
        self.tiempo_espera = 0
        self.tiempo_retorno = 0

def planificar_fcfs(procesos):
    # Ordenar por tiempo de llegada
    procs = sorted(procesos, key=lambda x: x.llegada)
    tiempo_actual = 0
    gantt = []

    for p in procs:
        if tiempo_actual < p.llegada:
            tiempo_actual = p.llegada
        inicio = tiempo_actual
        tiempo_actual += p.rafaga
        p.tiempo_retorno = tiempo_actual - p.llegada
        p.tiempo_espera = p.tiempo_retorno - p.rafaga
        gantt.append((p.pid, inicio, tiempo_actual))

    return procs, gantt

# Base de datos para M3 (SQLite)
def guardar_simulacion_m3(nombre_sim, resultados):
    conn = sqlite3.connect("simulaciones.db")
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS simulaciones_m3 (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT,
            algoritmo TEXT,
            prom_espera REAL,
            prom_retorno REAL
        )
    """)
    for alg, te, tr in resultados:
        cursor.execute("INSERT INTO simulaciones_m3 (nombre, algoritmo, prom_espera, prom_retorno) VALUES (?, ?, ?, ?)",
                       (nombre_sim, alg, te, tr))
    conn.commit()
    conn.close()

