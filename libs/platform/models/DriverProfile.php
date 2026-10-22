<?php

namespace app\modules\admin\models;

use app\models\User;
use app\modules\filestorage\services\MediaStore;
use Yii;
use yii\db\Query;

/**
 * Everything a partner enters while creating the profile in the partner app, in one place, readable and editable by the company:
 * personal details, driving licence, Aadhaar, PAN, bank details, emergency contacts, vehicle types and the police verification flag.
 *
 * The dashboard page "Drivers-profile" (users/driver-profile) is built on this. Each section is saved on its own (section = personal,
 * license, aadhar, pan, bank, contacts, vehicles, police). A section is checked like the app checks it and the row is the same row
 * the app writes, so the app and the dashboard always show the same data. Files go to the object storage as lifelong
 * documents under profile/<driver id>/<document type>/ (see MediaStore), and replacing a file keeps the old one.
 */
class DriverProfile
{
    const SECTIONS = ['personal', 'license', 'aadhar', 'pan', 'bank', 'contacts', 'vehicles', 'police'];

    const GENDERS = ['Male', 'Female', 'Others'];

    // [doc:start] load - All profile data of one driver, or null when there is no such driver.
    /**
     * @return array|null user, license, aadhar, pan, bank, contacts, vehicleIds, vehicleTypes, cities
     */
    public static function load($driverId)
    {
        $user = User::find()->where(['id' => (int) $driverId, 'user_role' => User::ROLE_DRIVER])->one();
        if (!$user) {
            return null;
        }
        $one = function ($table) use ($driverId) {
            return (new Query())->from($table)->where(['driver_id' => (int) $driverId])->orderBy(['id' => SORT_DESC])->one() ?: [];
        };

        return [
            'user' => $user,
            'license' => $one('driving_license'),
            'aadhar' => $one('driver_aadhar_card'),
            'pan' => $one('driver_pan_cards'),
            'bank' => $one('driver_bank_details'),
            'contacts' => (new Query())->from('driver_emergency_contacts')->where(['driver_id' => (int) $driverId])->orderBy(['id' => SORT_ASC])->all(),
            'vehicleIds' => array_map('intval', (new Query())->select('vehicle_type_id')->from('driver_vehicle_types')
                ->where(['driver_id' => (int) $driverId])->andWhere(['or', ['status' => 1], ['status' => null]])->column()),
            'vehicleTypes' => (new Query())->select(['id', 'title'])->from('vehicle_types')->where(['status' => 1])->orderBy(['title' => SORT_ASC])->all(),
            'cities' => (new Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy(['name' => SORT_ASC])->all(),
        ];
    }
    // [doc:end] load

    // [doc:start] missing - What a driver still has to fill in, per section (an empty list = the section is complete).
    /**
     * The mandatory parts of a profile: personal details (first and last name, e-mail, date of birth, gender, address, profile photo),
     * driving licence (number, dates, both pictures), Aadhaar (number, both pictures), PAN (number, picture), bank details (holder, account,
     * IFSC, bank, branch), at least one emergency contact and at least one vehicle type. The joining fee is checked by DriverOnboarding.
     * Optional: alternative contact, UPI id (the city is found from the address), the police verification flag.
     *
     * @return array section => string[] names of what is missing
     */
    public static function missing($driverId)
    {
        $d = self::load($driverId);
        if ($d === null) {
            return [];
        }
        $u = $d['user'];
        $need = function (array $pairs) {
            $out = [];
            foreach ($pairs as $label => $value) {
                if (trim((string) $value) === '') {
                    $out[] = $label;
                }
            }

            return $out;
        };
        $bank = $d['bank'];
        $license = $d['license'];
        $aadhar = $d['aadhar'];
        $pan = $d['pan'];
        $licenseMissing = $need([
            'licence number' => $license['driving_license_number'] ?? '', 'issued date' => $license['issued_date'] ?? '', 'expiry date' => $license['expire_date'] ?? '',
            'licence front picture' => $license['driving_license_front'] ?? '', 'licence back picture' => $license['driving_license_back'] ?? '',
        ]);
        if (!empty($license['expire_date']) && $license['expire_date'] < date('Y-m-d')) {
            $licenseMissing[] = 'a licence that has not expired';
        }

        return [
            'personal' => $need([
                'first name' => $u->first_name, 'last name' => $u->last_name, 'e-mail' => $u->email, 'date of birth' => $u->date_of_birth,
                'gender' => $u->gender, 'address' => $u->address, 'profile photo' => $u->profile_image,
            ]),
            'license' => $licenseMissing,
            'aadhar' => $need(['Aadhaar number' => $aadhar['aadhar_number'] ?? '', 'Aadhaar front picture' => $aadhar['aadhar_card_front'] ?? '', 'Aadhaar back picture' => $aadhar['aadhar_card_back'] ?? '']),
            'pan' => $need(['PAN number' => $pan['pan_number'] ?? '', 'PAN card picture' => $pan['pan_card'] ?? '']),
            'bank' => $need([
                'account holder' => $bank['account_holder_name'] ?? '', 'account number' => $bank['account_number'] ?? '', 'IFSC' => $bank['ifsc'] ?? '',
                'bank name' => $bank['bank_name'] ?? '', 'branch' => $bank['branch_name'] ?? '',
            ]),
            'contacts' => $d['contacts'] ? [] : ['at least one emergency contact'],
            'vehicles' => $d['vehicleIds'] ? [] : ['at least one vehicle type'],
        ];
    }
    // [doc:end] missing

    private static function text($post, $key, $max = 255)
    {
        return mb_substr(trim((string) ($post[$key] ?? '')), 0, $max);
    }

    private static function date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);

        return $d && $d->format('Y-m-d') === $value ? $value : false;
    }

    /**
     * Stores an uploaded file of this driver as a lifelong document and returns its address ('' when no file was sent).
     *
     * @throws \InvalidArgumentException when the file is not an allowed picture / PDF
     */
    private static function upload($field, $driverId, $kind, $docType)
    {
        $file = \yii\web\UploadedFile::getInstanceByName($field);
        if (!$file || $file->error === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        if ($file->error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('The file could not be uploaded.');
        }
        $saved = MediaStore::saveFile($file->tempName, (int) $driverId, $file->name, $kind, $docType);

        return $saved['url'];
    }

    private static function touch(array $row, $adminId, $isNew)
    {
        $now = date('Y-m-d H:i:s');
        $row['updated_on'] = $now;
        $row['update_user_id'] = $adminId;
        if ($isNew) {
            $row['created_on'] = $now;
            $row['create_user_id'] = $adminId;
            $row['status'] = 1;
        }

        return $row;
    }

    /** insert or update the single row of a driver in a table */
    private static function put($table, $driverId, array $values, $adminId)
    {
        $db = Yii::$app->db;
        $id = (new Query())->select('id')->from($table)->where(['driver_id' => (int) $driverId])->orderBy(['id' => SORT_DESC])->scalar();
        if ($id) {
            $db->createCommand()->update($table, self::touch($values, $adminId, false), ['id' => $id])->execute();
        } else {
            $db->createCommand()->insert($table, self::touch($values + ['driver_id' => (int) $driverId], $adminId, true))->execute();
        }
    }

    // [doc:start] save - Saves one section of a driver's profile. Answers [ok, message].
    /**
     * @param int    $driverId
     * @param string $section one of SECTIONS
     * @param array  $post    the submitted form
     * @param int    $adminId who saves (written to update_user_id)
     * @return array [bool ok, string message]
     */
    public static function save($driverId, $section, array $post, $adminId)
    {
        $user = User::find()->where(['id' => (int) $driverId, 'user_role' => User::ROLE_DRIVER])->one();
        if (!$user) {
            return [false, 'There is no such driver.'];
        }
        if (!in_array($section, self::SECTIONS, true)) {
            return [false, 'Unknown section.'];
        }
        try {
            $tx = Yii::$app->db->beginTransaction();
            $result = self::{'save' . ucfirst($section)}($user, $post, (int) $adminId);
            if ($result[0] && $section !== 'police') {
                // every section is mandatory: nothing is saved while a mandatory part of it is still empty (checked inside the transaction)
                $left = self::missing($driverId)[$section] ?? [];
                if ($left) {
                    $result = [false, 'Not saved. Still required: ' . implode(', ', $left) . '.'];
                }
            }
            if ($result[0]) {
                $tx->commit();
            } else {
                $tx->rollBack();
            }

            return $result;
        } catch (\InvalidArgumentException $e) {
            isset($tx) && $tx->isActive && $tx->rollBack();

            return [false, $e->getMessage()];
        } catch (\Throwable $e) {
            isset($tx) && $tx->isActive && $tx->rollBack();
            Yii::error('driver profile ' . $section . ': ' . $e->getMessage(), 'profile');

            return [false, 'Could not save. Nothing was changed.'];
        }
    }
    // [doc:end] save

    private static function savePersonal(User $u, array $p, $adminId)
    {
        $first = self::text($p, 'first_name', 100);
        $email = self::text($p, 'email', 255);
        $dob = self::date($p['date_of_birth'] ?? '');
        $gender = self::text($p, 'gender', 20);
        $alt = preg_replace('/\D+/', '', (string) ($p['alternative_contact'] ?? ''));
        $last = self::text($p, 'last_name', 100);
        if ($first === '') {
            return [false, 'The first name is required.'];
        }
        if ($last === '') {
            return [false, 'The last name is required.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'The e-mail address is not valid.'];
        }
        if ($email !== '' && User::find()->where(['email' => $email, 'user_role' => User::ROLE_DRIVER])->andWhere(['<>', 'id', $u->id])->exists()) {
            return [false, 'Another driver already uses this e-mail address.'];
        }
        if ($dob === false) {
            return [false, 'The date of birth must be a date (year-month-day).'];
        }
        if ($dob !== null && $dob > date('Y-m-d', strtotime('-18 years'))) {
            return [false, 'A partner must be at least 18 years old.'];
        }
        if ($gender !== '' && !in_array($gender, self::GENDERS, true)) {
            return [false, 'Choose Male, Female or Others.'];
        }
        if ($alt !== '' && strlen($alt) !== 10) {
            return [false, 'The alternative contact must be 10 digits.'];
        }
        $u->first_name = $first;
        $u->last_name = $last;
        $u->email = $email;
        $u->date_of_birth = $dob;
        $u->gender = $gender;
        $u->alternative_contact = $alt;
        $address = self::text($p, 'address', 255);
        // the city is not typed: it is found from the address (point on the map -> city boundary), see AddressCity
        $warning = '';
        if ($address !== trim((string) $u->address) || (trim($address) !== '' && !$u->city_id)) {
            $u->address = $address;
            $warning = \app\modules\geo\services\AddressCity::applyTo($u);
        }
        $photo = self::upload('profile_image_file', $u->id, MediaStore::KIND_PROFILE, 'profile_image');
        if ($photo !== '') {
            $u->profile_image = $photo;
        }

        if (!$u->save(false)) {
            return [false, 'Could not save.'];
        }

        return [true, 'Personal details saved.' . ($u->city_name ? ' City found from the address: ' . $u->city_name . '.' : '')
            . ($warning !== '' ? ' The city could not be found: ' . $warning : '')];
    }

    private static function saveLicense(User $u, array $p, $adminId)
    {
        $no = strtoupper(preg_replace('/[\s-]+/', '', self::text($p, 'driving_license_number', 30)));
        $issued = self::date($p['issued_date'] ?? '');
        $expire = self::date($p['expire_date'] ?? '');
        if ($no === '' || !preg_match('/^[A-Z0-9\/]{6,20}$/', $no)) {
            return [false, 'Enter the driving licence number (6 to 20 letters and digits).'];
        }
        if ($issued === false || $expire === false) {
            return [false, 'Dates must be dates (year-month-day).'];
        }
        if ($issued && $expire && $expire < $issued) {
            return [false, 'The licence expires before it was issued.'];
        }
        if ((new Query())->from('driving_license')->where(['driving_license_number' => $no])->andWhere(['<>', 'driver_id', $u->id])->exists()) {
            return [false, 'This driving licence number belongs to another driver.'];
        }
        $v = ['driving_license_number' => $no, 'issued_date' => $issued, 'expire_date' => $expire];
        foreach (['front' => 'driving_license_front', 'back' => 'driving_license_back'] as $side => $col) {
            $url = self::upload('license_' . $side, $u->id, MediaStore::KIND_DOCUMENT, 'driving_license');
            if ($url !== '') {
                $v[$col] = $url;
            }
        }
        self::put('driving_license', $u->id, $v + (self::exists('driving_license', $u->id) ? [] : ['driving_license_front' => '', 'driving_license_back' => '']), $adminId);

        return [true, 'Driving licence saved.'];
    }

    private static function exists($table, $driverId)
    {
        return (new Query())->from($table)->where(['driver_id' => (int) $driverId])->exists();
    }

    private static function saveAadhar(User $u, array $p, $adminId)
    {
        $no = preg_replace('/\D+/', '', (string) ($p['aadhar_number'] ?? ''));
        if (strlen($no) !== 12) {
            return [false, 'The Aadhaar number must be 12 digits.'];
        }
        if ((new Query())->from('driver_aadhar_card')->where(['aadhar_number' => $no])->andWhere(['<>', 'driver_id', $u->id])->exists()) {
            return [false, 'This Aadhaar number belongs to another driver.'];
        }
        $v = ['aadhar_number' => $no];
        foreach (['front' => 'aadhar_card_front', 'back' => 'aadhar_card_back'] as $side => $col) {
            $url = self::upload('aadhar_' . $side, $u->id, MediaStore::KIND_DOCUMENT, 'aadhar');
            if ($url !== '') {
                $v[$col] = $url;
            }
        }
        self::put('driver_aadhar_card', $u->id, $v + (self::exists('driver_aadhar_card', $u->id) ? [] : ['aadhar_card_front' => '', 'aadhar_card_back' => '']), $adminId);

        return [true, 'Aadhaar saved.'];
    }

    private static function savePan(User $u, array $p, $adminId)
    {
        $no = strtoupper(self::text($p, 'pan_number', 10));
        if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $no)) {
            return [false, 'The PAN must look like ABCDE1234F.'];
        }
        $v = ['pan_number' => $no];
        $url = self::upload('pan_file', $u->id, MediaStore::KIND_DOCUMENT, 'pan_card');
        if ($url !== '') {
            $v['pan_card'] = $url;
        } elseif (!self::exists('driver_pan_cards', $u->id)) {
            $v['pan_card'] = '';
        }
        self::put('driver_pan_cards', $u->id, $v, $adminId);

        return [true, 'PAN saved.'];
    }

    private static function saveBank(User $u, array $p, $adminId)
    {
        $holder = self::text($p, 'account_holder_name', 100);
        $acc = preg_replace('/\s+/', '', self::text($p, 'account_number', 30));
        $ifsc = strtoupper(self::text($p, 'ifsc', 15));
        $upi = self::text($p, 'upi', 100);
        if ($holder === '') {
            return [false, 'Enter the account holder name.'];
        }
        if (!preg_match('/^[0-9]{6,20}$/', $acc)) {
            return [false, 'The account number must be 6 to 20 digits.'];
        }
        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            return [false, 'The IFSC code must look like HDFC0001234.'];
        }
        if ($upi !== '' && !preg_match('/^[A-Za-z0-9._-]{2,}@[A-Za-z0-9]{2,}$/', $upi)) {
            return [false, 'The UPI id must look like name@bank.'];
        }
        self::put('driver_bank_details', $u->id, [
            'account_holder_name' => $holder, 'account_number' => $acc, 'ifsc' => $ifsc,
            'bank_name' => self::text($p, 'bank_name', 100), 'branch_name' => self::text($p, 'branch_name', 100), 'upi' => $upi,
        ], $adminId);

        return [true, 'Bank details saved.'];
    }

    private static function saveContacts(User $u, array $p, $adminId)
    {
        $db = Yii::$app->db;
        $rows = is_array($p['contact'] ?? null) ? $p['contact'] : [];
        $own = (new Query())->select('id')->from('driver_emergency_contacts')->where(['driver_id' => $u->id])->column();
        foreach ($rows as $id => $c) {
            $isNew = !ctype_digit((string) $id);
            $name = self::text($c, 'name', 100);
            $phone = preg_replace('/\D+/', '', (string) ($c['phone_number'] ?? ''));
            $aadhar = preg_replace('/\D+/', '', (string) ($c['aadhar_number'] ?? ''));
            $rel = self::text($c, 'relationship', 50);
            if (!$isNew && !in_array((int) $id, array_map('intval', $own), true)) {
                continue; // never touch another driver's contact
            }
            if (!empty($c['delete'])) {
                if (!$isNew) {
                    $db->createCommand()->delete('driver_emergency_contacts', ['id' => (int) $id, 'driver_id' => $u->id])->execute();
                }
                continue;
            }
            if ($isNew && $name === '' && $phone === '') {
                continue; // an empty new row
            }
            if ($name === '' || strlen($phone) !== 10) {
                return [false, 'Every emergency contact needs a name and a 10 digit phone number.'];
            }
            if ($aadhar !== '' && strlen($aadhar) !== 12) {
                return [false, 'A contact\'s Aadhaar number must be 12 digits (or empty).'];
            }
            $v = ['name' => $name, 'phone_number' => $phone, 'aadhar_number' => $aadhar !== '' ? $aadhar : null, 'relationship' => $rel];
            if ($isNew) {
                $db->createCommand()->insert('driver_emergency_contacts', self::touch($v + ['driver_id' => $u->id], $adminId, true))->execute();
            } else {
                $db->createCommand()->update('driver_emergency_contacts', self::touch($v, $adminId, false), ['id' => (int) $id, 'driver_id' => $u->id])->execute();
            }
        }

        return [true, 'Emergency contacts saved.'];
    }

    private static function saveVehicles(User $u, array $p, $adminId)
    {
        $db = Yii::$app->db;
        $valid = array_map('intval', (new Query())->select('id')->from('vehicle_types')->where(['status' => 1])->column());
        $want = array_values(array_intersect($valid, array_map('intval', (array) ($p['vehicle_type'] ?? []))));
        $db->createCommand()->delete('driver_vehicle_types', ['driver_id' => $u->id])->execute();
        foreach ($want as $vt) {
            $db->createCommand()->insert('driver_vehicle_types', self::touch(['driver_id' => $u->id, 'vehicle_type_id' => $vt], $adminId, true))->execute();
        }

        return [true, 'Vehicle types saved (' . count($want) . ').'];
    }

    private static function savePolice(User $u, array $p, $adminId)
    {
        $u->is_done_police_verification = !empty($p['police_done']) ? User::profile_verified_yes : User::profile_verified_no;

        return $u->save(false) ? [true, 'Police verification ' . (!empty($p['police_done']) ? 'marked done.' : 'marked not done.')] : [false, 'Could not save.'];
    }
}
