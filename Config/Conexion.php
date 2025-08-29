<?php
/**
 * Archivo de configuración de conexión a la base de datos
 * Base de datos: Zyra
 * Servidor: phpMyAdmin local
 */

class Conexion {
    private $host = 'localhost';
    private $dbname = 'zyra';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;
    
    /**
     * Constructor - Establece la conexión automáticamente
     */
    public function __construct() {
        $this->conectar();
    }
    
    /**
     * Método para establecer conexión con la base de datos
     * @return PDO|null
     */
    private function conectar() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $opciones);
            
            return $this->pdo;
            
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener la instancia de PDO
     * @return PDO
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * Cerrar la conexión
     */
    public function cerrarConexion() {
        $this->pdo = null;
    }
    
    /**
     * Método estático para obtener una nueva conexión
     * @return Conexion
     */
    public static function obtenerConexion() {
        return new self();
    }
    
    /**
     * Verificar si la conexión está activa
     * @return bool
     */
    public function estaConectado() {
        try {
            return $this->pdo !== null && $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            return false;
        }
    }
}

?>