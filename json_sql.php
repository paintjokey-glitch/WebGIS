<?php
    header('Content-Type: application/json; charset=utf-8');

    // Database connection
    $hostname_db = "localhost";
    $database_db="phitsanulok"; 
    $username_db = "postgres";
    $password_db = "postgres";
    $port_db     = "5433";

    // เช็คการเชื่อมต่อ
    $db = pg_connect("host=$hostname_db dbname=$database_db user=$username_db password=$password_db port=$port_db");
    if (!$db) {
        echo json_encode(['error' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล']);
        exit;
    }

    // รับค่าจาก GET หรือกำหนดค่า default
    $lat      = isset($_GET['lat']) ? floatval($_GET['lat']) : 16.416;
    $lng      = isset($_GET['lng']) ? floatval($_GET['lng']) : 102.832;
    $distance = isset($_GET['distance']) ? floatval($_GET['distance']) : 1000;

    // Query ข้อมูลโดยใช้ ST_DistanceSphere เชื่อมกับจุด 
    $sql = "
        SELECT gid, lm_name, ST_AsGeoJSON(geom) AS geojson
        FROM landmark
        WHERE ST_DistanceSphere(
           ST_SetSRID(ST_Point($lng, $lat), 4326),
            geom
        ) <= $distance;
    ";

    //เชื่อมกับ tam_nam_t ตำบล
    // $sql = "
    //    SELECT gid, tam_nam_t, ST_AsGeoJSON(ST_Transform(t.geom,4326)) AS geojson
    //    FROM tha_tambon t
    //    WHERE ST_DWithin(
    //        ST_Transform(ST_GeomFromText('POINT($lng $lat)',4326),3857),
    //        t.geom,
    //        $distance
    //    );

    // ";

    $query = pg_query($db, $sql);
    if (!$query) {
        echo json_encode(['error' => 'Query ล้มเหลว']);
        exit;
    }

    // สร้าง GeoJSON
    $geojson = array(
        'type' => 'FeatureCollection',
        'features' => array()
    );

    while ($row = pg_fetch_assoc($query)) {
        $geom = json_decode($row['geojson'], true);

        // ถ้า geometry type เป็น MultiPoint แต่มีแค่ 1 จุด ให้แปลงเป็น Point 
        if($geom['type'] == 'MultiPoint' && count($geom['coordinates']) == 1) {
            $geom['type'] = 'Point';
            $geom['coordinates'] = $geom['coordinates'][0];
        }

        $feature = array(
            'type' => 'Feature',
            'geometry' => $geom,
            'properties' => array(
                'gid' => $row['gid'],
                'lm_name' => $row['lm_name'] //แสดงจุด
                //'tam_nam_t' => $row['tam_nam_t'] //แสดงตำบล 
            )
        );
        $geojson['features'][] = $feature;
    }

    echo json_encode($geojson, JSON_UNESCAPED_UNICODE);
?>
Ple
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