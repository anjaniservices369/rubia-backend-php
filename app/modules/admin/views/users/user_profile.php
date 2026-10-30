<?php

use app\models\User;
use app\modules\admin\models\CustomerProfile;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user app\models\User */
/* @var $contacts array */
/* @var $places array */
/* @var $stats array */
/* @var $cities array */

$name = trim((string) $user->first_name);
$this->title = 'Customers-profile: ' . ($name !== '' ? $name : $user->contact_no);
$this->params['breadcrumbs'][] = ['label' => 'Customers-profile', 'url' => ['index']];
$this->params['breadcrumbs'][] = $name !== '' ? $name : $user->contact_no;

$missing = CustomerProfile::missing($user->id);
$active = (int) $user->status === User::STATUS_ACTIVE;

$readonly = !empty($readonly);
$form = function ($section, $inner, $multipart = false) use ($readonly) {
    return Html::beginForm(['user-profile', 'id' => Yii::$app->request->get('id')], 'post', ['enctype' => $multipart ? 'multipart/form-data' : null, 'autocomplete' => 'off'])
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
<div class="user-profile">
    <?php foreach (['success' => 'success', 'error' => 'danger'] as $k => $cls) : ?>
        <?php if ($msg = Yii::$app->session->getFlash($k)) : ?><div class="alert alert-<?= $cls ?>"><?= Html::encode($msg) ?></div><?php endif; ?>
    <?php endforeach; ?>

    <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <?php if ($user->profile_image) : ?><img src="<?= Html::encode($user->profile_image) ?>" alt="" class="rounded-circle" style="width:64px;height:64px;object-fit:cover" onerror="this.style.display='none'"><?php endif; ?>
            <div>
                <h4 class="mb-0"><?= Html::encode($name !== '' ? $name : 'No name yet') ?>
                    <span class="badge text-bg-<?= $active ? 'success' : 'danger' ?> fs-6"><?= $active ? 'Active' : 'Blocked' ?></span></h4>
                <div class="text-muted">Customer #<?= (int) $user->id ?> · <?= Html::encode($user->contact_no) ?> · registered <?= Html::encode($user->created_at) ?>
                    <?= $user->referal_code ? ' · referral code ' . Html::encode($user->referal_code) : '' ?></div>
            </div>
        </div>
        <div><?= Html::a('All customers', ['index'], ['class' => 'btn btn-outline-secondary']) ?></div>
    </div></div>

    <div class="rb-split">
    <div class="rb-main">
    <div class="alert alert-<?= $missing ? 'warning' : 'success' ?>">
        <?= $missing ? 'Registration incomplete: ' . Html::encode(implode(', ', $missing)) . '. The app requires a full name, e-mail and two emergency contact numbers.' : 'Required registration details are present: full name, e-mail and two emergency contact numbers.' ?>
    </div>

    <div class="card mb-3" id="personal"><div class="card-header"><strong>Personal details</strong></div><div class="card-body">
        <?php
        $photo = $user->profile_image
            ? '<a href="' . Html::encode($user->profile_image) . '" target="_blank" rel="noopener"><img src="' . Html::encode($user->profile_image) . '" alt="Photo" class="img-thumbnail mb-1" style="max-height:140px" loading="lazy"></a>'
            : '<div class="text-muted small mb-1">No photo</div>';
        $gender = $user->gender === 'Others' ? 'Other' : $user->gender;
        $inner = '<div class="row">'
            . $field('Full name (as entered in the app) *', $in('first_name', $user->first_name, ['required' => true, 'maxlength' => 100]))
            . $field('Mobile number (login, cannot be changed here)', $in('contact_no_view', $user->contact_no, ['disabled' => true]))
            . $field('E-mail *', Html::input('email', 'email', $user->email, ['class' => 'form-control', 'maxlength' => 255, 'required' => true]))
            . $field('Date of birth', Html::input('date', 'date_of_birth', $user->date_of_birth, ['class' => 'form-control']))
            . $field('Gender', Html::dropDownList('gender', $gender, array_combine(['Male', 'Female', 'Other'], ['Male', 'Female', 'Other']), ['class' => 'form-select', 'prompt' => '']))
            . $field('Alternative contact', $in('alternative_contact', $user->alternative_contact, ['maxlength' => 10, 'inputmode' => 'numeric']))
            . $field('City (found from the address)', $in('city_view', trim($user->city_name . ($user->lat ? ' (' . round((float) $user->lat, 5) . ', ' . round((float) $user->lng, 5) . ')' : '')) ?: 'not found yet - save the address', ['disabled' => true]))
            . $field('Address (the city is found from it)', $in('address', $user->address, ['maxlength' => 255]), 'col-md-8')
            . '<div class="col-md-4 mb-3"><div class="small fw-bold mb-1">Photo</div>' . $photo
            . '<input type="file" name="profile_image_file" accept="image/*" class="form-control form-control-sm"><div class="form-text">Choose a file only to replace it.</div></div>'
            . '</div>';
        echo $form('personal', $inner, true);
        ?>
    </div></div>

    <div class="card mb-3" id="contacts"><div class="card-header"><strong>Emergency contacts</strong></div><div class="card-body">
        <?php
        $inner = '';
        $rows = $contacts;
        $rows[] = ['id' => 'new1', 'name' => '', 'phone_number' => '', 'relationship' => ''];
        $inner .= '<p class="text-muted small">The customer app collects two phone numbers only. Contact names and relationships may be blank; add them here when available.</p>';
        foreach ($rows as $c) {
            $id = $c['id'];
            $isNew = !ctype_digit((string) $id);
            $inner .= '<div class="row border rounded p-2 mb-2 mx-0">'
                . $field($isNew ? 'Add a contact: name (optional)' : 'Name (optional)', $in("contact[$id][name]", $c['name'], ['maxlength' => 100]), 'col-md-4')
                . $field('Phone (10 digits)', $in("contact[$id][phone_number]", $c['phone_number'], ['maxlength' => 12, 'inputmode' => 'numeric']), 'col-md-3')
                . $field('Relationship (optional)', $in("contact[$id][relationship]", $c['relationship'], ['maxlength' => 50]), 'col-md-3')
                . ($isNew ? '' : '<div class="col-md-2 mb-3 d-flex align-items-end"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="contact[' . $id . '][delete]" value="1"> remove</label></div>')
                . '</div>';
        }
        echo $form('contacts', $inner);
        ?>
    </div></div>

    <div class="card mb-3" id="places"><div class="card-header"><strong>Saved places</strong></div><div class="card-body">
        <?php
        if (!$places) {
            echo '<p class="text-muted mb-0">No saved places. Customers add them in the app by choosing a point on the map.</p>';
        } else {
            $inner = '<p class="text-muted small">Places are added in the app (they need a point on the map). Here the type and the written address can be corrected, or a place removed.</p>';
            foreach ($places as $c) {
                $id = (int) $c['id'];
                $inner .= '<div class="row border rounded p-2 mb-2 mx-0">'
                    . $field('Type', Html::dropDownList("place[$id][address_type]", $c['address_type'], array_combine(CustomerProfile::PLACE_TYPES, CustomerProfile::PLACE_TYPES), ['class' => 'form-select']), 'col-md-2')
                    . $field('Address', $in("place[$id][address]", $c['address'], ['maxlength' => 255]), 'col-md-6')
                    . $field('Pincode', $in("place[$id][pincode]", $c['pincode'], ['maxlength' => 6, 'inputmode' => 'numeric']), 'col-md-2')
                    . '<div class="col-md-2 mb-3 d-flex align-items-end"><label class="form-check-label"><input type="checkbox" class="form-check-input" name="place[' . $id . '][delete]" value="1"> remove</label></div>'
                    . '</div>';
            }
            echo $form('places', $inner);
        }
        ?>
    </div></div>
    </div>
    <div class="rb-side rb-sticky">
    <?= $this->render('../partials/joined_map', ['user' => $user]) ?>

    <div class="row mb-3">
        <div class="col-6 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small">Rides booked</div><div class="fs-4"><?= (int) $stats['rides'] ?></div></div></div></div>
        <div class="col-6 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small">Rides completed</div><div class="fs-4"><?= (int) $stats['completed'] ?></div></div></div></div>
        <div class="col-12 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small">Last booking</div><div class="fs-6"><?= Html::encode($stats['last'] ?: 'none yet') ?></div></div></div></div>
    </div>

    </div>
    </div>
</div>
