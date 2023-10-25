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
    $daysOfWeek = array('월', '화', '수', '목', '금');
    $mealData = array();

    $totalCount = $object->head->list_total_count;

    $todayInfo = str_replace('<br/>', '\n', $object->row->NTR_INFO);
    $todayMenu = str_replace('<br/>', '\n', $object->row[0]->DDISH_NM);
    $tomorrowMenu = str_replace('<br/>', '\n', $object->row[1]->DDISH_NM);

    $todayInfo = $todayInfo ?: '오늘 급식 데이터가 없습니다.';
    $todayMenu = $todayMenu ?: '오늘 급식 데이터가 없습니다.';
    $tomorrowMenu = $tomorrowMenu ?: '내일 급식 데이터가 없습니다.';

    $todayInfo = date('Y년 m월 d일', strtotime($object->row[0]->MLSV_YMD)) . '\n<오늘의 영양 정보>\n' . $todayInfo;
    $todayMenu = date('Y년 m월 d일', strtotime($object->row[0]->MLSV_YMD)) . '\n<오늘의 급식 메뉴>\n' . $todayMenu;
    $tomorrowMenu = date('Y년 m월 d일', strtotime($object->row[1]->MLSV_YMD)) . '\n<내일의 급식 메뉴>\n' . $tomorrowMenu;

    $nextWeekMenu = '<다음 주 급식 메뉴>\n';
    $nextMondayDate = date('Ymd', strtotime('next monday'));

    for ($i = 0; $i < 5; $i++) {
        $temp = $object->row[$totalCount - 5 + $i];
        if (strtotime($temp->date) >= strtotime($nextMondayDate)) {
            $nextWeekMenu .= date('Y년 m월 d일 (' . $daysOfWeek[$i] . ')', strtotime($temp->date)) . '\n' . $temp->DDISH_NM . '\n\n';
        }
    }

    if ($nextWeekMenu === '<다음 주 급식 메뉴>\n') {
        $nextWeekMenu = '다음 주 급식 데이터가 없습니다.';
    } else {
        $nextWeekMenu = str_replace('<br/>', '\n', $nextWeekMenu);
    }

    $mealData = array($todayInfo, $todayMenu, $tomorrowMenu, $nextWeekMenu);
    return $mealData;
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
    $json = str_replace('\\\\n', '\\n', $json);

    echo $json;

    fwrite($file, $json);
    fclose($file);
}

?>
