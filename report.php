
<?php

$sqlite_file = '/Users/brianherbert/Library/Application Support/Anki2/User 1/collection.anki2';

$pdo = new PDO("sqlite:" . $sqlite_file)or die("Could not open database");

$words = [];

$query = "SELECT trim(trim(trim(notes.sfld, CHAR(10)), '&nbsp;')) AS word,
                   revlog.id AS revision_time,
                   revlog.ease AS answer,
                   cards.reps AS repetitions,
                   cards.lapses AS lapses
            FROM cards
            LEFT OUTER JOIN notes ON cards.nid = notes.id
            LEFT OUTER JOIN revlog ON cards.id = revlog.cid
            ORDER BY word,
                     revision_time;";

$csv = "Word,Last Revision,Percent Correct,Repetitions,Lapses\n";

$html = '<table>';
$html .= '<tr><th>Word</th><th>Last Revision</th><th>Percent Correct</th><th>Repetitions</th><th>Lapses</th></tr>';

$stmt = $pdo->query($query);
$projects = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $w = $r['word'];

    if (!isset($words[$w])) {
        $words[$w] = ['word'                    => $w,
                      'last_revision_timestamp' => 0,
                      'last_revision'           => '',
                      //'all_answers'             => [],
                      'num_correct'             => 0,
                      'num_wrong'               => 0,
                      'percent_correct'         => 0,
                      'repetitions'             => 0,
                      'lapses'                  => 0];
    }

    if ($r['revision_time'] > $words[$w]['last_revision_timestamp']) {
        $words[$w]['last_revision_timestamp'] = $r['revision_time'];
        $words[$w]['last_revision'] = date('Y-m-d',($r['revision_time']/1000));
    }

    if ($r['repetitions'] > $words[$w]['repetitions']) {
        $words[$w]['repetitions'] = $r['repetitions'];
    }

    if ($r['lapses'] > $words[$w]['lapses']) {
        $words[$w]['lapses'] = $r['lapses'];
    }

    //$words[$w]['all_answers'][] = $r['answer'];

    if ($r['answer'] > 1) {
        $words[$w]['num_correct']++;
    } else{
        $words[$w]['num_wrong']++;
    }
}

foreach($words AS &$word) {
    $total = $word['num_correct'] + $word['num_wrong'];
    $word['percent_correct'] = round(($word['num_correct'] / $total),2)*100;
    //echo $word['percent_correct']." -- ".$word['word']." total is ".$total." and correct is ".$word['num_correct']."\n";
}

foreach($words AS $word) {
    $csv .= '"'.str_replace('"', '""', $word['word']).'",'.$word['last_revision'].','.$word['percent_correct'].','.$word['repetitions'].','.$word['lapses']."\n";
    $html .= '<tr><td>'.$word['word'].'</td><td>'.$word['last_revision'].'</td><td>'.$word['percent_correct'].'</td><td>'.$word['repetitions'].'</td><td>'.$word['lapses'].'</td></tr>';
}

$html .= '</table>';

file_put_contents('report.html', $html);
file_put_contents('report.csv', $csv);
