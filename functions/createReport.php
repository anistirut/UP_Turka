<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../settings/connect_database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isset($_SESSION['user']) || $_SESSION['user'] == -1) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../app/Contexts/CacheContext.php';
require_once __DIR__ . '/../app/Contexts/UserContext.php';
require_once __DIR__ . '/../app/Contexts/ReportContext.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';

$userCtx = new UserContext($mysqli);
$row     = $userCtx->findRoleAndNameById((int) $_SESSION['user']);
if (!$row || $row['Role'] !== 'admin') {
    exit('Доступ запрещён');
}

$cacheCtx         = new CacheContext();
$reportCtx        = new ReportContext($mysqli, $cacheCtx);
$reportController = new ReportController($reportCtx);

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Пользователи');

$sheet->fromArray(
    ['ID', 'Фамилия', 'Имя', 'Отчество', 'Телефон', 'Роль'],
    null,
    'A1'
);

$users = $reportCtx->fetchUsersRows();
$r = 2;
while ($u = $users->fetch_assoc()) {
    $sheet->fromArray(array_values($u), null, "A{$r}");
    $r++;
}

foreach ($sheet->getColumnIterator() as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}

$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Блюда');

$sheet->fromArray(
    ['ID', 'Название', 'Состав', 'Цена'],
    null,
    'A1'
);

$dishes = $reportCtx->fetchDishesRows();
$r = 2;
while ($d = $dishes->fetch_assoc()) {
    $sheet->fromArray(array_values($d), null, "A{$r}");
    $r++;
}

foreach ($sheet->getColumnIterator() as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}

$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Заказы');

$sheet->fromArray(
    ['ID', 'Клиент', 'Курьер', 'Сумма', 'Статус', 'Адрес'],
    null,
    'A1'
);

$orders = $reportCtx->fetchOrdersRows();
$r = 2;
while ($o = $orders->fetch_assoc()) {
    $sheet->fromArray(array_values($o), null, "A{$r}");
    $r++;
}

foreach ($sheet->getColumnIterator() as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}

$sheet = $spreadsheet->createSheet();
$sheet->setTitle('Аналитика');

$analytics = $reportController->fetchAnalytics();

$sheet->fromArray(
    ['Показатель', 'Значение'],
    null,
    'A1'
);

$sheet->fromArray(
    ['Средний чек', round($analytics['avg_check'], 2) . ' ₽'],
    null,
    'A2'
);

$popular = $analytics['popular'];
$sheet->fromArray(
    ['Самое популярное блюдо', $popular ? $popular['Name'] . ' (' . $popular['qty'] . ' шт.)' : '—'],
    null,
    'A3'
);

$sheet->fromArray(
    ['Всего заказов', $analytics['count_orders']],
    null,
    'A4'
);

foreach ($sheet->getColumnIterator() as $column) {
    $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
}

$filename = 'restaurant_report_' . date('Y-m-d_H-i') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
