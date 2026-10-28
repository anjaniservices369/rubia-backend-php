<?php

namespace app\modules\admin\controllers;

use app\models\User;
use app\modules\geo\services\MapDesigner;
use app\modules\geo\services\PincodeAreas;
use app\modules\geo\services\ServiceModules;
use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Dashboard of ONE service module: its maps, its operations and its billing.
 *
 * The platform sells four modules - driver service (1), taxi (2), auto (3), bike (4) - and each one is run on its
 * own: its own maps of the cities it serves (pincodes, service area), its own pricing, its own franchises and its
 * own billing. Nothing done in one module changes another (see ServiceModules and MapDesigner).
 *
 *   /admin/service-map/index?module=4           the module's dashboard: today's numbers and its maps
 *   /admin/service-map/map?module=4&city=8      one map: pincodes, outline, pricing, franchise
 *   /admin/service-map/act?module=4&city=8      POST: change that map (add / move / remove pincode, copy pricing, ...)
 *   /admin/service-map/billing?module=4         the module's billing (own totals, per city, per day), CSV export
 *
 * Admins and sub-admins can do everything. A staff or franchise account that works in ONE module (user.vehicle_id)
 * can look at that module's dashboard, maps and billing only.
 */
class ServiceMapController extends Controller
{
    // [doc:start] behaviors - Behaviors
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['act' => ['post'], 'map' => ['get', 'post'], 'billing-profile' => ['get', 'post']],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['overview', 'index', 'map', 'act', 'billing', 'billing-csv', 'invoices', 'invoices-csv', 'invoice', 'tax-report', 'tax-report-csv', 'billing-profile'],
                        'matchCallback' => function () {
                            return User::isAdmin() || User::isSubAdmin();
                        },
                    ],
                    [
                        'allow' => true,
                        'actions' => ['index', 'map', 'billing', 'billing-csv', 'invoices', 'invoices-csv', 'invoice', 'tax-report', 'tax-report-csv'],
                        'matchCallback' => function () {
                            $u = Yii::$app->user->identity;
                            $module = (int) Yii::$app->request->get('module');

                            return $u && in_array($u->user_role, [User::ROLE_MANAGER, User::ROLE_FRANCHISE], true)
                                && $module > 0 && (int) $u->vehicle_id === $module;
                        },
                    ],
                ],
            ],
        ];
    }
    // [doc:end] behaviors

    // [doc:start] actionOverview - /admin/service-map/overview - Super admin: every service side by side. A service that runs on its own database is asked through its signed.
    /**
     * Super admin: every service side by side. A service that runs on its own database is asked through its signed
     * reporting endpoint (ServiceClient); one still on this database is read from it.
     */
    public function actionOverview($from = null, $to = null)
    {
        $rows = [];
        foreach (ServiceModules::all() as $id => $m) {
            $rows[$id] = \app\modules\adminapi\services\ServiceClient::summary($id, $from, $to) + [
                'dashboard' => \app\modules\adminapi\services\ServiceClient::dashboardUrl($id),
                'partnerApp' => \app\modules\adminapi\services\ServiceClient::hasPartnerApp($id),
            ];
            $rows[$id]['title'] = $rows[$id]['title'] ?? $m['title'];
            $rows[$id]['module'] = $id;
        }

        return $this->render('overview', [
            'rows' => $rows,
            'customer' => \app\modules\adminapi\services\ServiceClient::customer($from, $to) + ['dashboard' => getenv('ADMIN_DASHBOARD_URL_CUSTOMER') ?: null],
            'from' => $from ?: date('Y-m-01'),
            'to' => $to ?: date('Y-m-d'),
        ]);
    }
    // [doc:end] actionOverview

    // [doc:start] actionIndex - /admin/service-map/index - The dashboard of one module: today's numbers and the list of its maps.
    /**
     * The dashboard of one module: today's numbers and the list of its maps.
     */
    public function actionIndex($module)
    {
        $module = $this->module($module);

        return $this->render('index', [
            'module' => $module,
            'title' => ServiceModules::title($module),
            'today' => $this->numbers($module, date('Y-m-d'), date('Y-m-d')),
            'month' => $this->numbers($module, date('Y-m-01'), date('Y-m-d')),
            'maps' => $this->maps($module),
            'cities' => (new Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy('name')->all(),
            'canEdit' => User::isAdmin() || User::isSubAdmin(),
        ]);
    }
    // [doc:end] actionIndex

    // [doc:start] actionMap - /admin/service-map/map - One map of one module: pincodes, outline, pricing and franchise. POST refresh=1 / save=1 redraw or save the.
    /**
     * One map of one module: pincodes, outline, pricing and franchise. POST refresh=1 / save=1 redraw or save the
     * outline.
     */
    public function actionMap($module, $city)
    {
        $module = $this->module($module);
        $cityRow = (new Query())->from('city')->where(['id' => (int) $city])->one();
        if (!$cityRow) {
            throw new NotFoundHttpException('City not found.');
        }
        $request = Yii::$app->request;
        $canEdit = User::isAdmin() || User::isSubAdmin();
        $areas = PincodeAreas::forCity((int) $city, $request->isPost && $request->post('refresh'), $module);

        if ($request->isPost) {
            if (!$canEdit) {
                throw new ForbiddenHttpException('Only an administrator can change a map.');
            }
            if ($request->post('remove-pincode-map')) {
                Yii::$app->db->createCommand()->delete('pincode_map', ['module' => $module, 'city_id' => (int) $city])->execute();
                Yii::$app->session->setFlash('success', 'The pincode map of ' . $cityRow['name'] . ' was removed. The ' . ServiceModules::title($module)
                    . ' service now uses the hand-drawn map again (if there is one).');
            } elseif ($request->post('save')) {
                // saved as a pincode map, apart from the hand-drawn map (which is not changed)
                if (PincodeAreas::savePincodeMap($module, (int) $city, $areas['hull'], array_column($areas['pincodes'], 'pincode'))) {
                    Yii::$app->session->setFlash('success', 'The ' . ServiceModules::title($module) . ' service area of ' . $cityRow['name']
                        . ' now follows its pincodes (' . count($areas['hull']) . ' corner points). The hand-drawn map was not changed.');
                } else {
                    Yii::$app->session->setFlash('error', 'Nothing was saved: none of the pincodes could be placed on the map.');
                }
            }

            return $this->redirect(['map', 'module' => $module, 'city' => (int) $city]);
        }

        return $this->render('map', [
            'module' => $module,
            'title' => ServiceModules::title($module),
            'city' => $cityRow,
            'areas' => $areas,
            'map' => MapDesigner::snapshot($module, (int) $city),
            'current' => ServiceModules::boundary($module, (int) $city),
            'hasPincodeMap' => (new Query())->from('pincode_map')->where(['module' => $module, 'city_id' => (int) $city])->exists(),
            'canEdit' => $canEdit,
        ]);
    }
    // [doc:end] actionMap

    // [doc:start] actionAct - /admin/service-map/act - POST: a change to one map. op = pincode-add | pincode-remove | pincode-move | pricing-copy |.
    /**
     * POST: a change to one map. op = pincode-add | pincode-remove | pincode-move | pricing-copy |
     * franchise-assign | franchise-remove.
     */
    public function actionAct($module, $city)
    {
        $module = $this->module($module);
        $city = (int) $city;
        $post = Yii::$app->request->post();
        $admin = Yii::$app->user->id;
        switch ($post['op'] ?? '') {
            case 'pincode-add':
                $r = MapDesigner::addPincodes($module, $city, $post['pincodes'] ?? '', (array) ($post['vehicles'] ?? []), $admin);
                break;
            case 'pincode-remove':
                $r = MapDesigner::removePincode($module, $city, $post['pincode'] ?? '', $admin);
                break;
            case 'pincode-move':
                $r = MapDesigner::movePincode($module, $city, $post['pincode'] ?? '', $post['to_city'] ?? 0, $admin);
                break;
            case 'pricing-copy':
                $r = MapDesigner::copyPricing($module, $city, $post['from_city'] ?? 0, $admin);
                break;
            case 'franchise-assign':
                $r = MapDesigner::assignFranchise($module, $city, $post['user_id'] ?? 0, $admin);
                break;
            case 'franchise-remove':
                $r = MapDesigner::removeFranchise($module, $city, $post['user_id'] ?? 0, $admin);
                break;
            default:
                $r = ['ok' => false, 'message' => 'Unknown action.'];
        }
        Yii::$app->session->setFlash($r['ok'] ? 'success' : 'error', $r['message']);

        return $this->redirect(['map', 'module' => $module, 'city' => $city]);
    }
    // [doc:end] actionAct

    // [doc:start] actionBilling - /admin/service-map/billing - The billing of one module, on its own: totals, per city and per day for a date range.
    /**
     * The billing of one module, on its own: totals, per city and per day for a date range.
     */
    public function actionBilling($module, $from = null, $to = null, $city = null)
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);

        return $this->render('billing', [
            'module' => $module,
            'title' => ServiceModules::title($module),
            'from' => $from,
            'to' => $to,
            'cityId' => $city ? (int) $city : null,
            'totals' => $this->billing($module, $from, $to, $city, null),
            'byCity' => $this->billing($module, $from, $to, $city, 'city'),
            'byDay' => $this->billing($module, $from, $to, $city, 'day'),
            'payouts' => $this->payouts($module, $from, $to),
            'wallets' => $this->wallets($module),
            'cities' => (new Query())->select(['id', 'name'])->from('city')->where(['status' => 1])->orderBy('name')->all(),
        ]);
    }
    // [doc:end] actionBilling

    // [doc:start] actionBillingCsv - /admin/service-map/billing-csv - Billing Csv
    public function actionBillingCsv($module, $from = null, $to = null, $city = null)
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);
        $rows = $this->billing($module, $from, $to, $city, 'day');
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Module', 'Date', 'Rides', 'Gross (INR)', 'Company share (INR)', 'Driver share (INR)']);
        foreach ($rows as $r) {
            fputcsv($out, [ServiceModules::title($module), $r['label'], $r['rides'], $r['gross'], $r['company'], $r['driver']]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Yii::$app->response->sendContentAsFile($csv, 'billing-' . ServiceModules::all()[$module]['key'] . '-' . $from . '-' . $to . '.csv',
            ['mimeType' => 'text/csv']);
    }
    // [doc:end] actionBillingCsv


    // [doc:start] actionBillingProfile - /admin/service-map/billing-profile - The service's own tax identity and invoice series: legal name, GSTIN, PAN, address, state code, invoice prefix.
    /**
     * The service's own tax identity and invoice series: legal name, GSTIN, PAN, address, state code, invoice prefix.
     * Each service is billed under its own; admins and sub-admins only.
     */
    public function actionBillingProfile($module)
    {
        $module = $this->module($module);
        if (!(User::isAdmin() || User::isSubAdmin())) {
            throw new ForbiddenHttpException('Only an administrator can change the billing profile.');
        }
        $request = Yii::$app->request;
        if ($request->isPost) {
            $p = $request->post();
            $gstin = strtoupper(trim((string) ($p['gstin'] ?? '')));
            if ($gstin !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin)) {
                Yii::$app->session->setFlash('error', 'That GSTIN does not look right (15 characters, e.g. 36ABCDE1234F1Z5).');

                return $this->redirect(['billing-profile', 'module' => $module]);
            }
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($p['invoice_prefix'] ?? '')));
            if ($prefix === '' || strlen($prefix) > 8) {
                Yii::$app->session->setFlash('error', 'The invoice prefix must be 1 to 8 letters or digits.');

                return $this->redirect(['billing-profile', 'module' => $module]);
            }
            Yii::$app->db->createCommand()->update('service_billing_profile', [
                'legal_name' => mb_substr(trim((string) ($p['legal_name'] ?? '')), 0, 160),
                'gstin' => $gstin,
                'pan' => strtoupper(mb_substr(trim((string) ($p['pan'] ?? '')), 0, 12)),
                'address' => mb_substr(trim((string) ($p['address'] ?? '')), 0, 300),
                'state_code' => mb_substr(trim((string) ($p['state_code'] ?? '')), 0, 4),
                'invoice_prefix' => $prefix,
                'sac_code' => mb_substr(trim((string) ($p['sac_code'] ?? '9964')), 0, 10),
                'updated_on' => date('Y-m-d H:i:s'),
            ], ['module' => $module])->execute();
            Yii::$app->session->setFlash('success', 'Billing profile of ' . ServiceModules::title($module) . ' saved. It applies to invoices issued from now on.');

            return $this->redirect(['billing-profile', 'module' => $module]);
        }

        return $this->render('billing-profile', [
            'module' => $module, 'title' => ServiceModules::title($module),
            'profile' => \app\modules\billing\services\InvoiceService::profile($module),
        ]);
    }
    // [doc:end] actionBillingProfile

    // [doc:start] actionInvoices - /admin/service-map/invoices - Invoices of the service for a period, newest first.
    /** Invoices of the service for a period, newest first. */
    public function actionInvoices($module, $from = null, $to = null, $q = '')
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);
        $query = (new Query())->from('service_invoice')->where(['module' => $module])
            ->andWhere(['between', 'issued_on', $from . ' 00:00:00', $to . ' 23:59:59']);
        if ($q !== '') {
            $query->andWhere(['like', 'number', $q]);
        }
        $count = (int) (clone $query)->count();
        $rows = $query->orderBy(['id' => SORT_DESC])->limit(200)->all();

        return $this->render('invoices', ['module' => $module, 'title' => ServiceModules::title($module),
            'from' => $from, 'to' => $to, 'q' => $q, 'rows' => $rows, 'count' => $count]);
    }
    // [doc:end] actionInvoices

    // [doc:start] actionInvoicesCsv - /admin/service-map/invoices-csv - Invoices Csv
    public function actionInvoicesCsv($module, $from = null, $to = null)
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);
        $rows = (new Query())->from('service_invoice')->where(['module' => $module])
            ->andWhere(['between', 'issued_on', $from . ' 00:00:00', $to . ' 23:59:59'])->orderBy(['id' => SORT_ASC])->all();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Invoice no', 'Date', 'Source', 'Source id', 'City id', 'Customer id', 'Partner id', 'Taxable', 'CGST', 'SGST', 'IGST', 'Total', 'Company share', 'Status', 'Supplier GSTIN']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['number'], $r['issued_on'], $r['source_type'], $r['source_id'], $r['city_id'], $r['customer_id'], $r['partner_id'],
                $r['taxable'], $r['cgst'], $r['sgst'], $r['igst'], $r['total'], $r['commission'], $r['status'], $r['supplier_gstin']]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Yii::$app->response->sendContentAsFile($csv, 'invoices-' . ServiceModules::all()[$module]['key'] . '-' . $from . '-' . $to . '.csv', ['mimeType' => 'text/csv']);
    }
    // [doc:end] actionInvoicesCsv

    // [doc:start] actionInvoice - /admin/service-map/invoice - One printable tax invoice.
    /** One printable tax invoice. */
    public function actionInvoice($module, $id)
    {
        $module = $this->module($module);
        $inv = (new Query())->from('service_invoice')->where(['module' => $module, 'id' => (int) $id])->one();
        if (!$inv) {
            throw new NotFoundHttpException('Invoice not found.');
        }
        // the customer is the user-app system's: asked for, not read from a local table
        $customer = $inv['customer_id'] ? \app\modules\customerapi\services\CustomerClient::profile($inv['customer_id']) : null;
        $city = $inv['city_id'] ? (string) (new Query())->select('name')->from('city')->where(['id' => $inv['city_id']])->scalar() : '';
        $this->layout = false;

        return $this->render('invoice', ['module' => $module, 'title' => ServiceModules::title($module), 'inv' => $inv,
            'profile' => \app\modules\billing\services\InvoiceService::profile($module), 'customer' => $customer, 'city' => $city]);
    }
    // [doc:end] actionInvoice

    // [doc:start] actionTaxReport - /admin/service-map/tax-report - Tax report of the service: per month, taxable value, CGST, SGST, IGST, total, company share.
    /** Tax report of the service: per month, taxable value, CGST, SGST, IGST, total, company share. */
    public function actionTaxReport($module, $from = null, $to = null)
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);

        return $this->render('tax-report', ['module' => $module, 'title' => ServiceModules::title($module),
            'from' => $from, 'to' => $to, 'rows' => $this->taxRows($module, $from, $to),
            'profile' => \app\modules\billing\services\InvoiceService::profile($module)]);
    }
    // [doc:end] actionTaxReport

    // [doc:start] actionTaxReportCsv - /admin/service-map/tax-report-csv - Tax Report Csv
    public function actionTaxReportCsv($module, $from = null, $to = null)
    {
        $module = $this->module($module);
        list($from, $to) = $this->range($from, $to);
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Service', 'GSTIN', 'Month', 'Invoices', 'Taxable value', 'CGST', 'SGST', 'IGST', 'Total', 'Company share']);
        $gstin = \app\modules\billing\services\InvoiceService::profile($module)['gstin'];
        foreach ($this->taxRows($module, $from, $to) as $r) {
            fputcsv($out, [ServiceModules::title($module), $gstin, $r['month'], $r['invoices'], $r['taxable'], $r['cgst'], $r['sgst'], $r['igst'], $r['total'], $r['commission']]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return Yii::$app->response->sendContentAsFile($csv, 'tax-report-' . ServiceModules::all()[$module]['key'] . '-' . $from . '-' . $to . '.csv', ['mimeType' => 'text/csv']);
    }
    // [doc:end] actionTaxReportCsv

    private function taxRows($module, $from, $to)
    {
        return (new Query())->select([
                "DATE_FORMAT(issued_on, '%Y-%m') AS month", 'COUNT(*) AS invoices', 'SUM(taxable) AS taxable', 'SUM(cgst) AS cgst',
                'SUM(sgst) AS sgst', 'SUM(igst) AS igst', 'SUM(total) AS total', 'SUM(commission) AS commission'])
            ->from('service_invoice')->where(['module' => $module, 'status' => 'issued'])
            ->andWhere(['between', 'issued_on', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy([new \yii\db\Expression("DATE_FORMAT(issued_on, '%Y-%m')")])->orderBy('month ASC')->all();
    }

    // ------------------------------------------------------------------ data

    private function module($id)
    {
        $id = (int) $id;
        if (!ServiceModules::exists($id)) {
            throw new NotFoundHttpException('Unknown service module.');
        }
        // a service's own dashboard (params['serviceDashboard']) shows that service only
        $bound = (int) (Yii::$app->params['serviceDashboard'] ?? 0);
        if ($bound && $bound !== $id) {
            throw new NotFoundHttpException('This dashboard belongs to ' . ServiceModules::title($bound) . '.');
        }

        return $id;
    }

    private function range($from, $to)
    {
        $ok = function ($d) {
            return $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;
        };

        return [$ok($from) ? $from : date('Y-m-01'), $ok($to) ? $to : date('Y-m-d')];
    }

    /** Today's / this month's numbers of a module. */
    private function numbers($module, $from, $to)
    {
        $end = $to . ' 23:59:59';
        if (ServiceModules::isTaxi($module)) {
            $vehicles = array_keys(ServiceModules::vehicles($module));
            $rides = (new Query())->from('ride_request')->where(['vehical_type' => $vehicles])->andWhere(['between', 'created_on', $from . ' 00:00:00', $end]);
            $waiting = (new Query())->from('ride_request')->where(['vehical_type' => $vehicles, 'status' => 1])->count();
            $online = (new Query())->from('user')->innerJoin('driver_details dd', 'dd.user_id = user.id')
                ->where(['user.user_role' => 'taxi', 'user.status' => 10, 'user.online_status' => 1])
                ->andWhere(new \yii\db\Expression('IFNULL((SELECT v.vehicle_type FROM vehicals v WHERE v.id = dd.vehical_id), dd.vehical_id) = ' . (int) $module))->count();
            $done = (clone $rides)->andWhere(['status' => [9, 10]])->count();
        } else {
            $rides = (new Query())->from('booking')->andWhere(['between', 'created_on', $from . ' 00:00:00', $end]);
            $waiting = (new Query())->from('booking')->where(['status' => 1])->count();
            $online = (new Query())->from('user')->where(['user_role' => 'Driver', 'status' => 10, 'online_status' => 1])->count();
            $done = (clone $rides)->andWhere(['status' => 7])->count();
        }
        $b = $this->billing($module, $from, $to, null, null);

        return [
            'rides' => (int) $rides->count(),
            'completed' => (int) $done,
            'waiting' => (int) $waiting,
            'online' => (int) $online,
            'gross' => $b['gross'], 'company' => $b['company'], 'driver' => $b['driver'],
        ];
    }

    /** Cities that have a map in this module, with what each has. */
    private function maps($module)
    {
        $taxi = ServiceModules::isTaxi($module);
        $ids = [];
        if ($taxi) {
            $ids = array_merge($ids, (new Query())->select('city_id')->distinct()->from('pincode')
                ->where(['status' => 1, 'vehical_id' => array_keys(ServiceModules::vehicles($module))])->column());
            $ids = array_merge($ids, (new Query())->select('city_id')->distinct()->from('ride_commision')->where(['vehicle_id' => $module, 'status' => 1])->column());
        } else {
            $ids = array_merge($ids, (new Query())->select('city_id')->distinct()->from('service_map_pincode')->where(['module' => $module, 'status' => 1])->column());
            $ids = array_merge($ids, (new Query())->select('city_id')->distinct()->from('city_rate_card')->where(['status' => 1])->column());
        }
        $ids = array_merge($ids, (new Query())->select('city_id')->from('service_map')->where(['module' => $module])->andWhere(['is not', 'boundary', null])->column());
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }
        $out = [];
        $cities = (new Query())->select(['id', 'name'])->from('city')->where(['id' => $ids])->orderBy('name')->all();
        foreach ($cities as $c) {
            $names = MapDesigner::pincodeNames($module, $c['id']);
            $pricing = $taxi
                ? (new Query())->from('ride_commision')->where(['city_id' => $c['id'], 'vehicle_id' => $module, 'status' => 1])->count()
                : (new Query())->from('city_rate_card')->where(['city_id' => $c['id'], 'status' => 1])->count();
            $out[] = [
                'id' => (int) $c['id'], 'name' => $c['name'], 'pincodes' => count($names), 'pricing' => (int) $pricing,
                'area' => (bool) ServiceModules::boundary($module, $c['id']),
                'franchises' => (int) User::find()->where(['user_role' => User::ROLE_FRANCHISE, 'city_id' => $c['id'], 'vehicle_id' => $module])->count(),
            ];
        }

        return $out;
    }

    /**
     * Completed money of a module: grouped by nothing, 'city' or 'day'.
     */
    private function billing($module, $from, $to, $city, $group)
    {
        $taxi = ServiceModules::isTaxi($module);
        $q = new Query();
        if ($taxi) {
            $q->from(['e' => 'ride_earnings'])->innerJoin(['r' => 'ride_request'], 'r.id = e.ride_id')
                ->where(['e.module' => $module])->andWhere(['between', 'e.created_on', $from . ' 00:00:00', $to . ' 23:59:59']);
            $cityCol = 'r.city_id';
            $dateCol = 'e.created_on';
            $gross = 'e.total_amount';
            $company = 'e.admin_earning';
            $driver = 'e.driver_earning';
        } else {
            $q->from(['e' => 'driver_earnings'])->innerJoin(['r' => 'booking'], 'r.id = e.booking_id')
                ->where(['e.module' => $module])->andWhere(['between', 'e.created_on', $from . ' 00:00:00', $to . ' 23:59:59']);
            $cityCol = 'r.city_id';
            $dateCol = 'e.created_on';
            $gross = 'e.total_amount';
            $company = 'e.admin_earning';
            $driver = 'e.driver_earning';
        }
        if ($city) {
            $q->andWhere([$cityCol => (int) $city]);
        }
        $cols = ["COUNT(*) AS rides", "COALESCE(SUM($gross),0) AS gross", "COALESCE(SUM($company),0) AS company", "COALESCE(SUM($driver),0) AS driver"];
        if ($group === null) {
            $row = $q->select($cols)->one();

            return ['rides' => (int) $row['rides'], 'gross' => round($row['gross'], 2), 'company' => round($row['company'], 2), 'driver' => round($row['driver'], 2)];
        }
        if ($group === 'city') {
            $rows = $q->select(array_merge(["$cityCol AS key_id"], $cols))->groupBy($cityCol)->orderBy(['gross' => SORT_DESC])->all();
            $names = (new Query())->select('name')->indexBy('id')->from('city')->column();
            foreach ($rows as &$r) {
                $r['label'] = $names[$r['key_id']] ?? ($r['key_id'] ? '#' . $r['key_id'] : '(no city)');
            }
            unset($r);

            return $rows;
        }
        $rows = $q->select(array_merge(["DATE($dateCol) AS label"], $cols))->groupBy("DATE($dateCol)")->orderBy(['label' => SORT_DESC])->all();

        return $rows;
    }

    /** Driver wallets of this service only: what drivers hold in total, and how many wallets are below the minimum. */
    private function wallets($module)
    {
        $q = (new Query())->from('driver_wallet')->where(['module' => $module]);
        $credit = (float) (clone $q)->andWhere(['payment_type' => 1, 'status' => 1])->sum('amount');
        $debit = (float) (clone $q)->andWhere(['payment_type' => 2, 'status' => [0, 1]])->sum('amount');
        $drivers = (int) (clone $q)->select('driver_id')->distinct()->count('driver_id');

        return ['credit' => round($credit, 2), 'debit' => round($debit, 2), 'net' => round($credit - $debit, 2), 'drivers' => $drivers];
    }

    /** Payout / withdraw entries of the module's drivers in the period (taxi modules; the driver service pays through its wallet). */
    private function payouts($module, $from, $to)
    {
        if (!ServiceModules::isTaxi($module)) {
            return null;
        }
        $row = (new Query())->from('taxi_payout')->where(['module' => $module])
            ->andWhere(['between', 'created_on', $from . ' 00:00:00', $to . ' 23:59:59'])
            ->select(['COUNT(*) AS entries', 'COALESCE(SUM(amount),0) AS amount'])->one();

        return ['entries' => (int) $row['entries'], 'amount' => round($row['amount'], 2)];
    }
}
