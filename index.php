<?php
////////////////////////////////////////////////////////////////////
// LynxHD Formely ColdBrew Help Desk
// -----------------------------------------------------------------
// License info can be found in license.txt.
// You must leave this notice as is.
////////////////////////////////////////////////////////////////////

$show_install_notice = file_exists(__DIR__ . '/install/open-db.php');
$support_options = array(
    array(
        'title' => 'Create a ticket',
        'description' => 'Tell us how we can help and open a new support request.',
        'href' => 'newticket.php',
        'icon' => 'images/createticket.png',
        'action' => 'Open a ticket',
    ),
    array(
        'title' => 'View a ticket',
        'description' => 'Check the status and responses for an existing ticket.',
        'href' => 'ticketview.php',
        'icon' => 'images/viewticket.png',
        'action' => 'View ticket',
    ),
    array(
        'title' => 'Find a lost ticket',
        'description' => 'Recover the list of tickets associated with your email.',
        'href' => 'ticket.php?cmd=lost',
        'icon' => 'images/lostticket.png',
        'action' => 'Find tickets',
    ),
    array(
        'title' => 'Knowledge base',
        'description' => 'Browse helpful answers and solutions to common questions.',
        'href' => 'faq.php',
        'icon' => 'images/knowledgebase.png',
        'action' => 'Browse articles',
    ),
    array(
        'title' => 'Downloads',
        'description' => 'Access available files, resources, and downloadable material.',
        'href' => 'downloads/index.php',
        'icon' => 'images/downloads.png',
        'action' => 'View downloads',
    ),
    array(
        'title' => 'Announcements',
        'description' => 'Read the latest updates and important support information.',
        'href' => 'announcements.php',
        'icon' => 'images/announcement.png',
        'action' => 'Read updates',
    ),
);
$modern_home = true;
include __DIR__ . '/include/header.php';
?>

  <main>
    <section class="hero py-5 py-lg-6">
      <div class="container position-relative">
        <div class="row justify-content-center text-center">
          <div class="col-lg-8">
            <span class="eyebrow">LynxHD support</span>
            <h1 class="display-4 fw-bold mt-3">How can we help?</h1>
            <p class="lead text-secondary mt-3 mb-0">Create a request, follow an existing ticket, or find an answer in our support resources.</p>
          </div>
        </div>
      </div>
    </section>

    <section class="pb-5" aria-labelledby="support-options-title">
      <div class="container">
        <?php if ($show_install_notice): ?>
          <div class="alert alert-warning d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-sm mb-4" role="alert">
            <div>
              <strong>Installation files detected.</strong>
              <span class="d-block d-md-inline"> If this is a new installation, run the installer. Otherwise, remove the <code>install</code> directory for security.</span>
            </div>
            <a class="btn btn-warning text-nowrap" href="install/index.php">Open installer</a>
          </div>
        <?php endif; ?>

        <h2 id="support-options-title" class="visually-hidden">Support options</h2>
        <div class="row g-4">
          <?php foreach ($support_options as $option): ?>
            <div class="col-md-6 col-xl-4">
              <a class="support-card card h-100 border-0 shadow-sm text-decoration-none" href="<?php echo htmlspecialchars($option['href'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="card-body p-4">
                  <div class="icon-wrap mb-4">
                    <img src="<?php echo htmlspecialchars($option['icon'], ENT_QUOTES, 'UTF-8'); ?>" alt="" aria-hidden="true">
                  </div>
                  <h3 class="h5 text-body mb-2"><?php echo htmlspecialchars($option['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p class="text-secondary mb-4"><?php echo htmlspecialchars($option['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <span class="card-action"><?php echo htmlspecialchars($option['action'], ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">&rarr;</span></span>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

<?php include __DIR__ . '/include/footer.php'; ?>
