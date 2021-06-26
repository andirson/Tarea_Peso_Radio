<?php

  //configuracion de la conexion a la base de datos

   include('configuracion.php');
   
   session_start();
   
   if(!isset($_POST['peticion'])){
		$_POST['peticion'] = "peticion";
	}

	if(!isset($_POST['parametros'])){
		$_POST['parametros'] = "parametros";
	}
   
   $peticion = $_POST['peticion'];
   $parametros = $_POST['parametros'];
   
   switch($peticion)
   {
		//Caso para recuperar los edificios de la base de datos
		case 'recupera-edificios-geojson':
		{
			$sql="SELECT row_to_json(fc)
			 FROM ( SELECT 'FeatureCollection' As type, array_to_json(array_agg(f)) As features
			 FROM (SELECT 'Feature' As type
				, ST_AsGeoJSON(lg.the_geom)::json As geometry
				, row_to_json((SELECT l FROM (SELECT osm_id , name, st_area(st_transform(the_geom,3115))/10000 as area_edif ) As l
				  )) As properties
			   FROM edificios_univalle As lg  where ST_IsValid(the_geom) ) As f )  As fc;";
   
			$query = pg_query($dbcon,$sql);
			$row = pg_fetch_row($query);
			echo $row[0];
			break;
		}
		//Caso para recuperar los sitios de interes ( TAREA )
		case 'recupera-sitios-interes-geojson':
		{
			$sql="SELECT row_to_json(fc)
			 FROM ( SELECT 'FeatureCollection' As type, array_to_json(array_agg(f)) As features
			 FROM (SELECT 'Feature' As type
				, ST_AsGeoJSON(lg.the_geom)::json As geometry
				, row_to_json((SELECT l FROM (SELECT osm_id , name, type ) As l
				  )) As properties
			   FROM sitiosinteres_univalle As lg  where ST_IsValid(the_geom) ) As f )  As fc;";
   
			$query = pg_query($dbcon,$sql);
			$row = pg_fetch_row($query);
			echo $row[0];
			break;
		}


		//CASO PARA GENERAR UNA VISTA CON LA RUTA MAS CORTA
		// Tarea remplazar caso, por funcion en plgsql (implementada en clases anteriores)
		case 'genera-ruta-mascorta':
		{
				$x1 = $parametros['x1'];
				$y1 = $parametros['y1'];
				$x2 = $parametros['x2'];
				$y2 = $parametros['y2'];

				/*$sql="CREATE OR REPLACE VIEW rutatemporal AS SELECT seq, node AS node, edge AS edge, cost, b.the_geom FROM pgr_dijkstra('
                SELECT gid AS id,
                         source::integer,
                         target::integer,
                         costo::double precision AS cost
                        FROM redpeatonal_univalle',
                (select o.id::integer from (
 select id, st_distance(the_geom, ST_SetSRID(st_makepoint($x1,$y1),4326)  )  from  redpeatonal_univalle_vertices_pgr  
 order by 2 asc limit 1  )as o),(select d.id::integer  from (
 select id, st_distance(the_geom, ST_SetSRID(st_makepoint($x2,$y2),4326)  )  from  redpeatonal_univalle_vertices_pgr  
 order by 2 asc limit 1  )as d), false ) a LEFT JOIN redpeatonal_univalle b ON (a.edge = b.gid);";*/
 
			//NOTA: DESCOMENTAR PARA EJEMPLO 10 y COMENTAR DESDE LA LINEA 65 hasta la 75
			$sql = "SELECT creaRutaMasLatLon($x1,$y1,$x2,$y2);";

			//Ejecutar QUERY SQL
			$query = pg_query($dbcon,$sql);
					
			if($query)
			{
				//si se ejecuto la consulta con exito retorno un identificador
				echo "NUEVA_RUTA_CREADA";
			}else
			{
				//si NO se ejecuto la consulta retorno un identificador
				echo "NOSEPUDOCREARLARUTA";
			}
			break;
		}

		//CASO PARA RETORNAR LA RUTA GENERADA
		case 'recupera-ruta-geojson':
		{
			$sql=" SELECT row_to_json(fc)
	 FROM ( SELECT 'FeatureCollection' As type, array_to_json(array_agg(f)) As features
	 FROM (SELECT 'Feature' As type
		, ST_AsGeoJSON(lg.the_geom)::json As geometry
		, row_to_json((SELECT l FROM (SELECT node, edge) As l
		  )) As properties
	   FROM rutatemporal As lg   ) As f )  As fc;";
			
				$query3 = pg_query($dbcon,$sql);
				$row = pg_fetch_row($query3);
				echo $row[0];
			break;
		}
		
		//CASO PARA CONSULTAR LA INFORMACION DE LA RUTA CREADA
		case 'info-ruta-creada':
		{
			$sql = "SELECT * FROM info_rutatemporal limit 1;";
			$query4 = pg_query($dbcon,$sql);
			
			$tabla_html = "<table class='table table-bordered' style='width: 100%'><tr class='danger'>
			  <th>Desde</th>
			  <th>Hasta</th>
			  <th>Distancia Total Ruta</th>
			</tr>";
			
			while ($row = pg_fetch_row($query4)) 
			{
				$tabla_html .=  '<tr>';
				$tabla_html .=  '<td>' . $row[1] . '</td>';
				$tabla_html .=  '<td>' . $row[4] . '</td>';
				$tabla_html .=  '<td>' . round( $row[6] ,2 ) . ' metros</td>';
				$tabla_html .=  '</tr>';
			}

			$tabla_html.='</table>';

			echo $tabla_html;

			break;
		}

		//CASO PARA REGISTRAR UN REPORTE DESDE UNA VENTANA MODAL
		case 'registro-desde-ventana-modal':
		{
			$px = $parametros['x'];
			$py = $parametros['y'];
			$ppeso = $parametros['peso'];
			$pradio = $parametros['radio'];

			//$sql = "INSERT INTO reporte_tarea(x,y,peso,radio,fecha_registro)VALUES($px,$py,'$ppeso','$pradio',now());";
			$sql = "UPDATE redpeatonal_univalle SET costo=costo*$ppeso
					WHERE st_intersects(st_buffer(st_transform(ST_Setsrid(ST_Point($px,$py),4326),3115),$pradio),st_transform(redpeatonal_univalle.the_geom,3115));";

			$query = pg_query($dbcon,$sql);

			if($query)
			{
				//si se ejecuto la consulta con exito retorno un identificador
				echo "SE CAMBIO EL PESO DE LOS SEGMENTOS";
			}else
			{
				//si NO se ejecuto la consulta retorno un identificador
				echo "NO SE REALIZO NINGUN CAMBIO";
			}

		    break;
		}
		
		case 'limpiar_costos_ruta':
			{

				$sql = "UPDATE redpeatonal_univalle SET costo = st_length(st_transform(the_geom,3115));";

				$query = pg_query($dbcon,$sql);

				if($query)
				{
					//si se ejecuto la consulta con exito retorno un identificador
					echo "SE RESETIO LOS COSTOS";
				}else
				{
					//si NO se ejecuto la consulta retorno un identificador
					echo "NO SE RESETIARON LOS COSTOS";
				}
	
				break;
			}
   }
    

?>