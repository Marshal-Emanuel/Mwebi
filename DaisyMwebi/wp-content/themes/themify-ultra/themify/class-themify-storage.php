<?php

class Themify_Storage
{

    private static $table = null;

    public static function init()
    {
        if (self::$table === null) {
            global $wpdb;
            $errors = $wpdb->show_errors;
            try {
                self::$table = $wpdb->prefix . 'tf_storage';
                $q = 'CREATE TABLE IF NOT EXISTS ' . self::$table . ' ( 
		    `key` CHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL PRIMARY KEY,
		    `value` MEDIUMTEXT NOT NULL,
		    `expire` INT UNSIGNED,
		    KEY(expire)
		) ENGINE=InnoDB ' . $wpdb->get_charset_collate() . ';';
                $wpdb->hide_errors();
                $res = $wpdb->query($q);
                if ($res === false) {
                    self::$table = false;
                }
                unset($q, $res);
            } catch (Exception $e) {
                self::$table = false;
            } finally {
                if ($errors) {
                    $wpdb->show_errors();
                }
            }
        }
        return self::$table;
    }

    public static function cleanDb()
    {
        if (self::init() !== false) {
            $q = 'DELETE FROM %s WHERE `expire` IS NOT NULL AND `expire`<' . time();
            return self::query($q);
        }
        return false;
    }

    public static function query($q)
    {
        if (self::init() !== false) {
            global $wpdb;
            return $wpdb->query(sprintf($q, self::$table));
        }
        return false;
    }

    public static function get($key, $prefix = null, $from = null)
    {
        $k = self::getHash($key, $prefix);
        if (self::init() !== false) {
            global $wpdb;
            $q = 'SELECT `value`,`expire` FROM ' . self::$table . ' WHERE `key`="' . esc_sql($k) . '" LIMIT 1';
            $res = $wpdb->get_row($q, ARRAY_A);
            unset($k, $q);
            if (!empty($res)) {
                if (!empty($res['expire']) && time() > $res['expire']) {
                    self::delete($key, $prefix, $from);
                }
                elseif (isset($res['value'])) {
                    return $res['value'];
                }
            }
            return false;
        }
        return get_transient($k);
    }

    public static function set($key, $v, $exp = null, $prefix = null, $to = null)
    {
        $k = self::getHash($key, $prefix);
        if (is_array($v)) {
            $v = json_encode($v);
        } elseif ($v === true || $v === false) {
            $v = $v === true ? '1' : '0';
        }
        if (self::init() !== false) {
            global $wpdb;
	    $exp = $exp === null?'DEFAULT':((int)$exp + time());
            $q = 'INSERT INTO ' . self::$table . ' (`key`,`value`,`expire`) VALUES ("' . esc_sql($k) . '","' . esc_sql($v) . '",' . $exp . ') ON DUPLICATE KEY UPDATE `value`=VALUES(value),`expire`=VALUES(expire)';
            return $wpdb->query($q);
        }
        return set_transient($k, $v, $exp);
    }

    public static function delete($k, $prefix = null, $from = null)
    {
        $k = self::getHash($k, $prefix);
        if (self::init() !== false) {
            global $wpdb;
            $q = 'DELETE FROM ' . self::$table . ' WHERE `key`="' . esc_sql($k) . '" LIMIT 1';
            return $wpdb->query($q);
        }
        return delete_transient($k);
    }

    public static function getHash($k, $prefix = null)
    {
        static $h = null;
        if ($h === null) {
            $hashs = hash_algos();
            $h = 'fnv164';
            if (in_array('xxh3', $hashs, true)) {
                $h = 'xxh3';
            } elseif (in_array('fnv1a64', $hashs, true)) {
                $h = 'fnv1a64';
            }
            unset($hashs);
        }
        $k = hash($h, $k);
        if ($prefix !== null) {
            $k = substr_replace($k, $prefix, 0, strlen($prefix));
        }
        return $k;
    }

    public static function deleteByPrefix($prefix)
    {
        return self::query('DELETE FROM %s WHERE `key` LIKE "' . esc_sql($prefix) . '%%' . '"');
    }
}
