<?php

namespace app\modules\admin\models;

use Yii;
use \app\modules\admin\models\base\Booking as BaseBooking;

/**
 * This is the model class for table "booking".
 */
class Booking extends BaseBooking
{
    // [doc:start] radiusSummary - "Booked one way, billed another": the message and the route to draw.
    /**
     * For the closing details of a completed ride. When the ride was changed at the end because of its route
     * (booking_route_log.converted: booked in-city but went beyond the admin's radius and billed outstation, and/or booked one way
     * but came back (round trip) or booked round trip but never came back (one way)), returns radius_converted = true, a plain-language
     * radius_note, the farthest distance from the pickup, the radius, and route_points (the driver's route, thinned to at most
     * 150 points so the response stays small). Otherwise just radius_converted = false. (The key names date from the radius rule.)
     *
     * @return array
     */
    public function radiusSummary()
    {
        $none = ['radius_converted' => false];
        if (empty($this->id) || (int) $this->status !== self::STATUS_RIDE_COMPLETED) {
            return $none;
        }
        $row = (new \yii\db\Query())->from('booking_route_log')
            ->where(['booking_id' => $this->id, 'converted' => 1])->orderBy(['id' => SORT_DESC])->one();
        if (!$row) {
            return $none;
        }
        $points = json_decode((string) $row['route'], true);
        $points = is_array($points) ? array_values($points) : [];
        $count = count($points);
        if ($count > 150) {
            $step = ($count - 1) / 149;
            $thin = [];
            for ($i = 0; $i < 150; $i++) {
                $thin[] = $points[(int) round($i * $step)];
            }
            $points = $thin;
        }
        $maxKm = (float) $row['max_km_from_pickup'];
        $radius = (float) $row['radius_km'];
        $endKm = (float) $row['end_km_from_pickup'];
        $names = [self::RIDER_TYPE_ONEWAY => 'one-way', self::RIDER_TYPE_ROUNDTRIP => 'round'];
        $notes = [];
        if (!empty($row['ride_type_from']) && !empty($row['ride_type_to']) && (int) $row['ride_type_from'] !== (int) $row['ride_type_to']) {
            $notes[] = 'Booked as a ' . $names[(int) $row['ride_type_from']] . ' trip. '
                . ((int) $row['ride_type_to'] === self::RIDER_TYPE_ROUNDTRIP
                    ? 'The ride went ' . round($maxKm, 1) . ' km out and came back to the pickup point'
                    : 'The ride ended ' . round($endKm, 1) . ' km from the pickup point and did not come back')
                . ', so it was billed as a ' . $names[(int) $row['ride_type_to']] . ' trip.';
        }
        if (!empty($row['radius_converted'])) {
            $notes[] = ($radius > 0 && $maxKm > $radius)
                ? 'Booked as an in-city trip. The ride went ' . round($maxKm, 1) . ' km from the pickup point (the in-city limit is '
                    . round($radius, 1) . ' km), so it was billed as an outstation trip.'
                : 'Booked as an in-city trip. The ride left the city\'s in-city area (the farthest point was ' . round($maxKm, 1)
                    . ' km from the pickup), so it was billed as an outstation trip.';
        }

        return [
            'radius_converted' => true,
            'radius_note' => implode(' ', $notes),
            'max_km_from_pickup' => $maxKm,
            'incity_radius_km' => $radius,
            'route_points' => $points,
        ];
    }
    // [doc:end] radiusSummary

    // ================================================================================================================
    // Real-time distance tracking: the distance of a ride is built up on the server, position by position, while the
    // ride is running. The numbers below decide which reported positions count.
    // ================================================================================================================
    const TRACK_MAX_ACCURACY_M = 60;     // a position the phone itself says is worse than this is ignored
    const TRACK_MIN_MOVE_M = 15;         // moves smaller than this (or than half the reported accuracy) are GPS drift, not driving
    const TRACK_MAX_SPEED_KMH = 180;     // a jump that implies a faster speed than this is a GPS error
    const TRACK_GAP_SECONDS = 300;       // no position for this long (tunnel, phone asleep): the stretch is bridged, not measured
    const TRACK_GAP_KM = 3.0;            // the last stretch to the final drop, if longer than this, is bridged like a gap
    const TRACK_ROAD_FACTOR = 1.3;       // a bridged stretch is straight-line x this (roads are longer than the straight line)
    const TRACK_MAX_REJECTS = 3;         // after this many jumps in a row the new place is believed and measuring restarts from it

    // [doc:start] recordTrackForDriver - Adds a position the driver's phone reported to the ride that driver is on.
    /**
     * Called with every position the Partner app reports (update-lat-lng). Does nothing unless the driver has a ride that has started.
     *
     * @param int        $driverId
     * @param float      $lat
     * @param float      $lng
     * @param float|null $accuracyM   the phone's own accuracy estimate, in metres
     * @param float|null $speedKmh    the phone's speed reading, km/h
     * @param int|null   $recordedAtMs time the phone took the reading, ms since 1970 (the server clock is used when it is missing or off)
     * @return array|null see recordTrackPoint, or null when the driver is not on a started ride
     */
    public static function recordTrackForDriver($driverId, $lat, $lng, $accuracyM = null, $speedKmh = null, $recordedAtMs = null)
    {
        $bookingId = (new \yii\db\Query())->select('id')->from('booking')
            ->where(['driver_id' => (int) $driverId, 'status' => self::STATUS_START_RIDE])->orderBy(['id' => SORT_DESC])->scalar();

        return $bookingId ? self::recordTrackPoint((int) $bookingId, $lat, $lng, $accuracyM, $speedKmh, $recordedAtMs) : null;
    }
    // [doc:end] recordTrackForDriver

    // [doc:start] recordTrackPoint - Checks one reported position and, if it is good, adds the distance it covers to the ride.
    /**
     * The first point of a ride is measured from the pickup the driver arrived at (at the time the ride started), so the first
     * stretch is not lost. After that, each new point is measured from the last accepted one:
     *  - inaccurate readings, repeated or older readings, and moves smaller than the GPS drift are ignored;
     *  - a jump faster than TRACK_MAX_SPEED_KMH is an error and is ignored (after TRACK_MAX_REJECTS in a row it is believed);
         *  - a stretch with no readings for TRACK_GAP_SECONDS is bridged with the straight line x TRACK_ROAD_FACTOR
     *    and counted as "gap" distance, so the bill says how much of it was measured and how much estimated;
     *  - anything else adds the straight-line step. With a reading every 10-20 seconds the steps follow the road closely.
     * Runs in one database transaction with the booking row locked, so readings that arrive together cannot be counted twice.
     *
     * @return array accepted (bool), reason (string), distance_km (running total), segment_km (added by this point)
     */
    public static function recordTrackPoint($bookingId, $lat, $lng, $accuracyM = null, $speedKmh = null, $recordedAtMs = null)
    {
        $out = ['accepted' => false, 'reason' => '', 'distance_km' => null, 'segment_km' => 0.0];
        if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180 || ((float) $lat == 0.0 && (float) $lng == 0.0)) {
            $out['reason'] = 'bad_position';
            return $out;
        }
        $lat = (float) $lat;
        $lng = (float) $lng;
        $accuracyM = is_numeric($accuracyM) && (float) $accuracyM >= 0 ? (float) $accuracyM : null;
        $speedKmh = is_numeric($speedKmh) ? max(0.0, (float) $speedKmh) : null;
        $now = time();
        $t = $now;
        if (is_numeric($recordedAtMs) && (float) $recordedAtMs > 1e12) {
            $candidate = (int) round((float) $recordedAtMs / 1000);
            // a phone clock that is far off is not trusted
            if ($candidate <= $now + 60 && $candidate >= $now - 3600) {
                $t = $candidate;
            }
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            $b = $db->createCommand(
                'SELECT id, status, city_id, start_time, pickup_latitude, pickup_longitude, final_pickup_latitude, final_pickup_longitude, '
                . 'live_distance_km, track_points, track_gap_km, track_max_km, track_outside_points, track_rejects, track_last_lat, track_last_lng, track_last_at '
                . 'FROM booking WHERE id = :id FOR UPDATE',
                [':id' => (int) $bookingId]
            )->queryOne();
            if (!$b || (int) $b['status'] !== self::STATUS_START_RIDE) {
                $tx->rollBack();
                $out['reason'] = 'not_started';
                return $out;
            }
            $total = (float) $b['live_distance_km'];
            $out['distance_km'] = $total;
            if ($accuracyM !== null && $accuracyM > self::TRACK_MAX_ACCURACY_M) {
                $tx->rollBack();
                $out['reason'] = 'inaccurate';
                return $out;
            }

            // where the measuring continues from: the last accepted point, or (first point) the pickup at the start of the ride
            $hasLast = $b['track_last_lat'] !== null && $b['track_last_lng'] !== null && $b['track_last_at'] !== null;
            if ($hasLast) {
                $fromLat = (float) $b['track_last_lat'];
                $fromLng = (float) $b['track_last_lng'];
                $fromAt = strtotime($b['track_last_at']);
            } elseif (is_numeric($b['final_pickup_latitude']) && is_numeric($b['final_pickup_longitude']) && !empty($b['start_time'])) {
                $fromLat = (float) $b['final_pickup_latitude'];
                $fromLng = (float) $b['final_pickup_longitude'];
                $fromAt = strtotime($b['start_time']);
            } else {
                $fromLat = null; // no pickup to measure from: this point becomes the first one
                $fromLng = null;
                $fromAt = null;
            }

            $segmentKm = 0.0;
            $isGap = 0;
            $reason = 'ok';
            $rejects = (int) $b['track_rejects'];
            if ($fromLat !== null) {
                if ($t <= $fromAt) {
                    $tx->rollBack();
                    $out['reason'] = 'old_or_repeat';
                    return $out;
                }
                $km = self::kmBetween($fromLat, $fromLng, $lat, $lng);
                $dt = $t - $fromAt;
                $minMoveKm = max(self::TRACK_MIN_MOVE_M, ($accuracyM ?: 0) * 0.5) / 1000;
                if ($km < $minMoveKm) {
                    $tx->rollBack();
                    $out['reason'] = 'drift';
                    return $out;
                }
                // A gap is time passing with no readings. Distance alone never makes a gap: a long step taken in a few seconds is a GPS
                // error (an impossible speed), whether or not readings were missing before it.
                $isGapStep = $dt > self::TRACK_GAP_SECONDS;
                if (($km / $dt) * 3600 > self::TRACK_MAX_SPEED_KMH) {
                    if ($rejects + 1 >= self::TRACK_MAX_REJECTS) {
                        // the phone keeps saying it is somewhere else: believe it, but do not count the jump as driving
                        $segmentKm = 0.0;
                        $reason = 'reanchored';
                    } else {
                        $db->createCommand()->update('booking', ['track_rejects' => $rejects + 1], ['id' => (int) $bookingId])->execute();
                        $tx->commit();
                        $out['reason'] = 'jump';
                        return $out;
                    }
                } elseif ($isGapStep) {
                    $segmentKm = $km * self::TRACK_ROAD_FACTOR;
                    $isGap = 1;
                    $reason = 'gap';
                } else {
                    $segmentKm = $km;
                }
            }

            $total += $segmentKm;
            // how far from the booked pickup this point is (the running maximum of this is kept on the booking)
            $fromPickupKm = is_numeric($b['pickup_latitude']) && is_numeric($b['pickup_longitude'])
                ? self::kmBetween((float) $b['pickup_latitude'], (float) $b['pickup_longitude'], $lat, $lng) : 0.0;
            $db->createCommand()->insert('booking_track', [
                'booking_id' => (int) $bookingId,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy_m' => $accuracyM,
                'speed_kmh' => $speedKmh,
                'segment_km' => round($segmentKm, 3),
                'is_gap' => $isGap,
                'recorded_at' => date('Y-m-d H:i:s', $t),
            ])->execute();
            $db->createCommand()->update('booking', [
                'live_distance_km' => round($total, 3),
                'track_points' => (int) $b['track_points'] + 1,
                'track_gap_km' => round((float) $b['track_gap_km'] + ($isGap ? $segmentKm : 0.0), 3),
                'track_rejects' => 0,
                'track_max_km' => round(max((float) $b['track_max_km'], $fromPickupKm), 3),
                // points of this ride outside the city's map (the ride left the in-city area)
                'track_outside_points' => (int) $b['track_outside_points'] + (self::outsideServiceArea($lat, $lng, self::serviceAreaPolygon($b['city_id'])) ? 1 : 0),
                'track_last_lat' => $lat,
                'track_last_lng' => $lng,
                'track_last_at' => date('Y-m-d H:i:s', $t),
            ], ['id' => (int) $bookingId])->execute();
            $tx->commit();

            return ['accepted' => true, 'reason' => $reason, 'distance_km' => round($total, 3), 'segment_km' => round($segmentKm, 3)];
        } catch (\Throwable $e) {
            if ($tx->getIsActive()) {
                $tx->rollBack();
            }
            Yii::error('track point failed for booking ' . $bookingId . ': ' . $e->getMessage(), 'booking');
            $out['reason'] = 'error';

            return $out;
        }
    }
    // [doc:end] recordTrackPoint

    // [doc:start] trackedDistance - The ride's distance as measured on the server, closed at the final drop point.
    /**
     * For completing a ride: the running total plus the last stretch from the last tracked point to where the driver ended the ride.
     * Returns null when the server has too few points to trust (fewer than 3), and the caller falls back to the route the phone uploaded.
     * Long rides are handled without reading every point: the route is thinned in the database to about 300 points (for the map and the
     * bill), and the farthest-from-pickup figure is the running maximum kept on the booking, so the radius and round-trip checks stay exact.
     * Stretches the phone could not report (gaps) are re-measured with a few road routes, see refineGaps.
     *
     * @return array|null km (2 decimals), points (about 300), tracked_points, gap_km, max_km_from_pickup
     */
    public static function trackedDistance($bookingId, $endLat, $endLng)
    {
        $b = (new \yii\db\Query())->from('booking')->where(['id' => (int) $bookingId])->one();
        if (!$b || (int) $b['track_points'] < 3) {
            return null;
        }
        $refined = self::refineGaps((int) $bookingId);
        $km = (float) $b['live_distance_km'] + $refined;
        $gapKm = (float) $b['track_gap_km'] + $refined;
        $maxKm = (float) $b['track_max_km'];
        $outside = (int) $b['track_outside_points'];
        if (is_numeric($endLat) && is_numeric($endLng) && $b['track_last_lat'] !== null) {
            $last = self::kmBetween((float) $b['track_last_lat'], (float) $b['track_last_lng'], (float) $endLat, (float) $endLng);
            if ($last >= self::TRACK_MIN_MOVE_M / 1000) {
                $isGap = $last > self::TRACK_GAP_KM;
                $km += $isGap ? $last * self::TRACK_ROAD_FACTOR : $last;
                $gapKm += $isGap ? $last * self::TRACK_ROAD_FACTOR : 0.0;
            }
            if (self::outsideServiceArea($endLat, $endLng, self::serviceAreaPolygon($b['city_id']))) {
                $outside++;
            }
            if (is_numeric($b['pickup_latitude']) && is_numeric($b['pickup_longitude'])) {
                $maxKm = max($maxKm, self::kmBetween((float) $b['pickup_latitude'], (float) $b['pickup_longitude'], (float) $endLat, (float) $endLng));
            }
        }
        $count = (int) $b['track_points'];
        $step = max(1, (int) ceil($count / 300));
        $rows = (new \yii\db\Query())->select(['latitude', 'longitude'])->from('booking_track')
            ->where(['booking_id' => (int) $bookingId])
            ->andWhere($step > 1 ? 'MOD(id, ' . $step . ') = 0' : '1 = 1')
            ->orderBy(['id' => SORT_ASC])->limit(400)->all();
        $points = [];
        foreach ($rows as $r) {
            $points[] = ['lat' => (float) $r['latitude'], 'lng' => (float) $r['longitude']];
        }

        return ['km' => round($km, 2), 'points' => $points, 'tracked_points' => $count, 'gap_km' => round($gapKm, 2), 'max_km_from_pickup' => round($maxKm, 3), 'outside_points' => $outside];
    }
    // [doc:end] trackedDistance

    // [doc:start] refineGaps - Re-measures the stretches the phone could not report, with real road routes.
    /**
     * A gap (no readings for minutes: tunnel, no signal, phone off) was added to the total as the straight line x 1.3. On a long drive that
     * is a guess, so at completion the biggest gaps are measured again with the road route between the two points. At most 20 gaps and
     * about 20 seconds are spent; a gap that cannot be routed keeps its estimate. The answer from the road service must look sane (not
     * shorter than 90% of the straight line, not more than 3 times it) or it is ignored.
     *
     * @return float km added to the ride's total (can be negative)
     */
    public static function refineGaps($bookingId)
    {
        $gaps = (new \yii\db\Query())->from('booking_track')
            ->where(['booking_id' => (int) $bookingId, 'is_gap' => 1])->orderBy(['segment_km' => SORT_DESC])->limit(20)->all();
        if (!$gaps) {
            return 0.0;
        }
        $deadline = microtime(true) + 20;
        $delta = 0.0;
        $router = new \app\modules\geo\services\DrivingDistance();
        foreach ($gaps as $g) {
            if (microtime(true) > $deadline) {
                break;
            }
            $prev = (new \yii\db\Query())->select(['latitude', 'longitude'])->from('booking_track')
                ->where(['booking_id' => (int) $bookingId])->andWhere(['<', 'id', (int) $g['id']])->orderBy(['id' => SORT_DESC])->one();
            if (!$prev) {
                continue;
            }
            $straight = self::kmBetween((float) $prev['latitude'], (float) $prev['longitude'], (float) $g['latitude'], (float) $g['longitude']);
            try {
                $r = $router->getDrivingDistanceGoogleMap($prev['latitude'], $prev['longitude'], $g['latitude'], $g['longitude']);
                $road = isset($r['meters']) ? ((float) $r['meters']) / 1000 : 0.0;
            } catch (\Throwable $e) {
                continue;
            }
            if ($straight <= 0 || $road < $straight * 0.9 || $road > $straight * 3) {
                continue;
            }
            $delta += $road - (float) $g['segment_km'];
            Yii::$app->db->createCommand()->update('booking_track', ['segment_km' => round($road, 3), 'is_gap' => 2], ['id' => (int) $g['id']])->execute();
        }
        if (abs($delta) > 0.0005) {
            Yii::$app->db->createCommand('UPDATE booking SET live_distance_km = live_distance_km + :d, track_gap_km = track_gap_km + :d WHERE id = :id',
                [':d' => round($delta, 3), ':id' => (int) $bookingId])->execute();
        }

        return $delta;
    }
    // [doc:end] refineGaps

    // [doc:start] routeDistanceKm - Length of a route the phone uploaded, measured along its points (no road lookups).
    /**
     * The fallback when the server has no tracked points: the sum of the straight steps between the uploaded points. A route recorded
     * with big spaces between points (the old Partner app kept one point per 2 km) cuts corners, so such a route is lengthened by 15%.
     *
     * @param array $points list of points with lat and lng
     */
    public static function routeDistanceKm(array $points)
    {
        $km = 0.0;
        $steps = 0;
        $prev = null;
        foreach ($points as $p) {
            if (!is_array($p) || !isset($p['lat'], $p['lng']) || !is_numeric($p['lat']) || !is_numeric($p['lng'])) {
                continue;
            }
            if ($prev !== null) {
                $km += self::kmBetween($prev[0], $prev[1], $p['lat'], $p['lng']);
                $steps++;
            }
            $prev = [(float) $p['lat'], (float) $p['lng']];
        }

        return ($steps > 0 && $km / $steps > 1.0) ? $km * 1.15 : $km;
    }
    // [doc:end] routeDistanceKm

    // [doc:start] liveTripSummary - What the customer's screen shows while the driver is on the way or the ride is running.
    /**
     * @return array live_distance_km and ride_elapsed_min (ride running), and driver_latitude/driver_longitude/driver_position_at
     *               (driver on the way, arrived or ride running); empty for any other state. Only for the booking's own screens.
     */
    public function liveTripSummary()
    {
        $live = [Booking::STATUS_GO_FOR_PICKUP, Booking::STATUS_ARRIVED_LOCATION, Booking::STATUS_START_RIDE];
        if (empty($this->id) || empty($this->driver_id) || !in_array((int) $this->status, $live, true)) {
            return [];
        }
        $out = [];
        $pos = (new \yii\db\Query())->select(['lat', 'lng', 'updated_at'])->from('user')->where(['id' => (int) $this->driver_id])->one();
        if ($pos && is_numeric($pos['lat']) && is_numeric($pos['lng']) && (float) $pos['lat'] != 0.0) {
            $out['driver_latitude'] = (float) $pos['lat'];
            $out['driver_longitude'] = (float) $pos['lng'];
            $out['driver_position_at'] = !empty($pos['updated_at']) ? date('Y-m-d H:i:s', (int) $pos['updated_at']) : null;
        }
        if ((int) $this->status === self::STATUS_START_RIDE) {
            $out['live_distance_km'] = round((float) $this->live_distance_km, 2);
            $out['ride_elapsed_min'] = !empty($this->start_time) ? max(0, (int) floor((time() - strtotime($this->start_time)) / 60)) : 0;
        }

        return $out;
    }
    // [doc:end] liveTripSummary

    // [doc:start] serviceAreaPolygon - The drawn in-city area (service map) of a city, or null when it has none.
    /**
     * The "map" of a city: the service map of the driver service (the city's own boundary when no service map was saved). In-city means
     * inside it. Cached for the request, because the tracker asks for it with every reported position.
     *
     * @return array|null list of [lat, lng]
     */
    public static function serviceAreaPolygon($cityId)
    {
        static $cache = [];
        $key = (int) $cityId;
        if (!array_key_exists($key, $cache)) {
            $cache[$key] = $key > 0
                ? \app\modules\geo\services\ServiceModules::boundary(\app\modules\geo\services\ServiceModules::DRIVER, $key) : null;
        }

        return $cache[$key];
    }
    // [doc:end] serviceAreaPolygon

    // [doc:start] incityBufferKm - How far outside the map a point may be and still count as in-city.
    /**
     * Admin dashboard: Integrations > Customer settings > "In-city boundary buffer (km)" (setting "incity_boundary_buffer_km"). 0 when not
     * set: the map is exact. A few km can be allowed so a drop on the edge of the map is not sent to outstation fares.
     */
    public static function incityBufferKm()
    {
        $value = (new \app\modules\admin\models\WebSetting())->getSettingBykey('incity_boundary_buffer_km');

        return is_numeric($value) && (float) $value >= 0 ? (float) $value : 0.0;
    }
    // [doc:end] incityBufferKm

    // [doc:start] outsideServiceArea - Is a point outside the city's map (beyond the buffer)?
    /**
     * False when the city has no usable map (nothing can be said, so nobody is sent to outstation for it).
     */
    public static function outsideServiceArea($lat, $lng, $polygon)
    {
        if (empty($polygon) || !is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }
        if (\app\modules\geo\services\ServiceModules::inside((float) $lat, (float) $lng, $polygon)) {
            return false;
        }
        $buffer = self::incityBufferKm();

        return !($buffer > 0 && self::kmToPolygonEdge((float) $lat, (float) $lng, $polygon) <= $buffer);
    }
    // [doc:end] outsideServiceArea

    // [doc:start] kmToPolygonEdge - Shortest distance, in km, from a point to the edge of a map outline.
    /**
     * @param array $polygon list of [lat, lng] points
     */
    public static function kmToPolygonEdge($lat, $lng, array $polygon)
    {
        $kmPerLat = 110.57;
        $kmPerLng = 111.32 * cos(deg2rad($lat));
        $best = INF;
        $n = count($polygon);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $ax = ($polygon[$j][1] - $lng) * $kmPerLng;
            $ay = ($polygon[$j][0] - $lat) * $kmPerLat;
            $bx = ($polygon[$i][1] - $lng) * $kmPerLng;
            $by = ($polygon[$i][0] - $lat) * $kmPerLat;
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $len2 = $dx * $dx + $dy * $dy;
            $t = $len2 > 0 ? max(0, min(1, -($ax * $dx + $ay * $dy) / $len2)) : 0;
            $best = min($best, sqrt(pow($ax + $t * $dx, 2) + pow($ay + $t * $dy, 2)));
        }

        return $best;
    }
    // [doc:end] kmToPolygonEdge

    // [doc:start] returnToleranceKm - How close to the pickup a ride must end to count as "came back".
    /**
     * Admin dashboard: Integrations > Customer settings > "Return-to-pickup tolerance (km)" (setting "ride_return_tolerance_km"); 1 km when not set.
     */
    public static function returnToleranceKm()
    {
        $value = (new \app\modules\admin\models\WebSetting())->getSettingBykey('ride_return_tolerance_km');

        return is_numeric($value) && (float) $value > 0 ? (float) $value : 1.0;
    }
    // [doc:end] returnToleranceKm

    // [doc:start] actualRideType - Was the ride that was driven a one-way or a round trip, whatever was booked?
    /**
     * A customer can book one way and end up with a round trip (the driver goes out and comes back to the pickup point), or book
     * a round trip and end up one way (the driver never comes back). The route settles it:
     *  - booked one way, but the ride ENDED within the tolerance of the pickup after going out at least max(2 x tolerance, 2) km: round trip;
     *  - booked round trip, but the ride ENDED farther than max(2 x tolerance, 2) km from the pickup: one way;
     *  - anything in between keeps the booked type (a stop a little way from the pickup is not enough to change the bill).
     *
     * @param int   $booked  RIDER_TYPE_ONEWAY or RIDER_TYPE_ROUNDTRIP
     * @param float $maxKm   the farthest the ride got from the pickup
     * @param float $endKm   how far from the pickup it ended
     * @return int the ride type to bill
     */
    public static function actualRideType($booked, $maxKm, $endKm)
    {
        $tolerance = self::returnToleranceKm();
        $far = max(2 * $tolerance, 2.0);
        if ((int) $booked === self::RIDER_TYPE_ONEWAY && $endKm <= $tolerance && $maxKm >= $far) {
            return self::RIDER_TYPE_ROUNDTRIP;
        }
        if ((int) $booked === self::RIDER_TYPE_ROUNDTRIP && $endKm > $far) {
            return self::RIDER_TYPE_ONEWAY;
        }

        return (int) $booked;
    }
    // [doc:end] actualRideType

    // [doc:start] incityRadiusKm - The in-city limit around the pickup point, set by the admin.
    /**
     * Admin dashboard: Integrations > Customer settings > "In-city trip radius (km)" (setting "incity_trip_radius_km").
     * 0 means no radius limit is set.
     */
    public static function incityRadiusKm()
    {
        $value = (new \app\modules\admin\models\WebSetting())->getSettingBykey('incity_trip_radius_km');

        return is_numeric($value) && (float) $value > 0 ? (float) $value : 0.0;
    }
    // [doc:end] incityRadiusKm

    // [doc:start] kmBetween - Straight-line distance in km between two points.
    public static function kmBetween($lat1, $lng1, $lat2, $lng2)
    {
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLng = deg2rad((float) $lng2 - (float) $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLng / 2) ** 2;

        return 12742 * asin(min(1, sqrt($h)));
    }
    // [doc:end] kmBetween



}
