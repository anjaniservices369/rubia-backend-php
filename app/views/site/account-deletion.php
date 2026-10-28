<?php
/** @var yii\web\View $this */
/** @var string $done */
/** @var string $error */

use yii\helpers\Html;

$this->title = 'Delete your Rubia account';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= Html::encode($this->title) ?></title>
<style>
body{margin:0;background:#f4f6fa;font-family:Arial,Helvetica,sans-serif;color:#14213d}
.wrap{max-width:560px;margin:0 auto;padding:24px 16px}
.card{background:#fff;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.07);margin-bottom:16px}
h1{font-size:22px;margin:0 0 6px}h2{font-size:16px;margin:18px 0 6px}
p,li{font-size:14px;line-height:1.55}
label{display:block;font-size:13px;font-weight:700;margin:12px 0 4px}
input,select,textarea{width:100%;box-sizing:border-box;padding:11px;border:1px solid #c9d0de;border-radius:8px;font-size:15px}
button{margin-top:16px;width:100%;padding:13px;border:0;border-radius:8px;background:#e8590c;color:#fff;font-size:16px;font-weight:700}
.ok{background:#e6f6ea;border:1px solid #2f9e44;padding:12px;border-radius:8px}
.err{background:#fdeaea;border:1px solid #c92a2a;padding:12px;border-radius:8px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>Delete your Rubia account</h1>
    <p>Use this page to ask for the deletion of your <b>Rubia customer</b> or <b>Rubia Driver Partner</b> account and the personal data linked to it.
       You can also do it inside the app: <b>Profile &rarr; Delete account</b>, which deletes the account at once.</p>
    <?php if ($done): ?>
      <div class="ok"><?= Html::encode($done) ?></div>
    <?php else: ?>
      <?php if ($error): ?><div class="err"><?= Html::encode($error) ?></div><?php endif; ?>
      <form method="post" action="">
        <input type="hidden" name="<?= Html::encode(Yii::$app->request->csrfParam) ?>" value="<?= Html::encode(Yii::$app->request->csrfToken) ?>">
        <label for="role">I use the</label>
        <select name="role" id="role"><option value="User">Rubia customer app</option><option value="Driver">Rubia Driver Partner app</option></select>
        <label for="name">Name</label>
        <input name="name" id="name" maxlength="120" required>
        <label for="contact">Registered mobile number (10 digits)</label>
        <input name="contact" id="contact" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required>
        <label for="reason">Reason (optional)</label>
        <textarea name="reason" id="reason" rows="3" maxlength="500"></textarea>
        <button type="submit">Request account deletion</button>
      </form>
      <p>We call you back on this number to confirm the request, then delete the account within 30 days.</p>
    <?php endif; ?>
  </div>
  <div class="card">
    <h2>What is deleted</h2>
    <ul><li>Your name, email address, mobile number, photo, date of birth and gender</li>
        <li>Emergency contacts and saved places</li>
        <li>Your login sessions and device (notification) tokens</li>
        <li>For drivers: documents and bank details uploaded with the profile</li></ul>
    <h2>What we keep</h2>
    <ul><li>Ride, invoice and payment records, without the link to your identity, as required by tax and accounting law (up to 8 years)</li>
        <li>Records needed to settle a dispute or a safety complaint that is still open</li></ul>
    <p>A ride in progress must be finished before the account can be deleted.</p>
  </div>
</div>
</body>
</html>
