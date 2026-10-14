<?php

declare(strict_types=1);

function level_catalog(): array
{
    return [
        [1, 'hsk-1', 'HSK 1', 'inicial', 'Etapa inicial', 'Primeros pasos: sonidos, saludos, familia, hora, comida y el día. 300 palabras.', 300, 300],
        [2, 'hsk-2', 'HSK 2', 'inicial', 'Etapa inicial', 'Suma 200 palabras para seguir en la vida diaria. Acumuladas: 500.', 200, 500],
        [3, 'hsk-3', 'HSK 3', 'inicial', 'Etapa inicial', 'Suma 500 palabras. Desde este nivel el oral entra en el examen. Acumuladas: 1000.', 500, 1000],
        [4, 'hsk-4', 'HSK 4', 'media', 'Etapa media', 'Empieza la etapa media. Suma 1000 palabras. Acumuladas: 2000.', 1000, 2000],
        [5, 'hsk-5', 'HSK 5', 'media', 'Etapa media', 'Suma 1600 palabras. Acumuladas: 3600.', 1600, 3600],
        [6, 'hsk-6', 'HSK 6', 'media', 'Etapa media', 'Cierra la etapa media. Suma 1800 palabras. Acumuladas: 5400.', 1800, 5400],
        [7, 'hsk-7', 'HSK 7', 'avanzada', 'Etapa avanzada', 'Primer bloque de estudio del tramo alto. El examen de 7, 8 y 9 es uno solo.', 1867, 7267],
        [8, 'hsk-8', 'HSK 8', 'avanzada', 'Etapa avanzada', 'Segundo bloque de las 5600 palabras nuevas del tramo alto.', 1867, 9134],
        [9, 'hsk-9', 'HSK 9', 'avanzada', 'Etapa avanzada', 'Último bloque. Con este nivel se llega a las 11000 palabras del HSK.', 1866, 11000],
    ];
}

function split_sql(string $sql): array
{
    $parts = preg_split('/;\s*(?:\R|$)/', $sql) ?: [];
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return $out;
}

function lowest_row(array $byHanzi, string $hanzi): ?array
{
    $rows = $byHanzi[$hanzi] ?? [];
    if (!$rows) {
        return null;
    }
    usort($rows, static fn (array $a, array $b): int => $a['level'] <=> $b['level']);
    return $rows[0];
}

function lesson_words(array $pins, array $extras, array $byHanzi): array
{
    $words = [];
    $seen = [];
    foreach ($pins as $hanzi) {
        if (isset($seen[$hanzi])) {
            continue;
        }
        $row = lowest_row($byHanzi, $hanzi);
        if (!$row) {
            throw new RuntimeException('Falta en el léxico: ' . $hanzi);
        }
        $seen[$hanzi] = true;
        $words[] = $row;
    }
    foreach ($extras as $extra) {
        $hanzi = $extra['hanzi'];
        if (isset($seen[$hanzi])) {
            continue;
        }
        $row = lowest_row($byHanzi, $hanzi);
        $words[] = $row ?: [
            'hanzi' => $hanzi,
            'pinyin' => $extra['pinyin'],
            'es' => $extra['es'],
            'level' => 0,
            'ex_zh' => '',
            'ex_py' => '',
            'ex_es' => '',
        ];
        $seen[$hanzi] = true;
    }
    return $words;
}

function build_content(array $class, array $words): array
{
    $sections = [[
        'type' => 'text',
        'title' => $class['title'],
        'body' => $class['body'],
    ]];
    $sections[] = [
        'type' => 'vocab',
        'title' => 'Vocabulario',
        'items' => array_map(static fn (array $word): array => [
            'hanzi' => $word['hanzi'],
            'pinyin' => $word['pinyin'],
            'es' => $word['es'],
        ], $words),
    ];

    if (!empty($class['dialogue'])) {
        $examples = array_map(static fn (array $line): array => [
            'zh' => $line['zh'],
            'pinyin' => $line['py'],
            'es' => $line['es'],
        ], $class['dialogue']);
        $exerciseItems = [];
        foreach (array_slice($class['dialogue'], 0, 4) as $line) {
            $exerciseItems[] = [
                'prompt' => $line['es'],
                'answer' => $line['zh'],
                'hint' => $line['py'],
            ];
        }
        $exerciseTitle = 'Escribí el chino';
        $exercisePrompt = 'Escribí la frase en caracteres.';
        $exampleTitle = 'Diálogo';
    } else {
        $examples = [];
        foreach ($words as $word) {
            if (($word['ex_zh'] ?? '') === '') {
                continue;
            }
            $examples[] = [
                'zh' => $word['ex_zh'],
                'pinyin' => $word['ex_py'],
                'es' => $word['ex_es'],
            ];
            if (count($examples) >= 8) {
                break;
            }
        }
        if (count($examples) < 4) {
            foreach ($words as $word) {
                $examples[] = [
                    'zh' => $word['hanzi'],
                    'pinyin' => $word['pinyin'],
                    'es' => short_gloss($word['es']),
                ];
                if (count($examples) >= 6) {
                    break;
                }
            }
        }
        $exerciseItems = [];
        foreach (array_slice($words, 0, 6) as $word) {
            $exerciseItems[] = [
                'prompt' => short_gloss($word['es']),
                'answer' => $word['hanzi'],
                'hint' => $word['pinyin'],
            ];
        }
        $exerciseTitle = 'Práctica';
        $exercisePrompt = 'Escribí el carácter. También vale el pinyin.';
        $exampleTitle = 'Ejemplos';
    }

    $sections[] = ['type' => 'examples', 'title' => $exampleTitle, 'items' => $examples];
    $sections[] = [
        'type' => 'exercise',
        'title' => $exerciseTitle,
        'prompt' => $exercisePrompt,
        'items' => $exerciseItems,
    ];
    return ['sections' => $sections];
}

function build_quiz(array $class, array $words, array $pool): array
{
    if (!empty($class['quiz'])) {
        $questions = [];
        foreach ($class['quiz'] as $index => $question) {
            $options = [];
            $correct = 'a';
            foreach ($question['options'] as $optionIndex => $label) {
                $id = chr(97 + $optionIndex);
                $options[] = ['id' => $id, 'label' => $label];
                if ($optionIndex === (int) $question['correct']) {
                    $correct = $id;
                }
            }
            $questions[] = [
                'id' => 'q' . ($index + 1),
                'prompt' => $question['prompt'],
                'options' => $options,
                'correct' => $correct,
            ];
        }
        return $questions;
    }

    $candidates = $words;
    shuffle($candidates);
    $questions = [];
    $used = [];
    $bag = $pool;
    shuffle($bag);
    foreach ($candidates as $word) {
        if (count($questions) >= 5) {
            break;
        }
        $correctLabel = short_gloss($word['es']);
        if ($correctLabel === '' || isset($used[$correctLabel])) {
            continue;
        }
        $distractors = [];
        foreach ($bag as $other) {
            if ($other['hanzi'] === $word['hanzi']) {
                continue;
            }
            $label = short_gloss($other['es']);
            if ($label === '' || $label === $correctLabel || isset($distractors[$label])) {
                continue;
            }
            $distractors[$label] = $label;
            if (count($distractors) >= 3) {
                break;
            }
        }
        if (count($distractors) < 3) {
            continue;
        }
        $labels = array_merge([$correctLabel], array_values($distractors));
        shuffle($labels);
        $options = [];
        $correctId = 'a';
        foreach ($labels as $optionIndex => $label) {
            $id = chr(97 + $optionIndex);
            $options[] = ['id' => $id, 'label' => $label];
            if ($label === $correctLabel) {
                $correctId = $id;
            }
        }
        $used[$correctLabel] = true;
        $questions[] = [
            'id' => 'q' . (count($questions) + 1),
            'prompt' => '¿Qué significa ' . $word['hanzi'] . '?',
            'options' => $options,
            'correct' => $correctId,
        ];
    }
    return $questions;
}

function insert_lesson(PDO $db, int $levelId, int $number, string $title, string $summary, array $content, array $quiz): void
{
    $stmt = $db->prepare(
        'INSERT INTO lessons (level_id, slug, number, title, summary, content_json) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $levelId,
        'clase-' . $number,
        $number,
        $title,
        $summary,
        json_encode($content, JSON_UNESCAPED_UNICODE),
    ]);
    $lessonId = (int) $db->lastInsertId();
    $quizStmt = $db->prepare(
        'INSERT INTO quizzes (lesson_id, title, pass_score, max_attempts, questions_json) VALUES (?, ?, 70, 5, ?)'
    );
    $quizStmt->execute([
        $lessonId,
        'Autoevaluación — ' . $title,
        json_encode($quiz, JSON_UNESCAPED_UNICODE),
    ]);
}

function insert_dictionary(PDO $db, array $rows): void
{
    $prefix = 'INSERT INTO dictionary_entries (hanzi, pinyin, pinyin_plain, espanol, espanol_plain, hsk_level, example_zh, example_pinyin, example_es) VALUES ';
    foreach (array_chunk($rows, 200) as $chunk) {
        $marks = implode(',', array_fill(0, count($chunk), '(?,?,?,?,?,?,?,?,?)'));
        $params = [];
        foreach ($chunk as $row) {
            $es = mb_substr((string) $row['es'], 0, 500);
            $params[] = $row['hanzi'];
            $params[] = $row['pinyin'];
            $params[] = plain_pinyin((string) $row['pinyin']);
            $params[] = $es;
            $params[] = mb_substr(plain_es($es), 0, 500);
            $params[] = (int) $row['level'];
            $params[] = mb_substr((string) ($row['ex_zh'] ?? ''), 0, 255);
            $params[] = mb_substr((string) ($row['ex_py'] ?? ''), 0, 255);
            $params[] = mb_substr((string) ($row['ex_es'] ?? ''), 0, 500);
        }
        $stmt = $db->prepare($prefix . $marks);
        $stmt->execute($params);
    }
}

function generated_class(int $level, int $number, array $words): array
{
    $bits = [];
    foreach (array_slice($words, 0, 3) as $word) {
        $bits[] = mb_substr(short_gloss((string) $word['es']), 0, 28);
    }
    return [
        'number' => $number,
        'title' => mb_substr('Clase ' . $number . ' — ' . implode(', ', $bits), 0, 180),
        'summary' => count($words) . ' palabras nuevas del HSK ' . $level . '.',
        'body' => [
            'Estas palabras son nuevas en el HSK ' . $level . '. Escuchá cada una, leé el ejemplo y después escribí el carácter.',
            'El bloque sigue el orden del léxico de este nivel. Cuando termines la autoevaluación, seguí con la clase que viene.',
        ],
        'review' => false,
    ];
}

function install_run(): string
{
    set_time_limit(0);
    ini_set('memory_limit', '512M');

    $lexiconPath = app_root() . '/data/lexicon.json';
    $hsk1Path = app_root() . '/data/hsk1.json';
    if (!is_file($lexiconPath) || !is_file($hsk1Path)) {
        throw new RuntimeException('Faltan data/lexicon.json o data/hsk1.json');
    }

    $name = (string) config()['db']['name'];
    db_server()->exec(
        'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $db = db();
    foreach (split_sql((string) file_get_contents(app_root() . '/sql/schema.sql')) as $statement) {
        $db->exec($statement);
    }

    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['self_assessments', 'lesson_progress', 'course_certificates', 'certificates', 'quizzes', 'lessons', 'dictionary_entries', 'levels'] as $table) {
        $db->exec('TRUNCATE TABLE ' . $table);
    }
    $db->exec('SET FOREIGN_KEY_CHECKS=1');

    $levelStmt = $db->prepare(
        'INSERT INTO levels (id, slug, title, stage, stage_label, description, words_new, words_cumulative, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach (level_catalog() as $level) {
        $levelStmt->execute([$level[0], $level[1], $level[2], $level[3], $level[4], $level[5], $level[6], $level[7], $level[0]]);
    }

    $lexicon = json_decode((string) file_get_contents($lexiconPath), true, 512, JSON_THROW_ON_ERROR);
    insert_dictionary($db, $lexicon);

    $byHanzi = [];
    $byLevel = [];
    foreach ($lexicon as $row) {
        $byHanzi[$row['hanzi']][] = $row;
        $byLevel[(int) $row['level']][] = $row;
    }

    $hsk1 = json_decode((string) file_get_contents($hsk1Path), true, 512, JSON_THROW_ON_ERROR);
    $lessonCounts = [];
    foreach ($hsk1 as $class) {
        $words = lesson_words($class['pins'], $class['extras'] ?? [], $byHanzi);
        $content = build_content($class, $words);
        $quiz = build_quiz($class, $words, $byLevel[1] ?? $words);
        insert_lesson($db, 1, (int) $class['number'], $class['title'], $class['summary'], $content, $quiz);
        $lessonCounts[1] = ($lessonCounts[1] ?? 0) + 1;
    }

    for ($level = 2; $level <= 9; $level++) {
        $rows = $byLevel[$level] ?? [];
        $chunks = array_chunk($rows, 25);
        $number = 1;
        foreach ($chunks as $chunk) {
            $class = generated_class($level, $number, $chunk);
            $content = build_content($class, $chunk);
            $quiz = build_quiz($class, $chunk, $rows);
            insert_lesson($db, $level, $number, $class['title'], $class['summary'], $content, $quiz);
            $number++;
        }
        $lessonCounts[$level] = $number - 1;
    }

    $lines = ['Léxico: ' . count($lexicon)];
    foreach ($lessonCounts as $level => $count) {
        $lines[] = 'HSK ' . $level . ': ' . $count . ' clases';
    }
    file_put_contents(app_root() . '/storage/installed.lock', date('c'));
    return implode("\n", $lines);
}
