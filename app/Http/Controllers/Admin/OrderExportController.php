<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLog;
use App\Services\OrderExport;
use Illuminate\Http\Request;

// Admin > Orders > Export: orders from the chosen period as Excel or text.
class OrderExportController extends Controller
{
    public function __invoke(Request $request, OrderExport $export)
    {
        $data = $request->validate([
            'range' => 'required|in:' . implode(',', array_keys(OrderExport::RANGES)),
            'format' => 'required|in:' . implode(',', OrderExport::FORMATS),
        ]);

        [$label, $hours] = OrderExport::RANGES[$data['range']];
        $orders = $export->orders(now()->subHours($hours), now()->addSecond());
        $name = 'shopmoda-orders-' . $data['range'] . '-' . now()->format('Y-m-d-Hi') . '.' . $data['format'];

        // The file holds customers' personal data, so every download is logged.
        UserLog::record('orders.exported', 'Orders exported (' . strtolower($label) . ', ' . $orders->count() . ' orders, ' . $data['format'] . ')');

        if ($data['format'] === 'txt') {
            return response($export->text($orders, 'Orders, ' . strtolower($label)), 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $name . '"',
            ]);
        }

        // Temporary file outside the public folder, deleted right after the download.
        $path = tempnam(sys_get_temp_dir(), 'orders');
        $export->writeXlsx($orders, $path);

        return response()->download($path, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
