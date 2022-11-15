<?php
$days = array('월', '화', '수', '목', '금');
$params = array(
    'KEY' => '5c21b59a1000460eb087316813224fb5',
    'Type' => 'xml',
    'pIndex' => '1',
    'pSize' => '12',
    'ATPT_OFCDC_SC_CODE' => 'J10',
    'SD_SCHUL_CODE' => '7530138',
);
$today = date('Ymd');
$next_monday = date('Ymd', strtotime('next monday'));
$next_friday = date('Ymd', strtotime('next monday +4days'));
$params = array_merge($params, array('MLSV_FROM_YMD' => $today, 'MLSV_TO_YMD' => $next_friday));
$url = 'https://open.neis.go.kr/hub/mealServiceDietInfo?' . http_build_query($params, '');
echo $url;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);

$object = simplexml_load_string($response);
$count = $object->head->list_total_count;

$data = array();
$data[0] = str_replace('<br/>', '\n', $object->row->NTR_INFO);
$data[1] = str_replace('<br/>', '\n', $object->row[0]->DDISH_NM);
$data[2] = str_replace('<br/>', '\n', $object->row[1]->DDISH_NM);

for ($i = 0; $i < 3; $i++) {
    if ($data[$i] == '') {
        $data[$i] = '정보가 없습니다.';
    }
}

$data[0] = date('Y년 m월 d일', strtotime($object->row[0]->MLSV_YMD)) . '\n<오늘의 영양 정보>\n' . $data[0];
$data[1] = date('Y년 m월 d일', strtotime($object->row[0]->MLSV_YMD)) . '\n<오늘의 급식 메뉴>\n' . $data[1];
$data[2] = date('Y년 m월 d일', strtotime($object->row[1]->MLSV_YMD)) . '\n<내일의 급식 메뉴>\n' . $data[2];

for ($i = 0; $i < 5; $i++) {
    $data[3] = $data[3] . date('Y년 m월 d일 (' . $days[$i] . ')', strtotime($object->row[$count - 5 + $i]->MLSV_YMD)) . '\n' . $object->row[$count - 5 + $i]->DDISH_NM . '\n\n';
}
$data[3] = '<다음 주 급식 메뉴>\n' . str_replace('<br/>', '\n', $data[3]);

curl_close($ch);

$filenames = array('nutrients', 'today', 'tomorrow', 'nextweek');
for ($i = 0; $i < 4; $i++) {
    $file = fopen($filenames[$i] . '.json', 'w') or die('Unable to open file!');
    $json = '{
        "version": "2.0",
        "template": {
            "outputs": [
                {
                    "simpleText": {
                        "text": "' . $data[$i] . '"
                    }
                }
            ]
        }
    }';
    fwrite($file, $json);
    fclose($file);
}
?>