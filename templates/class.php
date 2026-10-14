<?php
$sections = $content['sections'] ?? [];
$attemptCount = count($attempts);
$maxAttempts = $quiz ? (int) $quiz['max_attempts'] : 5;
$passedAny = false;
foreach ($attempts as $attempt) {
    if ((int) $attempt['passed'] === 1) {
        $passedAny = true;
    }
}
$last = $attempts ? $attempts[$attemptCount - 1] : null;
$lastAnswers = $last ? (json_decode((string) $last['answers_json'], true) ?: []) : [];
$locked = $passedAny || $attemptCount >= $maxAttempts;
$meter = $lessonTotal > 0 ? (int) round($position / $lessonTotal * 100) : 0;
?>
<section class="class-hero">
  <div class="class-hero-inner">
    <p class="breadcrumb">
      <a href="<?= e(url()) ?>">Inicio</a>
      <span aria-hidden="true">/</span>
      <a href="<?= e(url($level['slug'])) ?>"><?= e($level['title']) ?></a>
    </p>
    <span class="class-kicker">Clase <?= str_pad((string) $position, 2, '0', STR_PAD_LEFT) ?> de <?= (int) $lessonTotal ?></span>
    <h1><?= e($lesson['title']) ?></h1>
    <p class="home-hero-line"><?= e($lesson['summary']) ?></p>
    <div class="class-meter" role="presentation">
      <div class="progress-bar"><i style="width: <?= $meter ?>%"></i></div>
    </div>
  </div>
</section>

<article class="lesson">
  <div class="lesson-layout">
    <nav class="lesson-toc" aria-label="En esta clase">
      <p class="toc-label">En esta clase</p>
      <ol>
        <?php foreach ($sections as $index => $section): ?>
          <li>
            <a href="#sec-<?= $index ?>">
              <span><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <?= e($section['title'] ?? ('Sección ' . ($index + 1))) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php if ($quiz): ?>
        <a class="toc-quiz-btn" href="#class-quiz">
          <span class="toc-quiz-kicker">Cierre</span>
          <strong>Autoevaluación</strong>
          <em>Repasá esta clase</em>
        </a>
      <?php endif; ?>
    </nav>
    <div class="lesson-body">
      <?php foreach ($sections as $index => $section): ?>
        <section class="section-block" id="sec-<?= $index ?>">
          <h2><?= e($section['title'] ?? '') ?></h2>
          <?php if (($section['type'] ?? '') === 'text'): ?>
            <div class="prose">
              <?php foreach ($section['body'] as $paragraph): ?>
                <p><?= e($paragraph) ?></p>
              <?php endforeach; ?>
            </div>
          <?php elseif (($section['type'] ?? '') === 'vocab'): ?>
            <div class="vocab-grid">
              <?php foreach ($section['items'] as $item): ?>
                <article class="vocab-card">
                  <button type="button" class="vocab-tile" aria-pressed="false">
                    <strong class="hanzi"><?= e($item['hanzi']) ?></strong>
                    <em class="vocab-py"><?= e($item['pinyin']) ?></em>
                    <span class="vocab-hint">tocá para traducir</span>
                    <span class="vocab-es"><?= e($item['es']) ?></span>
                  </button>
                  <?= audio_button($item['hanzi'], $item['pinyin'], 'Escuchar ' . $item['hanzi']) ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php elseif (($section['type'] ?? '') === 'examples'): ?>
            <div class="example-stack">
              <?php foreach ($section['items'] as $item): ?>
                <article class="example-card">
                  <p class="hanzi"><?= e($item['zh']) ?></p>
                  <p class="pinyin"><?= e($item['pinyin']) ?></p>
                  <p><?= e($item['es']) ?></p>
                  <div class="audio-block">
                    <div class="audio-label"><span class="audio-dot" aria-hidden="true"></span> Escuchar la frase</div>
                    <audio controls preload="none" src="<?= e(audio_src($item['zh'])) ?>">Tu navegador no soporta audio.</audio>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php elseif (($section['type'] ?? '') === 'exercise'): ?>
            <p class="prose"><?= e($section['prompt'] ?? '') ?></p>
            <div class="exercise" data-exercise>
              <ol class="exercise-list">
                <?php foreach ($section['items'] as $itemIndex => $item): ?>
                  <li>
                    <label>
                      <span class="exercise-prompt"><span class="exercise-num"><?= $itemIndex + 1 ?></span> <?= e($item['prompt']) ?></span>
                      <input type="text" data-answer="<?= e($item['answer']) ?>" data-hint="<?= e($item['hint'] ?? '') ?>" autocomplete="off" placeholder="Caracteres o pinyin">
                      <?php if (!empty($item['hint'])): ?><small>Pista: <?= e($item['hint']) ?></small><?php endif; ?>
                      <em class="exercise-note"></em>
                    </label>
                  </li>
                <?php endforeach; ?>
              </ol>
              <button type="button" class="primary-btn" data-check>Corregir</button>
            </div>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <?php if ($quiz): ?>
        <section class="section-block quiz-block" id="class-quiz">
          <h2><?= e($quiz['title']) ?></h2>
          <p class="quiz-rule">5 intentos. Se aprueba con <?= (int) $quiz['pass_score'] ?>%.</p>
          <?php if (!$user): ?>
            <p>Iniciá sesión para hacer la autoevaluación.</p>
            <a class="primary-btn" href="<?= e(url('cuenta')) ?>">Ir a la cuenta</a>
          <?php else: ?>
            <p class="quiz-status">Intentos: <?= $attemptCount ?> / <?= $maxAttempts ?><?= $passedAny ? ' · Aprobada' : '' ?></p>
            <?php if ($last): ?>
              <p>Último resultado: <strong><?= (int) $last['score'] ?>%</strong></p>
              <ol class="quiz-review">
                <?php foreach ($questions as $question): ?>
                  <?php $mark = $lastAnswers[$question['id']]['ok'] ?? false; ?>
                  <li class="<?= $mark ? 'ok' : 'bad' ?>"><?= e($question['prompt']) ?></li>
                <?php endforeach; ?>
              </ol>
            <?php endif; ?>
            <?php if (!$locked): ?>
              <form method="post" class="quiz-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="quiz">
                <?php foreach ($questions as $question): ?>
                  <fieldset>
                    <legend><?= e($question['prompt']) ?></legend>
                    <?php foreach ($question['options'] as $option): ?>
                      <label>
                        <input type="radio" name="answers[<?= e($question['id']) ?>]" value="<?= e($option['id']) ?>" required>
                        <span><?= e($option['label']) ?></span>
                      </label>
                    <?php endforeach; ?>
                  </fieldset>
                <?php endforeach; ?>
                <button class="primary-btn" type="submit">Enviar</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <div class="lesson-actions">
        <?php if ($user): ?>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="complete">
            <button class="primary-btn<?= !empty($progress['completed']) ? ' is-done' : '' ?>" type="submit"><?= !empty($progress['completed']) ? 'Clase hecha' : 'Marcar como hecha' ?></button>
          </form>
        <?php else: ?>
          <a class="primary-btn" href="<?= e(url('cuenta')) ?>">Entrá para guardar el progreso</a>
        <?php endif; ?>
        <div class="lesson-pager">
          <?php if ($prev): ?><a class="pager-link" href="<?= e(url($level['slug'] . '/clase/' . $prev['slug'])) ?>">← <?= e($prev['title']) ?></a><?php else: ?><span></span><?php endif; ?>
          <?php if ($next): ?><a class="pager-link" href="<?= e(url($level['slug'] . '/clase/' . $next['slug'])) ?>"><?= e($next['title']) ?> →</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</article>
