<?php

use app\models\User;
use app\modules\admin\models\DriverProfile;
use app\modules\payments\services\DriverOnboarding;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */
/* @var $license array */
/* @var $aadhar array */
/* @var $pan array */
/* @var $bank array */
/* @var $contacts array */
/* @var $vehicleIds int[] */
/* @var $vehicleTypes array */
/* @var $cities array */

$name = trim($user->first_name . ' ' . $user->last_name);
$this->title = 'Drivers-profile: ' . ($name !== '' ? $name : $user->contact_no);
$this->params['breadcrumbs'][] = ['label' => 'Drivers-profile', 'url' => ['drivers']];
$this->params['breadcrumbs'][] = $name !== '' ? $name : $user->contact_no;

$v = function ($arr, $key) {
    return Html::encode((string) ($arr[$key] ?? ''));
};
$steps = DriverOnboarding::steps($user);
$left = DriverProfile::missing($user->id);
$incomplete = array_filter($left);
/** the badge in a section heading: complete, or what is still required */
$state = function ($section) use ($left) {
    $m = $left[$section] ?? [];

    return $m ? ' <span class="badge text-bg-danger">Required: ' . Html::encode(implode(', ', $m)) . '</span>' : ' <span class="badge text-bg-success">Complete</span>';
};
$verified = [User::profile_verified_yes => ['success', 'Approved'], User::profile_verified_rejected => ['danger', 'Rejected']][(int) $user->profile_verified] ?? ['warning', 'Waiting for approval'];

/** a picture or PDF the partner uploaded, with a way to replace it */
$doc = function ($label, $url, $field) {
    $out = '<div class="col-md-4 mb-2"><div class="small fw-bold mb-1">' . Html::encode($label) . '</div>';
    if ($url) {
        $out .= '<a href="' . Html::encode($url) . '" target="_blank" rel="noopener"><img src="' . Html::encode($url)
            . '" alt="' . Html::encode($label) . '" class="img-thumbnail mb-1" style="max-height:140px;max-width:100%" loading="lazy"></a>';
    } else {
        $out .= '<div class="text-muted small mb-1">Not uploaded</div>';
    }
    $out .= '<input type="file" name="' . $field . '" accept="image/*,application/pdf" class="form-control form-control-sm">'
        . '<div class="form-text">Choose a file only to replace it. Documents are kept for life.</div></div>';

    return $out;
};

$readonly = !empty($readonly);
$form = function ($section, $inner, $multipart = false) use ($readonly) {
    return Html::beginForm(['driver-profile', 'id' => Yii::$app->request->get('id')], 'post', ['enctype' => $multipart ? 'multipart/form-data' : null, 'autocomplete' => 'off'])
        . Html::hiddenInput('section', $section) . '<fieldset' . ($readonly ? ' disabled' : '') . '>' . $inner . '</fieldset>'
        . ($readonly ? '' : '<div class="mt-3">' . Html::submitButton('Save', ['class' => 'btn btn-primary']) . '</div>') . Html::endForm();
};
$field = function ($label, $input, $col = 'col-md-4') {
    return '<div class="' . $col . ' mb-3"><label class="form-label">' . Html::encode($label) . '</label>' . $input . '</div>';
};
$in = function ($name, $value, $extra = []) {
    return Html::textInput($name, $value, $extra + ['class' => 'form-control']);
};
?>
<div class="driver-profile">
    <?php foreach (['success' => 'success', 'error' => 'danger'] as $k => $cls) : ?>
        <?php if ($msg = Yii::$app->session->getFlash($k)) : ?><div class="alert alert-<?= $cls ?>"><?= Html::encode($msg) ?></div><?php endif; ?>
    <?php endforeach; ?>

    <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <?php if ($user->profile_image) : ?><img src="<?= Html::encode($user->profile_image) ?>" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover" onerror="this.style.display='none'"><?php endif; ?>
            <div>
                <h4 class="mb-0"><?= Html::encode($name !== '' ? $name : 'No name yet') ?>
                    <span class="badge text-bg-<?= $verified[0] ?> fs-6"><?= $verified[1] ?></span></h4>
                <div class="text-muted">Driver #<?= (int) $user->id ?> · <?= Html::encode($user->contact_no) ?> · joined <?= Html::encode($user->joining_date ?: $user->created_at) ?></div>
            </div>
        </div>
        <div>
            <?= Html::a('Approval, wallet and rides', ['driver-view', 'id' => $user->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('All drivers', ['drivers'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div></div>

    <div class="rb-split">
    <div class="rb-main">
    <div class="alert alert-<?= $incomplete ? 'warning' : 'success' ?>">
        <?= $incomplete ? 'This profile is not complete yet. Every part below is mandatory (alternative contact, city and UPI id are optional): fill in what is marked Required.'
            : 'All mandatory profile details are filled in.' ?>
    </div>

    <div class="card mb-3" id="personal"><div class="card-header"><strong>Personal details</strong><?= $state('personal') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">'
            . $field('First name *', $in('first_name', $user->first_name, ['required' => true, 'maxlength' => 100]))
            . $field('Last name *', $in('last_name', $user->last_name, ['required' => true, 'maxlength' => 100]))
            . $field('Mobile number (login, cannot be changed here)', $in('contact_no_view', $user->contact_no, ['disabled' => true]))
            . $field('E-mail *', Html::input('email', 'email', $user->email, ['class' => 'form-control', 'maxlength' => 255]))
            . $field('Date of birth *', Html::input('date', 'date_of_birth', $user->date_of_birth, ['class' => 'form-control']))
            . $field('Gender *', Html::dropDownList('gender', $user->gender, array_combine(DriverProfile::GENDERS, DriverProfile::GENDERS), ['class' => 'form-select', 'prompt' => '']))
            . $field('Alternative contact', $in('alternative_contact', $user->alternative_contact, ['maxlength' => 10, 'inputmode' => 'numeric']))
            . $field('City (found from the address)', $in('city_view', trim($user->city_name . ($user->lat ? ' (' . round((float) $user->lat, 5) . ', ' . round((float) $user->lng, 5) . ')' : '')) ?: 'not found yet - save the address', ['disabled' => true]))
            . $field('Address * (the city is found from it)', $in('address', $user->address, ['maxlength' => 255]), 'col-md-8')
            . $doc('Profile photo *', $user->profile_image, 'profile_image_file')
            . '</div>';
        echo $form('personal', $inner, true);
        ?>
    </div></div>

    <div class="card mb-3" id="license"><div class="card-header"><strong>Driving licence</strong><?= $state('license') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">'
            . $field('Licence number *', $in('driving_license_number', $license['driving_license_number'] ?? '', ['maxlength' => 30]))
            . $field('Issued on *', Html::input('date', 'issued_date', $license['issued_date'] ?? '', ['class' => 'form-control']))
            . $field('Expires on *', Html::input('date', 'expire_date', $license['expire_date'] ?? '', ['class' => 'form-control']))
            . $doc('Front *', $license['driving_license_front'] ?? '', 'license_front')
            . $doc('Back *', $license['driving_license_back'] ?? '', 'license_back')
            . '</div>';
        echo $form('license', $inner, true);
        ?>
    </div></div>

    <div class="card mb-3" id="aadhar"><div class="card-header"><strong>Aadhaar card</strong><?= $state('aadhar') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">'
            . $field('Aadhaar number (12 digits) *', $in('aadhar_number', $aadhar['aadhar_number'] ?? '', ['maxlength' => 14, 'inputmode' => 'numeric']))
            . '</div><div class="row">'
            . $doc('Front *', $aadhar['aadhar_card_front'] ?? '', 'aadhar_front')
            . $doc('Back *', $aadhar['aadhar_card_back'] ?? '', 'aadhar_back')
            . '</div>';
        echo $form('aadhar', $inner, true);
        ?>
    </div></div>

    <div class="card mb-3" id="pan"><div class="card-header"><strong>PAN card</strong><?= $state('pan') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">'
            . $field('PAN number *', $in('pan_number', $pan['pan_number'] ?? '', ['maxlength' => 10, 'style' => 'text-transform:uppercase']))
            . $doc('PAN card *', $pan['pan_card'] ?? '', 'pan_file')
            . '</div>';
        echo $form('pan', $inner, true);
        ?>
    </div></div>

    <div class="card mb-3" id="bank"><div class="card-header"><strong>Bank details</strong><?= $state('bank') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">'
            . $field('Account holder name *', $in('account_holder_name', $bank['account_holder_name'] ?? '', ['maxlength' => 100]))
            . $field('Account number *', $in('account_number', $bank['account_number'] ?? '', ['maxlength' => 30, 'inputmode' => 'numeric']))
            . $field('IFSC *', $in('ifsc', $bank['ifsc'] ?? '', ['maxlength' => 15, 'style' => 'text-transform:uppercase']))
            . $field('Bank name *', $in('bank_name', $bank['bank_name'] ?? '', ['maxlength' => 100]))
            . $field('Branch *', $in('branch_name', $bank['branch_name'] ?? '', ['maxlength' => 100]))
            . $field('UPI id', $in('upi', $bank['upi'] ?? '', ['maxlength' => 100, 'placeholder' => 'name@bank']))
            . '</div>';
        echo $form('bank', $inner);
        ?>
    </div></div>

    <div class="card mb-3" id="contacts"><div class="card-header"><strong>Emergency contacts</strong><?= $state('contacts') ?></div><div class="card-body">
        <?php
        $inner = '';
        $rows = $contacts;
        $rows[] = ['id' => 'new1', 'name' => '', 'phone_number' => '', 'relationship' => '', 'aadhar_number' => ''];
        foreach ($rows as $c) {
            $id = $c['id'];
            $isNew = !ctype_digit((string) $id);
            $inner .= '<div class="row border rounded p-2 mb-2 mx-0">'
                . $field($isNew ? 'Add a contact: name' : 'Name', $in("contact[$id][name]", $c['name'], ['maxlength' => 100]), 'col-md-3')
                . $field('Phone (10 digits)', $in("contact[$id][phone_number]", $c['phone_number'], ['maxlength' => 12, 'inputmode' => 'numeric']), 'col-md-3')
                . $field('Relationship', $in("contact[$id][relationship]", $c['relationship'], ['maxlength' => 50]), 'col-md-2')
                . $field('Aadhaar (optional)', $in("contact[$id][aadhar_number]", $c['aadhar_number'], ['maxlength' => 12, 'inputmode' => 'numeric']), 'col-md-3')
                . ($isNew ? '' : '<div class="col-md-1 mb-3 d-flex align-items-end"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="contact[' . $id . '][delete]" value="1"> remove</label></div>')
                . '</div>';
        }
        echo $form('contacts', $inner);
        ?>
    </div></div>

    <div class="card mb-3" id="vehicles"><div class="card-header"><strong>Vehicle types the partner drives</strong><?= $state('vehicles') ?></div><div class="card-body">
        <?php
        $inner = '<div class="row">';
        foreach ($vehicleTypes as $t) {
            $inner .= '<div class="col-md-3 col-6 mb-2"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="vehicle_type[]" value="' . (int) $t['id'] . '"'
                . (in_array((int) $t['id'], $vehicleIds, true) ? ' checked' : '') . '> ' . Html::encode($t['title']) . '</label></div>';
        }
        echo $form('vehicles', $inner . '</div>');
        ?>
    </div></div>

    </div>
    <div class="rb-side rb-sticky">
    <div class="card mb-3"><div class="card-header"><strong>What the partner has finished in the app</strong></div>
        <div class="card-body"><div class="row">
            <?php foreach ($steps as $s) : ?>
                <div class="col-md-4 col-6 mb-1"><span class="badge text-bg-<?= $s['done'] ? 'success' : 'secondary' ?>"><?= $s['done'] ? '&#10003;' : '&#8226;' ?></span> <?= Html::encode($s['label']) ?></div>
            <?php endforeach; ?>
        </div></div>
    </div>

    <?= $this->render('../partials/joined_map', ['user' => $user]) ?>

    <div class="card mb-3" id="police"><div class="card-header"><strong>Police verification and joining fee</strong></div><div class="card-body">
        <?php
        $inner = '<label class="form-check-label"><input type="checkbox" class="form-check-input" name="police_done" value="1"'
            . ((int) $user->is_done_police_verification === User::profile_verified_yes ? ' checked' : '') . '> Police verification is done</label>'
            . '<p class="mt-2 mb-0 text-muted">Joining fee: ' . ((int) $user->is_paid_joining_fee === 1 ? '<span class="badge text-bg-success">paid</span>' : '<span class="badge text-bg-secondary">not paid</span>')
            . ' (paid by the partner in the app; see Payments).</p>';
        echo $form('police', $inner);
        ?>
    </div></div>
    </div>
    </div>
</div>
