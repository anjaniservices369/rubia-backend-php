<?php

namespace app\modules\geo\services;

use Yii;
use yii\db\Query;
use yii\helpers\Json;

/**
 * Draws a city's service area from its pincodes.
 *
 * How it works, in plain words:
 *   1. every pincode of the city (pincode table, active rows) is looked up once on OpenStreetMap (Google's Geocoding API is the backup) and its
 *      rectangle on the map is remembered in `pincode_area`;
 *   2. the service area is the smallest convex outline that contains all those rectangles;
 *   3. saved into `city.boundary`, which the apps' "do we serve this place" check (check-boundary, get-estimate)
 *      already reads - so nothing else in the system changes.
 *
 * A pincode that Google cannot find is listed as "missing" and does not widen the area. A pincode that lies far
 * from the others of its city is flagged "outlier": it is usually a pincode filed under the wrong city.
 */
class PincodeAreas
{
    /** A pincode further than this from the middle of its city's other pincodes is flagged. */
    const OUTLIER_KM = 150;

    // [doc:start] lookup - Location of one pincode: from the saved copy, otherwise from Google (and saved).
    /**
     * Location of one pincode: from the saved copy, otherwise from Google (and saved).
     *
     * @return array|null [pincode, found, lat, lng, ne_lat, ne_lng, sw_lat, sw_lng, formatted_address] or null when
     *                    Google could not be asked
     */
    public static function lookup($pincode, $refresh = false)
    {
        $pincode = trim((string) $pincode);
        if (!preg_match('/^[0-9]{6}$/', $pincode)) {
            return ['pincode' => $pincode, 'found' => 0];
        }
        $row = (new Query())->from('pincode_area')->where(['pincode' => $pincode])->one();
        if ($row && !$refresh) {
            return $row;
        }

        // OpenStreetMap first (free, no key); Google stays as the optional backup when OpenStreetMap does not know the pincode
        $data = self::fromOpenStreetMap($pincode);
        if ($data === null) {
            $data = self::fromGoogle($pincode);
        }
        if ($data === null) {
            return $row ?: null; // nobody could be asked (network, key, quota): do not remember it as "not found"
        }
        $db = Yii::$app->db;
        if ($row) {
            $db->createCommand()->update('pincode_area', $data, ['id' => $row['id']])->execute();
        } else {
            $db->createCommand()->insert('pincode_area', $data)->execute();
        }

        return (new Query())->from('pincode_area')->where(['pincode' => $pincode])->one();
    }
    // [doc:end] lookup

    /** @return array|null a pincode_area row's values, or null when OpenStreetMap found nothing or could not be asked */
    private static function fromOpenStreetMap($pincode)
    {
        static $last = 0.0;
        $wait = 1.1 - (microtime(true) - $last); // the service allows one request a second
        if ($wait > 0) {
            usleep((int) ($wait * 1000000));
        }
        $last = microtime(true);
        $ch = curl_init('https://nominatim.openstreetmap.org/search?' . http_build_query([
            'postalcode' => $pincode, 'countrycodes' => 'in', 'format' => 'jsonv2', 'limit' => 1,
        ]));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_USERAGENT => 'RubiaServices-admin/1.0']);
        $body = curl_exec($ch);
        curl_close($ch);
        $json = $body ? json_decode($body, true) : null;
        if (!is_array($json) || empty($json[0]['boundingbox']) || !isset($json[0]['lat'], $json[0]['lon'])) {
            return null;
        }
        $r = $json[0];
        $bb = $r['boundingbox']; // south, north, west, east

        return ['pincode' => $pincode, 'found' => 1, 'fetched_on' => date('Y-m-d H:i:s'),
            'lat' => (float) $r['lat'], 'lng' => (float) $r['lon'],
            'ne_lat' => (float) $bb[1], 'ne_lng' => (float) $bb[3], 'sw_lat' => (float) $bb[0], 'sw_lng' => (float) $bb[2],
            'formatted_address' => mb_substr((string) ($r['display_name'] ?? ''), 0, 255)];
    }

    /** @return array|null a pincode_area row's values (found 0 when Google says the pincode does not exist), or null when Google could not be asked */
    private static function fromGoogle($pincode)
    {
        $key = GoogleKeys::serverKey();
        if ($key === '') {
            return null;
        }
        $ch = curl_init('https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'components' => 'postal_code:' . $pincode . '|country:IN', 'key' => $key,
        ]));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8]);
        $body = curl_exec($ch);
        curl_close($ch);
        $json = $body ? json_decode($body, true) : null;
        if (!is_array($json) || !isset($json['status'])) {
            return null;
        }
        if (!in_array($json['status'], ['OK', 'ZERO_RESULTS'], true)) {
            Yii::warning('pincode geocode ' . $pincode . ': ' . $json['status'], 'geo');

            return null;
        }
        $data = ['pincode' => $pincode, 'found' => 0, 'fetched_on' => date('Y-m-d H:i:s')];
        if ($json['status'] === 'OK' && !empty($json['results'][0]['geometry'])) {
            $g = $json['results'][0]['geometry'];
            $view = $g['viewport'] ?? ($g['bounds'] ?? null);
            $data = array_merge($data, [
                'found' => 1, 'lat' => $g['location']['lat'], 'lng' => $g['location']['lng'],
                'ne_lat' => $view['northeast']['lat'] ?? $g['location']['lat'], 'ne_lng' => $view['northeast']['lng'] ?? $g['location']['lng'],
                'sw_lat' => $view['southwest']['lat'] ?? $g['location']['lat'], 'sw_lng' => $view['southwest']['lng'] ?? $g['location']['lng'],
                'formatted_address' => mb_substr((string) ($json['results'][0]['formatted_address'] ?? ''), 0, 255),
            ]);
        }

        return $data;
    }

    // [doc:start] forCity - Everything the dashboard page needs for one city.
    /**
     * Everything the dashboard page needs for one city.
     *
     * @return array [pincodes => [...], hull => [[lat,lng],...], center => [lat,lng]|null, found => n, missing => n]
     */
    public static function forCity($cityId, $refresh = false, $module = null)
    {
        $names = $module !== null
            ? MapDesigner::pincodeNames($module, $cityId)
            : (new Query())->select('name')->distinct()->from('pincode')
                ->where(['city_id' => (int) $cityId, 'status' => 1])->orderBy('name')->column();

        return self::forPincodes($names, $refresh);
    }
    // [doc:end] forCity

    // [doc:start] forPincodes - The same as forCity, for a plain list of pincodes (used for the maps kept in pincode_map).
    /**
     * The same as forCity, for a plain list of pincodes (used for the maps kept in pincode_map).
     */
    public static function forPincodes(array $names, $refresh = false)
    {
        $items = [];
        $points = [];
        foreach ($names as $name) {
            $a = self::lookup($name, $refresh);
            $item = ['pincode' => $name, 'found' => !empty($a['found']), 'lat' => null, 'lng' => null,
                'address' => $a['formatted_address'] ?? '', 'outlier' => false, 'asked' => $a !== null];
            if (!empty($a['found'])) {
                $item['lat'] = (float) $a['lat'];
                $item['lng'] = (float) $a['lng'];
                $item['box'] = [(float) $a['sw_lat'], (float) $a['sw_lng'], (float) $a['ne_lat'], (float) $a['ne_lng']];
            }
            $items[] = $item;
        }

        // outliers: far from the middle (median) of the city's other pincodes
        $located = array_values(array_filter($items, function ($i) { return $i['found']; }));
        $center = null;
        if (count($located) >= 3) {
            $lats = array_column($located, 'lat');
            $lngs = array_column($located, 'lng');
            sort($lats);
            sort($lngs);
            $mid = intdiv(count($lats), 2);
            $center = [$lats[$mid], $lngs[$mid]];
            foreach ($items as &$it) {
                if ($it['found'] && self::km($center[0], $center[1], $it['lat'], $it['lng']) > self::OUTLIER_KM) {
                    $it['outlier'] = true;
                }
            }
            unset($it);
        } elseif ($located) {
            $center = [$located[0]['lat'], $located[0]['lng']];
        }

        // the outline uses every located pincode that is not an outlier
        foreach ($items as $it) {
            if ($it['found'] && !$it['outlier']) {
                $b = $it['box'];
                $points[] = [$b[0], $b[1]];
                $points[] = [$b[0], $b[3]];
                $points[] = [$b[2], $b[1]];
                $points[] = [$b[2], $b[3]];
            }
        }

        return [
            'pincodes' => $items,
            'hull' => self::hull($points),
            'center' => $center,
            'found' => count($located),
            'missing' => count($items) - count($located),
            'outliers' => count(array_filter($items, function ($i) { return $i['outlier']; })),
        ];
    }
    // [doc:end] forPincodes

    // [doc:start] savePincodeMap - Saves the outline in pincode_map, together with the pincodes it was drawn from. The city's hand-drawn map (service_map,.
    /**
     * Saves the outline in pincode_map, together with the pincodes it was drawn from. The city's hand-drawn map (service_map,
     * city.boundary) is not touched; Service maps read this one first.
     */
    public static function savePincodeMap($module, $cityId, array $hull, array $pincodes)
    {
        if (count($hull) < 3) {
            return false;
        }
        $points = array_map(function ($p) {
            return [round($p[0], 7), round($p[1], 7)];
        }, $hull);
        $now = date('Y-m-d H:i:s');
        $row = [
            'boundary' => Json::encode(json_encode($points)),
            'pincodes' => Json::encode(array_values($pincodes)),
            'pincode_count' => count($pincodes),
            'updated_on' => $now,
        ];
        $db = Yii::$app->db;
        if ((new Query())->from('pincode_map')->where(['module' => (int) $module, 'city_id' => (int) $cityId])->exists()) {
            $db->createCommand()->update('pincode_map', $row, ['module' => (int) $module, 'city_id' => (int) $cityId])->execute();
        } else {
            $db->createCommand()->insert('pincode_map', $row + ['module' => (int) $module, 'city_id' => (int) $cityId, 'created_on' => $now])->execute();
        }

        return true;
    }
    // [doc:end] savePincodeMap

    // [doc:start] saveBoundary - Saves the outline as the city's boundary, in the format the existing boundary check reads.
    /**
     * Saves the outline as the city's boundary, in the format the existing boundary check reads.
     */
    public static function saveBoundary($cityId, array $hull)
    {
        if (count($hull) < 3) {
            return false;
        }
        $points = array_map(function ($p) {
            return [round($p[0], 7), round($p[1], 7)];
        }, $hull);
        // stored exactly like the hand-drawn boundaries: a JSON string that holds a JSON list of points
        $value = Json::encode(json_encode($points));

        return Yii::$app->db->createCommand()->update('city', ['boundary' => $value], ['id' => (int) $cityId])->execute() >= 0;
    }
    // [doc:end] saveBoundary

    // [doc:start] hull - Smallest convex outline around the points (monotone chain).
    /** Smallest convex outline around the points (monotone chain). */
    public static function hull(array $points)
    {
        $pts = [];
        foreach ($points as $p) {
            $pts[implode(',', $p)] = $p;
        }
        $pts = array_values($pts);
        usort($pts, function ($a, $b) {
            return $a[1] <=> $b[1] ?: $a[0] <=> $b[0];
        });
        if (count($pts) < 3) {
            return $pts;
        }
        $cross = function ($o, $a, $b) {
            return ($a[1] - $o[1]) * ($b[0] - $o[0]) - ($a[0] - $o[0]) * ($b[1] - $o[1]);
        };
        $lower = [];
        foreach ($pts as $p) {
            while (count($lower) >= 2 && $cross($lower[count($lower) - 2], $lower[count($lower) - 1], $p) <= 0) {
                array_pop($lower);
            }
            $lower[] = $p;
        }
        $upper = [];
        foreach (array_reverse($pts) as $p) {
            while (count($upper) >= 2 && $cross($upper[count($upper) - 2], $upper[count($upper) - 1], $p) <= 0) {
                array_pop($upper);
            }
            $upper[] = $p;
        }
        array_pop($lower);
        array_pop($upper);

        return array_merge($lower, $upper);
    }
    // [doc:end] hull

    private static function km($lat1, $lng1, $lat2, $lng2)
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 12742 * asin(min(1, sqrt($h)));
    }
}
