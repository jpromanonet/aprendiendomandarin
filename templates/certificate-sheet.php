<section class="cert-sheet-page">
  <article class="cert-sheet">
    <p class="cert-sheet-kicker"><?= e($sheet['kicker']) ?></p>
    <h1><?= e($sheet['title']) ?></h1>
    <p class="cert-sheet-lead">Se deja constancia de que</p>
    <p class="cert-sheet-name"><?= e($sheet['name']) ?></p>
    <p class="cert-sheet-body"><?= e($sheet['body']) ?></p>
    <p class="cert-sheet-code"><?= e($sheet['code']) ?></p>
    <p class="cert-sheet-meta"><?= e($sheet['date']) ?> · Sin validez oficial</p>
  </article>
  <a class="cert-sheet-back" href="<?= e(url('certificados')) ?>">← Volver a certificados</a>
</section>
