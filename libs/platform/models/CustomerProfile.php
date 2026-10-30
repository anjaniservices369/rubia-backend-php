<?php

namespace app\modules\admin\models;

use app\models\User;
use app\modules\filestorage\services\MediaStore;
use Yii;
use yii\db\Query;

/**
 * Everything a customer enters in the customer app, in one place, readable and editable by the company: personal details (with the photo),
 * emergency contacts and saved places (home, office, other). The dashboard page "Users-profile" (users/user-profile) is built on this.
 *
 * The customer app registration requires a full name, e-mail and two emergency contact numbers. Contact names and relationships are not collected by the app.
 * Date of birth, gender, address, alternative contact, city and photo are optional.
 * Each section is saved on its own (section = personal, contacts, places) into the rows the app writes, so both always agree.
 */
class CustomerProfile
{
    const SECTIONS = ['personal', 'contacts', 'places'];

    const PLACE_TYPES = ['Home', 'Office', 'Other'];

    // [doc:start] load - All profile data of one customer, or null when there is no such customer.
    /**
     * @return array|null user, contacts, places, stats, cities
     */
    public static function load($userId)
    {
        $user = User::find()->where(['id' => (int) $userId, 'user_role' => User::ROLE_USER])->one();
        if (!$user) {
            return null;
        }
        $rides = (new Query())->from('booking')->where(['user_id' => (int) $userId]);

        return [
            'user' => $user,
            'contacts' => (new Query())->from('user_emergency_contacts')->where(['user_id' => (int) $userId])->orderBy(['id' => SORT_ASC])->all(),
            'places' => (new Query())->from('favourite_location')->where(['user_id' => (int) $userId, 'status' => 1])->orderBy(['id' => SORT_ASC])->all(),
            'stats' => [
                'rides' => (int) (clone $rides)->count(),
                'completed' => (int) (clone $rides)->andWhere(['status' => \app\modules\admin\models\base\Booking::STATUS_RIDE_COMPLETED])->count(),
                'last' => (clone $rides)->max('created_on'),
            ],
            'cities' => (new Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy(['name' => SORT_ASC])->all(),
        ];
    }
    // [doc:end] load

    // [doc:start] missing - Required customer registration details not yet provided.
    /**
     * @return string[] required details that are missing
     */
    public static function missing($userId)
    {
        $u = User::find()->where(['id' => (int) $userId])->one();
        $out = [];
        if (!$u || trim((string) $u->first_name) === '') {
            $out[] = 'full name';
        }
        if (!$u || !filter_var(trim((string) $u->email), FILTER_VALIDATE_EMAIL)) {
            $out[] = 'e-mail';
        }
        $phones = (new Query())
            ->select('phone_number')
            ->from('user_emergency_contacts')
            ->where(['user_id' => (int) $userId, 'status' => 1])
            ->column();
        $validPhones = array_filter(array_map(static function ($phone) {
            $phone = preg_replace('/\D+/', '', (string) $phone);
            return preg_match('/^[6-9]\d{9}$/', $phone) ? $phone : null;
        }, $phones));
        if (count($validPhones) < 2) {
            $out[] = 'two emergency contacts';
        }

        return $out;
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

    private static function stamp(array $row, $adminId, $isNew)
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

    // [doc:start] save - Saves one section of a customer's profile. Answers [ok, message].
    /**
     * @return array [bool ok, string message]
     */
    public static function save($userId, $section, array $post, $adminId)
    {
        $user = User::find()->where(['id' => (int) $userId, 'user_role' => User::ROLE_USER])->one();
        if (!$user) {
            return [false, 'There is no such customer.'];
        }
        if (!in_array($section, self::SECTIONS, true)) {
            return [false, 'Unknown section.'];
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            $result = self::{'save' . ucfirst($section)}($user, $post, (int) $adminId);
            $result[0] ? $tx->commit() : $tx->rollBack();

            return $result;
        } catch (\InvalidArgumentException $e) {
            $tx->isActive && $tx->rollBack();

            return [false, $e->getMessage()];
        } catch (\Throwable $e) {
            $tx->isActive && $tx->rollBack();
            Yii::error('customer profile ' . $section . ': ' . $e->getMessage(), 'profile');

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
        if ($first === '') {
            return [false, 'The first name is required.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'A valid e-mail address is required.'];
        }
        if ($email !== '' && User::find()->where(['email' => $email, 'user_role' => User::ROLE_USER])->andWhere(['<>', 'id', $u->id])->exists()) {
            return [false, 'Another customer already uses this e-mail address.'];
        }
        if ($dob === false) {
            return [false, 'The date of birth must be a date (year-month-day).'];
        }
        if ($dob !== null && $dob > date('Y-m-d')) {
            return [false, 'The date of birth cannot be in the future.'];
        }
        if ($gender !== '' && !in_array($gender, ['Male', 'Female', 'Other', 'Others'], true)) {
            return [false, 'Choose Male, Female or Other.'];
        }
        if ($alt !== '' && strlen($alt) !== 10) {
            return [false, 'The alternative contact must be 10 digits.'];
        }
        $u->first_name = $first;
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
        $file = \yii\web\UploadedFile::getInstanceByName('profile_image_file');
        if ($file && $file->error !== UPLOAD_ERR_NO_FILE) {
            if ($file->error !== UPLOAD_ERR_OK) {
                return [false, 'The photo could not be uploaded.'];
            }
            $u->profile_image = MediaStore::saveFile($file->tempName, (int) $u->id, $file->name, MediaStore::KIND_PROFILE, 'profile_image')['url'];
        }

        if (!$u->save(false)) {
            return [false, 'Could not save.'];
        }

        return [true, 'Personal details saved.' . ($u->city_name ? ' City found from the address: ' . $u->city_name . '.' : '')
            . ($warning !== '' ? ' The city could not be found: ' . $warning : '')];
    }

    private static function saveContacts(User $u, array $p, $adminId)
    {
        $db = Yii::$app->db;
        $own = array_map('intval', (new Query())->select('id')->from('user_emergency_contacts')->where(['user_id' => $u->id])->column());
        $phones = [];
        foreach (is_array($p['contact'] ?? null) ? $p['contact'] : [] as $id => $c) {
            $isNew = !ctype_digit((string) $id);
            if (!$isNew && !in_array((int) $id, $own, true)) {
                continue; // never touch another customer's contact
            }
            if (!empty($c['delete'])) {
                if (!$isNew) {
                    $db->createCommand()->delete('user_emergency_contacts', ['id' => (int) $id, 'user_id' => $u->id])->execute();
                }
                continue;
            }
            $name = self::text($c, 'name', 100);
            $phone = preg_replace('/\D+/', '', (string) ($c['phone_number'] ?? ''));
            if ($isNew && $name === '' && $phone === '') {
                continue; // an empty new row
            }
            if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                return [false, 'Every emergency contact needs a valid 10 digit phone number.'];
            }
            if (in_array($phone, $phones, true)) {
                return [false, 'Emergency contact numbers for one customer must be different.'];
            }
            $phones[] = $phone;
            $v = ['name' => $name, 'phone_number' => $phone, 'relationship' => self::text($c, 'relationship', 50)];
            if ($isNew) {
                $db->createCommand()->insert('user_emergency_contacts', self::stamp($v + ['user_id' => $u->id], $adminId, true))->execute();
            } else {
                $db->createCommand()->update('user_emergency_contacts', self::stamp($v, $adminId, false), ['id' => (int) $id, 'user_id' => $u->id])->execute();
            }
        }

        return [true, 'Emergency contacts saved.'];
    }

    private static function savePlaces(User $u, array $p, $adminId)
    {
        $db = Yii::$app->db;
        $own = array_map('intval', (new Query())->select('id')->from('favourite_location')->where(['user_id' => $u->id, 'status' => 1])->column());
        foreach (is_array($p['place'] ?? null) ? $p['place'] : [] as $id => $c) {
            if (!ctype_digit((string) $id) || !in_array((int) $id, $own, true)) {
                continue; // places are added in the app (they need a point on the map); here they are corrected or removed
            }
            if (!empty($c['delete'])) {
                $db->createCommand()->update('favourite_location', self::stamp(['status' => 0], $adminId, false), ['id' => (int) $id, 'user_id' => $u->id])->execute();
                continue;
            }
            $type = self::text($c, 'address_type', 20);
            $address = self::text($c, 'address', 255);
            $pin = preg_replace('/\D+/', '', (string) ($c['pincode'] ?? ''));
            if (!in_array($type, self::PLACE_TYPES, true)) {
                return [false, 'A saved place must be Home, Office or Other.'];
            }
            if ($address === '') {
                return [false, 'A saved place needs its address.'];
            }
            if ($pin !== '' && strlen($pin) !== 6) {
                return [false, 'The pincode must be 6 digits.'];
            }
            $db->createCommand()->update('favourite_location', self::stamp(['address_type' => $type, 'address' => $address, 'pincode' => $pin !== '' ? (int) $pin : null], $adminId, false), ['id' => (int) $id, 'user_id' => $u->id])->execute();
        }

        return [true, 'Saved places saved.'];
    }
}
