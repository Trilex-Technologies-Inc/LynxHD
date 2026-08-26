<?php
function tasks_installed(){global $pre;$table=hd_module_escape($pre.'task');$r=mysql_query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='$table'");$row=$r?mysql_fetch_array($r):false;return $row&&(int)$row[0]===1;}
function tasks_install(){global $pre;return (bool)mysql_query("CREATE TABLE IF NOT EXISTS {$pre}task (id INT UNSIGNED NOT NULL AUTO_INCREMENT,title VARCHAR(255) NOT NULL,description TEXT,priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',due_date DATE DEFAULT NULL,assigned_to INT NOT NULL DEFAULT 0,is_complete TINYINT(1) NOT NULL DEFAULT 0,created_by INT NOT NULL DEFAULT 0,completed_by INT NOT NULL DEFAULT 0,created_at INT UNSIGNED NOT NULL,updated_at INT UNSIGNED NOT NULL,completed_at INT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(id),KEY assigned_to(assigned_to),KEY due_date(due_date),KEY is_complete(is_complete)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");}
function tasks_uninstall(){global $pre;return (bool)mysql_query("DROP TABLE IF EXISTS {$pre}task");}
function tasks_enable(){return tasks_installed();}
function tasks_disable(){return tasks_installed();}
