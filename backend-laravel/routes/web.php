<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
<?php

use Illuminate\Support\Facades\Route;
use App\Models\Budget;

Route::get('/budgets/{budget}/pdf', function (Budget $budget) {
    $budget->load(['customer', 'items.part']);
    
    $html = view('budgets.pdf', compact('budget'))->render();
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
    
    return $pdf->download("presupuesto-{$budget->id}.pdf");
})->name('budgets.pdf')->middleware('auth');
