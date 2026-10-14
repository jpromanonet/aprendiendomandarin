<section class="nivel-hero">
  <div class="nivel-hero-inner">
    <p class="breadcrumb"><a href="<?= e(url()) ?>">Inicio</a> <span aria-hidden="true">/</span> <?= e($level['title']) ?></p>
    <p class="eyebrow light"><?= e($level['stage_label']) ?></p>
    <h1><?= e($level['title']) ?></h1>
    <p class="lede"><?= e($level['description']) ?></p>
    <ul class="stat-row">
      <li><strong><?= count($lessons) ?></strong> clases</li>
      <li><strong><?= number_format((int) $level['words_new'], 0, ',', '.') ?></strong> palabras nuevas</li>
      <li><strong><?= number_format((int) $level['words_cumulative'], 0, ',', '.') ?></strong> acumuladas</li>
    </ul>
  </div>
</section>
<div class="page-wrap">
  <div class="class-index">
    <?php foreach ($lessons as $index => $lesson): ?>
      <a class="class-link<?= !empty($progress[(int) $lesson['id']]) ? ' is-done' : '' ?>" style="--i: <?= min((int) $index, 14) ?>" href="<?= e(url($level['slug'] . '/clase/' . $lesson['slug'])) ?>">
        <div class="class-link-top">
          <span class="class-number"><?= str_pad((string) $lesson['number'], 2, '0', STR_PAD_LEFT) ?></span>
          <span class="class-date"><?= !empty($progress[(int) $lesson['id']]) ? 'Hecha' : e($level['title']) ?></span>
        </div>
        <h2><?= e($lesson['title']) ?></h2>
        <p class="class-summary"><?= e($lesson['summary']) ?></p>
        <span class="class-cta"><?= !empty($progress[(int) $lesson['id']]) ? 'Repasar' : 'Entrar' ?> →</span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
