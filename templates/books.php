<section class="page-hero">
  <div class="page-hero-inner">
    <p class="eyebrow light">Lecturas</p>
    <h1>Libros</h1>
    <p class="lede"><?= $books ? 'Textos para acompañar las clases. Se descargan desde acá.' : 'Acá van a estar los libros que sumemos. Por ahora la estantería está vacía.' ?></p>
    <a class="text-link on-dark" href="<?= e(url()) ?>">← Volver al inicio</a>
  </div>
</section>
<?php if (!$books): ?>
  <p class="empty-note">Cuando haya un PDF publicado, va a aparecer en esta lista.</p>
<?php endif; ?>
<div class="book-list">
  <?php foreach ($books as $book): ?>
    <article class="book-card">
      <h2><?= e($book['title']) ?></h2>
      <p><?= e($book['description']) ?></p>
      <p><?= e($book['size_label']) ?></p>
      <a class="primary-btn" href="<?= e(url('libros/' . (int) $book['id'] . '/descargar')) ?>">Descargar</a>
    </article>
  <?php endforeach; ?>
</div>
