<?php
$letters = range('A', 'Z');
function dict_query(array $extra): string {
    $params = array_merge($_GET, $extra);
    return url('diccionario') . '?' . http_build_query($params);
}
?>
<section class="page-hero dict-hero">
  <div class="page-hero-inner">
    <p class="eyebrow light">Buscador bilingüe</p>
    <h1>Diccionario</h1>
    <p class="lede">Las palabras de los 9 niveles del HSK, en caracteres, pinyin y español.</p>
  </div>
</section>
<form class="dict-search" method="get" action="<?= e(url('diccionario')) ?>">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscá en español, pinyin o caracteres">
  <select name="modo">
    <option value="ambos"<?= $mode === 'ambos' ? ' selected' : '' ?>>Español y chino</option>
    <option value="es"<?= $mode === 'es' ? ' selected' : '' ?>>Español</option>
    <option value="zh"<?= $mode === 'zh' ? ' selected' : '' ?>>Chino</option>
  </select>
  <select name="nivel">
    <option value="0">Todos los HSK</option>
    <?php for ($i = 1; $i <= 9; $i++): ?>
      <option value="<?= $i ?>"<?= $level === $i ? ' selected' : '' ?>>HSK <?= $i ?></option>
    <?php endfor; ?>
  </select>
  <button class="primary-btn" type="submit">Buscar</button>
</form>
<p class="dict-count">Hay <?= number_format($total, 0, ',', '.') ?> en esta búsqueda<?php if ($q === '' && $level === 0): ?>, sobre unas 11.000 en total<?php endif; ?>.</p>
<?php if ($q === ''): ?>
  <nav class="letter-row" aria-label="Inicial">
    <?php foreach ($letters as $item): ?>
      <a class="<?= $item === $letter ? 'is-on' : '' ?>" href="<?= e(dict_query(['letra' => $item, 'pagina' => 1])) ?>"><?= e($item) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>
<div class="dict-results">
  <?php if (!$entries): ?>
    <p>No hay resultados.</p>
  <?php endif; ?>
  <?php foreach ($entries as $entry): ?>
    <article class="dict-entry">
      <div class="dict-entry-top">
        <div>
          <strong class="hanzi"><?= e($entry['hanzi']) ?></strong>
          <span class="pinyin"><?= e($entry['pinyin']) ?></span>
        </div>
        <em class="dict-badge">HSK <?= (int) $entry['hsk_level'] ?></em>
      </div>
      <p><?= e($entry['espanol']) ?></p>
      <?= audio_button($entry['hanzi'], $entry['pinyin'], 'Escuchar ' . $entry['hanzi']) ?>
      <?php if ($entry['example_zh'] !== ''): ?>
        <div class="dict-example">
          <p class="hanzi"><?= e($entry['example_zh']) ?></p>
          <p class="pinyin"><?= e($entry['example_pinyin']) ?></p>
          <?php if ($entry['example_es'] !== ''): ?><p><?= e($entry['example_es']) ?></p><?php endif; ?>
          <div class="audio-block">
            <div class="audio-label"><span class="audio-dot" aria-hidden="true"></span> Frase</div>
            <audio controls preload="none" src="<?= e(audio_src($entry['example_zh'])) ?>">Tu navegador no soporta audio.</audio>
          </div>
        </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</div>
<?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Páginas">
    <?php if ($page > 1): ?><a href="<?= e(dict_query(['pagina' => $page - 1])) ?>">Anterior</a><?php endif; ?>
    <span><?= $page ?> / <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e(dict_query(['pagina' => $page + 1])) ?>">Siguiente</a><?php endif; ?>
  </nav>
<?php endif; ?>
<p class="dict-credit">Lista del HSK armada por <a href="https://gushichinese.com/hsk-3.0/downloads">Gushi</a> (CC BY-SA 4.0), con sentidos de partida de CC-CEDICT y ejemplos en gran parte de Tatoeba (CC BY 2.0 FR). Las glosas de esta página están en español.</p>
