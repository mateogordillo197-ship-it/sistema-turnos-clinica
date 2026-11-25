<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 204 No Content', true, 204);
    exit;
}

require_once dirname(__FILE__) . '/../config/db.php';
$pdo = getConnection();

function responder($status, $payload) {
    header('Content-Type: application/json; charset=utf-8', true, $status);
    echo json_encode($payload);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // 1. OBTENER ESTADO DE SALA, CONTEO PARA TÓTEM Y FILA DE RECEPCIÓN
    if ($metodo === 'GET') {
        $stmtActuales = $pdo->query("
            SELECT t.id, t.codigo, e.nombre AS especialidad, c.nombre AS consultorio, t.llamado_en
            FROM turnos t
            INNER JOIN especialidades e ON t.especialidad_id = e.id
            LEFT JOIN consultorios c ON t.consultorio_id = c.id
            WHERE t.estado = 'atendiendo'
            ORDER BY t.llamado_en DESC
        ");
        $enAtencion = $stmtActuales->fetchAll();

        $stmtEspera = $pdo->query("
            SELECT t.id, t.codigo, t.especialidad_id, e.nombre AS especialidad, t.creado_en,
                   TIMESTAMPDIFF(MINUTE, t.creado_en, NOW()) AS minutos_espera
            FROM turnos t
            INNER JOIN especialidades e ON t.especialidad_id = e.id
            WHERE t.estado = 'espera'
            ORDER BY t.creado_en ASC
            LIMIT 15
        ");
        $enEspera = $stmtEspera->fetchAll();

        $stmtConteo = $pdo->query("
            SELECT e.id, e.nombre, e.prefijo, COUNT(t.id) AS en_espera
            FROM especialidades e
            LEFT JOIN turnos t ON t.especialidad_id = e.id AND t.estado = 'espera'
            WHERE e.activo = 1
            GROUP BY e.id, e.nombre, e.prefijo
            ORDER BY e.id ASC
        ");
        $resumenEspecialidades = $stmtConteo->fetchAll();

        responder(200, array(
            'ok' => true,
            'en_atencion' => $enAtencion,
            'en_espera' => $enEspera,
            'especialidades' => $resumenEspecialidades
        ));
    }

    // 2. ACCIONES: CREAR TURNO, LLAMAR SIGUIENTE, FINALIZAR
    if ($metodo === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = array();
        }

        $accion = isset($input['accion']) ? $input['accion'] : '';

        // A) TÓTEM: Emitir nuevo turno
        if ($accion === 'crear') {
            $especialidadId = isset($input['especialidad_id']) ? (int)$input['especialidad_id'] : 0;
            if ($especialidadId <= 0) {
                responder(400, array('ok' => false, 'error' => 'Especialidad inválida.'));
            }

            $stmtEsp = $pdo->prepare("SELECT prefijo, nombre FROM especialidades WHERE id = ? AND activo = 1");
            $stmtEsp->execute(array($especialidadId));
            $esp = $stmtEsp->fetch();

            if (!$esp) {
                responder(404, array('ok' => false, 'error' => 'Especialidad no encontrada.'));
            }

            $stmtSeq = $pdo->prepare("
                SELECT COUNT(*) AS total FROM turnos
                WHERE especialidad_id = ? AND DATE(creado_en) = CURDATE()
            ");
            $stmtSeq->execute(array($especialidadId));
            $rowSeq = $stmtSeq->fetch();
            $correlativo = ((int)$rowSeq['total']) + 1;
            $codigo = $esp['prefijo'] . '-' . str_pad((string)$correlativo, 3, '0', STR_PAD_LEFT);

            $stmtDelante = $pdo->prepare("
                SELECT COUNT(*) AS delante FROM turnos
                WHERE especialidad_id = ? AND estado = 'espera'
            ");
            $stmtDelante->execute(array($especialidadId));
            $rowDelante = $stmtDelante->fetch();
            $turnosDelante = (int)$rowDelante['delante'];

            $stmtInsert = $pdo->prepare("
                INSERT INTO turnos (codigo, especialidad_id, estado) VALUES (?, ?, 'espera')
            ");
            $stmtInsert->execute(array($codigo, $especialidadId));

            responder(201, array(
                'ok' => true,
                'turno' => array(
                    'id' => (int)$pdo->lastInsertId(),
                    'codigo' => $codigo,
                    'especialidad' => $esp['nombre'],
                    'turnos_delante' => $turnosDelante,
                    'tiempo_estimado_min' => $turnosDelante * 5
                )
            ));
        }

        // B) RECEPCIÓN: Llamar siguiente turno
        if ($accion === 'llamar_siguiente') {
            $consultorioId = isset($input['consultorio_id']) ? (int)$input['consultorio_id'] : 0;
            $recepcionistaId = isset($input['recepcionista_id']) ? (int)$input['recepcionista_id'] : 0;

            if ($consultorioId <= 0 || $recepcionistaId <= 0) {
                responder(400, array('ok' => false, 'error' => 'Consultorio y recepcionista son obligatorios.'));
            }

            $stmtCons = $pdo->prepare("SELECT especialidad_id, nombre FROM consultorios WHERE id = ?");
            $stmtCons->execute(array($consultorioId));
            $consultorio = $stmtCons->fetch();

            if (!$consultorio) {
                responder(404, array('ok' => false, 'error' => 'Consultorio no encontrado.'));
            }

            $stmtNext = $pdo->prepare("
                SELECT id, codigo FROM turnos
                WHERE especialidad_id = ? AND estado = 'espera'
                ORDER BY creado_en ASC, id ASC
                LIMIT 1
            ");
            $stmtNext->execute(array($consultorio['especialidad_id']));
            $siguiente = $stmtNext->fetch();

            if (!$siguiente) {
                responder(200, array('ok' => true, 'mensaje' => 'No hay pacientes en espera.', 'turno' => null));
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE turnos
                SET estado = 'atendiendo',
                    consultorio_id = ?,
                    recepcionista_id = ?,
                    llamado_en = NOW()
                WHERE id = ?
            ");
            $stmtUpdate->execute(array($consultorioId, $recepcionistaId, $siguiente['id']));

            responder(200, array(
                'ok' => true,
                'turno' => array(
                    'id' => (int)$siguiente['id'],
                    'codigo' => $siguiente['codigo'],
                    'consultorio' => $consultorio['nombre']
                )
            ));
        }

        // C) RECEPCIÓN: Finalizar turno en atención
        if ($accion === 'finalizar') {
            $turnoId = isset($input['turno_id']) ? (int)$input['turno_id'] : 0;
            if ($turnoId <= 0) {
                responder(400, array('ok' => false, 'error' => 'ID de turno inválido.'));
            }

            $stmtFin = $pdo->prepare("
                UPDATE turnos SET estado = 'finalizado', finalizado_en = NOW()
                WHERE id = ? AND estado = 'atendiendo'
            ");
            $stmtFin->execute(array($turnoId));

            responder(200, array('ok' => true, 'mensaje' => 'Consulta finalizada.'));
        }

        responder(400, array('ok' => false, 'error' => 'Acción no reconocida.'));
    }

    responder(405, array('ok' => false, 'error' => 'Método HTTP no permitido.'));
} catch (Exception $e) {
    responder(500, array('ok' => false, 'error' => 'Error interno del servidor.'));
}