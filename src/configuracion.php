<?php
 #Archivo de configuracion de la base de datos
 
    define("PG_DB"  , "uvruteo");
	define("PG_HOST", "localhost");
	define("PG_USER", "user");
	define("PG_PSWD", "user");
	define("PG_PORT", "5432");
	
	$dbcon = pg_connect("dbname=".PG_DB." host=".PG_HOST." user=".PG_USER ." password=".PG_PSWD." port=".PG_PORT."");


?>
