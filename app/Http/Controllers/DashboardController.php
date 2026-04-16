<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvoicePayment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $dateFromStr = $request->query('date_from', Carbon::today()->toDateString());
        $dateToStr = $request->query('date_to', Carbon::today()->toDateString());

        $dateFrom = Carbon::parse($dateFromStr)->startOfDay();
        $dateTo = Carbon::parse($dateToStr)->endOfDay();
        
        $diffInDays = $dateFrom->diffInDays($dateTo);

        $prevDateFrom = $dateFrom->copy()->subDays($diffInDays + 1)->startOfDay();
        $prevDateTo = $dateTo->copy()->subDays($diffInDays + 1)->endOfDay();

        // 1. Incomes
        $currentIncome = InvoicePayment::whereBetween('payment_date', [$dateFrom, $dateTo])->sum('amount');
        $prevIncome = InvoicePayment::whereBetween('payment_date', [$prevDateFrom, $prevDateTo])->sum('amount');
        $incomeVar = $prevIncome > 0 ? (($currentIncome - $prevIncome) / $prevIncome) * 100 : 100;

        // 2. Appointments
        $currentAppointments = Appointment::whereBetween('scheduled_start_at', [$dateFrom, $dateTo])->count();
        $prevAppointments = Appointment::whereBetween('scheduled_start_at', [$prevDateFrom, $prevDateTo])->count();
        $appointmentsVar = $prevAppointments > 0 ? (($currentAppointments - $prevAppointments) / $prevAppointments) * 100 : 100;

        // 3. New Patients
        $currentPatients = Patient::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $prevPatients = Patient::whereBetween('created_at', [$prevDateFrom, $prevDateTo])->count();
        $patientsVar = $prevPatients > 0 ? (($currentPatients - $prevPatients) / $prevPatients) * 100 : 100;

        // 4. Income by Payment Method (Donut)
        $incomeByMethod = InvoicePayment::join('payment_methods', 'invoice_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereBetween('invoice_payments.payment_date', [$dateFrom, $dateTo])
            ->select('payment_methods.name', DB::raw('SUM(invoice_payments.amount) as total'))
            ->groupBy('payment_methods.name')
            ->get();

        // 5. Flow
        $flowQuery = Appointment::whereBetween('scheduled_start_at', [$dateFrom, $dateTo])
            ->select(
                DB::raw('DATE(scheduled_start_at) as date'),
                DB::raw('count(*) as scheduled'),
                DB::raw("sum(case when status in ('completed', 'in_progress') then 1 else 0 end) as effective")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $period = CarbonPeriod::create($dateFrom, $dateTo);
        $weeklyFlow = [];
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $data = $flowQuery->get($dateStr);
            $weeklyFlow[] = [
                'date' => $dateStr,
                'scheduled' => $data ? (int) $data->scheduled : 0,
                'effective' => $data ? (int) $data->effective : 0,
            ];
        }

        // 6. Stock Alerts (Treemap/List)
        $lowStockProducts = Product::whereColumn('stock', '<=', 'min_stock')->get(['id', 'name', 'stock', 'min_stock']);

        // 7. Predictive Alerts (simplified proxy logic: if stock < 20% of min_stock e.g., and used recently)
        $predictiveAlerts = [];
        $criticalThreshold = Product::whereRaw('stock < (min_stock * 0.2)')->where('min_stock', '>', 0)->get();
        foreach ($criticalThreshold as $prod) {
            $predictiveAlerts[] = [
                'type' => 'stock_critical',
                'message' => "El stock de {$prod->name} está críticamente bajo considerando el uso reciente.",
                'product_id' => $prod->id,
            ];
        }

        return response()->json([
            'kpis' => [
                'income' => ['value' => $currentIncome, 'variation' => round($incomeVar, 2)],
                'appointments' => ['value' => $currentAppointments, 'variation' => round($appointmentsVar, 2)],
                'new_patients' => ['value' => $currentPatients, 'variation' => round($patientsVar, 2)],
            ],
            'income_distribution' => $incomeByMethod,
            'weekly_flow' => $weeklyFlow,
            'low_stock' => $lowStockProducts,
            'predictive_alerts' => $predictiveAlerts,
        ]);
    }
}
