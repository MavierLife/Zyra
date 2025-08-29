<?php
/**
 * Gestor de monedas para el sistema Zyra
 * Maneja la obtención de símbolos de moneda desde la base de datos
 */

require_once 'Conexion.php';

class CurrencyManager {
    private $pdo;
    
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->getPdo();
    }
    
    /**
     * Obtiene el símbolo de moneda basado en el UUID del contribuyente
     * @param string $uuidContribuyente UUID del contribuyente
     * @return string Símbolo de la moneda (por defecto '$' si no se encuentra)
     */
    public function getCurrencySymbolByContributor($uuidContribuyente) {
        try {
            $sql = "SELECT tm.Symbol 
                    FROM tblcontribuyentes tc 
                    INNER JOIN tbltipomoneda tm ON tc.idcurrency = tm.idcurrency 
                    WHERE tc.UUIDContribuyente = :uuid_contribuyente";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            if ($result && isset($result['Symbol'])) {
                return $result['Symbol'];
            }
            
            // Valor por defecto si no se encuentra
            return '$';
            
        } catch (PDOException $e) {
            error_log("Error al obtener símbolo de moneda: " . $e->getMessage());
            return '$'; // Valor por defecto en caso de error
        }
    }
    
    /**
     * Obtiene información completa de la moneda basada en el UUID del contribuyente
     * @param string $uuidContribuyente UUID del contribuyente
     * @return array Información de la moneda
     */
    public function getCurrencyInfoByContributor($uuidContribuyente) {
        try {
            $sql = "SELECT tm.idcurrency, tm.CurrencyISO, tm.CurrencyName, tm.Symbol, tm.Money 
                    FROM tblcontribuyentes tc 
                    INNER JOIN tbltipomoneda tm ON tc.idcurrency = tm.idcurrency 
                    WHERE tc.UUIDContribuyente = :uuid_contribuyente";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':uuid_contribuyente', $uuidContribuyente);
            $stmt->execute();
            
            $result = $stmt->fetch();
            
            if ($result) {
                return [
                    'id' => $result['idcurrency'],
                    'iso' => $result['CurrencyISO'],
                    'name' => $result['CurrencyName'],
                    'symbol' => $result['Symbol'],
                    'money' => $result['Money']
                ];
            }
            
            // Valores por defecto si no se encuentra
            return [
                'id' => 4,
                'iso' => 'USD',
                'name' => 'Dolar Estadounidense',
                'symbol' => '$',
                'money' => 'US.Dolar'
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener información de moneda: " . $e->getMessage());
            // Valores por defecto en caso de error
            return [
                'id' => 4,
                'iso' => 'USD',
                'name' => 'Dolar Estadounidense',
                'symbol' => '$',
                'money' => 'US.Dolar'
            ];
        }
    }
    
    /**
     * Obtiene todas las monedas disponibles
     * @return array Lista de todas las monedas
     */
    public function getAllCurrencies() {
        try {
            $sql = "SELECT idcurrency, CurrencyISO, CurrencyName, Symbol, Money FROM tbltipomoneda ORDER BY CurrencyName";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Error al obtener todas las monedas: " . $e->getMessage());
            return [];
        }
    }
}
?>