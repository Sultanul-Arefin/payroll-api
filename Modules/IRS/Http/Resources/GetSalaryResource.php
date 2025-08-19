<?php

namespace Modules\IRS\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payslip\Entities\Payslip;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem as EntitiesEmployeeSalaryItem;
use Modules\Payslip\Entities\PayslipDetailsForDeduction;
use Carbon\Carbon;

class GetSalaryResource extends JsonResource
{
    protected $startDate;
    protected $endDate;

    public function __construct($resource, $startDate = null, $endDate = null)
    {
        parent::__construct($resource);

        $this->startDate = $startDate ?? now()->startOfYear();
        $this->endDate   = $endDate ?? now()->endOfYear();
    }
 
    public function toArray($request)
    {
        $companyId = $this->id;

        $employeeData       = $this->get_employees($companyId, $this->startDate, $this->endDate);
        $TotalAdditionalTaxes = $this->irs_additional_taxes($companyId, $this->startDate, $this->endDate);
        $totalGovernmentDeductions = $this->irs_government_deductions($companyId, $this->startDate, $this->endDate);
        $employee_wise_taxable_gross = $this->get_employee_wise_taxable_gross($companyId, $this->startDate, $this->endDate);

        return [
            'company_name'                   => $this->company_name,
            'employer_identification_number' => $this->employer_identification_number,
            'company_email'                  => $this->company_email,
            'company_phone'                  => $this->company_phone,
            'gross_pay'                      => $employeeData['gross_pay'],
            'total_employee'                 => $employeeData['total_employee'],
            'income_tax'                     => $employeeData['income_tax'], 
            'additional_taxes'               => $TotalAdditionalTaxes,
            'government_deductions_yearly'   => $totalGovernmentDeductions,
            'employee_wise_taxable_gross'    => $employee_wise_taxable_gross,
        ];
    }

    public function get_employees($companyId, $startDate, $endDate)
    {
        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('role_id', '!=', User::ADMIN)
            ->get();

        $gross_pay = 0;
        $income_tax = 0;

        foreach ($employees as $employee) {
            $payslips = Payslip::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $gross_pay += $payslips->sum('gross_pay_before_tax');
            $income_tax += $payslips->sum('income_tax'); 
        }

        return [
            'total_employee' => $employees->count(),
            'gross_pay'      => $gross_pay,
            'income_tax'     => $income_tax,
        ];
    }

    public function irs_additional_taxes($companyId, $startDate, $endDate)
    {
        $employees = User::query()
            ->where('company_id', $companyId)
            ->where('role_id', '!=', User::ADMIN)
            ->pluck('id')
            ->toArray();

        $allowedItems = [
            'State Tax',
            'Federal Income Tax',
            'Social Security',
            'Medicare',
        ];

        $items = EntitiesEmployeeSalaryItem::with('salaryItemsName')
            ->whereHas('salaryItemsName', function ($query) use ($allowedItems) {
                $query->whereIn('name', $allowedItems)
                      ->where('salary_items_category_id', 6);
            })
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employees)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return $items->groupBy(function ($item) {
                return $item->salaryItemsName->name ?? 'Unknown Item';
            })
            ->mapWithKeys(fn($groupedItems, $itemName) => [$itemName => $groupedItems->sum('amount')]);
    }

    public function irs_government_deductions($companyId, $startDate, $endDate)
    {
        $allowedItems = [
            'ESI',
            'Social Security',
            'Government Deductions 2',
            'Medicare',
        ];

        $items = PayslipDetailsForDeduction::with('salary_item_name')
            ->whereHas('salary_item_name', function ($query) use ($allowedItems){
                $query->whereIn('name', $allowedItems)
                      ->where('salary_items_category_id', 7);
            })
            ->whereHas('payslip.employee', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->where('role_id', '!=', User::ADMIN);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return $items->groupBy(fn($item) => $item->salary_item_name->name ?? 'Unknown Item')
                     ->mapWithKeys(fn($groupedItems, $itemName) => [$itemName => $groupedItems->sum('government_or_company_amount')]);
    }

    public function get_employee_wise_taxable_gross($companyId, $startDate, $endDate)
{
    $employees = User::query()
        ->where('company_id', $companyId)
        ->where('role_id', '!=', User::ADMIN)
        ->get();

    $employeeWiseData = [];

    foreach ($employees as $employee) {
        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $empGrossPay  = $payslips->sum('gross_pay_before_tax'); // yearly taxable gross pay
        $empIncomeTax = $payslips->sum('income_tax');

        $employeeWiseData[] = [
            'employee_id'       => $employee->id,
            'employee_name'     => $employee->name,
            'taxable_gross_pay' => $empGrossPay,
            'income_tax'        => $empIncomeTax,
        ];
    }

    return $employeeWiseData;
}
}
