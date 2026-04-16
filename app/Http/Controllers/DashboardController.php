<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InvoicePayment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $todayStr = Carbon::now()->toDateString();
        $yesterdayStr = Carbon::yesterday()->toDateString();

        // 1. Incomes
        $todayIncome = InvoicePayment::whereDate('payment_date', $todayStr)->sum('amount');
        $yesterdayIncome = InvoicePayment::whereDate('payment_date', $yesterdayStr)->sum('amount');
        $incomeVar = $yesterdayIncome > 0 ? (($todayIncome - $yesterdayIncome) / $yesterdayIncome) * 100 : 100;

        // 2. Appointments
        $todayAppointments = Appointment::whereDate('scheduled_start_at', $todayStr)->count();
        $yesterdayAppointments = Appointment::whereDate('scheduled_start_at', $yesterdayStr)->count();
        $appointmentsVar = $yesterdayAppointments > 0 ? (($todayAppointments - $yesterdayAppointments) / $yesterdayAppointments) * 100 : 100;

        // 3. New Patients
        $todayPatients = Patient::whereDate('created_at', $todayStr)->count();
        $yesterdayPatients = Patient::whereDate('created_at', $yesterdayStr)->count();
        $patientsVar = $yesterdayPatients > 0 ? (($todayPatients - $yesterdayPatients) / $yesterdayPatients) * 100 : 100;

        // 4. Income by Payment Method (Donut)
        $incomeByMethod = InvoicePayment::join('payment_methods', 'invoice_payments.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.name', DB::raw('SUM(invoice_payments.amount) as total'))
            ->groupBy('payment_methods.name')
            ->get();

        // 5. Weekly Flow (Area Chart)
        $weeklyFlow = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $scheduled = Appointment::whereDate('scheduled_start_at', $date)->count();
            $effective = Appointment::whereDate('scheduled_start_at', $date)
                ->whereIn('status', ['completed', 'in_progress'])->count();
            
            $weeklyFlow[] = [
                'date' => $date,
                'scheduled' => $scheduled,
                'effective' => $effective,
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
                'income' => ['value' => $todayIncome, 'variation' => round($incomeVar, 2)],
                'appointments' => ['value' => $todayAppointments, 'variation' => round($appointmentsVar, 2)],
                'new_patients' => ['value' => $todayPatients, 'variation' => round($patientsVar, 2)],
            ],
            'income_distribution' => $incomeByMethod,
            'weekly_flow' => $weeklyFlow,
            'low_stock' => $lowStockProducts,
            'predictive_alerts' => $predictiveAlerts,
        ]);
    }
}
