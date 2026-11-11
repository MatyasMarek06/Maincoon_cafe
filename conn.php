<?php

class connector
{
    private $conn;

    public function __construct($host, $user, $password, $dbname)
    {
        try {
            $this->conn = new mysqli($host, $user, $password, $dbname);
            if ($this->conn->connect_error) {
                throw new Exception("Chyba vytvoření spojení s databází: " . $this->conn->connect_error);
            }
            // Čeština (háčky, čárky) a emoji v názvech
            $this->conn->set_charset('utf8mb4');
        } catch (Exception $e) {
            die("Chyba: " . $e->getMessage());
        }
    }

    public function query($stmt)
    {
        try {
            $result = $this->conn->query($stmt);
            if (!$result) {
                throw new Exception("Chyba dotazu na databázi: " . $this->conn->error);
            }
            return $result;
        } catch (Exception $e) {
            echo "Chyba: " . $e->getMessage();
            return false;
        }
    }

    public function prepare($stmt)
    {
        try {
            $preparedStmt = $this->conn->prepare($stmt);
            if (!$preparedStmt) {
                throw new Exception("Chyba přípravy dotazu: " . $this->conn->error);
            }
            return $preparedStmt;
        } catch (Exception $e) {
            echo "Chyba: " . $e->getMessage();
            return false;
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    /* ---------- Pomocné metody pro dotazy s parametry ----------
     * Hodnoty se předávají odděleně od SQL (otazníky ?), takže jsou
     * chráněné proti SQL injection. Při chybě vyhodí výjimku. */

    /** Provede dotaz s parametry a vrátí připravený statement. */
    private function run($sql, array $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Chyba přípravy dotazu: " . $this->conn->error);
        }
        if (!$stmt->execute($params ?: null)) {
            throw new Exception("Chyba dotazu na databázi: " . $stmt->error);
        }
        return $stmt;
    }

    /** Všechny řádky jako pole asociativních polí. */
    public function fetchAll($sql, array $params = [])
    {
        $result = $this->run($sql, $params)->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** První řádek, nebo null. */
    public function fetchOne($sql, array $params = [])
    {
        $result = $this->run($sql, $params)->get_result();
        return ($result ? $result->fetch_assoc() : null) ?: null;
    }

    /** Hodnota prvního sloupce prvního řádku, nebo null. */
    public function fetchValue($sql, array $params = [])
    {
        $result = $this->run($sql, $params)->get_result();
        $row = $result ? $result->fetch_row() : null;
        return $row ? $row[0] : null;
    }

    /** První sloupec všech řádků jako jednoduché pole. */
    public function fetchColumn($sql, array $params = [])
    {
        $result = $this->run($sql, $params)->get_result();
        return $result ? array_column($result->fetch_all(MYSQLI_NUM), 0) : [];
    }

    /** INSERT / UPDATE / DELETE – vrátí počet ovlivněných řádků. */
    public function execute($sql, array $params = [])
    {
        return $this->run($sql, $params)->affected_rows;
    }

    /** ID posledního vloženého řádku. */
    public function lastId()
    {
        return (int) $this->conn->insert_id;
    }

    public function begin()
    {
        $this->conn->begin_transaction();
    }

    public function commit()
    {
        $this->conn->commit();
    }

    public function rollback()
    {
        $this->conn->rollback();
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Přihlašovací údaje k databázi (XAMPP výchozí: uživatel root bez hesla)
$db = new connector("", "", "", "");
