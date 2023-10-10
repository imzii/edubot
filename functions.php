<?php

require_once 'config.php';

function buildUrl() {
    $today = date('Ymd');
    $next_friday = date('Ymd', strtotime('next monday +4days'));

    $params = [
        'KEY' => API_KEY,
        'Type' => 'xml',
        'pIndex' => '1',
        'pSize' => '12',
        'ATPT_OFCDC_SC_CODE' => ATPT_OFCDC_SC_CODE,
        'SD_SCHUL_CODE' => SD_SCHUL_CODE,
        'MLSV_FROM_YMD' => $today,
        'MLSV_TO_YMD' => $next_friday
    ];

    return API_ENDPOINT . '?' . http_build_query($params, '');
}

function fetchMealData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if ($response === false) {
        die('Curl failed: ' . curl_error($ch));
    }

    curl_close($ch);

    $object = simplexml_load_string($response);

    if ($object === false) {
        die('Failed to parse XML');
    }

    return $object;
}

function parseMealData($object) {
    $days = array('월', '화', '수', '목', '금');
    $data = array();
    
    $count = $object->head->list_total_count;
    
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

    return $data;
}

function writeToFile($filename, $data) {
    $file = fopen($filename . '.json', 'w') or die('Unable to open file!');

    $jsonData = [
        "version" => "2.0",
        "template" => [
            "outputs" => [
                [
                    "simpleText" => [
                        "text" => $data
                    ]
                ]
            ]
        ]
    ];

    $json = json_encode($jsonData, JSON_UNESCAPED_UNICODE);

    fwrite($file, $json);
    fclose($file);
}

?>
