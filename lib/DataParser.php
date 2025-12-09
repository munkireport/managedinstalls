<?php
/**
 * DataParser - A flexible parser supporting both plist and YAML formats
 * 
 * This class provides unified parsing for configuration data that may be
 * in either Apple Property List (plist) or YAML format. It automatically
 * detects the format and parses accordingly.
 * 
 * @package munkireport/managedinstalls
 * @author Rod Christiansen
 */

namespace munkireport\managedinstalls\lib;

use CFPropertyList\CFPropertyList;

class DataParser
{
    /**
     * Parse data that may be in plist or YAML format
     * 
     * @param string $data The raw data to parse
     * @return array|null Parsed data as array, or null on failure
     * @throws \Exception If parsing fails for both formats
     */
    public static function parse($data)
    {
        if (empty($data)) {
            return null;
        }

        // Try to detect format
        $trimmedData = ltrim($data);
        
        // Check if it looks like XML plist
        if (self::isPlist($trimmedData)) {
            return self::parsePlist($data);
        }
        
        // Check if it looks like YAML
        if (self::isYaml($trimmedData)) {
            return self::parseYaml($data);
        }
        
        // Default to plist parsing (original behavior)
        return self::parsePlist($data);
    }

    /**
     * Check if data appears to be in plist format
     * 
     * @param string $data Trimmed data to check
     * @return bool True if data looks like plist
     */
    private static function isPlist($data)
    {
        return (
            strpos($data, '<?xml') === 0 ||
            strpos($data, '<!DOCTYPE plist') !== false ||
            strpos($data, '<plist') !== false
        );
    }

    /**
     * Check if data appears to be in YAML format
     * 
     * @param string $data Trimmed data to check
     * @return bool True if data looks like YAML
     */
    private static function isYaml($data)
    {
        // YAML document markers or common YAML patterns
        return (
            strpos($data, '---') === 0 ||
            strpos($data, '%YAML') === 0 ||
            // Check for common YAML key: value pattern at start
            preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*:/', $data)
        );
    }

    /**
     * Parse plist data
     * 
     * @param string $data Plist data
     * @return array|null Parsed array or null
     */
    private static function parsePlist($data)
    {
        try {
            $parser = new CFPropertyList();
            $parser->parse($data, CFPropertyList::FORMAT_XML);
            return $parser->toArray();
        } catch (\Exception $e) {
            // If plist parsing fails, try YAML as fallback
            return self::parseYaml($data);
        }
    }

    /**
     * Parse YAML data
     * 
     * Uses symfony/yaml if available, falls back to basic parsing
     * 
     * @param string $data YAML data
     * @return array|null Parsed array or null
     */
    private static function parseYaml($data)
    {
        // Try symfony/yaml if available
        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            try {
                return \Symfony\Component\Yaml\Yaml::parse($data);
            } catch (\Exception $e) {
                // Fall through to other methods
            }
        }
        
        // Try using spyc (Simple PHP YAML Class) if available
        if (function_exists('spyc_load')) {
            return spyc_load($data);
        }
        
        // Try basic JSON fallback if YAML is simple enough
        // Convert basic YAML to JSON-like structure
        try {
            return self::basicYamlParse($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Basic YAML parser for simple key-value structures
     * This is a fallback when no proper YAML library is available
     * 
     * @param string $data YAML data
     * @return array Parsed array
     */
    private static function basicYamlParse($data)
    {
        $result = [];
        $lines = explode("\n", $data);
        $currentKey = null;
        $currentIndent = 0;
        
        foreach ($lines as $line) {
            // Skip empty lines and comments
            if (empty(trim($line)) || strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Skip YAML document markers
            if (trim($line) === '---' || trim($line) === '...') {
                continue;
            }
            
            // Basic key: value parsing
            if (preg_match('/^(\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:\s*(.*)$/', $line, $matches)) {
                $key = $matches[2];
                $value = trim($matches[3]);
                
                // Handle quoted strings
                if (preg_match('/^["\'](.*)["\']\s*$/', $value, $quoted)) {
                    $value = $quoted[1];
                }
                
                // Handle booleans
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                } elseif ($value === 'null' || $value === '~') {
                    $value = null;
                } elseif (is_numeric($value)) {
                    $value = strpos($value, '.') !== false ? (float)$value : (int)$value;
                }
                
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
}
