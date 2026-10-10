
# ==========================================
# MÓDULO M4: INTERBLOQUEOS (ALGORITMO DEL BANQUERO)
# ==========================================

class AlgoritmoBanquero:
    def __init__(self, asignacion, maximo, disponible):
        self.asignacion = asignacion
        self.maximo = maximo
        self.disponible = disponible
        self.num_procesos = len(asignacion)
        self.num_recursos = len(disponible)
        self.necesidad = self.calcular_necesidad()

    def calcular_necesidad(self):
        return [[self.maximo[i][j] - self.asignacion[i][j] 
                 for j in range(self.num_recursos)] 
                for i in range(self.num_procesos)]

    def es_estado_seguro(self):
        trabajo = list(self.disponible)
        finalizado = [False] * self.num_procesos
        secuencia_segura = []
        pasos = []

        while len(secuencia_segura) < self.num_procesos:
            encontrado = False
            for i in range(self.num_procesos):
                if not finalizado[i]:
                    # Verificar si Necesidad <= Trabajo
                    if all(self.necesidad[i][j] <= trabajo[j] for j in range(self.num_recursos)):
                        for j in range(self.num_recursos):
                            trabajo[j] += self.asignacion[i][j]
                        finalizado[i] = True
                        secuencia_segura.append(f"P{i}")
                        pasos.append(f"P{i} ejecutado. Nuevo disponible: {list(trabajo)}")
                        encontrado = True
                        break
            if not encontrado:
                return False, [], ["Estado no seguro detectado: Interbloqueo posible."]

        return True, secuencia_segura, pasos