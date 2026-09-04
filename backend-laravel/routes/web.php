<?php

use Illuminate\Support\Facades\Route;
use App\Models\Budget;

Route::get('/', function () {
    return redirect('/sertecapp');
});

Route::get('/budgets/{budget}/pdf', function (Budget $budget) {
    // Chequeo manual en vez de middleware('auth'): esta app no tiene una ruta
    // 'login' generica (solo la del panel Filament), asi que el redirect
    // automatico del middleware tiraba 500 (RouteNotFoundException) en vez
    // de mandar a un usuario sin sesion a la pantalla de login real.
    if (!auth()->check()) {
        return redirect()->route('filament.sertecapp.auth.login');
    }

    abort_unless(
        auth()->user()->hasAnyRole(['super_admin', 'administrador', 'supervisor']),
        403
    );

    $budget->load(['customer', 'items.part']);

    $html = view('budgets.pdf', compact('budget'))->render();

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

    return $pdf->download("presupuesto-{$budget->id}.pdf");
})->name('budgets.pdf');
