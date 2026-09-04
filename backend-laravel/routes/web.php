<?php

use Illuminate\Support\Facades\Route;
use App\Models\Budget;

Route::get('/', function () {
    return redirect('/sertecapp');
});

Route::get('/budgets/{budget}/pdf', function (Budget $budget) {
    abort_unless(
        auth()->check() && auth()->user()->hasAnyRole(['super_admin', 'administrador', 'supervisor']),
        403
    );

    $budget->load(['customer', 'items.part']);
    
    $html = view('budgets.pdf', compact('budget'))->render();
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    
    return $pdf->download("presupuesto-{$budget->id}.pdf");
})->name('budgets.pdf')->middleware('auth');
