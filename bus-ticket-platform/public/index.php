<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$controllers = [
    'HomeController',
    'AuthController',
    'AdminController',
    'TripController',
    'TicketController',
    'CompanyController',
    'AccountController',
];

foreach ($controllers as $ctrl) {
    $path = BASE_PATH . "/app/controllers/{$ctrl}.php";
    if (is_file($path)) require_once $path;
}


$require_post = static function (): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo 'Method Not Allowed';
        exit;
    }
};

$not_found = static function (string $msg = '404 - Sayfa bulunamadı'): void {
    http_response_code(404);
    echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
    exit;
};

$route = trim(strtolower($_GET['r'] ?? 'home/index'));
$route = preg_replace('/[^a-z0-9_\/\-]/', '', $route);


switch ($route) {

    case 'home/index':
        (new HomeController($pdo))->index();
        break;

    case 'trip/search':
        (new TripController($pdo))->search();
        break;

    case 'trip/show':
        (new TripController($pdo))->show();
        break;

    case 'trip/public':
        (new TripController($pdo))->publicIndex();
        break;

    case 'trip/public-detail':
        (new TripController($pdo))->publicDetail();
        break;

    case 'auth/login': {
        $c = new AuthController($pdo);
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $c->loginPost() : $c->login();
        break;
    }

    case 'auth/register': {
        $c = new AuthController($pdo);
        ($_SERVER['REQUEST_METHOD'] === 'POST') ? $c->registerPost() : $c->register();
        break;
    }

    case 'auth/logout':
        (new AuthController($pdo))->logout();
        break;

    case 'admin/panel':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->panel();
        break;

    case 'admin/account':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->account();
        break;

    case 'admin/account-update':
        require_role(ROLE_ADMIN);
        $require_post();
        (new AdminController($pdo))->accountUpdatePost();
        break;

    case 'admin/companies':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->companies();
        break;

    case 'admin/company-new':
        require_role(ROLE_ADMIN);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new AdminController($pdo))->companyCreatePost()
            : (new AdminController($pdo))->companyCreateForm();
        break;

    case 'admin/company-edit':
        require_role(ROLE_ADMIN);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new AdminController($pdo))->companyEditPost()
            : (new AdminController($pdo))->companyEditForm();
        break;

    case 'admin/company-new-post':
        require_role(ROLE_ADMIN);
        $require_post();
        csrf_verify_or_die();
        (new AdminController($pdo))->companyCreatePost();
        break;

    case 'admin/company-edit-post':
        require_role(ROLE_ADMIN);
        $require_post();
        csrf_verify_or_die();
        (new AdminController($pdo))->companyEditPost();
        break;

    case 'admin/company-delete-post':
        require_role(ROLE_ADMIN);
        $require_post();
        (new AdminController($pdo))->companyDeletePost();
        break;

    case 'admin/company-admins':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->companyAdmins();
        break;

    case 'admin/admin-new':
        require_role(ROLE_ADMIN);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new AdminController($pdo))->adminCreatePost()
            : (new AdminController($pdo))->adminCreateForm();
        break;
        case 'admin/admin-new-post':
        require_role(ROLE_ADMIN);
        $require_post();
        csrf_verify_or_die();
        (new AdminController($pdo))->adminCreatePost();
        break;

    case 'admin/admin-edit':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->adminEdit();
        break;

    case 'admin/admin-edit-post':
        require_role(ROLE_ADMIN);
        $require_post();
        (new AdminController($pdo))->adminEditPost();
        break;

    case 'admin/coupons':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->coupons();
        break;

    case 'admin/coupon-new':
        require_role(ROLE_ADMIN);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new AdminController($pdo))->couponCreatePost()
            : (new AdminController($pdo))->couponCreateForm();
        break;

    case 'admin/coupon-edit':
        require_role(ROLE_ADMIN);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new AdminController($pdo))->couponEditPost()
            : (new AdminController($pdo))->couponEditForm();
        break;

    case 'admin/coupon-new-post':
        require_role(ROLE_ADMIN);
        $require_post();
        csrf_verify_or_die();
        (new AdminController($pdo))->couponCreatePost();
        break;

    case 'admin/coupon-edit-post':
        require_role(ROLE_ADMIN);
        $require_post();
        csrf_verify_or_die();
        (new AdminController($pdo))->couponEditPost();
        break;

    case 'admin/coupon-delete-post':
        require_role(ROLE_ADMIN);
        $require_post();
        (new AdminController($pdo))->couponDeletePost();
        break;

    
    case 'admin/trips':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->trips();
        break;

    case 'admin/trip-show':
        require_role(ROLE_ADMIN);
        (new AdminController($pdo))->tripShow();
        break;

    case 'ticket/purchase':
        require_user();
        (new TicketController($pdo))->purchaseForm();
        break;

    case 'ticket/calc-price':
        $require_post();
        require_user();
        csrf_verify_or_die();
        (new TicketController($pdo))->calcPrice();
        break;

    case 'ticket/purchase-post':
        $require_post();
        require_user();
        csrf_verify_or_die();
        (new TicketController($pdo))->purchasePost();
        break;

    case 'ticket/my':
        require_user();
        (new TicketController($pdo))->myTickets();
        break;

    case 'ticket/cancel':
        $require_post();
        require_user();
        csrf_verify_or_die();
        (new TicketController($pdo))->cancel();
        break;

    case 'ticket/pdf':
        require_login();
        (new TicketController($pdo))->pdf();
        break;

    case 'company/panel':
    case 'company/trips':
        require_role(ROLE_COMPANY);
        (new CompanyController($pdo))->trips();
        break;

    case 'company/trip-new':
        require_role(ROLE_COMPANY);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new CompanyController($pdo))->tripCreatePost()
            : (new CompanyController($pdo))->tripCreateForm();
        break;

    case 'company/trip-new-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->tripCreatePost();
        break;

    case 'company/trip-edit':
        require_role(ROLE_COMPANY);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new CompanyController($pdo))->tripEditPost()
            : (new CompanyController($pdo))->tripEditForm();
        break;

    case 'company/trip-edit-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->tripEditPost();
        break;

    case 'company/trip-delete-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->tripDeletePost();
        break;

    case 'company/trip-seats':
        require_role(ROLE_COMPANY);
        (new CompanyController($pdo))->tripSeats();
        break;

    case 'company/coupons':
        require_role(ROLE_COMPANY);
        (new CompanyController($pdo))->coupons();
        break;

    case 'company/coupon-new':
        require_role(ROLE_COMPANY);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new CompanyController($pdo))->couponCreatePost()
            : (new CompanyController($pdo))->couponCreateForm();
        break;

    case 'company/coupon-new-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->couponCreatePost();
        break;

    case 'company/coupon-edit':
        require_role(ROLE_COMPANY);
        ($_SERVER['REQUEST_METHOD'] === 'POST')
            ? (new CompanyController($pdo))->couponEditPost()
            : (new CompanyController($pdo))->couponEditForm();
        break;

    case 'company/coupon-edit-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->couponEditPost();
        break;

    case 'company/coupon-delete-post':
        require_role(ROLE_COMPANY);
        $require_post();
        csrf_verify_or_die();
        (new CompanyController($pdo))->couponDeletePost();
        break;

    case 'company/tickets':
        require_role(ROLE_COMPANY);
        (new CompanyController($pdo))->tickets();
        break;

    case 'company/ticket-cancel-post':
        require_role(ROLE_COMPANY);
        $require_post();
        (new CompanyController($pdo))->cancelTicketPost();
        break;

    case 'account':
    case 'account/index':
        require_login();
        (new AccountController($pdo))->index();
        break;

    case 'account/update':
        require_user();
        $require_post();
        csrf_verify_or_die();
        (new AccountController($pdo))->update();
        break;

    case 'account/topup':
        require_user();
        $require_post();
        csrf_verify_or_die();
        (new AccountController($pdo))->topup();
        break;

    default:
        $not_found();
        break;
}
