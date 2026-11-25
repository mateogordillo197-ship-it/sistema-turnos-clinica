const API_URL = '../backend/api/turnos.php';

async function apiRequest(options = {}) {
  const response = await fetch(API_URL, options);
  let data;
  try {
    data = await response.json();
  } catch (e) {
    throw new Error('Respuesta inválida del servidor.');
  }
  if (!response.ok || !data.ok) {
    throw new Error(data.error || 'Error al comunicarse con el sistema de turnos.');
  }
  return data;
}

function obtenerEstadoTurnos() {
  return apiRequest();
}

function crearTurno(especialidadId) {
  return apiRequest({
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      accion: 'crear',
      especialidad_id: especialidadId
    })
  });
}

function llamarSiguiente(consultorioId, recepcionistaId) {
  return apiRequest({
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      accion: 'llamar_siguiente',
      consultorio_id: consultorioId,
      recepcionista_id: recepcionistaId
    })
  });
}

function finalizarTurno(turnoId) {
  return apiRequest({
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      accion: 'finalizar',
      turno_id: turnoId
    })
  });
}