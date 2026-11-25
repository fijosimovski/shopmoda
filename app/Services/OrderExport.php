<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\NumericCell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Orders as an Excel file (2 sheets: "Orders" and "Items") or a readable text file.
 * Used by Admin > Orders > Export and by the orders:daily-report command (midnight email).
 */
class OrderExport
{
    // Choices in the admin form: key => [label, hours back].
    public const RANGES = [
        '12h' => ['Last 12 hours', 12],
        '24h' => ['Last 24 hours', 24],
        '7d' => ['Last 7 days', 24 * 7],
        '30d' => ['Last 30 days', 24 * 30],
    ];

    public const FORMATS = ['xlsx', 'txt'];

    private const DATE = 'd.m.Y. H:i';

    // Orders placed from $from up to (not including) $to, oldest first, with everything the export shows.
    public function orders(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return Order::with(['items.product', 'discounts', 'user'])
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to)
            ->orderBy('created_at')
            ->get();
    }

    // Writes the Excel file to $path.
    public function writeXlsx(Collection $orders, string $path): void
    {
        $writer = new Writer();
        $writer->openToFile($path);
        $header = (new Style())->setFontBold()->setBackgroundColor('FFC107');

        // Sheet 1: one row per order.
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Orders');
        $sheet->setColumnWidth(14, 1);
        $sheet->setColumnWidth(17, 2, 4);
        $sheet->setColumnWidth(24, 5, 6, 9, 10);
        $sheet->setColumnWidth(48, 11);
        $writer->addRow($this->row($this->orderHeadings(), $header));

        foreach ($orders as $order) {
            $writer->addRow($this->row($this->orderRow($order)));
        }

        if ($orders->isNotEmpty()) {
            $writer->addRow($this->row(['', '', '', '', '', '', '', '', '', '', 'TOTAL', '', (float) $orders->sum('subtotal'), (float) $orders->sum('discount_total'), '', '', (float) $orders->sum('total')], (new Style())->setFontBold()));
        }

        // Sheet 2: one row per product in an order (for counting sizes / models).
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Items');
        $sheet->setColumnWidth(14, 1);
        $sheet->setColumnWidth(17, 2, 3);
        $sheet->setColumnWidth(36, 4);
        $writer->addRow($this->row(['Order', 'Placed at', 'Status', 'Product', 'Size', 'Quantity', 'Unit price (RSD)', 'Line total (RSD)'], $header));

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $writer->addRow($this->row([
                    $order->number,
                    $order->created_at->format(self::DATE),
                    ucfirst($order->status),
                    $this->productName($item),
                    (float) $item->size,
                    (int) $item->quantity,
                    (float) $item->unit_price,
                    (float) $item->subtotal(),
                ]));
            }
        }

        $writer->close();
    }

    // Plain text, easy to read or print: a summary, then one block per order.
    public function text(Collection $orders, string $title): string
    {
        $line = str_repeat('=', 60);
        $out = ["ShopModa - {$title}", 'Generated: ' . now()->format(self::DATE), $line,
            'Orders: ' . $orders->count() . '   Total: ' . $this->money($orders->sum('total')), $line, ''];

        foreach ($orders as $order) {
            $out[] = $order->number . '   ' . $order->created_at->format(self::DATE) . '   [' . strtoupper($order->status) . ']';
            $out[] = 'Customer: ' . $order->name . ($order->user_id ? '' : ' (guest)');
            $out[] = 'Email:    ' . $order->email;
            $out[] = 'Phone:    ' . $order->phone;
            $out[] = 'Address:  ' . $order->address . ', ' . $order->city;
            if ($order->note) {
                $out[] = 'Note:     ' . preg_replace('/\s+/', ' ', $order->note);
            }
            $out[] = 'Items:';
            foreach ($order->items as $item) {
                $out[] = '  - ' . $item->quantity . ' x ' . $this->productName($item) . ' (size ' . $this->size($item->size) . ') @ '
                    . $this->money($item->unit_price) . ' = ' . $this->money($item->subtotal());
            }
            $out[] = 'Subtotal: ' . $this->money($order->subtotal);
            foreach ($order->discounts as $discount) {
                $out[] = 'Discount: ' . $discount->name . ' -' . $this->money($discount->amount);
            }
            $out[] = 'TOTAL:    ' . $this->money($order->total) . ' (cash on delivery)';
            $out[] = str_repeat('-', 60);
        }

        if ($orders->isEmpty()) {
            $out[] = 'No orders in this period.';
        }

        // \r\n: opens correctly in Notepad too.
        return implode("\r\n", $out) . "\r\n";
    }

    private function orderHeadings(): array
    {
        return ['Order', 'Placed at', 'Status', 'Confirmed at', 'Customer', 'Email', 'Phone', 'City', 'Address', 'Note',
            'Products', 'Pairs', 'Subtotal (RSD)', 'Discount (RSD)', 'Coupon', 'Discounts', 'Total (RSD)', 'Account'];
    }

    private function orderRow(Order $order): array
    {
        return [
            $order->number,
            $order->created_at->format(self::DATE),
            ucfirst($order->status),
            $order->confirmed_at?->format(self::DATE) ?? '',
            $order->name,
            $order->email,
            $order->phone,
            $order->city,
            $order->address,
            (string) $order->note,
            $order->items->map(fn ($i) => $i->quantity . ' x ' . $this->productName($i) . ' (' . $this->size($i->size) . ')')->implode('; '),
            (int) $order->items->sum('quantity'),
            (float) $order->subtotal,
            (float) $order->discount_total,
            (string) $order->coupon_code,
            $order->discounts->map(fn ($d) => $d->name . ' -' . $this->money($d->amount))->implode('; '),
            (float) $order->total,
            $order->user_id ? 'Registered' : 'Guest',
        ];
    }

    // Every text goes in as a plain text cell: a customer typing "=HYPERLINK(...)" as their name
    // must not become a working formula in Excel.
    private function row(array $values, ?Style $style = null): Row
    {
        $cells = array_map(fn ($v): Cell => is_int($v) || is_float($v) ? new NumericCell($v, null) : new StringCell((string) $v, null), $values);

        return new Row($cells, $style);
    }

    // The product may have been deleted since; the order item still has its id.
    private function productName(OrderItem $item): string
    {
        return $item->product?->name ?? 'Product #' . $item->product_id;
    }

    private function size($size): string
    {
        return rtrim(rtrim(number_format((float) $size, 1, '.', ''), '0'), '.');
    }

    private function money($amount): string
    {
        return \App\Support\Money::format((float) $amount);
    }
}
