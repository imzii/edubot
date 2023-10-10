<?php

require_once 'functions.php';

$url = buildUrl();
$mealObject = fetchMealData($url);
$parsedData = parseMealData($mealObject);

$filenames = array('nutrients', 'today', 'tomorrow', 'nextweek');
for ($i = 0; $i < 4; $i++) {
    writeToFile($filenames[$i], $parsedData[$i]);
}

?>
