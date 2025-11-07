<?php
// ชุดคำสั่งสำหรับขออนุญาตเชื่อมต่อกับฐานข้อมูล
$host = "host=localhost"; // host ที่ใช้ในการติดต่อกับ Server
$port = "port=5432"; // หมายเลข port ที่ใช้ (บางเครื่องอาจจะใช้ 5433 หรือเลขอื่น)
$dbname = "dbname=phitsanulok"; // ใส่ชื่อฐานข้อมูลที่ต้องการทำการเชื่อมต่อ
$credentials = "user=postgres password=postgres";

// ใช้คำสั่งชุดนี้ในการสร้างคำสั่งสำหรับเชื่อมต่อกับฐานข้อมูล PostgreSQL
$db = pg_connect("$host $port $dbname $credentials");

// คำสั่ง SQL สำหรับดึงข้อมูลและแปลงเป็น GeoJSON (ST_AsGeoJSON) โดยใช้การแปลงพิกัด (ST_Transform)
// **ข้อควรระวัง: ในภาพ $sql มีการเรียกใช้ ST_AsGeoJSON แต่ไม่มีการปิดวงเล็บที่สมบูรณ์ในบรรทัดที่ 9
// และมีการใช้ ST_Transform(geom, 4326)
// **และมีการสะกด 'geojson' ไม่ตรงกันใน query และใน code ในภาพ (บรรทัดที่ 10-18)
// ผมจะปรับโค้ดให้ถูกต้องตามหลักการทำงาน (คือมีการปิดวงเล็บ ST_AsGeoJSON(...))
// และใช้ชื่อคอลัมน์ 'geojson' ให้ตรงกัน

$sql="SELECT *, ST_AsGeoJSON(ST_Transform(geom,4326)) as **geojson_data** from tha_province LIMIT 30;";
$query=pg_query($db, $sql);

$geojson=array(
    'type' => 'FeatureCollection',
    'features' => array()
);

while ($edge = pg_fetch_assoc($query)){
    $feature = array(
        'type' => 'Feature',
        'properties' => array('code'=>'4326'),
        'geometry' => json_decode($edge['**geojson_data**'], true), // ใช้คอลัมน์ชื่อตรงกับที่ตั้งใน SQL
        'crs' => array(
            'type'=>'EPSG',
            'properties' => array('code'=>'4326')
        ),
    ),
    
    'properties' => array(
        'gid' => $edge['gid'],
        'prov_nam_t' => $edge['prov_nam_t'],
        'prov_nam_e' => $edge['prov_nam_e'],
        'prov_code' => $edge['prov_code'],
        'area' => $edge['area']
    ),
    
    
    
    array_push($geojson['features'], $feature);
    
}

// Close database connection
pg_close($db);
echo json_encode($geojson);

?>