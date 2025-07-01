<?php
// Database export script
require_once 'public/api/db.php';

try {
    // Get all table names
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $output = "-- Class Booking System Database Export\n";
    $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    foreach ($tables as $table) {
        // Get table structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $output .= "-- Table structure for `$table`\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $createTable['Create Table'] . ";\n\n";
        
        // Get table data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $output .= "-- Data for table `$table`\n";
            
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, array_values($row));
                
                $columns = '`' . implode('`, `', array_keys($row)) . '`';
                $values = implode(', ', $values);
                
                $output .= "INSERT INTO `$table` ($columns) VALUES ($values);\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    
    // Write to file
    file_put_contents('database_export.sql', $output);
    
    echo "Database exported successfully to database_export.sql\n";
    echo "Export size: " . number_format(filesize('database_export.sql')) . " bytes\n";
    echo "Tables exported: " . count($tables) . "\n";
    echo "Tables: " . implode(', ', $tables) . "\n";
    
} catch (Exception $e) {
    echo "Export failed: " . $e->getMessage() . "\n";
}
?> 