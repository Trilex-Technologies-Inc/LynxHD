<?php
/*
Created by: Adam Patterson

Installer is released under the GPL license.
This script creates the database configuration used by LynxHD.
*/

$settings_file = __DIR__ . '/../include/settings.php';
$include_directory = dirname($settings_file);
$step = isset($_GET['step']) ? (int) $_GET['step'] : 0;
$step = max(0, min(3, $step));

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LynxHD Installer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../css/install.css" rel="stylesheet">
</head>
<body>
  <main class="container py-4 py-md-5">
    <div class="installer-shell mx-auto">
      <header class="text-center mb-4">
        <img class="installer-logo img-fluid" src="../images/logo.jpg" alt="LynxHD">
        <p class="text-secondary mt-3 mb-0">Help desk installation</p>
      </header>

      <nav aria-label="Installation progress" class="mb-4">
        <div class="progress-steps">
          <?php foreach (array('Welcome', 'Database', 'Configuration', 'Setup') as $number => $label): ?>
            <div class="progress-step <?php echo $number <= $step ? 'is-active' : ''; ?>">
              <span><?php echo $number + 1; ?></span>
              <small><?php echo $label; ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </nav>

      <section class="card border-0 shadow-lg">
        <div class="card-body p-4 p-md-5">
<?php
if (!is_writable($include_directory)) {
    echo '<div class="alert alert-danger mb-0" role="alert"><h1 class="h4 alert-heading">Permission required</h1><p class="mb-0">The <code>include</code> directory is not writable. Change its permissions or create <code>include/settings.php</code> manually from <code>settings-sample.php</code>.</p></div>';
} else {
    switch ($step) {
        case 0:
            if (file_exists($settings_file)) {
                echo '<div class="alert alert-warning mb-0" role="alert"><h1 class="h4 alert-heading">Configuration already exists</h1><p class="mb-0"><code>include/settings.php</code> already exists. Delete it only if you intend to reinstall LynxHD.</p></div>';
                break;
            }
?>
          <span class="badge text-bg-primary mb-3">Step 1 of 4</span>
          <h1 class="h2 mb-3">Welcome to LynxHD</h1>
          <p class="lead text-secondary">Let’s connect LynxHD to your MySQL database.</p>
          <p>Before continuing, have these details ready:</p>
          <ul class="list-group list-group-flush mb-4">
            <li class="list-group-item px-0">Database name</li>
            <li class="list-group-item px-0">Database username and password</li>
            <li class="list-group-item px-0">Database host (usually <code>localhost</code>)</li>
          </ul>
          <div class="alert alert-info">If automatic setup is unavailable, copy <code>include/settings-sample.php</code> to <code>include/settings.php</code> and enter the same details manually.</div>
          <div class="d-flex justify-content-end mt-4">
            <a class="btn btn-primary btn-lg" href="?step=1">Get started <span aria-hidden="true">&rarr;</span></a>
          </div>
<?php
            break;

        case 1:
?>
          <span class="badge text-bg-primary mb-3">Step 2 of 4</span>
          <h1 class="h2 mb-3">Database connection</h1>
          <p class="text-secondary mb-4">Enter the credentials supplied by your hosting provider.</p>
          <form method="post" action="?step=2" class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="dbname">Database name</label>
              <input class="form-control form-control-lg" id="dbname" name="dbname" type="text" required autocomplete="off">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="uname">Database username</label>
              <input class="form-control form-control-lg" id="uname" name="uname" type="text" required autocomplete="username">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="pwd">Database password</label>
              <input class="form-control form-control-lg" id="pwd" name="pwd" type="password" autocomplete="current-password">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="dbhost">Database host</label>
              <input class="form-control form-control-lg" id="dbhost" name="dbhost" type="text" value="localhost" required>
              <div class="form-text">Usually <code>localhost</code>.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="dbprefix">Table prefix <span class="text-secondary fw-normal">(optional)</span></label>
              <input class="form-control" id="dbprefix" name="dbprefix" type="text" placeholder="hd_" pattern="[A-Za-z0-9_]*">
              <div class="form-text">Useful when multiple applications share one database.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="dbsqlpath">Path to MySQL <span class="text-secondary fw-normal">(optional)</span></label>
              <input class="form-control" id="dbsqlpath" name="dbsqlpath" type="text" placeholder="/usr/local/mysql/">
              <div class="form-text">Only required for legacy database backups.</div>
            </div>
            <div class="col-12 d-flex justify-content-between align-items-center pt-2">
              <a class="btn btn-outline-secondary" href="?step=0">Back</a>
              <button class="btn btn-primary btn-lg" type="submit">Save and continue</button>
            </div>
          </form>
<?php
            break;

        case 2:
            $db_name = trim($_POST['dbname'] ?? '');
            $db_user = trim($_POST['uname'] ?? '');
            $db_password = (string) ($_POST['pwd'] ?? '');
            $db_host = trim($_POST['dbhost'] ?? '');
            $db_prefix = trim($_POST['dbprefix'] ?? '');
            $db_path_to_mysql = trim($_POST['dbsqlpath'] ?? '');

            if ($db_name === '' || $db_user === '' || $db_host === '') {
                echo '<div class="alert alert-danger" role="alert">Database name, username, and host are required.</div><a class="btn btn-outline-secondary" href="?step=1">Return to database settings</a>';
                break;
            }
            if (!preg_match('/^[A-Za-z0-9_]*$/', $db_prefix)) {
                echo '<div class="alert alert-danger" role="alert">The table prefix may contain only letters, numbers, and underscores.</div><a class="btn btn-outline-secondary" href="?step=1">Return to database settings</a>';
                break;
            }

            require __DIR__ . '/open-db.php';

            $settings = "<?php\n"
                . '$db_name = ' . var_export($db_name, true) . ";\n"
                . '$db_user = ' . var_export($db_user, true) . ";\n"
                . '$db_password = ' . var_export($db_password, true) . ";\n"
                . '$db_host = ' . var_export($db_host, true) . ";\n"
                . '$db_prefix = ' . var_export($db_prefix, true) . ";\n"
                . '$db_path_to_mysql = ' . var_export($db_path_to_mysql, true) . ";\n";

            if (file_put_contents($settings_file, $settings, LOCK_EX) === false) {
                echo '<div class="alert alert-danger mb-0" role="alert">Unable to create <code>include/settings.php</code>. Check directory permissions and try again.</div>';
                break;
            }
?>
          <div class="text-center py-3">
            <div class="success-icon mb-3" aria-hidden="true">&#10003;</div>
            <span class="badge text-bg-success mb-3">Configuration saved</span>
            <h1 class="h2">Database connection succeeded</h1>
            <p class="text-secondary mb-4">Your settings file was created successfully.</p>
            <a class="btn btn-primary btn-lg" href="?step=3">Continue to table setup <span aria-hidden="true">&rarr;</span></a>
          </div>
<?php
            break;

        case 3:
            if (!file_exists($settings_file) || filesize($settings_file) === 0) {
                echo '<div class="alert alert-danger" role="alert">The settings file is missing or empty. Return to the database step and create it again.</div><a class="btn btn-outline-secondary" href="?step=1">Database settings</a>';
                break;
            }
            require $settings_file;
            require __DIR__ . '/open-db.php';
?>
          <div class="text-center py-3">
            <div class="success-icon mb-3" aria-hidden="true">&#10003;</div>
            <span class="badge text-bg-success mb-3">Step 4 of 4</span>
            <h1 class="h2">Ready to create your tables</h1>
            <p class="text-secondary mb-4">The database connection is working. Finish setup by creating the LynxHD tables and administrator account.</p>
            <a class="btn btn-success btn-lg" href="../setup.php">Install tables and create admin</a>
          </div>
<?php
            break;
    }
}
?>
        </div>
      </section>
      <footer class="text-center text-secondary small mt-4">LynxHD Help Desk Installer</footer>
    </div>
  </main>
</body>
</html>
