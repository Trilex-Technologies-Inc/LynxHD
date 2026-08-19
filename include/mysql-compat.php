<?php

// Keep PHP 8 diagnostics in the server log without leaking paths, SQL details,
// or request data into rendered pages.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

/**
 * Compatibility layer for the mysql_* API removed in PHP 7.
 *
 * LynxHD's queries are kept intact while database access is delegated to
 * mysqli. New code should use mysqli or PDO directly.
 */

if (!function_exists('mysql_connect')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $GLOBALS['_lynxhd_mysql_connection'] = null;

    function mysql_connect($host = null, $username = null, $password = null)
    {
        $connection = @mysqli_connect($host, $username, $password);
        $GLOBALS['_lynxhd_mysql_connection'] = $connection;
        return $connection;
    }

    function mysql_select_db($database, $connection = null)
    {
        return mysqli_select_db($connection ?: $GLOBALS['_lynxhd_mysql_connection'], $database);
    }

    function mysql_query($query, $connection = null)
    {
        return mysqli_query($connection ?: $GLOBALS['_lynxhd_mysql_connection'], $query);
    }

    function mysql_fetch_array($result, $resultType = MYSQLI_BOTH)
    {
        return mysqli_fetch_array($result, $resultType);
    }

    function mysql_num_rows($result)
    {
        return mysqli_num_rows($result);
    }

    function mysql_insert_id($connection = null)
    {
        return mysqli_insert_id($connection ?: $GLOBALS['_lynxhd_mysql_connection']);
    }

    function mysql_error($connection = null)
    {
        $connection = $connection ?: $GLOBALS['_lynxhd_mysql_connection'];
        return $connection ? mysqli_error($connection) : mysqli_connect_error();
    }
}

if (!function_exists('each')) {
    function each(&$array)
    {
        $key = key($array);
        if ($key === null) {
            return false;
        }
        $value = current($array);
        next($array);
        return [0 => $key, 1 => $value, 'key' => $key, 'value' => $value];
    }
}

if (!function_exists('split')) {
    function split($pattern, $subject, $limit = -1)
    {
        return preg_split('~' . str_replace('~', '\\~', $pattern) . '~', $subject, $limit);
    }
}

if (!function_exists('ereg')) {
    function ereg($pattern, $subject, &$matches = null)
    {
        return preg_match('~' . str_replace('~', '\\~', $pattern) . '~', $subject, $matches);
    }
}

if (!function_exists('eregi')) {
    function eregi($pattern, $subject, &$matches = null)
    {
        return preg_match('~' . str_replace('~', '\\~', $pattern) . '~i', $subject, $matches);
    }
}
