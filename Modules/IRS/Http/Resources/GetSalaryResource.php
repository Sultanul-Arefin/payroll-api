<?php

namespace Modules\IRS\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payslip\Entities\Payslip;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem as EntitiesEmployeeSalaryItem;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Carbon\Carbon;
use Modules\Payslip\Entities\PayslipDetail;

class GetSalaryResource extends JsonResource
// {
//     protected $startDate;
//     protected $endDate;

//     public function __construct($resource, $startDate = null, $endDate = null)
//     {
//         parent::__construct($resource);

//         $this->startDate = $startDate ?? now()->startOfYear();
//         $this->endDate   = $endDate ?? now()->endOfYear();
//     }
 
//     public function toArray($request)
//     {
//         $companyId = $this->id;

//         $employeeData       = $this->get_employees($companyId, $this->startDate, $this->endDate);
//         $TotalAdditionalTaxes = $this->irs_additional_taxes($companyId, $this->startDate, $this->endDate);
//         $totalGovernmentDeductions = $this->irs_government_deductions($companyId, $this->startDate, $this->endDate);
//         $employee_wise_taxable_gross = $this->get_employee_wise_taxable_gross($companyId, $this->startDate, $this->endDate);

//         return [
//             'company_name'                   => $this->company_name,
//             'employer_identification_number' => $this->employer_identification_number,
//             'company_email'                  => $this->company_email,
//             'company_phone'                  => $this->company_phone,
//             'gross_pay'                      => $employeeData['gross_pay'],
//             'non_taxable_allowance'          => $employeeData['non_taxable_allowance'],
//             'total_employee'                 => $employeeData['total_employee'],
//             'additional_taxes'               => $TotalAdditionalTaxes,
//             'government_deductions_yearly'   => $totalGovernmentDeductions,
//             'employee_wise_taxable_gross'    => $employee_wise_taxable_gross,
//         ];
//     }

//     public function get_employees($companyId, $startDate, $endDate)
//     {
//         $employees = User::query()
//             ->where('company_id', $companyId)
//             ->where('role_id', '!=', User::ADMIN)
//             ->get();

//         $gross_pay = 0;
//         $non_taxable_allowance=0;

//         foreach ($employees as $employee) {
//             $payslips = Payslip::query()
//                 ->where('employee_id', $employee->id)
//                 ->whereBetween('created_at', [$startDate, $endDate])
//                 ->get();

//             $gross_pay += $payslips->sum('gross_pay_before_tax');
//             $non_taxable_allowance += $payslips->sum('non_taxable_allowance'); 
//         }

//         return [
//             'total_employee' => $employees->count(),
//             'gross_pay'      => $gross_pay,
//             'non_taxable_allowance'     => $non_taxable_allowance,
//         ];
//     }

//     public function irs_additional_taxes($companyId, $startDate, $endDate)
//     {
//         $employees = User::query()
//             ->where('company_id', $companyId)
//             ->where('role_id', '!=', User::ADMIN)
//             ->pluck('id')
//             ->toArray();

//         $allowedItems = [
//             'Additional Taxes(Tax TopUp) 1',
//             'Medicare Tax',
//             'Federal Income Tax',
//             'Social Security',
//             'Medicare',
//         ];

//         $items = EntitiesEmployeeSalaryItem::with('salaryItemsName')
//             ->whereHas('salaryItemsName', function ($query) use ($allowedItems) {
//                 $query->whereIn('name', $allowedItems)
//                       ->where('salary_items_category_id', 6);
//             })
//             ->where('company_id', $companyId)
//             ->whereIn('employee_id', $employees)
//             ->whereBetween('created_at', [$startDate, $endDate])
//             ->get();

//         return $items->groupBy(function ($item) {
//                 return $item->salaryItemsName->name ?? 'Unknown Item';
//             })
//             ->mapWithKeys(fn($groupedItems, $itemName) => [$itemName => $groupedItems->sum('amount')]);
//     }

//     public function irs_government_deductions($companyId, $startDate, $endDate)
//     {
//         $allowedItems = [
//             'SUTA',
//             'Futa',
//             'Social Security',
//             'Government Deductions 2',
//             'Medicare',
//         ];

//         $items = PayslipDetailsForDeduction::with('salary_item_name')
//             ->whereHas('salary_item_name', function ($query) use ($allowedItems){
//                 $query->whereIn('name', $allowedItems)
//                       ->where('salary_items_category_id', 7);
//             })
//             ->whereHas('payslip.employee', function ($query) use ($companyId) {
//                 $query->where('company_id', $companyId)
//                       ->where('role_id', '!=', User::ADMIN);
//             })
//             ->whereBetween('created_at', [$startDate, $endDate])
//             ->get();

//         return $items->groupBy(fn($item) => $item->salary_item_name->name ?? 'Unknown Item')
//                      ->mapWithKeys(fn($groupedItems, $itemName) => [$itemName => $groupedItems->sum('government_or_company_amount')]);
//     }

//     public function get_employee_wise_taxable_gross($companyId, $startDate, $endDate)
//     {
//         $employees = User::query()
//             ->where('company_id', $companyId)
//             ->where('role_id', '!=', User::ADMIN)
//             ->get();

//         $employeeWiseData = [];

//         foreach ($employees as $employee) {
//             $payslips = Payslip::query()
//                 ->where('employee_id', $employee->id)
//                 ->whereBetween('created_at', [$startDate, $endDate])
//                 ->get();

//             $empGrossPay  = $payslips->sum('gross_pay_before_tax'); // yearly taxable gross pay
//             $empIncomeTax = $payslips->sum('income_tax');

//             $employeeWiseData[] = [
//                 'employee_id'       => $employee->id,
//                 'employee_name'     => $employee->name,
//                 'taxable_gross_pay' => $empGrossPay,
//                 'income_tax'        => $empIncomeTax,
//             ];
//         }

//         return $employeeWiseData;
//     }
// }

// {
//     protected $months;

//     public function __construct($resource, $months = [])
//     {
//         parent::__construct($resource);
//         $this->months = $months; // array of month names, e.g. ['January','February','March']
//     }

//     public function toArray($request)
//     {
//         $companyId = $this->id;

//         $employeeData = $this->get_employees($companyId, $this->months);
//         $totalAdditionalTaxes = $this->irs_additional_taxes($companyId, $this->months);
//         $totalGovernmentDeductions = $this->irs_government_deductions($companyId, $this->months);
//         $employeeWiseTaxableGross = $this->get_employee_wise_taxable_gross($companyId, $this->months);

//         return [
//             'company_name' => $this->company_name,
//             'employer_identification_number' => $this->employer_identification_number,
//             'company_email' => $this->company_email,
//             'company_phone' => $this->company_phone,
//             'gross_pay' => $employeeData['gross_pay'],
//             'non_taxable_allowance' => $employeeData['non_taxable_allowance'],
//             'total_employee' => $employeeData['total_employee'],
//             'additional_taxes' => $totalAdditionalTaxes,
//             'government_deductions_yearly' => $totalGovernmentDeductions,
//             'employee_wise_taxable_gross' => $employeeWiseTaxableGross,
//         ];
//     }

//     // =========================
//     // Employee Gross & Allowance
//     // =========================
//     public function get_employees($companyId, $months)
//     {
//         $employees = User::query()
//             ->where('company_id', $companyId)
//             ->where('role_id', '!=', User::ADMIN)
//             ->get();

//         $gross_pay = 0;
//         $non_taxable_allowance = 0;

//         foreach ($employees as $employee) {
//             $payslips = Payslip::query()
//                 ->where('employee_id', $employee->id)
//                 ->whereIn('month', $months)
//                 ->get();

//             $gross_pay += $payslips->sum('gross_pay_before_tax');
//             $non_taxable_allowance += $payslips->sum('non_taxable_allowance');
//         }

//         return [
//             'total_employee' => $employees->count(),
//             'gross_pay' => $gross_pay,
//             'non_taxable_allowance' => $non_taxable_allowance,
//         ];
//     }

//     // =========================
//     // Employee Deductions (Category 6)
//     // =========================
//     public function irs_additional_taxes($companyId, $months)
//     {
//         $allowedItems = [
//             'Additional Taxes(Tax TopUp) 1',
//             'Medicare Tax',
//             'Federal Income Tax',
//             'Social Security',
//             'Medicare',
//         ];

//         $items = PayslipDetail::with('salary_item')
//             ->whereHas('salary_item', function ($query) use ($allowedItems) {
//                 $query->whereIn('name', $allowedItems)
//                       ->where('salary_items_category_id', 6);
//             })
//             ->whereHas('payslip', function ($query) use ($companyId, $months) {
//                 $query->where('company_id', $companyId)
//                       ->whereIn('month', $months);
//             })
//             ->get();

//         return $items->groupBy(fn($item) => $item->salary_item->name ?? 'Unknown Item')
//                      ->mapWithKeys(fn($group, $name) => [$name => $group->sum('amount')]);
//     }

//     // =========================
//     // Employer Contributions (Category 7)
//     // =========================
//     public function irs_government_deductions($companyId, $months)
//     {
//         $allowedItems = [
//             'SUTA',
//             'Futa',
//             'Social Security',
//             'Government Deductions 2',
//             'Medicare',
//         ];

//         $items = PayslipDetailsForDeduction::with('salary_item_name')
//             ->whereHas('salary_item_name', function ($query) use ($allowedItems) {
//                 $query->whereIn('name', $allowedItems)
//                       ->where('salary_items_category_id', 7);
//             })
//             ->whereHas('payslip', function ($query) use ($companyId, $months) {
//                 $query->where('company_id', $companyId)
//                       ->whereIn('month', $months);
//             })
//             ->get();

//         return $items->groupBy(fn($item) => $item->salary_item_name->name ?? 'Unknown Item')
//                      ->mapWithKeys(fn($group, $name) => [$name => $group->sum('government_or_company_amount')]);
//     }

//     // =========================
//     // Employee Wise Taxable Gross
//     // =========================
//     public function get_employee_wise_taxable_gross($companyId, $months)
//     {
//         $employees = User::query()
//             ->where('company_id', $companyId)
//             ->where('role_id', '!=', User::ADMIN)
//             ->get();

//         $employeeWiseData = [];

//         foreach ($employees as $employee) {
//             $payslips = Payslip::query()
//                 ->where('employee_id', $employee->id)
//                 ->whereIn('month', $months)
//                 ->get();

//             $empGrossPay = $payslips->sum('gross_pay_before_tax');
//             $empIncomeTax = $payslips->sum('income_tax');

//             $employeeWiseData[] = [
//                 'employee_id' => $employee->id,
//                 'employee_name' => $employee->name,
//                 'taxable_gross_pay' => $empGrossPay,
//                 'income_tax' => $empIncomeTax,
//             ];
//         }

//         return $employeeWiseData;
//     }
// }

{
    protected $months;
    protected $year;

    public function __construct($resource, $months = [], $year = null)
    {
        parent::__construct($resource);
        $this->year = $year ?? now()->year;
        $this->months = $months; // array of month names, e.g. ['January','February','March']
    }

    public function toArray($request)
    {
        $companyId = $this->id;

        $employeeData = $this->get_employees($companyId, $this->year, $this->months);
        $totalAdditionalTaxes = $this->irs_additional_taxes($companyId, $this->year, $this->months);
        $totalGovernmentDeductions = $this->irs_government_deductions($companyId, $this->year, $this->months);
        $employeeWiseTaxableGross = $this->get_employee_wise_taxable_gross($companyId, $this->year, $this->months);

        return [
            'company_name' => $this->company_name,
            'employer_identification_number' => $this->employer_identification_number,
            'company_email' => $this->company_email,
            'company_phone' => $this->company_phone,
            'gross_pay' => $employeeData['gross_pay'],
            'non_taxable_allowance' => $employeeData['non_taxable_allowance'],
            'total_employee' => $employeeData['total_employee'],
            'additional_taxes' => $totalAdditionalTaxes,
            'government_deductions_yearly' => $totalGovernmentDeductions,
            'employee_wise_taxable_gross' => $employeeWiseTaxableGross,
        ];
    }

    // =========================
    // Employee Gross & Allowance
    // =========================
    public function get_employees($companyId, $year, $months)
    {
        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('role_id', '!=', User::ADMIN)
            ->get();

        $gross_pay = 0;
        $non_taxable_allowance = 0;

        foreach ($employees as $employee) {
            $payslips = Payslip::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->whereYear('first_date', $year)
                ->whereIn('month', $months)
                ->get();

            $gross_pay += $payslips->sum('gross_pay_before_tax');
            $non_taxable_allowance += $payslips->sum('non_taxable_allowance');
        }

        return [
            'total_employee' => $employees->count(),
            'gross_pay' => $gross_pay,
            'non_taxable_allowance' => $non_taxable_allowance,
        ];
    }

    // =========================
    // Employee Deductions (Category 6)
    // =========================
    public function irs_additional_taxes($companyId, $year, $months)
    {
        $allowedItems = [
            'Additional Taxes(Tax TopUp) 1',
            'Medicare Tax',
            'Federal Income Tax',
            'Social Security',
            'Medicare',
        ];

        $items = PayslipDetail::with('salary_item')
            ->whereHas('salary_item', function ($query) use ($allowedItems) {
                $query->whereIn('name', $allowedItems)
                      ->where('salary_items_category_id', 6);
            })
            ->whereHas('payslip', function ($query) use ($companyId, $year, $months) {
                $query->where('company_id', $companyId)
                      ->whereYear('first_date', $year)
                      ->whereIn('month', $months);
            })
            ->get();

        return $items->groupBy(fn($item) => $item->salary_item->name ?? 'Unknown Item')
                     ->mapWithKeys(fn($group, $name) => [$name => $group->sum('amount')]);
    }

    // =========================
    // Employer Contributions (Category 7)
    // =========================
    public function irs_government_deductions($companyId, $year, $months)
    {
        $allowedItems = [
            'SUTA',
            'Futa',
            'Social Security',
            'Government Deductions 2',
            'Medicare',
        ];

        $items = PayslipDetailsForDeduction::with('salary_item_name')
            ->whereHas('salary_item_name', function ($query) use ($allowedItems) {
                $query->whereIn('name', $allowedItems)
                      ->where('salary_items_category_id', 7);
            })
            ->whereHas('payslip', function ($query) use ($companyId, $year, $months) {
                $query->where('company_id', $companyId)
                      ->whereYear('first_date', $year)
                      ->whereIn('month', $months);
            })
            ->get();

        return $items->groupBy(fn($item) => $item->salary_item_name->name ?? 'Unknown Item')
                     ->mapWithKeys(fn($group, $name) => [$name => $group->sum('government_or_company_amount')]);
    }

    // =========================
    // Employee Wise Taxable Gross
    // =========================
    public function get_employee_wise_taxable_gross($companyId, $year, $months)
    {
        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('role_id', '!=', User::ADMIN)
            ->get();

        $employeeWiseData = [];

        foreach ($employees as $employee) {
            $payslips = Payslip::query()
                ->where('employee_id', $employee->id)
                ->where('company_id', $companyId)
                ->whereYear('first_date', $year)
                ->whereIn('month', $months)
                ->get();

            $empGrossPay = $payslips->sum('gross_pay_before_tax');
            $empIncomeTax = $payslips->sum('income_tax');

            $employeeWiseData[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'taxable_gross_pay' => $empGrossPay,
                'income_tax' => $empIncomeTax,
            ];
        }

        return $employeeWiseData;
    }
}
