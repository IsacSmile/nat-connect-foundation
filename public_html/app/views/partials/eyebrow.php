<?php
declare(strict_types=1);

/**
 * Shared Eyebrow Label Partial
 *
 * Requirements: 12px, uppercase, tracking-wider, font-semibold, text-accent-strong.
 *
 * @var string $label
 * @var string|null $class
 */

$extraClass = !empty($class) ? ' ' . $class : '';
?>
<span class="text-[12px] uppercase tracking-wider font-semibold text-accent-strong<?= $extraClass ?>">
  <?= e($label) ?>
</span>
