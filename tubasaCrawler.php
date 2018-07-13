<?php
header('Content-type: application/json');

function rand_color() {
    return sprintf('%06X', mt_rand(0, 0xFFFFFF));
}

function request_url_contents($url) {
  $tries = 0;
  do {
    if ($tries > 0) sleep(1); # Wait for a sec before retrieving again
    $contents = file_get_contents($url);
    $tries++;
  }
  while ($tries <= 5 && $contents === FALSE);
  return $contents;
}

function crawlStops($lineId, $stops) {
  $crawledStops = array();
  $numberOfStops = count ($stops[0]);
  for ($j=0; $j < $numberOfStops; $j++) {
    $currentStop["idp"] = $stops[1][$j];
    $currentStop["ido"] = $stops[2][$j];

    $url = 'http://badajoz.twa.es/code/getparadas.php?idl=' . urlencode($lineId) . '&idp=' . urlencode($currentStop["idp"]) . '&ido=' . urlencode($currentStop["ido"]);
    $scheduleTimeRaw = request_url_contents($url);

    preg_match_all('/Parada:\W(.*?)<\/h3>/', $scheduleTimeRaw, $scheduleTime);

    mb_internal_encoding('UTF-8');
    $currentStop["label"] = (ucwords(mb_strtolower($scheduleTime[1][0])));

    array_push ($crawledStops, $currentStop);
  }
  return $crawledStops;
}

$url = 'http://badajoz.twa.es/code/getlineas.php';
$linesRaw = request_url_contents($url);
preg_match_all('/mostrarParadas\(\'(L.*?)\'\).*LINEA\W(.*?)<\/a>/mi', $linesRaw, $lines);

$numberOfLines = count ($lines[0]);
$result["lines"] = array();

for ($i=0; $i < $numberOfLines; $i++) {
  $lineId = $lines[1][$i];
  $lineName = $lines[2][$i];

  $currentLine["code"] = $lineId;
  $currentLine["label"] = $lineName;
  $currentLine["color"] = rand_color();

  $url = 'http://badajoz.twa.es/code/getparadas.php?idl=' . urlencode($lineId);
  $stopsRaw = request_url_contents($url);

  preg_match_all('/id="ar0-.*?value,\W\'(.*?)::(\d+.\d+)\'/', $stopsRaw, $stops);
  $currentLine["stops"]["outbound"] = crawlStops($lineId, $stops);


  preg_match_all('/id="ar1-.*?value,\W\'(.*?)::(\d+.\d+)\'/', $stopsRaw, $stops);
  $currentLine["stops"]["return"] = array_reverse(crawlStops($lineId, $stops));

  array_push ($result["lines"], $currentLine);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>

