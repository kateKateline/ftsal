<?php
require_once "../includes/config.php";
require_once "../vendor/autoload.php";

use Dompdf\Dompdf;

$dompdf = new Dompdf();
$data = $conn->query("SELECT * FROM lapangan");

$html = "
<h2>Data Lapangan</h2>
<table border='1' cellpadding='8' cellspacing='0' width='100%'>
<tr>
<th>ID</th>
<th>Nama</th>
<th>Harga/Jam</th>
<th>Status</th>
</tr>";

while($d = $data->fetch_assoc()) {
    $html .= "
    <tr>
        <td>{$d['id']}</td>
        <td>{$d['nama']}</td>
        <td>{$d['harga_per_jam']}</td>
        <td>{$d['status']}</td>
    </tr>";
}

$html .= "</table>";

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "portrait");
$dompdf->render();
$dompdf->stream("lapangan.pdf");
