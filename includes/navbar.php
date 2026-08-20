﻿﻿﻿﻿<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <svg viewBox="0 0 120 130" width="70" height="76" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif"
            font-weight="900" font-size="36" fill="#ffffff" letter-spacing="2">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif"
            font-weight="700" font-size="22" fill="#ffffff">IT</text>
    </svg>
    <div class="sidebar-title">ANED Roma</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Principale</div>
    <a href="<?= APP_URL ?>/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
    </a>
    <a href="<?= APP_URL ?>/note/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/note')!==false?'active':'' ?>">
      <i class="bi bi-journal-text"></i><span>Note</span>
    </a>

    <?php if (hasRole('admin','direttivo','segreteria','utente')): ?>
    <div class="nav-section-label">Associazione</div>
    <?php if (hasRole('admin','direttivo','segreteria')): ?>
    <a href="<?= APP_URL ?>/iscritti/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/iscritti')!==false?'active':'' ?>">
      <i class="bi bi-people-fill"></i><span>Iscritti</span>
    </a>
    <a href="<?= APP_URL ?>/consiglio/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/consiglio')!==false?'active':'' ?>">
      <i class="bi bi-person-badge-fill"></i><span>Consiglio Direttivo</span>
    </a>
    <?php endif; ?>
    
    <a href="<?= APP_URL ?>/triangolo-rosso.php" class="nav-item">
      <i class="bi bi-triangle-fill"></i><span>Triangolo Rosso</span>
    </a>

    <a href="<?= APP_URL ?>/statuto/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/statuto')!==false?'active':'' ?>">
      <i class="bi bi-journal-bookmark-fill"></i><span>Statuto</span>
    </a>
    
    <a href="<?= APP_URL ?>/verbali/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/verbali')!==false?'active':'' ?>">
      <i class="bi bi-file-earmark-text-fill"></i><span>Verbali</span>
    </a>
    <?php endif; ?>

    <?php if (hasRole('admin','direttivo')): ?>
    <div class="nav-section-label">Finanze</div>
    <a href="<?= APP_URL ?>/estratti/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/estratti')!==false?'active':'' ?>">
      <i class="bi bi-bank2"></i><span>Estratti Conto</span>
    </a>
    <a href="<?= APP_URL ?>/spese/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/spese')!==false?'active':'' ?>">
      <i class="bi bi-receipt-cutoff"></i><span>Spese</span>
    </a>
    <a href="<?= APP_URL ?>/entrate/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/entrate')!==false?'active':'' ?>">
      <i class="bi bi-cash-coin"></i><span>Entrate</span>
    </a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/bilanci/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/bilanci')!==false?'active':'' ?>">
      <i class="bi bi-file-earmark-bar-graph"></i><span>Bilanci Annuali</span>
    </a>

    <div class="nav-section-label">Attività</div>
    <a href="<?= APP_URL ?>/attivita/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/attivita')!==false?'active':'' ?>">
      <i class="bi bi-calendar-event-fill"></i><span>Attività ed Eventi</span>
    </a>

    <?php if (hasRole('admin','direttivo','segreteria')): ?>
    <div class="nav-section-label">Comunicazioni</div>
    <a href="<?= APP_URL ?>/rubrica/index.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/rubrica')!==false?'active':'' ?>">
      <i class="bi bi-telephone-fill"></i><span>Rubrica Contatti</span>
    </a>
    <?php endif; ?>

    <?php if (hasRole('admin')): ?>
    <div class="nav-section-label">Amministrazione</div>
    <a href="<?= APP_URL ?>/admin/utenti.php" class="nav-item <?= strpos($_SERVER['REQUEST_URI'],'/admin')!==false?'active':'' ?>">
      <i class="bi bi-shield-lock-fill"></i><span>Gestione Utenti</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_nome']??'U',0,1).substr($_SESSION['user_cognome']??'',0,1)) ?></div>
      <div>
        <div class="user-name"><?= sanitize(($_SESSION['user_nome']??'').' '.($_SESSION['user_cognome']??'')) ?></div>
        <div class="user-role badge-role-<?= $_SESSION['user_role']??'utente' ?>"><?= ucfirst($_SESSION['user_role']??'utente') ?></div>
      </div>
    </div>
    <a href="<?= APP_URL ?>/profilo.php" class="btn-logout" title="Profilo" style="margin-right:4px"><i class="bi bi-gear"></i></a>
    <a href="<?= APP_URL ?>/logout.php" class="btn-logout" title="Esci"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</div>

<!-- Top navbar mobile -->
<nav class="topbar d-lg-none">
  <button class="btn-sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
  <div class="topbar-brand">
    <svg viewBox="0 0 120 130" width="36" height="40" xmlns="http://www.w3.org/2000/svg">
      <text x="60" y="38" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="36" fill="#ffffff">ANED</text>
      <polygon points="10,48 110,48 60,128" fill="#c0392b"/>
      <text x="60" y="96" text-anchor="middle" font-family="Arial,sans-serif" font-weight="700" font-size="22" fill="#ffffff">IT</text>
    </svg>
  </div>
  <div></div>
</nav>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
