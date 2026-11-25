<?php
function getConnection() {
    $host = '127.0.0.1';
    $db   = 'clinica_turnos';
    $user = 'root';
    $pass = '';

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8";
    $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    );

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode(array(
            'ok' => false,
            'error' => 'Error de conexión con la base de datos.'
        ));
        exit;
    }
}