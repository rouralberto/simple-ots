<?php

global $pdo;
require_once 'common.php';

if (isset($_GET['read'])) {
    $uuid       = $_GET['read'];
    $secret     = get_secret($uuid, false);
    $secret_url = get_url("?uuid=$uuid");
    $sharing_url = get_url("?read=$uuid");

    if ($secret) { ?>
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <h1><?php echo $uuid; ?></h1>
                <p>Click to see the secret: <a href="<?php echo $secret_url; ?>"><?php echo $secret_url; ?></a>.</p>

                <button id="copy-button" class="btn btn-sm btn-success" onclick="copyUrl()">
                    <span id="copy-link"><i class="bi bi-copy me-2"></i>Copy Secret Link</span>
                    <span id="link-copied" class="d-none"><i class="bi bi-check me-2"></i>Secret Link Copied</span>
                </button>
                <script>
                  function copyUrl () {
                    navigator.clipboard.writeText(<?php echo json_encode($sharing_url); ?>)
                      .then(() => {
                        document.getElementById('copy-link').classList.add('d-none');
                        document.getElementById('link-copied').classList.remove('d-none');
                        document.getElementById('copy-button').disabled = true;
                      });
                  }
                </script>

                <hr>

                <p class="small text-muted mb-1">(Remember: If you open the secret, it will instantly disappear)</p>

                <p class="small text-muted">
                    Remaining time: <code><?php echo get_remaining_time($secret['createdAt'], $secret['expiry']); ?></code>
                </p>

            </div>
        </div>
        <?php
    } else {
        echo get_template('not-found');
    }
    echo get_template('footer');
    exit;
}

if (isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];

    if (!is_valid_uuidv4($uuid)) {
        echo get_template('not-found');
        echo get_template('footer');
        exit;
    }

    $secret = get_secret($uuid, true);

    if ($secret) { ?>
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h1 class="text-center"><?php echo $uuid; ?></h1>
                <h2 class="h5 small text-muted text-center">(This will only be shown once)</h2>
                <label style="display: none" for="theSecret">Secret Contents:</label>
                <div class="position-relative">
                    <textarea id="theSecret" class="form-control mt-4" rows="3" style="padding-right: 45px;"><?php echo $secret['value']; ?></textarea>
                    <button id="copy-secret-button" class="btn btn-sm btn-success position-absolute top-0 end-0 mt-4 me-2" onclick="copySecret()">
                        <span id="copy-secret"><i class="bi bi-copy"></i></span>
                        <span id="secret-copied" class="d-none"><i class="bi bi-check"></i></span>
                    </button>
                </div>
            </div>
        </div>
        <script type="application/javascript">
          const jsConfetti = new JSConfetti();
          jsConfetti.addConfetti({emojis: ['💥', '🦵', '💣', '🤯', '🔥']});

          function copySecret() {
            const secretText = document.getElementById('theSecret').value;
            navigator.clipboard.writeText(secretText)
              .then(() => {
                document.getElementById('copy-secret').classList.add('d-none');
                document.getElementById('secret-copied').classList.remove('d-none');
                document.getElementById('copy-secret-button').disabled = true;

                // Show a temporary tooltip or notification
                const button = document.getElementById('copy-secret-button');
                button.setAttribute('title', 'Copied!');
                setTimeout(() => {
                  button.removeAttribute('title');
                }, 2000);
              });
          }
        </script>
        <?php
    } else {
        echo get_template('not-found');
    }
    echo get_template('footer');
    exit;
}

if (!is_allowed()) {
    echo get_template('not-authorised');
    echo get_template('footer');
    exit;
}

echo get_template('new-secret');
echo get_template('footer');
