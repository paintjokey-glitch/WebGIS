<?php
    $hostname_db = "localhost";
    $database_db="phitsanulok";
    $username_db = "postgres";
    $password_db = "postgres";
    $port_db     = "5433";

    $db = pg_connect("host=$hostname_db port=$port_db dbname=$database_db user=$username_db password=$password_db");
    if (!$db) {
        die(json_encode(["error" => "Connection failed"]));
    }

    $lat  = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
    $lng  = isset($_GET['lng']) ? floatval($_GET['lng']) : 0;
    $name = isset($_GET['name']) ? pg_escape_string($_GET['name']) : '';

    pg_query($db, "INSERT INTO points (geom, name) VALUES (ST_SetSRID(ST_Point($lng, $lat), 4326), '$name')");

    $query = pg_query($db, "SELECT *, ST_AsGeoJSON(geom, 5) AS geojson FROM points");

    $geojson = array(
        'type'     => 'FeatureCollection',
        'features' => array()
    );

    while ($edge = pg_fetch_assoc($query)) {
        $feature = array(
            'type'     => 'Feature',
            'geometry' => json_decode($edge['geojson'], true),
            'properties' => array(
                'gid'  => $edge['gid'],
                'name' => $edge['name']
            )
        );
        array_push($geojson['features'], $feature);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($geojson);
?>