Company Name: {{ $others['company_name'] }}
Address: {{ $others['company_address'] }}
Employer Government No: {{ $others['company_government_no'] }}
Email: {{ $others['company_email'] }}
BIC/Swift Code: {{ $others['company_bank_bic_or_swift_code'] }}
IBAN/Account No: {{ $others['company_bank_iban_or_account_no'] }}

----------------------------------------
MONTHLY TAX REPORT (DETAILS)
Month: {{ $others['month'] }}
Year: {{ $others['year'] }}

Total Wages: {{ $total['total_wages'] }}
Taxable Value: {{ $total['total_taxable_value'] }}
Total Tax Deducted: {{ $total['total_tax_deducted'] }}
Net Pay: {{ $total['total_net_pay'] }}
----------------------------------------

EMPLOYEE TAX DETAILS:
----------------------------------------
| Name       | Tax No    | Month  | Year  | Gross Pay | Income Tax | Other Tax | Total Paid |
|-----------|----------|--------|------|----------|-----------|---------|-----------|
@foreach($data as $report)
| {{ $report->employee?->name }} | {{ $report->employee?->user_deatils?->fax }} | {{ $report->month }} | {{ date('Y', strtotime($report->year)) }} | {{ $report->gross_pay_before_tax }} | {{ $report->tax_value }} | {{ $report->tax_value }} | {{ $report->net_pay }} |
@endforeach
----------------------------------------
